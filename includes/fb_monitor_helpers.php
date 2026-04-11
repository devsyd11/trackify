<?php
declare(strict_types=1);

/**
 * Facebook Monitor — shared helpers.
 * Required by api.php and fb_checker_cron.php.
 * Assumes trackify_capture.php is already loaded (provides trackify_pdo, trackify_send_telegram_html, trackify_send_telegram_photo_url).
 */

// ---------------------------------------------------------------------------
// Config helpers
// ---------------------------------------------------------------------------

function fb_monitor_user_dir(int $uid): string
{
    $dir = __DIR__ . '/../data/fb_checker/u' . $uid;
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    return $dir;
}

function fb_monitor_read_config(int $uid): array
{
    $defaults = ['cookies' => '', 'check_interval_minutes' => 15, 'updated_at' => ''];
    $path = fb_monitor_user_dir($uid) . '/config.json';
    if (!is_readable($path)) {
        return $defaults;
    }
    $raw = file_get_contents($path);
    $j = (is_string($raw) && $raw !== '') ? json_decode($raw, true) : null;
    return is_array($j) ? array_merge($defaults, $j) : $defaults;
}

function fb_monitor_write_config(int $uid, array $cfg): bool
{
    $dir  = fb_monitor_user_dir($uid);
    $file = $dir . '/config.json';
    $fp   = @fopen($file, 'c+');
    if ($fp === false) {
        return false;
    }
    if (!flock($fp, LOCK_EX)) {
        fclose($fp);
        return false;
    }
    rewind($fp);
    ftruncate($fp, 0);
    fwrite($fp, (string) json_encode($cfg, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
    return true;
}

/**
 * Append-only activity log for the FB Monitor dashboard (cron + manual checks).
 *
 * @return list<array{at: string, source: string, status: string, detail: string, label: string, profile_url: string}>
 */
function fb_monitor_read_activity_log(int $uid): array
{
    $path = fb_monitor_user_dir($uid) . '/activity_log.json';
    if (!is_readable($path)) {
        return [];
    }
    $raw = file_get_contents($path);
    if ($raw === false || $raw === '') {
        return [];
    }
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        return [];
    }
    $out = [];
    foreach ($data as $row) {
        if (!is_array($row)) {
            continue;
        }
        $out[] = [
            'at'          => (string) ($row['at'] ?? ''),
            'source'      => (string) ($row['source'] ?? ''),
            'status'      => (string) ($row['status'] ?? 'unknown'),
            'detail'      => (string) ($row['detail'] ?? ''),
            'label'       => (string) ($row['label'] ?? ''),
            'profile_url' => (string) ($row['profile_url'] ?? ''),
        ];
    }

    return $out;
}

function fb_monitor_urls_match(string $a, string $b): bool
{
    $a = trim($a);
    $b = trim($b);
    if ($a === '' || $b === '') {
        return false;
    }
    if ($a === $b) {
        return true;
    }

    return rtrim($a, '/') === rtrim($b, '/');
}

/**
 * True if two Facebook profile/page URLs refer to the same destination
 * (after host/path normalization used elsewhere in FB Tools).
 */
function fb_monitor_profile_urls_equivalent(string $a, string $b): bool
{
    $a = trim($a);
    $b = trim($b);
    if ($a === '' || $b === '') {
        return false;
    }
    if (fb_monitor_urls_match($a, $b)) {
        return true;
    }
    $ca = rtrim(fb_url_to_mbasic($a), '/');
    $cb = rtrim(fb_url_to_mbasic($b), '/');

    return strcasecmp($ca, $cb) === 0;
}

/** Escape special LIKE wildcards in user-supplied search strings. */
function fb_monitor_sql_like_escape(string $s): string
{
    return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $s);
}

