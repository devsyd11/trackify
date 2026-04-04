<?php
declare(strict_types=1);

return [
    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'trackify_auth',
        'user' => 'trackify_user',
        'pass' => '@Silenthacker123',
        'charset' => 'utf8mb4',
    ],
    // Dashboard access control (IP whitelist)
    // - Set ip_whitelist_enabled to true to restrict access.
    // - Add allowed IPs (IPv4/IPv6) and/or CIDR ranges (e.g. "203.0.113.0/24").
    // - If you are behind a proxy (Cloudflare, Nginx, etc), add the proxy IP(s) to trusted_proxies
    //   so we can safely use CF-Connecting-IP / X-Forwarded-For. Otherwise, we only trust REMOTE_ADDR.
    'ip_whitelist_enabled' => true,
    'ip_whitelist' => [
        '127.0.0.1',
	'124.217.122.248',
        '::1',
    ],
    'trusted_proxies' => [
        // '127.0.0.1',
        // '::1',
    ],
    'ip_block_message' => 'Access is blocked. Please contact the owner of this site to have access.',
    // MUST point at THIS project’s document root (not C:/laragon/www alone — that shows Laragon’s default page).
    // Easiest: run serve.cmd / serve.sh then use port 8000 below.
    // Or use your vhost if cloudflared sends the correct Host header, e.g. http://trackify.test
    'tunnel_origin' => 'http://127.0.0.1:8000',
    'serpapi_key' => 'ffb1302d139b78d362036e24bf75fd329595e5a9ff795ed193d28fe7676c321e',
    // IP Lookup (https://findip.net/) — API base + token from your FindIP dashboard
    'findip_api_base' => 'https://api.findip.net',
    'findip_token' => '12e7fe0b019e408a81e9868009940aa2',
];
