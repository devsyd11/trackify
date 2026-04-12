<?php

declare(strict_types=1);

/**
 * Instagram profile checker — Playwright fetch + HTML heuristics.
 */

function ig_monitor_node_binary(): string
{
    $cfgPath = __DIR__ . '/../config.php';
    if (is_readable($cfgPath)) {
        $cfg = require $cfgPath;
        $n = trim((string) ($cfg['fb_monitor_node'] ?? ''));
        if ($n !== '') {
            return $n;
        }
    }

    return 'node';
}

function ig_monitor_playwright_should_run(): bool
{
    $cfgPath = __DIR__ . '/../config.php';
    if (is_readable($cfgPath)) {
        $cfg = require $cfgPath;
        if (array_key_exists('fb_monitor_use_playwright', $cfg) && !$cfg['fb_monitor_use_playwright']) {
            return false;
        }
    }
    $script = __DIR__ . '/../scripts/ig_monitor_playwright.mjs';
    $pwPkg  = __DIR__ . '/../node_modules/playwright/package.json';

    return is_file($script) && is_file($pwPkg);
}

/**
 * @return null|array{ok: bool, html: string, visible_text: string, http_code: int, effective_url: string, error?: string}
 */
function ig_monitor_try_playwright(string $url): ?array
{
    if (!ig_monitor_playwright_should_run()) {
        return null;
    }
    $node = ig_monitor_node_binary();
    $script = realpath(__DIR__ . '/../scripts/ig_monitor_playwright.mjs');
    if ($script === false || !is_file($script)) {
        return null;
    }
    $root = dirname(__DIR__);
    $payload = json_encode(['profileUrl' => $url], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($payload === false) {
        return [
            'ok'            => false,
            'error'         => 'Could not encode request for browser worker',
            'html'          => '',
            'visible_text'  => '',
            'http_code'     => 0,
            'effective_url' => '',
        ];
    }
    $cmd = escapeshellarg($node) . ' ' . escapeshellarg($script);
    $des = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
    $proc = @proc_open($cmd, $des, $pipes, $root);
    if (!is_resource($proc)) {
        return [
            'ok'            => false,
            'error'         => 'Could not start Instagram browser worker',
            'html'          => '',
            'visible_text'  => '',
            'http_code'     => 0,
            'effective_url' => '',
        ];
    }
    fwrite($pipes[0], $payload);
    fclose($pipes[0]);
    $stdout = (string) stream_get_contents($pipes[1]);
    $stderr = trim((string) stream_get_contents($pipes[2]));
    fclose($pipes[1]);
    fclose($pipes[2]);
    proc_close($proc);

    $fail = static function (string $err) use ($stderr): array {
        if ($err === '' && $stderr !== '') {
            $err = $stderr;
        }
        if ($err === '') {
            $err = 'Instagram browser check did not complete.';
        }

        return [
            'ok'            => false,
            'error'         => $err,
            'html'          => '',
            'visible_text'  => '',
            'http_code'     => 0,
            'effective_url' => '',
        ];
    };

    if ($stdout === '') {
        return $fail($stderr !== '' ? $stderr : 'Browser worker produced no output');
    }

    $decoded = json_decode($stdout, true);
    if (!is_array($decoded) || empty($decoded['ok'])) {
        $msg = is_array($decoded) ? trim((string) ($decoded['error'] ?? '')) : '';

        return $fail($msg);
    }

    return [
        'ok'            => true,
        'html'          => (string) ($decoded['html'] ?? ''),
        'visible_text'  => (string) ($decoded['visible_text'] ?? ''),
        'http_code'     => (int) ($decoded['http_code'] ?? 0),
        'effective_url' => (string) ($decoded['effective_url'] ?? ''),
    ];
}

function ig_normalize_profile_url(string $url): string
{
    $u = trim($url);
    if ($u === '') {
        return '';
    }
    $p = @parse_url($u);
    if (!is_array($p) || empty($p['host'])) {
        return $u;
    }
    $host = strtolower((string) $p['host']);
    if (!preg_match('/(^|\.)instagram\.com$/', $host) && $host !== 'instagr.am') {
        return $u;
    }
    if ($host === 'instagr.am' || $host === 'instagram.com' || $host === 'm.instagram.com') {
        $host = 'www.instagram.com';
    }
    $path = isset($p['path']) ? (string) $p['path'] : '/';
    if ($path === '') {
        $path = '/';
    }
    if ($path !== '/' && !str_ends_with($path, '/')) {
        $path .= '/';
    }
    $out = 'https://' . $host . $path;
    if (!empty($p['query'])) {
        $out .= '?' . $p['query'];
    }

    return $out;
}

/**
 * @return array{status: string, detail: string, update_last_status?: bool}
 */
function ig_classify_instagram(string $html, string $visibleText, int $httpCode): array
{
    $combined = strtolower($html . "\n" . $visibleText);

    $unavailableNeedles = [
        "profile isn't available",
        "this page isn't available",
        'the link may be broken',
        'sorry, this page isn\'t available',
        'sorry, this page isn’t available',
        'user not found',
        'no users found',
        'page not found',
    ];
    foreach ($unavailableNeedles as $needle) {
        if (strpos($combined, $needle) !== false) {
            return [
                'status'               => 'unavailable',
                'detail'               => 'Instagram shows this profile as unavailable.',
                'update_last_status'   => true,
            ];
        }
    }

    if (preg_match('/content\s*=\s*"[^"]*profile[^"]*isn\'t available/i', $html)) {
        return [
            'status'             => 'unavailable',
            'detail'             => 'Instagram meta indicates profile unavailable.',
            'update_last_status' => true,
        ];
    }

    if ($httpCode === 404) {
        return [
            'status'             => 'unavailable',
            'detail'             => 'HTTP 404.',
            'update_last_status' => true,
        ];
    }

    $vt = $visibleText;
    if (strlen($vt) < 1200 && (stripos($vt, 'log in') !== false || stripos($vt, 'log in to instagram') !== false)) {
        return [
            'status'               => 'unknown',
            'detail'               => 'Page may require login or blocked automated access.',
            'update_last_status'   => false,
        ];
    }

    if (stripos($html, '"edge_followed_by"') !== false
        || stripos($html, '"edge_follow"') !== false
        || preg_match('/property="og:type"\s+content="profile"/i', $html)) {
        return [
            'status'             => 'active',
            'detail'             => 'Profile signals detected.',
            'update_last_status' => true,
        ];
    }

    if (stripos($vt, 'this account is private') !== false) {
        return [
            'status'             => 'active',
            'detail'             => 'Account exists (private).',
            'update_last_status' => true,
        ];
    }

    if ($httpCode >= 200 && $httpCode < 400 && strlen($html) > 4000 && strpos($combined, "isn't available") === false) {
        return [
            'status'             => 'active',
            'detail'             => 'Page loaded (heuristic).',
            'update_last_status' => true,
        ];
    }

    return [
        'status'               => 'unknown',
        'detail'               => 'Could not determine Instagram status.',
        'update_last_status'   => false,
    ];
}

/**
 * @return array{status: string, detail: string, update_last_status?: bool}
 */
function ig_check_profile_url(string $url): array
{
    $url = trim($url);
    $pw  = ig_monitor_try_playwright($url);
    if ($pw === null) {
        return [
            'status'             => 'unknown',
            'detail'             => 'Instagram checker needs Playwright (npm ci in project root).',
            'update_last_status' => false,
        ];
    }
    if (empty($pw['ok'])) {
        $err = trim((string) ($pw['error'] ?? ''));

        return [
            'status'             => 'unknown',
            'detail'             => $err !== '' ? ('Browser: ' . $err) : 'Browser check failed.',
            'update_last_status' => false,
        ];
    }

    return ig_classify_instagram(
        (string) ($pw['html'] ?? ''),
        (string) ($pw['visible_text'] ?? ''),
        (int) ($pw['http_code'] ?? 0)
    );
}

function ig_monitor_sql_like_escape(string $s): string
{
    return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $s);
}