function fb_monitor_append_activity(
    int $uid,
    string $source,
    string $status,
    string $detail,
    string $label = '',
    string $profileUrl = ''
): void {
    $path = fb_monitor_user_dir($uid) . '/activity_log.json';
    $entry = [
        'at'          => gmdate('Y-m-d\TH:i:s\Z'),
        'source'      => $source,
        'status'      => $status,
        'detail'      => $detail,
        'label'       => $label,
        'profile_url' => $profileUrl,
    ];

    $fp = @fopen($path, 'c+');
    if ($fp === false) {
        return;
    }
    if (!flock($fp, LOCK_EX)) {
        fclose($fp);
        return;
    }
    rewind($fp);
    $raw = stream_get_contents($fp);
    $list = [];
    if (is_string($raw) && $raw !== '') {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            $list = $decoded;
        }
    }
    array_unshift($list, $entry);
    if (count($list) > 120) {
        $list = array_slice($list, 0, 120);
    }
    rewind($fp);
    ftruncate($fp, 0);
    fwrite($fp, (string) json_encode($list, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
}

// ---------------------------------------------------------------------------
// Cookie format normalizer
//
// Accepts either:
//   (a) flat string:  "c_user=123; xs=abc; datr=xyz"
//   (b) JSON array:   [{"name":"c_user","value":"123"}, ...]
//       (format exported by browser extensions like EditThisCookie / Cookie-Editor)
// Returns a flat "name=value; name=value" string for CURLOPT_COOKIE.
// ---------------------------------------------------------------------------

function fb_cookies_normalize(string $raw): string
{
    $raw = trim($raw);
    if ($raw === '') {
        return '';
    }

    // Try to decode as JSON array
    if ($raw[0] === '[') {
        $arr = json_decode($raw, true);
        if (is_array($arr)) {
            $parts = [];
            foreach ($arr as $item) {
                if (!is_array($item)) continue;
                $name  = (string) ($item['name']  ?? '');
                $value = (string) ($item['value'] ?? '');
                if ($name !== '') {
                    $parts[] = $name . '=' . $value;
                }
            }
            return implode('; ', $parts);
        }
    }

    // Already a flat cookie string — return as-is
    return $raw;
}

// ---------------------------------------------------------------------------
// Core profile check
//
// Returns an array:
//   [
//     'status'    => 'active' | 'inactive' | 'unavailable' | 'unknown',
//     'detail'    => human-readable explanation,
//     'http_code' => int,
//   ]
// ---------------------------------------------------------------------------

/**
 * Convert a www.facebook.com or fb.com URL to mbasic.facebook.com.
 * mbasic returns simple HTML, avoids most bot-detection, and has clear
 * "Content Not Available" messages.
 */
function fb_url_to_mbasic(string $url): string
{
    // Normalize common Facebook hosts to mbasic for simpler HTML + fewer bot walls.
    $url = trim($url);
    $url = (string) preg_replace('#^https?://(www\.|m\.|mbasic\.)?facebook\.com#i', 'https://mbasic.facebook.com', $url);
    $url = (string) preg_replace('#^https?://(www\.)?fb\.com#i', 'https://mbasic.facebook.com', $url);

    return $url;
}

/**
 * Extract an identifier we can reliably match in mbasic HTML.
 *
 * Supports:
 * - https://facebook.com/username
 * - https://facebook.com/profile.php?id=123
 *
 * @return array{type: 'username'|'id'|'none', value: string}
 */
function fb_profile_identifier_from_url(string $url): array
{
    $path = (string) parse_url($url, PHP_URL_PATH);
    $path = ltrim($path, '/');
    $pathLower = strtolower($path);

    // Numeric profile id URL style
    if ($pathLower === 'profile.php') {
        $q = (string) parse_url($url, PHP_URL_QUERY);
        parse_str($q, $qs);
        $id = isset($qs['id']) ? trim((string) $qs['id']) : '';
        if ($id !== '' && ctype_digit($id)) {
            return ['type' => 'id', 'value' => $id];
        }
    }

    // Username / vanity URL style
    $first = strtolower(strtok($path, '/') ?: $path);
    if ($first !== '' && $first !== 'pages' && $first !== 'people') {
        return ['type' => 'username', 'value' => $first];
    }

    return ['type' => 'none', 'value' => ''];
}

function fb_monitor_node_binary(): string
{
    $cfgPath = __DIR__ . '/../config.php';
    if (is_readable($cfgPath)) {
        $cfg = require $cfgPath;
        $n = trim((string) ($cfg['fb_monitor_node'] ?? ''));
        if ($n !== '') {
            return $n;
        }
    }

    return 'node';
}

function fb_monitor_playwright_should_run(): bool
{
    $cfgPath = __DIR__ . '/../config.php';
    if (is_readable($cfgPath)) {
        $cfg = require $cfgPath;
        if (array_key_exists('fb_monitor_use_playwright', $cfg) && !$cfg['fb_monitor_use_playwright']) {
            return false;
        }
    }
    $script = __DIR__ . '/../scripts/fb_monitor_playwright.mjs';
    $pwPkg  = __DIR__ . '/../node_modules/playwright/package.json';

    return is_file($script) && is_file($pwPkg);
}

/**
 * Run the Playwright worker (stdin JSON → stdout JSON). Returns null to fall back to HTTP.
 *
 * @return array{html: string, http_code: int, effective_url: string}|null
 */
function fb_monitor_try_playwright(string $url, string $cookieString): ?array
{
    if (!fb_monitor_playwright_should_run()) {
        return null;
    }
    $node = fb_monitor_node_binary();
    $script = realpath(__DIR__ . '/../scripts/fb_monitor_playwright.mjs');
    if ($script === false || !is_file($script)) {
        return null;
    }
    $root = dirname(__DIR__);
    $payload = json_encode(['profileUrl' => $url, 'cookies' => $cookieString], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($payload === false) {
        return null;
    }
    $cmd = escapeshellarg($node) . ' ' . escapeshellarg($script);
    $des = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
    $proc = @proc_open($cmd, $des, $pipes, $root);
    if (!is_resource($proc)) {
        return null;
    }
    fwrite($pipes[0], $payload);
    fclose($pipes[0]);
    $stdout = (string) stream_get_contents($pipes[1]);
    $stderr = (string) stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    proc_close($proc);
    if ($stdout === '' && $stderr !== '') {
        return null;
    }

    $decoded = json_decode($stdout, true);
    if (!is_array($decoded) || empty($decoded['ok'])) {
        return null;
    }

    return [
        'html'          => (string) ($decoded['html'] ?? ''),
        'visible_text'  => (string) ($decoded['visible_text'] ?? ''),
        'http_code'     => (int) ($decoded['http_code'] ?? 0),
        'effective_url' => (string) ($decoded['effective_url'] ?? ''),
    ];
}

/**
 * Classify profile from fetched HTML (HTTP or Playwright).
 *
 * @return array{status: string, detail: string, http_code: int}
 */
function fb_classify_fetched_profile(string $html, string $finalUrl, string $originalUrl, int $code, string $curlErr = '', string $visibleText = ''): array
{
    if ($code === 0 && $curlErr !== '') {
        return ['status' => 'unknown', 'detail' => 'Connection failed: ' . ($curlErr ?: 'unknown error'), 'http_code' => 0];
    }
    if ($code === 0) {
        return ['status' => 'unknown', 'detail' => 'Connection failed', 'http_code' => 0];
    }
    if ($code >= 500) {
        return ['status' => 'unknown', 'detail' => "Server error HTTP {$code}", 'http_code' => $code];
    }
    if ($code >= 400) {
        if (in_array($code, [404, 410], true)) {
            return ['status' => 'unavailable', 'detail' => "Facebook returned HTTP {$code} (not found)", 'http_code' => $code];
        }

        return ['status' => 'unknown', 'detail' => "Facebook returned HTTP {$code}", 'http_code' => $code];
    }

    // Include visible text (Playwright) so "content not available" is found even when it is client-rendered.
    $scan = $html . "\n" . $visibleText;
    $lower = strtolower($scan);

    // Redirects/landings that indicate auth issues (cookies invalid / checkpoint)
    $finalLower = strtolower($finalUrl);
    if (strpos($finalLower, '/login.php') !== false || strpos($finalLower, '/checkpoint/') !== false) {
        return ['status' => 'unknown', 'detail' => 'Redirected to login/checkpoint — cookies expired or restricted', 'http_code' => $code];
    }

    // Login page returned — cookies expired
    if (
        (strpos($lower, 'name="email"') !== false || strpos($lower, 'id="email"') !== false) &&
        (strpos($lower, 'name="pass"')  !== false || strpos($lower, 'id="pass"')  !== false)
    ) {
        return ['status' => 'unknown', 'detail' => 'Login page returned — cookies expired or invalid', 'http_code' => $code];
    }

    $normalised = str_replace(
        ["\xe2\x80\x99", '&#8217;', '&#x2019;', '&rsquo;', '\u2019'],
        "'",
        $lower
    );

    if (strpos($normalised, 'sorry, something went wrong') !== false) {
        return ['status' => 'unknown', 'detail' => 'Facebook returned a generic error page — try again later or refresh cookies', 'http_code' => $code];
    }

    $unavailablePhrases = [
        "this content isn't available right now",
        "this content isn't available",
        "content not available",
        "page not found",
        "sorry, this page isn't available",
        "the page you requested cannot be displayed",
        "the link you followed may be broken",
        // Full-screen “unavailable” copy (often only in visible text, not raw HTML)
        "when this happens, it's usually because",
        "only shared it with a small group of people",
        "changed who can see it or it's been deleted",
    ];
    foreach ($unavailablePhrases as $p) {
        if (strpos($normalised, $p) !== false) {
            return ['status' => 'unavailable', 'detail' => 'Facebook returned “content not available” (private/restricted/deleted)', 'http_code' => $code];
        }
    }

    if (
        strpos($lower, 'not available on this browser') !== false ||
        strpos($lower, 'hindi available ang facebook sa browser') !== false ||
        strpos($lower, 'hindi sinusuportahan ang browser na ito') !== false ||
        strpos($lower, 'gamitin ang facebook app') !== false ||
        strpos($lower, 'gumamit ng sinusuportahang browser') !== false ||
        strpos($lower, 'use the facebook app') !== false ||
        strpos($lower, 'use a supported browser') !== false
    ) {
        return ['status' => 'unknown', 'detail' => 'Facebook returned a browser/app compatibility wall', 'http_code' => $code];
    }

    $ident = fb_profile_identifier_from_url($originalUrl);
    $finalIdent = fb_profile_identifier_from_url($finalUrl);

    if ($ident['type'] === 'none' && $finalIdent['type'] === 'none') {
        return ['status' => 'unknown', 'detail' => 'Could not extract a stable profile identifier from URL', 'http_code' => $code];
    }

    $linkCount = 0;
    $needle = '';
    $want = $ident['type'] !== 'none' ? $ident : $finalIdent;
    if ($want['type'] === 'username') {
        $needle = '/' . strtolower($want['value']);
        $linkCount = substr_count($lower, $needle);
    } elseif ($want['type'] === 'id') {
        $needle = 'profile.php?id=' . $want['value'];
        $linkCount = substr_count($lower, $needle);
    }

    $finalLowerUrl = strtolower($finalUrl);
    $onProfileAfterRedirect = ($needle !== '' && strpos($finalLowerUrl, strtolower($needle)) !== false);

    // Do NOT treat URL path alone as “active”: /username URLs also serve “content isn’t available” with HTTP 200.
    // Require at least one content match for the identifier in HTML + visible text.
    if ($linkCount >= 1) {
        $detail = 'Profile content matches the monitored identifier (' . $linkCount . '× "' . $needle . '") — account appears active';
        if ($onProfileAfterRedirect) {
            $detail = 'Profile page loaded and content matches the monitored profile — account appears active';
        }

        return [
            'status'    => 'active',
            'detail'    => $detail,
            'http_code' => $code,
        ];
    }

    // Inconclusive: page loaded but identifier not found in HTML/visible text (SPA, layout changes, etc.).
    // Treat as active so the UI does not show a false “inactive” when we simply could not parse proof either way.
    return [
        'status'    => 'active',
        'detail'    => 'Active — page loaded; identifier not detected in text (inconclusive parse; counted as active)',
        'http_code' => $code,
    ];
}

/**
 * Normalize image URL for Telegram sendPhoto (HTTPS only; upgrade http → https).
 */
function fb_monitor_normalize_telegram_photo_url(string $raw): string
{
    $u = trim(html_entity_decode($raw, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    if ($u === '' || strlen($u) > 2048) {
        return '';
    }
    if (preg_match('#^http://#i', $u)) {
        $u = 'https://' . preg_replace('#^http://#i', '', $u);
    }
    if (!preg_match('#^https://#i', $u)) {
        return '';
    }

    return $u;
}

/**
 * Fallback: first plausible Facebook CDN image in HTML (mbasic often has profile thumbs in img src).
 */
function fb_monitor_extract_fbcdn_img_fallback(string $html): string
{
    if (!preg_match_all('#https://[a-z0-9.-]+\.fbcdn\.net/[^\s"\'<>]+#i', $html, $matches)) {
        return '';
    }
    $best = '';
    foreach ($matches[0] as $raw) {
        $u = fb_monitor_normalize_telegram_photo_url($raw);
        if ($u === '') {
            continue;
        }
        if (preg_match('#/(s\d+x\d+/|/v/t\d+\.\d+-\d+/)#', $u) || preg_match('#\d+x\d+#', $u)) {
            return $u;
        }
        if ($best === '') {
            $best = $u;
        }
    }

    return $best;
}

/**
 * Best-effort profile/page image URL from fetched Facebook HTML (og:image, then CDN img).
 */
function fb_monitor_extract_page_image(string $html): string
{
    if ($html === '') {
        return '';
    }
    foreach (['og:image:secure_url', 'og:image', 'twitter:image'] as $prop) {
        $q = preg_quote($prop, '/');
        if (preg_match('/<meta\s[^>]*property\s*=\s*["\']' . $q . '["\'][^>]*\bcontent\s*=\s*["\']([^"\']+)["\']/is', $html, $m)) {
            $u = fb_monitor_normalize_telegram_photo_url($m[1]);
            if ($u !== '') {
                return $u;
            }
        }
        if (preg_match('/<meta\s[^>]*\bcontent\s*=\s*["\']([^"\']+)["\'][^>]*property\s*=\s*["\']' . $q . '["\']/is', $html, $m)) {
            $u = fb_monitor_normalize_telegram_photo_url($m[1]);
            if ($u !== '') {
                return $u;
            }
        }
    }
    foreach (['og:image', 'twitter:image'] as $name) {
        $q = preg_quote($name, '/');
        if (preg_match('/<meta\s[^>]*name\s*=\s*["\']' . $q . '["\'][^>]*\bcontent\s*=\s*["\']([^"\']+)["\']/is', $html, $m)) {
            $u = fb_monitor_normalize_telegram_photo_url($m[1]);
            if ($u !== '') {
                return $u;
            }
        }
    }
    if (preg_match('/<link\s[^>]*rel\s*=\s*["\']image_src["\'][^>]*\bhref\s*=\s*["\']([^"\']+)["\']/is', $html, $m)) {
        $u = fb_monitor_normalize_telegram_photo_url($m[1]);
        if ($u !== '') {
            return $u;
        }
    }

    return fb_monitor_extract_fbcdn_img_fallback($html);
}

function fb_check_profile_url(string $url, string $cookieString): array
{
    if (fb_cookies_normalize($cookieString) === '') {
        return ['status' => 'unknown', 'detail' => 'Cookie string is empty after normalization', 'http_code' => 0, 'preview_image' => ''];
    }

    $pw = fb_monitor_try_playwright($url, $cookieString);
    if ($pw !== null) {
        $out = fb_classify_fetched_profile(
            $pw['html'],
            $pw['effective_url'],
            $url,
            $pw['http_code'],
            '',
            $pw['visible_text'] ?? ''
        );
        $out['preview_image'] = fb_monitor_extract_page_image($pw['html']);

        return $out;
    }

    if (!function_exists('curl_init')) {
        return [
            'status'    => 'unknown',
            'detail'    => 'Playwright not available and cURL missing. Install Node, run: npm ci && npx playwright install chromium, or enable PHP cURL.',
            'http_code' => 0,
            'preview_image' => '',
        ];
    }

    $fetch = fb_monitor_fetch_facebook_html($url, $cookieString);
    $out = fb_classify_fetched_profile(
        $fetch['html'],
        $fetch['effective_url'],
        $url,
        $fetch['http_code'],
        $fetch['curl_error']
    );
    $out['preview_image'] = fb_monitor_extract_page_image($fetch['html']);

    return $out;
}

/**
 * Fetch Facebook profile HTML in the most reliable way we can without a real browser.
 *
 * Strategy:
 * - Try mbasic (simple HTML) with a mobile UA.
 * - If we detect the app/unsupported-browser wall, retry via www with a desktop UA.
 *
 * @return array{html: string, http_code: int, effective_url: string, curl_error: string, used_url: string, attempted_urls: list<string>}
 */
function fb_monitor_fetch_facebook_html(string $url, string $cookieString): array
{
    $flatCookies = fb_cookies_normalize($cookieString);
    if ($flatCookies === '') {
        return [
            'html' => '',
            'http_code' => 0,
            'effective_url' => '',
            'curl_error' => 'Cookie string is empty after normalization',
            'used_url' => '',
            'attempted_urls' => [],
        ];
    }

    $mbasicUrl = fb_url_to_mbasic($url);
    $mUrl = (string) preg_replace(
        '#^https?://(www\.|m\.|mbasic\.)?facebook\.com#i',
        'https://m.facebook.com',
        trim($url)
    );
    $mUrl = (string) preg_replace('#^https?://(www\.)?fb\.com#i', 'https://m.facebook.com', $mUrl);
    $wwwUrl = (string) preg_replace(
        '#^https?://(www\.|m\.|mbasic\.)?facebook\.com#i',
        'https://www.facebook.com',
        trim($url)
    );
    $wwwUrl = (string) preg_replace('#^https?://(www\.)?fb\.com#i', 'https://www.facebook.com', $wwwUrl);

    // Locate CA bundle (needed on Windows / Laragon)
    $caBundle = '';
    foreach ([
        'C:/laragon/etc/ssl/cacert.pem',
        'C:/laragon/bin/php/php-current/extras/ssl/cacert.pem',
        php_ini_loaded_file() ? dirname((string) php_ini_loaded_file()) . '/extras/ssl/cacert.pem' : '',
    ] as $c) {
        if ($c !== '' && is_readable($c)) { $caBundle = $c; break; }
    }

    $uaMobile = 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Mobile/15E148 Safari/604.1';
    $uaDesktop = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36';

    $headersMobile = [
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'Accept-Language: en-US,en;q=0.9',
        'Upgrade-Insecure-Requests: 1',
    ];
    $headersDesktop = [
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'Accept-Language: en-US,en;q=0.9',
        'Cache-Control: no-cache',
        'Pragma: no-cache',
        'Upgrade-Insecure-Requests: 1',
    ];

    /**
     * @return array{html: string, code: int, final_url: string, curl_err: string}
     */
    $fetchOnce = function (string $u, string $ua, array $headers) use ($flatCookies, $caBundle): array {
        $ch = curl_init();
        $opts = [
            CURLOPT_URL            => $u,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_TIMEOUT        => 25,
            CURLOPT_COOKIE         => $flatCookies,
            CURLOPT_USERAGENT      => $ua,
            CURLOPT_ENCODING       => '',
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_SSL_VERIFYPEER => $caBundle !== '',
            CURLOPT_SSL_VERIFYHOST => $caBundle !== '' ? 2 : 0,
        ];
        if ($caBundle !== '') {
            $opts[CURLOPT_CAINFO] = $caBundle;
        }
        curl_setopt_array($ch, $opts);
        $html     = (string) curl_exec($ch);
        $code     = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $finalUrl = (string) curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        return ['html' => $html, 'code' => $code, 'final_url' => $finalUrl, 'curl_err' => $curlErr];
    };

    $attempted = [$mbasicUrl];
    $resp = $fetchOnce($mbasicUrl, $uaMobile, $headersMobile);
    $html = $resp['html'];
    $code = $resp['code'];
    $finalUrl = $resp['final_url'];
    $curlErr = $resp['curl_err'];
    $usedUrl = $mbasicUrl;

    $lower = strtolower($html);
    $isCompatWall = (
        strpos($lower, 'not available on this browser') !== false ||
        strpos($lower, 'hindi available ang facebook sa browser') !== false ||
        strpos($lower, 'hindi sinusuportahan ang browser na ito') !== false ||
        strpos($lower, 'gamitin ang facebook app') !== false ||
        strpos($lower, 'gumamit ng sinusuportahang browser') !== false ||
        strpos($lower, 'use the facebook app') !== false ||
        strpos($lower, 'use a supported browser') !== false
    );

    if ($isCompatWall) {
        // Some regions/accounts show the "use the app" wall on mbasic, but m works.
        $attempted[] = $mUrl;
        $retryM = $fetchOnce($mUrl, $uaDesktop, $headersDesktop);
        $html = $retryM['html'];
        $code = $retryM['code'];
        $finalUrl = $retryM['final_url'];
        $curlErr = $retryM['curl_err'];
        $usedUrl = $mUrl;

        $lower = strtolower($html);
        $stillCompatWall = (
            strpos($lower, 'not available on this browser') !== false ||
            strpos($lower, 'hindi available ang facebook sa browser') !== false ||
            strpos($lower, 'hindi sinusuportahan ang browser na ito') !== false ||
            strpos($lower, 'gamitin ang facebook app') !== false ||
            strpos($lower, 'gumamit ng sinusuportahang browser') !== false ||
            strpos($lower, 'use the facebook app') !== false ||
            strpos($lower, 'use a supported browser') !== false
        );

        // If m still walls (or errors), try www as last resort.
        if ($stillCompatWall || $code >= 400 || $code === 0) {
            $attempted[] = $wwwUrl;
            $retryW = $fetchOnce($wwwUrl, $uaDesktop, $headersDesktop);
            $html = $retryW['html'];
            $code = $retryW['code'];
            $finalUrl = $retryW['final_url'];
            $curlErr = $retryW['curl_err'];
            $usedUrl = $wwwUrl;
        }
    }

    return [
        'html' => $html,
        'http_code' => (int) $code,
        'effective_url' => (string) $finalUrl,
        'curl_error' => (string) $curlErr,
        'used_url' => (string) $usedUrl,
        'attempted_urls' => $attempted,
    ];
}

// ---------------------------------------------------------------------------
// Telegram alert
// ---------------------------------------------------------------------------

function fb_monitor_send_active_alert(string $url, string $label, string $previewImageUrl = ''): void
{
    $esc = static fn(string $s): string => htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $u = trim($url);
    if ($u === '') {
        return;
    }
    $when = $esc(gmdate('Y-m-d H:i:s') . ' UTC');
    $href = $esc($u);

    $lines = [
        '✅ <b>Account Checker · profile accessible</b>',
        '',
        'A monitored Facebook profile or page is loading again (no longer restricted or unavailable).',
        '',
    ];
    if ($label !== '') {
        $lines[] = '<b>Label</b>';
        $lines[] = $esc($label);
        $lines[] = '';
    }
    $lines[] = '<b>Profile</b>';
    $lines[] = '<a href="' . $href . '">' . $href . '</a>';
    $lines[] = '';
    $lines[] = '<b>Detected at</b>';
    $lines[] = '<code>' . $when . '</code>';
    $lines[] = '';
    $lines[] = '<i>Trackify · Facebook Tools</i>';

    $html = implode("\n", $lines);
    if (strlen($html) > 4000) {
        $html = trackify_trunc_utf8($html, 3990) . '…';
    }

    $img = fb_monitor_normalize_telegram_photo_url($previewImageUrl);
    if ($img !== '' && trackify_send_telegram_photo_url($img, $html)) {
        return;
    }

    trackify_send_telegram_html($html, false);
}
