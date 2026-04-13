<?php

declare(strict_types=1);

/**
 * Delete Watch — shared helpers.
 * Required by api.php and delete_watch_cron.php.
 * Assumes trackify_capture.php is already loaded (provides trackify_pdo, trackify_send_telegram_html, trackify_trunc_utf8()).
 */

function delete_watch_user_dir(int $uid): string
{
    $dir = __DIR__ . '/../data/delete_watch/u' . $uid;
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    return $dir;
}

function delete_watch_read_config(int $uid): array
{
    $defaults = ['check_interval_minutes' => 15, 'updated_at' => ''];
    $path = delete_watch_user_dir($uid) . '/config.json';
    if (!is_readable($path)) {
        return $defaults;
    }
    $raw = file_get_contents($path);
    $j = (is_string($raw) && $raw !== '') ? json_decode($raw, true) : null;
    return is_array($j) ? array_merge($defaults, $j) : $defaults;
}

function delete_watch_write_config(int $uid, array $cfg): bool
{
    $dir  = delete_watch_user_dir($uid);
    $file = $dir . '/config.json';
    $fp   = @fopen($file, 'c+');
    if ($fp === false) {
        return false;
    }
    if (!flock($fp, LOCK_EX)) {
        fclose($fp);
        return false;
    }
    rewind($fp);
    ftruncate($fp, 0);
    fwrite($fp, (string) json_encode($cfg, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
    return true;
}

/**
 * Append-only activity log for Delete Watch (cron + manual checks).
 *
 * @return list<array{at: string, source: string, status: string, detail: string, label: string, profile_url: string}>
 */
function delete_watch_read_activity_log(int $uid): array
{
    $path = delete_watch_user_dir($uid) . '/activity_log.json';
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

function delete_watch_urls_match(string $a, string $b): bool
{
    $a = trim($a);
    $b = trim($b);
    if ($a === '' || $b === '') {
        return false;
    }
    if ($a === $b) {
        return true;
    }
    return rtrim($a, '/') === rtrim($b, '/');
}

function delete_watch_append_activity(
    int $uid,
    string $source,
    string $status,
    string $detail,
    string $label = '',
    string $profileUrl = ''
): void {
    $path = delete_watch_user_dir($uid) . '/activity_log.json';
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
 * Remove activity log entries tied to one or more profile URLs (e.g. after deleting monitors).
 *
 * @param list<string> $profileUrls
 */
function delete_watch_purge_activity_for_profile_urls(int $uid, array $profileUrls): void
{
    $want = [];
    foreach ($profileUrls as $u) {
        $u = trim((string) $u);
        if ($u !== '') {
            $want[$u] = true;
        }
    }
    if ($want === []) {
        return;
    }
    $keys = array_keys($want);

    $path = delete_watch_user_dir($uid) . '/activity_log.json';
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
                if (delete_watch_urls_match($pu, $k)) {
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

function delete_watch_send_unavailable_alert(string $url, string $label): void
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
        '🗑 <b>Delete Watch</b> — profile/page unavailable',
        '🚨 Monitored Facebook URL appears unavailable (restricted/deleted/not found).',
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

