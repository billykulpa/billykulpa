<?php
/**
 * Emailing power rankings to the league through Resend's API.
 *
 * Addresses live in mfl_owners and are never shown on the site. Everyone gets
 * their own copy, so nobody sees the others' addresses. Publishing sends once;
 * sending again is a separate, deliberate action.
 */

function mfl_email_settings(): array
{
    return (config()['mfl_email'] ?? []) + ['mode' => 'resend', 'resend_key' => '', 'from' => 'MFL Power Rankings <rankings@billykulpa.com>', 'reply_to' => ''];
}

/** Whether sending is configured. 'log' mode writes to a local file instead of sending. */
function mfl_email_ready(): bool
{
    $s = mfl_email_settings();
    return $s['mode'] === 'log' || $s['resend_key'] !== '';
}

/** Email addresses of the current owners. */
function mfl_league_recipients(): array
{
    return db()->query(
        "SELECT DISTINCT o.email FROM mfl_owners o
           JOIN mfl_team_seasons ts ON ts.owner_id = o.id
          WHERE ts.season = (SELECT MAX(season) FROM mfl_seasons) AND o.email <> ''"
    )->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Send one message to each recipient. Returns an error message, or NULL.
 * The idempotency key makes Resend ignore an accidental repeat for 24 hours.
 */
function mfl_send_emails(array $recipients, array $message, string $key): ?string
{
    $s = mfl_email_settings();
    if ($s['mode'] === 'log') {
        $dir = dirname(APP_DIR) . '/logs';
        if (!is_dir($dir)) mkdir($dir, 0775, true);
        file_put_contents("$dir/mfl-mail.log", '[' . date('c') . '] TO: ' . implode(', ', $recipients)
            . "\nSUBJECT: {$message['subject']}\nKEY: $key\n{$message['text']}\n---\n", FILE_APPEND);
        return null;
    }
    if ($s['resend_key'] === '') return "League email isn't set up yet.";

    $batch = [];
    foreach ($recipients as $to) {
        $email = ['from' => $s['from'], 'to' => [$to], 'subject' => $message['subject'], 'html' => $message['html'], 'text' => $message['text']];
        if ($s['reply_to'] !== '') $email['reply_to'] = $s['reply_to'];
        $batch[] = $email;
    }

    $ch = curl_init('https://api.resend.com/emails/batch');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $s['resend_key'],
            'Content-Type: application/json',
            'Idempotency-Key: ' . $key,
            'User-Agent: mfl.billykulpa.com',
        ],
        CURLOPT_POSTFIELDS     => json_encode($batch),
    ]);
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($code >= 200 && $code < 300) return null;
    $reply = json_decode((string) $body, true);
    return 'Resend said: ' . (is_array($reply) && !empty($reply['message']) ? $reply['message'] : ($curlError ?: "error $code"));
}

