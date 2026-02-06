<?php
/**
 * API Endpoint for Trackify
 * Returns capture data in JSON format
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

// Read geolocations.json file
$geoFile = 'geolocations.json';
$data = [];

if (file_exists($geoFile)) {
    $content = @file_get_contents($geoFile);
    if ($content !== false) {
        $data = json_decode($content, true) ?: [];
    }
}

// Read saved IPs file for additional data
$ipFile = 'saved.ip.txt';
$ipData = [];
if (file_exists($ipFile)) {
    $content = @file_get_contents($ipFile);
    if ($content !== false) {
        // Parse IP file to extract IPs and timestamps
        $lines = explode("\n", $content);
        $currentIP = null;
        $currentTime = null;
        
        foreach ($lines as $line) {
            if (preg_match('/IP:\s*(.+)/', $line, $matches)) {
                $currentIP = trim($matches[1]);
            }
            if (preg_match('/Time:\s*(.+)/', $line, $matches)) {
                $currentTime = trim($matches[1]);
            }
            if ($currentIP && $currentTime) {
                $ipData[$currentIP] = $currentTime;
                $currentIP = null;
                $currentTime = null;
            }
        }
    }
}

// Merge data
$result = [];
foreach ($data as $entry) {
    $ip = $entry['ip'];
    $geo = $entry['geo'];
    
    $result[] = [
        'ip' => $ip,
        'timestamp' => $entry['timestamp'],
        'latitude' => isset($geo['latitude']) ? floatval($geo['latitude']) : null,
        'longitude' => isset($geo['longitude']) ? floatval($geo['longitude']) : null,
        'city' => $geo['city'] ?? 'Unknown',
        'region' => $geo['regionName'] ?? 'Unknown',
        'country' => $geo['country'] ?? 'Unknown',
        'countryCode' => $geo['countryCode'] ?? '',
        'isp' => $geo['isp'] ?? 'Unknown',
        'org' => $geo['org'] ?? 'Unknown',
        'timezone' => $geo['timezone'] ?? 'Unknown',
        'location' => $geo['location'] ?? 'Unknown'
    ];
}

// Sort by timestamp (newest first)
usort($result, function($a, $b) {
    return strtotime($b['timestamp']) - strtotime($a['timestamp']);
});

// Return JSON response
echo json_encode([
    'status' => 'success',
    'count' => count($result),
    'data' => $result
], JSON_PRETTY_PRINT);

?>
