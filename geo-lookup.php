<?php
/**
 * Geo Lookup API - Returns IP geolocation for the current request
 * Used as fallback when GPS hardware is unavailable or permission denied
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'geo.php';

// Get client IP
$ip = $_SERVER['REMOTE_ADDR'] ?? '';
if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
    $ip = $_SERVER['HTTP_CLIENT_IP'];
} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $ip = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
}

$geo = getIPLocation($ip);

if ($geo && isset($geo['status']) && $geo['status'] === 'success') {
    echo json_encode([
        'status' => 'success',
        'source' => 'ip',
        'latitude' => $geo['latitude'],
        'longitude' => $geo['longitude'],
        'city' => $geo['city'] ?? 'Unknown',
        'region' => $geo['regionName'] ?? 'Unknown',
        'country' => $geo['country'] ?? 'Unknown',
        'countryCode' => $geo['countryCode'] ?? '',
        'isp' => $geo['isp'] ?? 'Unknown',
        'timezone' => $geo['timezone'] ?? 'Unknown',
        'location' => $geo['location'] ?? 'Unknown',
        'ip' => $geo['ip']
    ]);
} else {
    echo json_encode([
        'status' => 'fail',
        'message' => $geo['message'] ?? 'Unable to determine location',
        'ip' => $ip
    ]);
}
