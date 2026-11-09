<?php
/**
 * Owner sign-in for the league site, used to vote in polls and to use the
 * trade tools. An owner enters
 * their league email and gets a link; there are no passwords. This is separate
 * from the CMS login.
 *
 * A vote is stored under a hash of the owner, so the database allows one vote
 * per owner on any device, and the site never shows who voted for what.
 */

const MFL_OWNER_COOKIE = 'mfl_owner';
const MFL_LINK_MINUTES = 30;
const MFL_SESSION_DAYS = 365;
const MFL_LINKS_PER_15_MINUTES = 3;
/** Pages a sign-in link can return to. A fixed list, so the link can't be pointed anywhere else. */
const MFL_SIGN_IN_PAGES = ['/polls', '/trades', '/trades/values'];

function mfl_sign_in_next(?string $next): string
{
    return in_array($next, MFL_SIGN_IN_PAGES, true) ? $next : '/polls';
}

/** What a vote is stored under for this owner. */
function mfl_owner_hash(string $ownerId): string
{
    return hash('sha256', 'owner:' . $ownerId);
}

/** The signed-in owner on this browser, or NULL. */
function mfl_current_owner(): ?array
{
    static $owner = false;
    if ($owner !== false) return $owner;
    $token = $_COOKIE[MFL_OWNER_COOKIE] ?? '';
    $owner = null;
    if (preg_match('/^[0-9a-f]{64}$/', $token)) {
        $stmt = db()->prepare('SELECT o.* FROM mfl_owner_sessions s JOIN mfl_owners o ON o.id = s.owner_id WHERE s.token_hash = ? AND s.expires_at > ?');
        $stmt->execute([hash('sha256', $token), date('Y-m-d H:i:s')]);
        $owner = $stmt->fetch() ?: null;
    }
    return $owner;
}

/**
 * Email a sign-in link if this address belongs to a current owner. Says nothing
 * either way, so the form can't be used to find out who's in the league.
 */
function mfl_request_sign_in(string $email, string $next = '/polls'): void
{
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !mfl_email_ready()) return;
    $stmt = db()->prepare("SELECT o.* FROM mfl_owners o JOIN mfl_team_seasons ts ON ts.owner_id = o.id
                            WHERE o.email = ? AND o.email <> '' AND ts.season = (SELECT MAX(season) FROM mfl_seasons) LIMIT 1");
    $stmt->execute([$email]);
    $owner = $stmt->fetch();
    if (!$owner) return;

    $now = time();
    db()->prepare('DELETE FROM mfl_login_tokens WHERE expires_at < ?')->execute([date('Y-m-d H:i:s', $now)]);
    $stmt = db()->prepare('SELECT COUNT(*) FROM mfl_login_tokens WHERE owner_id = ? AND created_at > ?');
    $stmt->execute([$owner['id'], date('Y-m-d H:i:s', $now - 900)]);
    if ((int) $stmt->fetchColumn() >= MFL_LINKS_PER_15_MINUTES) return;

    $token = bin2hex(random_bytes(32));
    $stmt = db()->prepare('INSERT INTO mfl_login_tokens (token_hash, owner_id, created_at, expires_at) VALUES (?, ?, ?, ?)');
    $stmt->execute([hash('sha256', $token), $owner['id'], date('Y-m-d H:i:s', $now), date('Y-m-d H:i:s', $now + MFL_LINK_MINUTES * 60)]);

    // Always the fixed address, never the request's Host header, which a
    // visitor controls and could use to send a real link to their own site.
    $link = MFL_BASE_URL . '/sign-in/verify?token=' . $token . (mfl_sign_in_next($next) !== '/polls' ? '&next=' . rawurlencode(mfl_sign_in_next($next)) : '');
    $ink = 'color:#16150f;font-family:Helvetica,Arial,sans-serif;';
    $html = '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head>'
        . '<body style="margin:0;padding:24px 16px;background:#faf9f5;"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:480px;margin:0 auto;">'
        . '<tr><td style="padding-bottom:16px;"><img src="' . esc(MFL_BASE_URL) . '/assets/logo-email.png" width="120" height="94" alt="Midwest Football League" style="display:block;border:0;' . $ink . '"></td></tr>'
        . '<tr><td style="' . $ink . 'font-size:24px;font-weight:bold;">Your MFL sign-in link</td></tr>'
        . '<tr><td style="padding:10px 0 20px;' . $ink . 'font-size:16px;line-height:1.5;">Tap the button to sign in to the MFL site, where you can vote in polls and use the trade tools. You\'ll stay signed in on this device for a year.</td></tr>'
        . '<tr><td><a href="' . esc($link) . '" style="display:inline-block;background:#35b9ff;padding:12px 24px;border-radius:2px;text-decoration:none;font-weight:bold;' . $ink . 'font-size:16px;">Sign in</a></td></tr>'
        . '<tr><td style="padding-top:20px;color:#4c4a40;font-family:Helvetica,Arial,sans-serif;font-size:13px;line-height:1.5;">This link works for ' . MFL_LINK_MINUTES . ' minutes. If you didn\'t ask for it, you can ignore this email.</td></tr>'
        . '</table></body></html>';
    $text = "Your MFL sign-in link\n\nOpen this link to sign in to the MFL site, where you can vote in polls and use the trade tools. You'll stay signed in on this device for a year.\n\n$link\n\n"
          . 'This link works for ' . MFL_LINK_MINUTES . " minutes. If you didn't ask for it, you can ignore this email.\n";
    mfl_send_emails([$owner['email']], ['subject' => 'Your MFL sign-in link', 'html' => $html, 'text' => $text], 'mfl-signin/' . sha1($token));
}

