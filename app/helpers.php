<?php

/** HTML-escape. Use on every piece of dynamic output. */
function esc(?string $s): string
{
    return htmlspecialchars($s ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Fetch a page row by slug ('' = home). Returns sensible defaults if missing. */
function page(string $slug): array
{
    $stmt = db()->prepare('SELECT * FROM pages WHERE slug = ?');
    $stmt->execute([$slug]);
    $row = $stmt->fetch();
    return $row ?: ['slug' => $slug, 'h1' => '', 'meta_title' => '', 'meta_description' => ''];
}

/** Render a view inside the public layout. */
function render(string $view, array $data = []): void
{
    extract($data, EXTR_SKIP);
    ob_start();
    require __DIR__ . '/views/' . $view . '.php';
    $content = ob_get_clean();
    require __DIR__ . '/views/layout.php';
}

/** Render an admin view inside the admin layout. */
function render_admin(string $view, array $data = []): void
{
    extract($data, EXTR_SKIP);
    ob_start();
    require __DIR__ . '/views/admin/' . $view . '.php';
    $content = ob_get_clean();
    require __DIR__ . '/views/admin/layout.php';
}

/** URL-safe slug from a title. */
function slugify(string $s): string
{
    $s = strtolower(trim($s));
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    return trim(preg_replace('/-+/', '-', $s), '-');
}

/** Format a datetime for display, e.g. "Jul 27, 2026". */
function nice_date(?string $dt): string
{
    if (!$dt) return '';
    return date('M j, Y', strtotime($dt));
}

/**
 * Format a server-time datetime string in US Central time. The server
 * (and its database timestamps) run on UTC; Billy runs on Roscoe time.
 */
function central_time(?string $dt, string $fmt = 'M j, g:i a'): string
{
    if (!$dt) return '';
    try {
        $d = new DateTime($dt, new DateTimeZone(date_default_timezone_get()));
        $d->setTimezone(new DateTimeZone('America/Chicago'));
        return $d->format($fmt);
    } catch (Exception $e) {
        return $dt;
    }
}

/** Minutes to add to server time to get US Central, for SQL date math. */
function central_offset_minutes(): int
{
    $srv = new DateTimeZone(date_default_timezone_get());
    $now = new DateTime('now', $srv);
    return intdiv((new DateTimeZone('America/Chicago'))->getOffset($now) - $srv->getOffset($now), 60);
}

/* ---------------------------------------------------------------------------
 * Uploads (the About portrait)
 * ------------------------------------------------------------------------- */

/**
 * The web root on disk. Locally that's public/ beside app/; on Hostinger
 * app/ lives inside the docroot, so the docroot itself is the web root.
 */
function public_dir(): string
{
    $beside = dirname(APP_DIR) . '/public';
    return is_dir($beside) ? $beside : dirname(APP_DIR);
}

/**
 * URL for the About portrait. Prefers the admin-uploaded file (which lives
 * in assets/uploads/, a path the deploy never touches, so it survives every
 * push) and falls back to the committed photo. Cache-busted by mtime so a
 * new upload shows up immediately, Cloudflare and all.
 */
function portrait_url(): string
{
    foreach (['portrait.webp', 'portrait.jpg'] as $f) {
        $path = public_dir() . '/assets/uploads/' . $f;
        if (is_file($path)) {
            return '/assets/uploads/' . $f . '?v=' . filemtime($path);
        }
    }
    return '/assets/img/billy-kulpa-2026.webp';
}

/* ---------------------------------------------------------------------------
 * Sessions / auth / CSRF
 * ------------------------------------------------------------------------- */

function start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) return;
    session_name(config()['session_name']);
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']),
    ]);
    session_start();
}