function ig_monitor_urls_equivalent(string $a, string $b): bool
{
    $na = ig_normalize_profile_url($a);
    $nb = ig_normalize_profile_url($b);

    return strcasecmp($na, $nb) === 0;
}

function ig_monitor_send_active_alert(string $url, string $label): void
{
    $esc = static fn(string $s): string => htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $u = trim($url);
    if ($u === '') {
        return;
    }
    $when = $esc(gmdate('Y-m-d H:i:s') . ' UTC');
    $href = $esc($u);
    $labelTrim = trim($label);
    $labelLine = $labelTrim !== '' ? $esc($labelTrim) : '—';

    $lines = [
        '✅ <b>Instagram checker</b> — profile accessible again',
        '🎯 Monitored Instagram URL is loading (no longer unavailable).',
        '📌 <b>Name:</b> ' . $labelLine,
        '🔗 <b>Profile:</b> <a href="' . $href . '">' . $href . '</a>',
        '🕒 <b>Last Checked At:</b> <code>' . $when . '</code>',
        '<i>Trackify · Meta tools</i>',
    ];

    $html = implode("\n", $lines);
    if (strlen($html) > 4000) {
        $html = trackify_trunc_utf8($html, 3990) . '…';
    }

    trackify_send_telegram_html($html, false);
}