/**
 * Sign in with an emailed link. It stays valid until it expires rather than
 * working once, because some mail filters open every link to scan it, which
 * would otherwise use it up before the owner taps it.
 */
function mfl_sign_in(string $token): bool
{
    if (!preg_match('/^[0-9a-f]{64}$/', $token)) return false;
    $stmt = db()->prepare('SELECT owner_id FROM mfl_login_tokens WHERE token_hash = ? AND expires_at > ?');
    $stmt->execute([hash('sha256', $token), date('Y-m-d H:i:s')]);
    $ownerId = $stmt->fetchColumn();
    if (!$ownerId) return false;

    $session = bin2hex(random_bytes(32));
    $expires = time() + MFL_SESSION_DAYS * 86400;
    $stmt = db()->prepare('INSERT INTO mfl_owner_sessions (token_hash, owner_id, created_at, expires_at) VALUES (?, ?, ?, ?)');
    $stmt->execute([hash('sha256', $session), $ownerId, date('Y-m-d H:i:s'), date('Y-m-d H:i:s', $expires)]);
    setcookie(MFL_OWNER_COOKIE, $session, ['expires' => $expires, 'path' => '/', 'secure' => !empty($_SERVER['HTTPS']), 'httponly' => true, 'samesite' => 'Lax']);

    // Votes cast from this browser before sign-in became the owner's, so they
    // can't vote on those polls a second time. IGNORE skips polls they've
    // already voted on from elsewhere.
    $old = $_COOKIE[MFL_VOTER_COOKIE] ?? '';
    if (preg_match('/^[0-9a-f]{64}$/', $old)) {
        $stmt = db()->prepare('UPDATE IGNORE mfl_poll_votes SET voter_hash = ? WHERE voter_hash = ?');
        $stmt->execute([mfl_owner_hash((string) $ownerId), hash('sha256', $old)]);
    }
    return true;
}

function mfl_sign_out(): void
{
    $token = $_COOKIE[MFL_OWNER_COOKIE] ?? '';
    if (preg_match('/^[0-9a-f]{64}$/', $token)) {
        db()->prepare('DELETE FROM mfl_owner_sessions WHERE token_hash = ?')->execute([hash('sha256', $token)]);
    }
    setcookie(MFL_OWNER_COOKIE, '', ['expires' => time() - 3600, 'path' => '/', 'secure' => !empty($_SERVER['HTTPS']), 'httponly' => true, 'samesite' => 'Lax']);
}