function current_user(): ?array
{
    start_session();
    if (empty($_SESSION['user_id'])) return null;
    $stmt = db()->prepare('SELECT id, email FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}

function require_login(): array
{
    $user = current_user();
    if (!$user) {
        header('Location: /admin/login');
        exit;
    }
    return $user;
}

function csrf_token(): string
{
    start_session();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . esc(csrf_token()) . '">';
}

function verify_csrf(): void
{
    start_session();
    $sent = $_POST['_csrf'] ?? '';
    if (!$sent || !hash_equals($_SESSION['csrf'] ?? '', $sent)) {
        http_response_code(403);
        exit('Invalid request token. Go back, reload the page, and try again.');
    }
}

/* ---------------------------------------------------------------------------
 * Markdown — a small, dependency-free converter.
 * Supports: # h2 / ## h3 / ### h4 (h1 is reserved for the post title),
 * paragraphs, **bold**, *italic*, `code`, [links](url), ![images](src),
 * - unordered lists, 1. ordered lists, > blockquotes, ``` code fences, --- rules.
 * ------------------------------------------------------------------------- */

function markdown_to_html(string $md): string
{
    $md    = str_replace(["\r\n", "\r"], "\n", $md);
    $lines = explode("\n", $md);
    $html  = [];
    $i     = 0;
    $n     = count($lines);

    while ($i < $n) {
        $line = $lines[$i];

        // Blank line
        if (trim($line) === '') { $i++; continue; }

        // Raw HTML block: a line starting with '<' passes through untouched
        // until the next blank line. Keeps embeds (iframes, figures, videos)
        // editable inside markdown posts — used heavily by imported posts.
        if (preg_match('/^</', ltrim($line))) {
            $raw = [];
            while ($i < $n && trim($lines[$i]) !== '') {
                $raw[] = $lines[$i];
                $i++;
            }
            $html[] = implode("\n", $raw);
            continue;
        }

        // Code fence
        if (preg_match('/^```/', $line)) {
            $i++;
            $code = [];
            while ($i < $n && !preg_match('/^```/', $lines[$i])) {
                $code[] = $lines[$i];
                $i++;
            }
            $i++; // closing fence
            $html[] = '<pre><code>' . esc(implode("\n", $code)) . '</code></pre>';
            continue;
        }

        // Horizontal rule
        if (preg_match('/^(-{3,}|\*{3,})\s*$/', $line)) {
            $html[] = '<hr>';
            $i++;
            continue;
        }

        // Headings (shifted down one level: post title owns the h1)
        if (preg_match('/^(#{1,4})\s+(.*)$/', $line, $m)) {
            $level  = min(strlen($m[1]) + 1, 5);
            $html[] = "<h{$level}>" . md_inline($m[2]) . "</h{$level}>";
            $i++;
            continue;
        }

        // Blockquote
        if (preg_match('/^>\s?/', $line)) {
            $quote = [];
            while ($i < $n && preg_match('/^>\s?(.*)$/', $lines[$i], $m)) {
                $quote[] = $m[1];
                $i++;
            }
            $html[] = '<blockquote><p>' . md_inline(implode(' ', $quote)) . '</p></blockquote>';
            continue;
        }

        // Unordered list
        if (preg_match('/^[-*]\s+/', $line)) {
            $items = [];
            while ($i < $n && preg_match('/^[-*]\s+(.*)$/', $lines[$i], $m)) {
                $items[] = '<li>' . md_inline($m[1]) . '</li>';
                $i++;
            }
            $html[] = '<ul>' . implode('', $items) . '</ul>';
            continue;
        }

        // Ordered list
        if (preg_match('/^\d+\.\s+/', $line)) {
            $items = [];
            while ($i < $n && preg_match('/^\d+\.\s+(.*)$/', $lines[$i], $m)) {
                $items[] = '<li>' . md_inline($m[1]) . '</li>';
                $i++;
            }
            $html[] = '<ol>' . implode('', $items) . '</ol>';
            continue;
        }

        // Paragraph: gather consecutive non-blank, non-special lines
        $para = [];
        while ($i < $n && trim($lines[$i]) !== ''
            && !preg_match('/^(#{1,4}\s|[-*]\s|\d+\.\s|>|```|-{3,}\s*$)/', $lines[$i])) {
            $para[] = trim($lines[$i]);
            $i++;
        }
        $html[] = '<p>' . md_inline(implode(' ', $para)) . '</p>';
    }

    return implode("\n", $html);
}

/** Inline markdown within a block. Escapes HTML first, then applies spans. */
function md_inline(string $s): string
{
    $s = esc($s);
    // Images before links
    $s = preg_replace('/!\[([^\]]*)\]\(([^)\s]+)\)/', '<img src="$2" alt="$1" loading="lazy">', $s);
    $s = preg_replace('/\[([^\]]+)\]\(([^)\s]+)\)/', '<a href="$2">$1</a>', $s);
    $s = preg_replace('/`([^`]+)`/', '<code>$1</code>', $s);
    $s = preg_replace('/\*\*([^*]+)\*\*/', '<strong>$1</strong>', $s);
    $s = preg_replace('/\*([^*]+)\*/', '<em>$1</em>', $s);
    return $s;
}

/**
 * Live Restreak numbers for the case study, pulled from restreak.com and
 * cached on disk for six hours. Falls back to the last good cache, then to
 * baked-in numbers (captured July 2026), so the page never breaks and never
 * blocks on the network for more than a couple of seconds.
 *
 * Returns: registered_players, active_players_30d, total_plays,
 * longest_streak, plus 'live' (bool) so the template can label the source.
 */
function restreak_stats(): array
{
    $fallback = [
        'registered_players' => 58,
        'active_players_30d' => 58,
        'total_plays'        => 1725,
        'longest_streak'     => 44,
        'live'               => false,
    ];

    $cacheDir = __DIR__ . '/cache';
    $cacheFile = $cacheDir . '/restreak-stats.json';
    $url = config()['restreak_stats_url'] ?? 'https://restreak.com/api/stats.php';

    // Fresh cache? Use it without touching the network.
    if (is_file($cacheFile) && (time() - filemtime($cacheFile)) < 21600) {
        $data = json_decode((string) file_get_contents($cacheFile), true);
        if (is_array($data)) return $data + ['live' => true];
    }

    $ctx = stream_context_create(['http' => ['timeout' => 3]]);
    $body = @file_get_contents($url, false, $ctx);
    $data = $body ? json_decode($body, true) : null;

    if (is_array($data) && isset($data['total_plays'])) {
        if (!is_dir($cacheDir)) @mkdir($cacheDir);
        @file_put_contents($cacheFile, $body, LOCK_EX);
        return $data + ['live' => true];
    }

    // Network failed: a stale cache still beats the baked-in numbers.
    if (is_file($cacheFile)) {
        $data = json_decode((string) file_get_contents($cacheFile), true);
        if (is_array($data)) return $data + ['live' => true];
    }

    return $fallback;
}