/** The email for a set of rankings, draft or published: ['subject', 'html', 'text']. */
function mfl_ranking_message(array $ranking): array
{
    $stmt = db()->prepare('SELECT franchise_id, rank_no FROM mfl_ranking_entries WHERE ranking_id = ?');
    $stmt->execute([$ranking['id']]);
    $ranking['ranks'] = array_map('intval', $stmt->fetchAll(PDO::FETCH_KEY_PAIR));
    $earlier = array_filter(mfl_rank_history(), fn($h) => [(int) $h['season'], (int) $h['week']] < [(int) $ranking['season'], (int) $ranking['week']]);
    $rows = mfl_ranking_rows($ranking, [...$earlier, $ranking]);

    $title = mfl_ranking_title($ranking);
    $url = MFL_BASE_URL . mfl_ranking_url($ranking);
    $ink = 'color:#16150f;';
    $soft = 'color:#4c4a40;';
    $sans = "font-family:Helvetica,Arial,sans-serif;";

    $text = "$title\nBy {$ranking['author_name']}\n\n";
    if (trim((string) $ranking['intro_md']) !== '') $text .= trim($ranking['intro_md']) . "\n\n";

    $body = '';
    foreach ($rows as $e) {
        $delta = $e['delta'];
        $move = $delta === null ? 'New' : ($delta === 0 ? '—' : ($delta > 0 ? '▲ ' : '▼ ') . abs($delta));
        $moveColor = $delta > 0 ? '#1a7f37' : ($delta < 0 ? '#b42318' : '#4c4a40');
        $owner = mfl_owner_label($e['owner_name'], $e['real_name']);
        $writeup = mfl_writeup_html($e['writeup_md']);
        $writeup = str_replace('<p>', "<p style=\"margin:8px 0 0;{$ink}{$sans}font-size:15px;line-height:1.5;\">", $writeup);
        $writeup = str_replace('class="link"', 'style="color:#0a70c2;"', $writeup);

        $body .= '<tr><td style="padding:16px 12px 16px 0;border-top:1px solid #dcd9cf;vertical-align:top;width:44px;' . $ink . $sans . 'font-size:26px;font-weight:bold;">' . (int) $e['rank_no'] . '</td>'
            . '<td style="padding:16px 0;border-top:1px solid #dcd9cf;vertical-align:top;' . $sans . '">'
            . '<div style="' . $ink . 'font-size:18px;font-weight:bold;">' . esc($e['team_name'])
            . ' <span style="color:' . $moveColor . ';font-size:13px;font-weight:normal;white-space:nowrap;">' . esc($move) . '</span></div>'
            . '<div style="' . $soft . 'font-size:14px;">' . esc($owner) . ' · ' . esc($e['record']) . '</div>'
            . $writeup . '</td></tr>';

        $text .= (int) $e['rank_no'] . ". {$e['team_name']} ($move) - $owner, {$e['record']}\n";
        if (trim((string) $e['writeup_md']) !== '') $text .= trim($e['writeup_md']) . "\n";
        $text .= "\n";
    }
    $text .= "Read it on the site: $url\n\nYou're getting this because you're in the Midwest Football League.\n";

    $intro = trim((string) $ranking['intro_md']) !== ''
        ? str_replace('<p>', "<p style=\"margin:12px 0 0;{$ink}{$sans}font-size:16px;line-height:1.5;\">", str_replace('class="link"', 'style="color:#0a70c2;"', mfl_writeup_html($ranking['intro_md'])))
        : '';

    $html = '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head><body style="margin:0;padding:0;background:#faf9f5;">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#faf9f5;"><tr><td align="center" style="padding:24px 16px;">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;">'
        . '<tr><td style="' . $soft . $sans . 'font-size:12px;letter-spacing:1px;text-transform:uppercase;">Midwest Football League</td></tr>'
        . '<tr><td style="padding-top:8px;' . $ink . $sans . 'font-size:30px;font-weight:bold;line-height:1.15;">' . esc($title) . '</td></tr>'
        . '<tr><td style="padding-top:6px;' . $soft . $sans . 'font-size:14px;">By ' . esc($ranking['author_name']) . '</td></tr>'
        . ($intro ? "<tr><td>$intro</td></tr>" : '')
        . '<tr><td style="padding-top:20px;"><table role="presentation" width="100%" cellpadding="0" cellspacing="0">' . $body . '</table></td></tr>'
        . '<tr><td style="padding:20px 0;border-top:1px solid #dcd9cf;' . $sans . 'font-size:15px;"><a href="' . esc($url) . '" style="color:#0a70c2;">Read it on the site</a></td></tr>'
        . '<tr><td style="' . $soft . $sans . 'font-size:12px;">You\'re getting this because you\'re in the Midwest Football League.</td></tr>'
        . '</table></td></tr></table></body></html>';

    return ['subject' => $title, 'html' => $html, 'text' => $text];
}

/** Email the current owners. Returns an error message, or NULL. */
function mfl_email_league(array $ranking, bool $again = false): ?string
{
    $recipients = mfl_league_recipients();
    if (!$recipients) return 'No owner emails are on file yet.';
    $message = mfl_ranking_message($ranking);
    $key = 'mfl-ranking/' . (int) $ranking['id'] . '/' . sha1($message['html']) . ($again ? '/' . time() : '');
    $error = mfl_send_emails($recipients, $message, $key);
    if ($error !== null) return $error;
    db()->prepare('UPDATE mfl_rankings SET emailed_at = ?, emailed_count = ? WHERE id = ?')
        ->execute([date('Y-m-d H:i:s'), count($recipients), $ranking['id']]);
    return null;
}
