<?php
/** Public routes for mfl.billykulpa.com. Nothing here starts a session. */

require_once __DIR__ . '/data.php';

function mfl_site_route(string $path): void
{
    switch ($path) {
        case '':
            $season = mfl_latest_season();
            mfl_render('home', [
                'season'    => $season,
                'standings' => $season ? mfl_standings((int) $season['season']) : [],
                'champions' => mfl_champions(),
            ]);
            break;

        default:
            http_response_code(404);
            mfl_render('404', ['title' => 'Not found']);
    }
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
