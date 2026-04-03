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
    // Where cloudflared sends traffic — must be THIS app, not Laragon’s default site (C:/laragon/www).
    // Recommended: run ../serve.cmd or: php -S 127.0.0.1:8000 from the Trackify project root, then:
    'tunnel_origin' => 'http://127.0.0.1:8000',
    // Alternative (only if opening http://trackify.test in the browser shows Trackify, not Laragon home):
    // 'tunnel_origin' => 'http://trackify.test',
];
