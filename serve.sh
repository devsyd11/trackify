#!/usr/bin/env bash
cd "$(dirname "$0")"
echo ""
echo "  Trackify local server: http://127.0.0.1:8000"
echo "  Keep this terminal open while using Generate Link / Cloudflare tunnel."
echo "  config.php (root) tunnel_origin should be http://127.0.0.1:8000"
echo ""
exec php -S 127.0.0.1:8000
