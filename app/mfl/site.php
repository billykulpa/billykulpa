<?php
/** Public routes for mfl.billykulpa.com. Nothing here starts a session. */

require_once __DIR__ . '/data.php';
require_once __DIR__ . '/rankings.php';
require_once __DIR__ . '/polls.php';
require_once __DIR__ . '/history.php';
require_once __DIR__ . '/reports.php';
require_once __DIR__ . '/trades.php';
require_once __DIR__ . '/players.php';
require_once __DIR__ . '/awards.php';
require_once __DIR__ . '/email.php';
require_once __DIR__ . '/owners.php';
require_once __DIR__ . '/values.php';

function mfl_site_route(string $path): void
{
    switch ($path) {
        case '':
            // Polls differ per visitor, so a cached copy could show the wrong state.
            header('Cache-Control: no-store');
            $season = mfl_latest_season();
            $history = mfl_rank_history();
            $latest = $history ? end($history) : null;
            mfl_render('home', [
                'nav'        => 'home',
                'polls'      => array_slice(array_filter(mfl_public_polls(), fn($p) => $p['open']), 0, 3),
                'season'     => $season,
                'standings'  => $season ? mfl_standings((int) $season['season']) : [],
                'champions'  => mfl_champions(),
                'latest'     => $latest,
                'latestRows' => $latest ? mfl_ranking_rows($latest, $history) : [],
                'pickups'    => $season ? array_slice(mfl_pickup_rows(mfl_pickup_filters([], [(int) $season['season']])), 0, 5) : [],
            ]);
            break;

        case 'polls':
            header('Cache-Control: no-store');
            $polls = mfl_public_polls();
            mfl_render('polls', [
                'nav'    => 'polls',
                'title'  => 'Polls',
                'open'   => array_filter($polls, fn($p) => $p['open']),
                'closed' => array_filter($polls, fn($p) => !$p['open']),
            ]);
            break;

        case 'sign-in':
            header('Cache-Control: no-store');
            $sent = false;
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                mfl_check_origin();
                mfl_request_sign_in(trim((string) ($_POST['email'] ?? '')));
                $sent = true;
            }
            mfl_render('sign-in', ['nav' => 'polls', 'title' => 'Sign in', 'sent' => $sent, 'failed' => isset($_GET['expired']), 'owner' => mfl_current_owner()]);
            break;

        case 'sign-in/verify':
            header('Cache-Control: no-store');
            header('Location: ' . (mfl_sign_in((string) ($_GET['token'] ?? '')) ? '/polls' : '/sign-in?expired=1'), true, 303);
            break;

        case 'sign-out':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                mfl_check_origin();
                mfl_sign_out();
            }
            header('Location: /polls', true, 303);
            break;

        case 'trades/values/how':
            mfl_render('values-faq', ['nav' => 'trades', 'title' => 'How trade values are calculated', 'run' => mfl_value_meta('run')]);
            break;

        case 'trades/values':
            // Owners only, so the page differs per visitor.
            header('Cache-Control: no-store');
            $owner = mfl_current_owner();
            $f = mfl_values_filters($_GET);
            mfl_render('values', [
                'nav' => 'trades', 'title' => 'Trade Values', 'script' => true, 'owner' => $owner, 'f' => $f,
                'run' => $owner ? mfl_value_meta('run') : null,
                'rows' => $owner && $f['view'] === 'players' ? mfl_values_rows($f) : [],
                'picks' => $owner && $f['view'] === 'picks' ? mfl_pick_values() : [],
                'names' => array_column(mfl_franchises(), 'name', 'id'),
            ]);
            break;

        case 'polls/vote':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                mfl_check_origin();
                $owner = mfl_current_owner();
                if ($owner) mfl_poll_vote((int) ($_POST['poll'] ?? 0), (int) ($_POST['answer'] ?? 0), $owner);
            }
            $back = ($_POST['from'] ?? '') === 'home' ? '/' : '/polls';
            header('Location: ' . $back . '#poll-' . (int) ($_POST['poll'] ?? 0), true, 303);
            break;

        case 'rankings':
            $history = mfl_rank_history();
            mfl_render('rankings', [
                'nav'     => 'rankings',
                'title'   => 'Power Rankings',
                'history' => $history,
                'teams'   => mfl_ranking_team_stats($history),
                'trend'   => mfl_trend_data($history),
                'script'  => count($history) > 1,
            ]);
            break;

        default:
            if (mfl_history_route($path)) return;
            if (preg_match('#^rankings/(\d{4})/([a-z0-9-]+)$#', $path, $m)) {
                $week = mfl_week_from_slug($m[2]);
                $history = mfl_rank_history();
                foreach ($history as $i => $r) {
                    if ((int) $r['season'] !== (int) $m[1] || (int) $r['week'] !== $week) continue;
                    mfl_render('ranking', [
                        'nav'     => 'rankings',
                        'title'   => mfl_ranking_title($r),
                        'ranking' => $r,
                        'rows'    => mfl_ranking_rows($r, $history),
                        'prev'    => $history[$i - 1] ?? null,
                        'next'    => $history[$i + 1] ?? null,
                    ]);
                    return;
                }
            }
            http_response_code(404);
            mfl_render('404', ['title' => 'Not found']);
    }
}

/**
 * This host has no session to hold a CSRF token. Browsers send Origin on every
 * POST, so a form on another site is refused.
 */
function mfl_check_origin(): void
{
    $origin = parse_url($_SERVER['HTTP_ORIGIN'] ?? '', PHP_URL_HOST);
    $host = explode(':', $_SERVER['HTTP_HOST'] ?? '')[0];
    if ($origin && $origin !== $host) { http_response_code(403); exit('Not allowed.'); }
}

/** Asset URL with a cache-busting mtime. The folder sits in a different place locally. */
function mfl_asset(string $file): string
{
    $root = dirname(APP_DIR);
    $mtime = @filemtime("$root/public/mfl/assets/$file") ?: @filemtime("$root/mfl/assets/$file") ?: 0;
    return "/assets/$file?v=$mtime";
}

function mfl_render(string $view, array $data = []): void
{
    extract($data, EXTR_SKIP);
    $updated = mfl_last_updated();
    ob_start();
    require APP_DIR . '/views/mfl/' . $view . '.php';
    $content = ob_get_clean();
    require APP_DIR . '/views/mfl/layout.php';
}
