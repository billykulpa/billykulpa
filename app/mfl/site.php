<?php
/** Public routes for mfl.billykulpa.com. Nothing here starts a session. */

require_once __DIR__ . '/data.php';
require_once __DIR__ . '/rankings.php';

function mfl_site_route(string $path): void
{
    switch ($path) {
        case '':
            $season = mfl_latest_season();
            $history = mfl_rank_history();
            $latest = $history ? end($history) : null;
            mfl_render('home', [
                'nav'        => 'home',
                'season'     => $season,
                'standings'  => $season ? mfl_standings((int) $season['season']) : [],
                'champions'  => mfl_champions(),
                'latest'     => $latest,
                'latestRows' => $latest ? mfl_ranking_rows($latest, $history) : [],
            ]);
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
