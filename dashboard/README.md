# Trackify Web Dashboard

A web-based interface for Trackify that generates tracker links and displays captures in a terminal-style UI.

## Quick Start

1. **Start the PHP server** (from the Trackify project root):
   ```bash
   php -S localhost:3333
   ```

2. **Open the dashboard** in your browser:
   ```
   http://localhost:3333/dashboard/
   ```

3. **Install Cloudflare Tunnel** (required for link generation):
   - Download from: https://github.com/cloudflare/cloudflared/releases
   - Place `cloudflared` (or `cloudflared.exe` on Windows) in the Trackify folder, or add it to your PATH

## Features

- **Generate Tracker Link** – Select a template (Google Meet, Instagram, Netflix, etc.) and generate a Cloudflare tunnel link
- **Terminal** – Hack-style terminal UI showing real-time capture events
- **Recent Captures** – Sidebar with IP addresses and locations
- **Telegram** – Optional Telegram bot notifications

## Usage

1. Select a template from the dropdown
2. (For YouTube) Enter a video ID
3. Optionally enable Telegram and enter bot token + chat ID
4. Click **Generate Link**
5. Wait for the tunnel to start (~10–15 seconds)
6. Copy the link and share it
7. Watch the terminal for new captures

## Requirements

- PHP 7.4+
- Cloudflared (Cloudflare Tunnel)
- Modern web browser
