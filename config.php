<?php
declare(strict_types=1);

return [
    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'trackify_auth',
        'user' => 'root',
        'pass' => '',
        'charset' => 'utf8mb4',
    ],
    // MUST point at THIS project’s document root (not C:/laragon/www alone — that shows Laragon’s default page).
    // Easiest: run serve.cmd / serve.sh then use port 8000 below.
    // Or use your vhost if cloudflared sends the correct Host header, e.g. http://trackify.test
    'tunnel_origin' => 'http://127.0.0.1:8000',
    'serpapi_key' => 'ffb1302d139b78d362036e24bf75fd329595e5a9ff795ed193d28fe7676c321e',
];
