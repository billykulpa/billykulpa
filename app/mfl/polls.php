<?php
/** Polls: voting and results for the site, draft and publish for the CMS. */

const MFL_VOTER_COOKIE = 'mfl_voter';
const MFL_POLL_MAX_ANSWERS = 20;

/** Hash of this browser's voter cookie, or NULL if it hasn't voted yet. */
function mfl_voter_hash(): ?string
{
    $token = $_COOKIE[MFL_VOTER_COOKIE] ?? '';
    return preg_match('/^[0-9a-f]{64}$/', $token) ? hash('sha256', $token) : null;
}

/** Published polls, open ones first, soonest to close at the top; then closed, newest first. */
function mfl_public_polls(): array
{
    $polls = db()->query("SELECT * FROM mfl_polls WHERE status = 'published'")->fetchAll();
    if (!$polls) return [];

    $answers = [];
    foreach (db()->query(
        "SELECT a.*, COUNT(v.answer_id) AS votes
           FROM mfl_poll_answers a
           JOIN mfl_polls p ON p.id = a.poll_id AND p.status = 'published'
           LEFT JOIN mfl_poll_votes v ON v.answer_id = a.id
          GROUP BY a.id
          ORDER BY a.sort_order"
    ) as $a) {
        $answers[$a['poll_id']][] = $a;
    }

    $mine = [];
    if ($hash = mfl_voter_hash()) {
        $stmt = db()->prepare('SELECT poll_id, answer_id FROM mfl_poll_votes WHERE voter_hash = ?');
        $stmt->execute([$hash]);
        $mine = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    $now = date('Y-m-d H:i:s');
    foreach ($polls as &$p) {
        $p['open'] = $p['expires_at'] > $now;
        $p['answers'] = $answers[$p['id']] ?? [];
        $p['total'] = array_sum(array_column($p['answers'], 'votes'));
        $p['my_answer'] = isset($mine[$p['id']]) ? (int) $mine[$p['id']] : null;
        $p['show_results'] = !$p['open'] || $p['my_answer'] !== null;
    }
    unset($p);
    usort($polls, fn($a, $b) => [$b['open'], $a['open'] ? $a['expires_at'] : 0, $a['open'] ? 0 : $b['expires_at']]
                            <=> [$a['open'], $b['open'] ? $b['expires_at'] : 0, $b['open'] ? 0 : $a['expires_at']]);
    return $polls;
}

/** Record a vote. Quietly does nothing for a closed poll, a bad answer or a repeat vote. */
function mfl_poll_vote(int $pollId, int $answerId): void
{
    $stmt = db()->prepare(
        "SELECT a.id FROM mfl_poll_answers a
           JOIN mfl_polls p ON p.id = a.poll_id
          WHERE a.id = ? AND a.poll_id = ? AND p.status = 'published' AND p.expires_at > ?"
    );
    $stmt->execute([$answerId, $pollId, date('Y-m-d H:i:s')]);
    if (!$stmt->fetchColumn()) return;

    $hash = mfl_voter_hash();
    if ($hash === null) {
        $token = bin2hex(random_bytes(32));
        setcookie(MFL_VOTER_COOKIE, $token, [
            'expires'  => time() + 60 * 60 * 24 * 365 * 5,
            'path'     => '/',
            'secure'   => !empty($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        $hash = hash('sha256', $token);
    }
    $stmt = db()->prepare('INSERT IGNORE INTO mfl_poll_votes (poll_id, voter_hash, answer_id, created_at) VALUES (?, ?, ?, ?)');
    $stmt->execute([$pollId, $hash, $answerId, date('Y-m-d H:i:s')]);
}

/** Whole-number share of the vote. */
function mfl_poll_percent(int $votes, int $total): int
{
    return $total > 0 ? (int) round($votes / $total * 100) : 0;
}

/* ------------------------------- CMS writes ------------------------------- */

/** draft, open or closed. */
function mfl_poll_state(array $poll): string
{
    if ($poll['status'] === 'draft') return 'draft';
    return $poll['expires_at'] > date('Y-m-d H:i:s') ? 'open' : 'closed';
}

function mfl_admin_polls(): array
{
    return db()->query(
        'SELECT p.*, COUNT(v.voter_hash) AS votes
           FROM mfl_polls p
           LEFT JOIN mfl_poll_votes v ON v.poll_id = p.id
          GROUP BY p.id
          ORDER BY p.created_at DESC, p.id DESC'
    )->fetchAll();
}

/** Load a poll the signed-in user may change. Authors edit their own; the admin edits all. */
function mfl_poll_for_edit(int $id, array $user): array
{
    $stmt = db()->prepare('SELECT * FROM mfl_polls WHERE id = ?');
    $stmt->execute([$id]);
    $poll = $stmt->fetch();
    if (!$poll) { http_response_code(404); exit('Not found.'); }
    if ($user['role'] !== 'admin' && (int) $poll['author_user_id'] !== (int) $user['id']) {
        http_response_code(403);
        exit('Only the author can edit this poll.');
    }
    return $poll;
}

function mfl_poll_vote_count(int $pollId): int
{
    $stmt = db()->prepare('SELECT COUNT(*) FROM mfl_poll_votes WHERE poll_id = ?');
    $stmt->execute([$pollId]);
    return (int) $stmt->fetchColumn();
}

/** Values for the editor: a saved poll, a blank one, or the text from a rejected save. */
function mfl_poll_form(?array $poll, ?array $posted): array
{
    $answers = [];
    if ($poll) {
        $stmt = db()->prepare('SELECT label FROM mfl_poll_answers WHERE poll_id = ? ORDER BY sort_order');
        $stmt->execute([$poll['id']]);
        $answers = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    $votes = $poll ? mfl_poll_vote_count((int) $poll['id']) : 0;
    if ($posted && !$votes) $answers = array_map('strval', (array) ($posted['answers'] ?? []));

    return [
        'id'       => $poll['id'] ?? 0,
        'state'    => $poll ? mfl_poll_state($poll) : 'draft',
        'status'   => $poll['status'] ?? 'draft',
        'author'   => $poll['author_name'] ?? '',
        'question' => (string) ($posted['question'] ?? $poll['question'] ?? ''),
        'description' => (string) ($posted['description'] ?? $poll['description'] ?? ''),
        'expires'  => (string) ($posted['expires_at']
            ?? ($poll ? central_time($poll['expires_at'], 'Y-m-d\TH:i') : mfl_poll_default_expiry())),
        'answers'  => $votes ? $answers : array_pad($answers, 4, ''),
        'votes'    => $votes,
    ];
}

/**
 * Copy a poll into a new draft owned by whoever clones it. Answers and the
 * description come along; votes don't, and the closing date starts fresh.
 * Returns the new poll's id.
 */
function mfl_poll_clone(int $id, array $user): int
{
    $db = db();
    $stmt = $db->prepare('SELECT * FROM mfl_polls WHERE id = ?');
    $stmt->execute([$id]);
    $poll = $stmt->fetch();
    if (!$poll) { http_response_code(404); exit('Not found.'); }

    $db->beginTransaction();
    $stmt = $db->prepare("INSERT INTO mfl_polls (question, description, status, expires_at, author_user_id, author_name, created_at) VALUES (?, ?, 'draft', ?, ?, ?, ?)");
    $stmt->execute([mb_substr('Copy of ' . $poll['question'], 0, 200), $poll['description'], mfl_poll_parse_expiry(mfl_poll_default_expiry()),
                    $user['id'], $user['display_name'] ?: 'The Commissioner', date('Y-m-d H:i:s')]);
    $copy = (int) $db->lastInsertId();
    $stmt = $db->prepare('INSERT INTO mfl_poll_answers (poll_id, label, sort_order) SELECT ?, label, sort_order FROM mfl_poll_answers WHERE poll_id = ? ORDER BY sort_order');
    $stmt->execute([$copy, $id]);
    $db->commit();
    return $copy;
}

/** A week out, at the end of that day, Central. */
function mfl_poll_default_expiry(): string
{
    return (new DateTime('+7 days', new DateTimeZone('America/Chicago')))->format('Y-m-d') . 'T23:59';
}

/** The form's Central time as the server-time string the database holds. NULL if unreadable. */
function mfl_poll_parse_expiry(string $value): ?string
{
    $central = new DateTimeZone('America/Chicago');
    $d = DateTime::createFromFormat('Y-m-d\TH:i', $value, $central);
    if (!$d) return null;
    return $d->setTimezone(new DateTimeZone(date_default_timezone_get()))->format('Y-m-d H:i:s');
}

/**
 * Save the form. $action is save, publish, unpublish or close. Returns the
 * poll id, or an error message.
 *
 * Saving keeps the current status. Once a poll has votes its answers are
 * locked, because rewording one would change what people voted for.
 */
function mfl_poll_save(?array $poll, array $in, string $action, array $user): int|string
{
    $db = db();
    $now = date('Y-m-d H:i:s');
    $question = trim((string) ($in['question'] ?? ''));
    if ($question === '' || mb_strlen($question) > 200) return 'Enter a title of up to 200 characters.';
    $description = mb_substr(trim((string) ($in['description'] ?? '')), 0, 2000);

    $expires = mfl_poll_parse_expiry((string) ($in['expires_at'] ?? ''));
    if ($expires === null) return 'Choose when the poll closes.';
    if ($action === 'close') $expires = $now;
    if ($action === 'publish' && $expires <= $now) return 'Choose a closing time in the future.';

    $votes = $poll ? mfl_poll_vote_count((int) $poll['id']) : 0;
    $answers = [];
    if (!$votes) {
        foreach ((array) ($in['answers'] ?? []) as $a) {
            $a = trim((string) $a);
            if ($a !== '') $answers[] = $a;
        }
        if (count($answers) < 2) return 'Add at least two answers.';
        if (count($answers) > MFL_POLL_MAX_ANSWERS) return 'Use ' . MFL_POLL_MAX_ANSWERS . ' answers or fewer.';
        if (count(array_unique(array_map('mb_strtolower', $answers))) !== count($answers)) return 'Each answer needs to be different.';
        foreach ($answers as $a) {
            if (mb_strlen($a) > 200) return 'Keep each answer to 200 characters.';
        }
    }

    $status = match ($action) {
        'publish'   => 'published',
        'unpublish' => 'draft',
        default     => $poll['status'] ?? 'draft',
    };
    $live = $status === 'published';

    $db->beginTransaction();
    if ($poll) {
        $id = (int) $poll['id'];
        $stmt = $db->prepare('UPDATE mfl_polls SET question = ?, description = ?, status = ?, expires_at = ?, published_at = ?, public_updated_at = ? WHERE id = ?');
        $stmt->execute([$question, $description, $status, $expires, $live ? ($poll['published_at'] ?: $now) : null, $live ? $now : null, $id]);
    } else {
        $stmt = $db->prepare('INSERT INTO mfl_polls (question, description, status, expires_at, author_user_id, author_name, created_at, published_at, public_updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$question, $description, $status, $expires, $user['id'], $user['display_name'] ?: 'The Commissioner', $now, $live ? $now : null, $live ? $now : null]);
        $id = (int) $db->lastInsertId();
    }
    if (!$votes) {
        $db->prepare('DELETE FROM mfl_poll_answers WHERE poll_id = ?')->execute([$id]);
        $stmt = $db->prepare('INSERT INTO mfl_poll_answers (poll_id, label, sort_order) VALUES (?, ?, ?)');
        foreach ($answers as $i => $a) $stmt->execute([$id, $a, $i]);
    }
    $db->commit();
    return $id;
}
