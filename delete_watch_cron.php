<?php

declare(strict_types=1);

/**
 * Delete Watch — cron runner.
 *
 * Iterates every user's delete_watch config and checks monitored profiles
 * that are due according to their configured check_interval_minutes.
 * Sends a Telegram alert when a previously reachable profile becomes unavailable.
 *
 * Schedule (Linux/macOS crontab):
 *   * * * * * php /path/to/trackify/delete_watch_cron.php >> /tmp/dw_cron.log 2>&1
 *
 * Schedule (Windows Task Scheduler):
 *   Program: php.exe
 *   Arguments: C:\laragon\www\trackify\delete_watch_cron.php
 *   Trigger: Every 1 minute (or your smallest interval)
 */

$root = __DIR__;

require_once $root . '/trackify_capture.php';
require_once $root . '/includes/fb_monitor_helpers.php';
require_once $root . '/includes/delete_watch_helpers.php';
require_once $root . '/includes/fb_global_cookies.php';

// Prevent overlapping runs
$lockFile = $root . '/data/delete_watch/cron.lock';
if (!is_dir(dirname($lockFile))) {
    @mkdir(dirname($lockFile), 0755, true);
}
$lock = @fopen($lockFile, 'c');
if ($lock === false) {
    fwrite(STDERR, "[dw_cron] Could not open lock file: {$lockFile}\n");
    exit(1);
}
if (!flock($lock, LOCK_EX | LOCK_NB)) {
    fclose($lock);
    exit(0);
}

$pdo = trackify_pdo();
if (!$pdo instanceof PDO) {
    fwrite(STDERR, "[dw_cron] Database not available\n");
    flock($lock, LOCK_UN);
    fclose($lock);
    exit(1);
}

$baseDir  = $root . '/data/delete_watch';
$userDirs = glob($baseDir . '/u*', GLOB_ONLYDIR);
if ($userDirs === false) {
    $userDirs = [];
}

foreach ($userDirs as $dir) {
    $basename = basename($dir);
    if (!preg_match('/^u(\d+)$/', $basename, $m)) {
        continue;
    }
    $uid = (int) $m[1];
    if ($uid <= 0) {
        continue;
    }

    $cfg = delete_watch_read_config($uid);
    $interval = max(1, (int) ($cfg['check_interval_minutes'] ?? 15));
    $cookieString = fb_cookies_best_cookie_string($uid);

    $stmt = $pdo->prepare(
        'SELECT id, profile_url, label, last_status
         FROM delete_watch_monitor
         WHERE user_id = ?
           AND last_status != \'unavailable\'
           AND (last_checked_at IS NULL
                OR last_checked_at < DATE_SUB(NOW(), INTERVAL ? MINUTE))'
    );
    $stmt->execute([$uid, $interval]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $row) {
        $url        = (string) $row['profile_url'];
        $prevStatus = (string) $row['last_status'];
        $check      = fb_check_profile_url($url, $cookieString);
        $newStatus  = $check['status'];
        $detail     = $check['detail'];

        if ($newStatus === 'unknown' && empty($check['update_last_status'])) {
            $pdo->prepare('UPDATE delete_watch_monitor SET last_checked_at = NOW() WHERE id = ?')
                ->execute([(int) $row['id']]);
            echo "[dw_cron] unknown uid={$uid} id={$row['id']}: {$detail}\n";
            delete_watch_append_activity(
                $uid,
                'cron',
                'unknown',
                $detail,
                (string) ($row['label'] ?? ''),
                $url
            );
            continue;
        }

        $changed = $newStatus !== $prevStatus;
        $now     = gmdate('Y-m-d H:i:s');

        $upd = $pdo->prepare(
            'UPDATE delete_watch_monitor
             SET last_status = ?,
                 last_checked_at = ?,
                 last_changed_at = CASE WHEN last_status != ? THEN ? ELSE last_changed_at END
             WHERE id = ?'
        );
        $upd->execute([$newStatus, $now, $newStatus, $now, (int) $row['id']]);

        if ($changed && $newStatus === 'unavailable' && in_array($prevStatus, ['active', 'inactive', 'unknown'], true)) {
            delete_watch_send_unavailable_alert($url, (string) ($row['label'] ?? ''));
            echo "[dw_cron] ALERT sent — user {$uid} profile unavailable: {$url}\n";
        } else {
            echo "[dw_cron] uid={$uid} id={$row['id']} status={$newStatus}" . ($changed ? " (was {$prevStatus})" : '') . " — {$detail}\n";
        }

        delete_watch_append_activity(
            $uid,
            'cron',
            $newStatus,
            $detail,
            (string) ($row['label'] ?? ''),
            $url
        );
    }
}

flock($lock, LOCK_UN);
fclose($lock);
echo "[dw_cron] done " . gmdate('Y-m-d H:i:s') . "\n";

