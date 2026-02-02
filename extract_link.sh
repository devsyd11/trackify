#!/bin/bash
# Extract and display link from sendlink file

if [[ -e "sendlink" ]]; then
    # Method 1: Strip ANSI codes and extract URL (most reliable)
    send_link=$(cat sendlink | sed 's/\x1b\[[0-9;]*m//g' | grep -oE "https://[^[:space:]]+" | head -n1)
    
    # Method 2: Alternative - remove control chars first
    if [[ -z "$send_link" ]]; then
        send_link=$(cat sendlink | tr -d '\r' | sed 's/\x1b\[[0-9;]*m//g' | grep -oE "https://[a-zA-Z0-9\-\.]+\.(serveo\.net|serveousercontent\.com)" | head -n1)
    fi
    
    # Method 3: Extract anything after https://
    if [[ -z "$send_link" ]]; then
        send_link=$(cat sendlink | sed 's/.*https:\/\///' | sed 's/[[:space:]].*//' | head -n1)
        if [[ -n "$send_link" && "$send_link" != *"https://"* ]]; then
            send_link="https://$send_link"
        fi
    fi
    
    # Display result
    if [[ -n "$send_link" ]]; then
        echo ""
        echo -e "\e[1;93m[\e[0m\e[1;77m+\e[0m\e[1;93m] Direct link:\e[0m\e[1;77m $send_link\e[0m"
        echo ""
        # Also print without colors for easy copying
        echo "$send_link"
        echo ""
    else
        echo ""
        echo -e "\e[1;31m[!] Could not extract link\e[0m"
        echo ""
        echo "Raw sendlink contents:"
        cat sendlink
        echo ""
        echo "Cleaned contents (without ANSI codes):"
        cat sendlink | sed 's/\x1b\[[0-9;]*m//g'
    fi
else
    echo "sendlink file not found!"
fi
