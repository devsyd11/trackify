@echo off
cd /d "%~dp0"
echo.
echo  Trackify local server: http://127.0.0.1:8000
echo  Keep this window open while using Generate Link / Cloudflare tunnel.
echo  config.php (root) tunnel_origin should be http://127.0.0.1:8000
echo.
php -S 127.0.0.1:8000
