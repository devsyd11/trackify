#!/bin/bash
# Extract and display link from sendlink file — hack-style terminal

C="\e[0;36m"   # cyan
G="\e[0;32m"   # green
Y="\e[0;33m"   # amber
R="\e[0;31m"   # red
W="\e[0;37m"   # white
Dim="\e[0;90m" # dim
Reset="\e[0m"

if [[ -e "sendlink" ]]; then
    send_link=$(cat sendlink | sed 's/\x1b\[[0-9;]*m//g' | grep -oE "https://[^[:space:]]+" | head -n1)
    if [[ -z "$send_link" ]]; then
        send_link=$(cat sendlink | tr -d '\r' | sed 's/\x1b\[[0-9;]*m//g' | grep -oE "https://[a-zA-Z0-9\-\.]+\.(serveo\.net|serveousercontent\.com)" | head -n1)
    fi
    if [[ -z "$send_link" ]]; then
        send_link=$(cat sendlink | sed 's/.*https:\/\///' | sed 's/[[:space:]].*//' | head -n1)
        [[ -n "$send_link" && "$send_link" != *"https://"* ]] && send_link="https://$send_link"
    fi

    if [[ -n "$send_link" ]]; then
        echo ""
        echo -e "${C}[${G}+${C}] Direct link:${W} $send_link${Reset}"
        echo ""
        echo "$send_link"
        echo ""
    else
        echo ""
        echo -e "${R}[!] Could not extract link${Reset}"
        echo ""
        echo -e "${Dim}Raw sendlink contents:${Reset}"
        cat sendlink
        echo ""
        echo -e "${Dim}Cleaned contents (without ANSI codes):${Reset}"
        cat sendlink | sed 's/\x1b\[[0-9;]*m//g'
    fi
else
    echo -e "${R}[!] sendlink file not found!${Reset}"
fi