/**
 * Activity log for Instagram checker (separate file from Facebook monitor).
 *
 * @return list<array{at: string, source: string, status: string, detail: string, label: string, profile_url: string}>
 */
function ig_monitor_read_activity_log(int $uid): array
{
    $path = fb_monitor_user_dir($uid) . '/ig_activity_log.json';
    if (!is_readable($path)) {
        return [];
    }
    $raw = file_get_contents($path);
    if ($raw === false || $raw === '') {
        return [];
    }
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        return [];
    }
    $out = [];
    foreach ($data as $row) {
        if (!is_array($row)) {
            continue;
        }
        $out[] = [
            'at'          => (string) ($row['at'] ?? ''),
            'source'      => (string) ($row['source'] ?? ''),
            'status'      => (string) ($row['status'] ?? 'unknown'),
            'detail'      => (string) ($row['detail'] ?? ''),
            'label'       => (string) ($row['label'] ?? ''),
            'profile_url' => (string) ($row['profile_url'] ?? ''),
        ];
    }

    return $out;
}

function ig_monitor_append_activity(
    int $uid,
    string $source,
    string $status,
    string $detail,
    string $label = '',
    string $profileUrl = ''
): void {
    $path = fb_monitor_user_dir($uid) . '/ig_activity_log.json';
    $entry = [
        'at'          => gmdate('Y-m-d\TH:i:s\Z'),
        'source'      => $source,
        'status'      => $status,
        'detail'      => $detail,
        'label'       => $label,
        'profile_url' => $profileUrl,
    ];

    $fp = @fopen($path, 'c+');
    if ($fp === false) {
        return;
    }
    if (!flock($fp, LOCK_EX)) {
        fclose($fp);
        return;
    }
    rewind($fp);
    $raw = stream_get_contents($fp);
    $list = [];
    if (is_string($raw) && $raw !== '') {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            $list = $decoded;
        }
    }
    array_unshift($list, $entry);
    if (count($list) > 120) {
        $list = array_slice($list, 0, 120);
    }
    rewind($fp);
    ftruncate($fp, 0);
    fwrite($fp, (string) json_encode($list, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
}

/**
 * @param list<string> $profileUrls
 */
function ig_monitor_purge_activity_for_profile_urls(int $uid, array $profileUrls): void
{
    $keys = [];
    foreach ($profileUrls as $u) {
        $u = trim((string) $u);
        if ($u !== '') {
            $keys[] = $u;
        }
    }
    if ($keys === []) {
        return;
    }

    $path = fb_monitor_user_dir($uid) . '/ig_activity_log.json';
    $fp   = @fopen($path, 'c+');
    if ($fp === false) {
        return;
    }
    if (!flock($fp, LOCK_EX)) {
        fclose($fp);

        return;
    }
    rewind($fp);
    $raw = stream_get_contents($fp);
    $list = [];
    if (is_string($raw) && $raw !== '') {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            $list = $decoded;
        }
    }

    $kept = [];
    foreach ($list as $entry) {
        if (!is_array($entry)) {
            continue;
        }
        $pu = trim((string) ($entry['profile_url'] ?? ''));
        $drop = false;
        if ($pu !== '') {
            foreach ($keys as $k) {
                if (ig_monitor_urls_equivalent($pu, $k)) {
                    $drop = true;
                    break;
                }
            }
        }
        if (!$drop) {
            $kept[] = $entry;
        }
    }

    rewind($fp);
    ftruncate($fp, 0);
    fwrite($fp, (string) json_encode($kept, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
}
