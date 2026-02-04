# 🔗 Trackify v1.5

<div align="center">

```
████████╗██████╗  █████╗  ██████╗██╗  ██╗██╗███████╗██╗   ██╗ 
╚══██╔══╝██╔══██╗██╔══██╗██╔════╝██║ ██╔╝██║██╔════╝╚██╗ ██╔╝ 
   ██║   ██████╔╝███████║██║     █████╔╝ ██║█████╗   ╚████╔╝  
   ██║   ██╔══██╗██╔══██║██║     ██╔═██╗ ██║██╔══╝    ╚██╔╝   
   ██║   ██║  ██║██║  ██║╚██████╗██║  ██╗██║██║        ██║    
   ╚═╝   ╚═╝  ╚═╝╚═╝  ╚═╝ ╚═════╝╚═╝  ╚═╝╚═╝╚═╝        ╚═╝    
```

**▄︻̷̿┻̿═━一  TRACKIFY  一━═┻̿︻̷▄**

*Advanced IP Tracking & Phishing Link Generator*

**Developed by:** 0Cod3  
**Powered by:** TechChip  
**Credits:** [thelinuxchoice](https://github.com/thelinuxchoice/)

[![Version](https://img.shields.io/badge/version-1.5-blue.svg)](https://github.com)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![Platform](https://img.shields.io/badge/platform-Linux%20%7C%20Windows-lightgrey.svg)]()

</div>

---

## 📋 Table of Contents

- [Overview](#-overview)
- [Features](#-features)
- [Dependencies](#-dependencies)
- [Installation](#-installation)
- [Usage](#-usage)
- [Configuration](#-configuration)
- [Tunnel Services](#-tunnel-services)
- [Templates](#-templates)
- [Notifications](#-notifications)
- [Troubleshooting](#-troubleshooting)
- [Disclaimer](#-disclaimer)

---

## 🎯 Overview

**Trackify** is a powerful IP tracking tool that generates phishing links with multiple tunnel services and notification methods. It captures IP addresses, user agents, and can optionally capture camera photos when targets interact with the generated links.

### What it does:
- Generates tracking links using various tunnel services
- Captures IP addresses and browser information
- Supports multiple phishing templates (YouTube Live, Google Meet, Age Verification)
- Sends real-time notifications via Telegram or saves locally
- Optional camera capture functionality

---

## ✨ Features

- 🔗 **Multiple Tunnel Services**: Ngrok, Cloudflare Tunnel, Serveo.net
- 📱 **Telegram Integration**: Real-time notifications with bot support
- 🎨 **Multiple Templates**: YouTube Live, Google Meet, Sensitive Video
- 📸 **Camera Capture**: Optional photo capture functionality
- 💾 **Local Storage**: Saves captured data to files
- 🎯 **IP Tracking**: Captures IP, User-Agent, and timestamp
- 🚀 **Easy Setup**: Interactive menu-driven interface
- 🔄 **Auto-Download**: Automatically downloads tunnel binaries when needed

---

## 📦 Dependencies

### Required Dependencies

| Dependency | Purpose | Installation |
|------------|---------|--------------|
| **PHP** | Server-side scripting | `apt install php` (Linux) or download from [php.net](https://www.php.net/) |
| **Bash** | Script execution | Pre-installed on Linux/Mac, Git Bash on Windows |

### Optional Dependencies (Based on Tunnel Service)

| Dependency | Purpose | When Needed |
|------------|---------|-------------|
| **SSH** | Serveo tunnel service | Required for Serveo option |
| **Ngrok** | Ngrok tunnel service | Auto-downloaded if not present |
| **Cloudflared** | Cloudflare tunnel | Auto-downloaded if not present |
| **curl/wget** | HTTP requests | For testing Telegram bot |
| **unzip** | Extract archives | For Ngrok installation |

### Platform-Specific Notes

- **Linux**: All dependencies available via package manager
- **Windows**: Use Git Bash or WSL (Windows Subsystem for Linux)
- **macOS**: Use Homebrew: `brew install php`
- **Android/Termux**: Install via `pkg install php wget unzip`

---

## 🚀 Installation

### Method 1: Clone Repository

```bash
git clone https://github.com/yourusername/trackify.git
cd trackify
chmod +x trackify.sh
```

### Method 2: Direct Download

1. Download the repository as ZIP
2. Extract to your desired location
3. Navigate to the directory
4. Make script executable:
   ```bash
   chmod +x trackify.sh
   ```

### Verify Installation

```bash
# Check PHP installation
php -v

# Check script permissions
ls -l trackify.sh
```

---

## 💻 Usage

### Basic Usage

```bash
./trackify.sh
```

The script will guide you through an interactive menu:

1. **Select Notification Method**
   - Option 1: Default (Local file storage)
   - Option 2: Telegram Bot (requires bot token)

2. **Choose Template**
   - Option 1: YouTube Live (requires video ID)
   - Option 2: Google Meet
   - Option 3: Sensitive Video (Age Verification)

3. **Select Tunnel Service**
   - Option 1: Ngrok (requires authtoken)
   - Option 2: Cloudflare Tunnel (auto-downloads)
   - Option 3: Serveo.net (requires SSH)

### Example Workflow

```bash
# Start Trackify
./trackify.sh

# Follow prompts:
# 1. Choose notification: 2 (Telegram)
# 2. Enter Telegram bot token: YOUR_BOT_TOKEN
# 3. Enter Telegram username: @yourusername
# 4. Choose template: 1 (YouTube Live)
# 5. Enter YouTube video ID: dQw4w9WgXcQ
# 6. Choose tunnel: 2 (Cloudflare)
```

### Windows Usage

```bash
# Using Git Bash
bash trackify.sh

# Or using WSL
wsl bash trackify.sh
```

---

## ⚙️ Configuration

### Telegram Bot Setup

1. **Create a Bot**:
   - Open Telegram and search for `@BotFather`
   - Send `/newbot` and follow instructions
   - Save your bot token

2. **Get Your Chat ID**:
   - Send a message to your bot
   - Visit: `https://api.telegram.org/bot<YOUR_TOKEN>/getUpdates`
   - Find your chat ID in the response
   - Or use your username (e.g., `@yourusername`)

3. **Configure in Trackify**:
   - Select notification option 2
   - Enter bot token when prompted
   - Enter your username or chat ID

### Ngrok Setup

1. **Get Authtoken**:
   - Sign up at [ngrok.com](https://ngrok.com/)
   - Copy your authtoken from dashboard

2. **Configure**:
   - Trackify will prompt for authtoken on first use
   - Or manually: `./ngrok authtoken YOUR_TOKEN`

### Cloudflare Tunnel

- No configuration needed!
- Cloudflared binary auto-downloads on first use
- Works out of the box

### Serveo Setup

- Requires SSH client
- No additional configuration needed
- May be slower than other options

---

## 🌐 Tunnel Services

### 1. Ngrok
- ✅ Most reliable
- ✅ Custom domains available
- ❌ Requires account and authtoken
- 📝 Get token: [ngrok.com](https://ngrok.com/)

### 2. Cloudflare Tunnel
- ✅ No account needed
- ✅ Fast and reliable
- ✅ Auto-downloads binary
- ⚠️ Links expire after inactivity

### 3. Serveo.net
- ✅ No installation needed
- ✅ Uses SSH
- ⚠️ Can be slower
- ⚠️ Less reliable

---

## 🎨 Templates

### 1. YouTube Live Template
- Mimics YouTube live stream page
- Requires YouTube video watch ID
- Example: `dQw4w9WgXcQ` from `youtube.com/watch?v=dQw4w9WgXcQ`

### 2. Google Meet Template
- Mimics Google Meet waiting room
- No additional configuration needed

### 3. Sensitive Video Template
- Age verification page
- Requires user interaction
- Higher engagement rate

---

## 🔔 Notifications

### Local File Storage (Default)
- Saves IPs to `saved.ip.txt`
- Camera captures saved in date folders (YYYY-MM-DD)
- Check `ip.txt` for latest capture

### Telegram Bot
- Real-time notifications
- IP address and User-Agent sent immediately
- Camera photos sent automatically
- Formatted with emojis and timestamps

---

## 🔧 Troubleshooting

### Issue: PHP not found
```bash
# Linux
sudo apt install php

# macOS
brew install php

# Windows
# Download from php.net or use Laragon/XAMPP
```

### Issue: Ngrok link not generating
- ✅ Verify authtoken is correct
- ✅ Check internet connection
- ✅ Kill existing ngrok processes: `killall ngrok`
- ✅ On Android: Turn on mobile hotspot

### Issue: Cloudflare tunnel fails
- ✅ Check internet connection
- ✅ Verify port 3333 is not in use
- ✅ Try restarting the script

### Issue: Telegram bot not working
- ✅ Verify bot token is correct
- ✅ Send `/start` to your bot first
- ✅ Check username/chat ID format
- ✅ Ensure bot has permission to send messages

### Issue: SSH/Serveo not working
- ✅ Install SSH client: `apt install openssh-client`
- ✅ Check firewall settings
- ✅ Try different tunnel service

### Issue: Permission denied
```bash
chmod +x trackify.sh
```

---

## 📁 File Structure

```
trackify/
├── trackify.sh          # Main script
├── template.php         # PHP template
├── ip.php              # IP capture script
├── post.php            # Camera capture handler
├── Youtube.html        # YouTube template
├── Gmeet.html          # Google Meet template
├── Sensitive.html      # Age verification template
├── index.php           # Generated redirect page
├── index2.html         # Generated phishing page
├── saved.ip.txt        # Saved IP addresses
└── README.md           # This file
```

---

## ⚠️ Disclaimer

**IMPORTANT LEGAL NOTICE**

This tool is provided for **educational and authorized testing purposes only**. 

- ⚠️ **Unauthorized use** of this tool to track or phish individuals without explicit consent is **illegal** and **unethical**
- ⚠️ Only use this tool on systems you own or have explicit written permission to test
- ⚠️ The developers and contributors are **not responsible** for any misuse of this tool
- ⚠️ Use responsibly and in accordance with local laws and regulations
- ⚠️ Always obtain proper authorization before conducting security testing

**By using this tool, you agree to:**
- Use it only for legitimate security testing
- Obtain proper authorization before use
- Comply with all applicable laws
- Not use it for malicious purposes

---

## 📝 License

This project is licensed under the MIT License - see the LICENSE file for details.

---

## 🙏 Credits

- **Developer**: 0Cod3
- **Powered by**: TechChip
- **Original Inspiration**: [thelinuxchoice](https://github.com/thelinuxchoice/)
- **Tunnel Services**: Ngrok, Cloudflare, Serveo

---

## 📞 Support

For issues, questions, or contributions:
- Open an issue on GitHub
- Check existing issues for solutions
- Review troubleshooting section above

---

<div align="center">

**Made with ❤️ by the Trackify Team**

⭐ Star this repo if you find it useful!

</div>
