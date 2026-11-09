<?php
/**
 * Read-only client for Sleeper's public API. No key or login is involved.
 * https://docs.sleeper.com
 */

require_once __DIR__ . '/league.php';

const SLEEPER_API = 'https://api.sleeper.app/v1';

function sleeper_get(string $path): mixed
{
    return sleeper_get_many([$path])[$path];
}

/**
 * Fetch many paths at once, a few at a time. A season is ~45 requests, and
 * "Sync now" runs inside a web request, so doing them one by one would risk
 * the host's time limit. Throws if any request fails, which leaves the
 * season's previous data untouched.
 */
function sleeper_get_many(array $paths, int $timeout = 20): array
{
    $results = [];
    foreach (array_chunk(array_values(array_unique($paths)), 8) as $batch) {
        $multi = curl_multi_init();
        $handles = [];
        foreach ($batch as $path) {
            $ch = curl_init(SLEEPER_API . $path);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => $timeout,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_ENCODING       => '',
                CURLOPT_USERAGENT      => 'mfl.billykulpa.com',
            ]);
            curl_multi_add_handle($multi, $ch);
            $handles[$path] = $ch;
        }
        do {
            $status = curl_multi_exec($multi, $running);
            if ($running) curl_multi_select($multi, 1.0);
        } while ($running && $status === CURLM_OK);

        foreach ($handles as $path => $ch) {
            $body = curl_multi_getcontent($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            $err  = curl_error($ch);
            curl_multi_remove_handle($multi, $ch);
            curl_close($ch);
            if ($code !== 200 || !is_string($body)) {
                curl_multi_close($multi);
                throw new RuntimeException("Sleeper request failed ($code) for $path. $err");
            }
            // Sleeper answers `null` for things that don't exist yet, such as
            // the bracket of a season that hasn't reached the playoffs.
            $results[$path] = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        }
        curl_multi_close($multi);
    }
    return $results;
}
