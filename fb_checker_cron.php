<?php
declare(strict_types=1);

/**
 * Facebook Monitor — cron runner.
 *
 * Iterates every user's fb_checker config and checks monitored profiles
 * that are due according to their configured check_interval_minutes.
 * Sends a Telegram alert when a previously unavailable profile becomes active.
 *
 * Schedule (Linux/macOS crontab):
 *   *\/5 * * * * php /path/to/trackify/fb_checker_cron.php >> /tmp/fb_cron.log 2>&1
 *
 * Schedule (Windows Task Scheduler):
 *   Program: php.exe
 *   Arguments: C:\laragon\www\trackify\fb_checker_cron.php
 *   Trigger: Every 5 minutes
 */

$root = __DIR__;

// trackify_capture.php provides: trackify_pdo(), trackify_send_telegram_html()
require_once $root . '/trackify_capture.php';
require_once $root . '/includes/fb_monitor_helpers.php';

// Prevent overlapping cron runs with a lock file
$lockFile = $root . '/data/fb_checker/cron.lock';

// Ensure the lock file directory exists
if (!is_dir(dirname($lockFile))) {
    @mkdir(dirname($lockFile), 0755, true);
}

$lock = @fopen($lockFile, 'c');
if ($lock === false) {
    fwrite(STDERR, "[fb_cron] Could not open lock file: {$lockFile}\n");
    exit(1);
}
if (!flock($lock, LOCK_EX | LOCK_NB)) {
    // Another instance is already running
    fclose($lock);
    exit(0);
}

$pdo = trackify_pdo();
if (!$pdo instanceof PDO) {
    fwrite(STDERR, "[fb_cron] Database not available\n");
    flock($lock, LOCK_UN);
    fclose($lock);
    exit(1);
}

// Scan all user directories under /data/fb_checker/
$baseDir  = $root . '/data/fb_checker';
$userDirs = glob($baseDir . '/u*', GLOB_ONLYDIR);
if ($userDirs === false) {
    $userDirs = [];
}

foreach ($userDirs as $dir) {
    // Extract uid from directory name "u123"
    $basename = basename($dir);
    if (!preg_match('/^u(\d+)$/', $basename, $m)) {
        continue;
    }
    $uid = (int) $m[1];
    if ($uid <= 0) {
        continue;
    }

    $cfg     = fb_monitor_read_config($uid);
    $cookies = $cfg['cookies'];
    if ($cookies === '') {
        continue;  // User has not configured cookies — skip
    }

    $interval = max(5, (int) ($cfg['check_interval_minutes'] ?? 15));

    // Fetch monitors that are due (never checked, or last check older than interval)
    $stmt = $pdo->prepare(
        'SELECT id, profile_url, label, last_status
         FROM facebook_monitor
         WHERE user_id = ?
           AND (last_checked_at IS NULL
                OR last_checked_at < DATE_SUB(NOW(), INTERVAL ? MINUTE))'
    );
    $stmt->execute([$uid, $interval]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $row) {
        $url        = (string) $row['profile_url'];
        $prevStatus = (string) $row['last_status'];
        $check      = fb_check_profile_url($url, $cookies);
        $newStatus  = $check['status'];
        $detail     = $check['detail'];

        if ($newStatus === 'unknown') {
            // Network/cookie error — update checked_at so we don't hammer, leave status unchanged
            $pdo->prepare('UPDATE facebook_monitor SET last_checked_at = NOW() WHERE id = ?')
                ->execute([(int) $row['id']]);
            echo "[fb_cron] unknown uid={$uid} id={$row['id']}: {$detail}\n";
            fb_monitor_append_activity(
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
            'UPDATE facebook_monitor
             SET last_status = ?,
                 last_checked_at = ?,
                 last_changed_at = CASE WHEN last_status != ? THEN ? ELSE last_changed_at END
             WHERE id = ?'
        );
        $upd->execute([$newStatus, $now, $newStatus, $now, (int) $row['id']]);

        if ($changed && $newStatus === 'active'
                && in_array($prevStatus, ['inactive', 'unavailable', 'unknown'], true)) {
            fb_monitor_send_active_alert($url, (string) ($row['label'] ?? ''));
            echo "[fb_cron] ALERT sent — user {$uid} profile active: {$url}\n";
        } else {
            echo "[fb_cron] uid={$uid} id={$row['id']} status={$newStatus}" . ($changed ? " (was {$prevStatus})" : '') . " — {$detail}\n";
        }

        fb_monitor_append_activity(
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
echo "[fb_cron] done " . gmdate('Y-m-d H:i:s') . "\n";
