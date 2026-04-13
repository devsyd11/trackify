<?php

declare(strict_types=1);

/**
 * Global Facebook cookies (per user) used by Facebook checker + Delete Watch.
 *
 * Storage: data/fb_cookies/u{uid}/cookies.json
 * Format:
 *   { "cookies": ["c_user=...; xs=...", "..."], "updated_at": "ISO8601" }
 */

function fb_cookies_user_dir(int $uid): string
{
    $dir = __DIR__ . '/../data/fb_cookies/u' . $uid;
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    return $dir;
}

/**
 * @return array{cookies: list<string>, updated_at: string}
 */
function fb_cookies_read(int $uid): array
{
    $defaults = ['cookies' => [], 'updated_at' => ''];
    $path = fb_cookies_user_dir($uid) . '/cookies.json';
    if (!is_readable($path)) {
        return $defaults;
    }
    $raw = file_get_contents($path);
    $j = (is_string($raw) && $raw !== '') ? json_decode($raw, true) : null;
    if (!is_array($j)) {
        return $defaults;
    }
    $cookies = [];
    if (isset($j['cookies']) && is_array($j['cookies'])) {
        foreach ($j['cookies'] as $c) {
            $c = trim((string) $c);
            if ($c !== '') {
                $cookies[] = $c;
            }
        }
    }
    return [
        'cookies'    => $cookies,
        'updated_at' => (string) ($j['updated_at'] ?? ''),
    ];
}

/**
 * @param list<string> $cookies
 */
function fb_cookies_write(int $uid, array $cookies): bool
{
    $dir  = fb_cookies_user_dir($uid);
    $file = $dir . '/cookies.json';
    $payload = json_encode(
        ['cookies' => array_values($cookies), 'updated_at' => gmdate('c')],
        JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
    );
    if ($payload === false) {
        return false;
    }
    return @file_put_contents($file, $payload, LOCK_EX) !== false;
}

/**
 * Return best cookie string to use (first non-empty), normalized to flat "k=v; k=v" format.
 */
function fb_cookies_best_cookie_string(int $uid): string
{
    $cfg = fb_cookies_read($uid);
    foreach (($cfg['cookies'] ?? []) as $raw) {
        $raw = trim((string) $raw);
        if ($raw === '') {
            continue;
        }
        // fb_cookies_normalize is defined in fb_monitor_helpers.php.
        $flat = function_exists('fb_cookies_normalize') ? fb_cookies_normalize($raw) : $raw;
        $flat = trim((string) $flat);
        if ($flat !== '') {
            return $flat;
        }
    }
    return '';
}

