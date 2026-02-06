<?php
/**
 * IP Geolocation Helper Functions
 * Uses ip-api.com (free tier: 45 requests/minute)
 */

/**
 * Get IP geolocation information
 * @param string $ip IP address
 * @return array|false Geolocation data or false on failure
 */
function getIPLocation($ip) {
    // Remove any whitespace/newlines from IP
    $ip = trim($ip);
    
    // Skip localhost/private IPs
    if ($ip === '127.0.0.1' || $ip === 'localhost' || 
        filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
        return [
            'status' => 'private',
            'message' => 'Private or local IP address',
            'ip' => $ip
        ];
    }
    
    // Use ip-api.com free API
    $url = "http://ip-api.com/json/{$ip}?fields=status,message,country,countryCode,region,regionName,city,zip,lat,lon,timezone,isp,org,as,query";
    
    // Set timeout and context
    $context = stream_context_create([
        'http' => [
            'timeout' => 5,
            'user_agent' => 'Trackify/1.0'
        ]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    
    if ($response === false) {
        return false;
    }
    
    $data = json_decode($response, true);
    
    if (isset($data['status']) && $data['status'] === 'success') {
        return [
            'status' => 'success',
            'ip' => $data['query'],
            'country' => $data['country'] ?? 'Unknown',
            'countryCode' => $data['countryCode'] ?? '',
            'region' => $data['region'] ?? '',
            'regionName' => $data['regionName'] ?? 'Unknown',
            'city' => $data['city'] ?? 'Unknown',
            'zip' => $data['zip'] ?? '',
            'latitude' => $data['lat'] ?? 0,
            'longitude' => $data['lon'] ?? 0,
            'timezone' => $data['timezone'] ?? 'Unknown',
            'isp' => $data['isp'] ?? 'Unknown',
            'org' => $data['org'] ?? 'Unknown',
            'as' => $data['as'] ?? 'Unknown',
            'location' => ($data['city'] ?? 'Unknown') . ', ' . ($data['regionName'] ?? 'Unknown') . ', ' . ($data['country'] ?? 'Unknown')
        ];
    }
    
    return [
        'status' => 'fail',
        'message' => $data['message'] ?? 'Unknown error',
        'ip' => $ip
    ];
}

/**
 * Format geolocation data for display
 * @param array $geo Geolocation data
 * @return string Formatted string
 */
function formatGeoLocation($geo) {
    if (!$geo || !isset($geo['status']) || $geo['status'] !== 'success') {
        return 'Location: Unknown';
    }
    
    $location = [];
    if (!empty($geo['city'])) $location[] = $geo['city'];
    if (!empty($geo['regionName'])) $location[] = $geo['regionName'];
    if (!empty($geo['country'])) $location[] = $geo['country'];
    
    $result = '📍 Location: ' . implode(', ', $location);
    
    if (!empty($geo['isp'])) {
        $result .= "\n🌐 ISP: " . $geo['isp'];
    }
    
    if (!empty($geo['timezone'])) {
        $result .= "\n🕐 Timezone: " . $geo['timezone'];
    }
    
    return $result;
}

/**
 * Format geolocation for Telegram message
 * @param array $geo Geolocation data
 * @return string Formatted string for Telegram
 */
function formatGeoForTelegram($geo) {
    if (!$geo || !isset($geo['status']) || $geo['status'] !== 'success') {
        return '';
    }
    
    $message = '';
    
    if (!empty($geo['city']) || !empty($geo['regionName']) || !empty($geo['country'])) {
        $location = [];
        if (!empty($geo['city'])) $location[] = $geo['city'];
        if (!empty($geo['regionName'])) $location[] = $geo['regionName'];
        if (!empty($geo['country'])) $location[] = $geo['country'];
        
        $message .= "📍 *Location:* " . implode(', ', $location) . "\n";
    }
    
    if (!empty($geo['isp'])) {
        $message .= "🌐 *ISP:* " . $geo['isp'] . "\n";
    }
    
    if (!empty($geo['org']) && $geo['org'] !== $geo['isp']) {
        $message .= "🏢 *Organization:* " . $geo['org'] . "\n";
    }
    
    if (!empty($geo['latitude']) && !empty($geo['longitude'])) {
        $mapUrl = "https://www.google.com/maps?q={$geo['latitude']},{$geo['longitude']}";
        $message .= "🗺️ [View on Map]($mapUrl)\n";
    }
    
    return $message;
}

/**
 * Save geolocation data to JSON file
 * @param string $ip IP address
 * @param array $geo Geolocation data
 * @return bool Success status
 */
function saveGeoData($ip, $geo) {
    $ip_clean = trim($ip);
    $file = 'geolocations.json';
    
    // Read existing data
    $data = [];
    if (file_exists($file)) {
        $content = @file_get_contents($file);
        if ($content !== false) {
            $data = json_decode($content, true) ?: [];
        }
    }
    
    // Add new entry
    $data[] = [
        'ip' => $ip_clean,
        'timestamp' => date('Y-m-d H:i:s'),
        'geo' => $geo
    ];
    
    // Save back to file
    return @file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT)) !== false;
}

?>
