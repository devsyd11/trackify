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
    $defaults = ['check_interval_minutes' => 15, 'updated_at' => ''];
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

/**
 * Remove activity log entries tied to one or more profile URLs (e.g. after deleting monitors).
 *
 * @param list<string> $profileUrls
 */
function fb_monitor_purge_activity_for_profile_urls(int $uid, array $profileUrls): void
{
    $want = [];
    foreach ($profileUrls as $u) {
        $u = trim((string) $u);
        if ($u !== '') {
            $want[$u] = true;
        }
    }
    if ($want === []) {
        return;
    }
    $keys = array_keys($want);

    $path = fb_monitor_user_dir($uid) . '/activity_log.json';
    $fp   = @fopen($path, 'c+');
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

    $kept = [];
    foreach ($list as $entry) {
        if (!is_array($entry)) {
            continue;
        }
        $pu = trim((string) ($entry['profile_url'] ?? ''));
        $drop = false;
        if ($pu !== '') {
            foreach ($keys as $k) {
                if (fb_monitor_urls_match($pu, $k)) {
                    $drop = true;
                    break;
                }
            }
        }
        if (!$drop) {
            $kept[] = $entry;
        }
    }

    rewind($fp);
    ftruncate($fp, 0);
    fwrite($fp, (string) json_encode($kept, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
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
 * - https://www.facebook.com/people/Display-Name/123456789012345/
 * - https://www.facebook.com/pages/PageName/123…
 *
 * @return array{type: 'username'|'id'|'none', value: string}
 */
function fb_profile_identifier_from_url(string $url): array
{
    $path = (string) parse_url($url, PHP_URL_PATH);
    $path = ltrim($path, '/');
    $pathLower = strtolower($path);
    $segments = $path === '' ? [] : array_values(array_filter(explode('/', $path), static fn ($s) => $s !== ''));

    // Numeric profile id URL style
    if ($pathLower === 'profile.php') {
        $q = (string) parse_url($url, PHP_URL_QUERY);
        parse_str($q, $qs);
        $id = isset($qs['id']) ? trim((string) $qs['id']) : '';
        if ($id !== '' && ctype_digit($id)) {
            return ['type' => 'id', 'value' => $id];
        }
    }

    // Directory profile: /people/Display-Name/123456789012345/
    if (count($segments) >= 3 && strtolower($segments[0]) === 'people' && ctype_digit((string) $segments[2])) {
        return ['type' => 'id', 'value' => (string) $segments[2]];
    }
    if (count($segments) >= 2 && strtolower($segments[0]) === 'people' && $segments[1] !== '') {
        return ['type' => 'username', 'value' => strtolower($segments[1])];
    }

    // Page: /pages/PageSlug/…
    if (count($segments) >= 2 && strtolower($segments[0]) === 'pages' && $segments[1] !== '') {
        return ['type' => 'username', 'value' => strtolower($segments[1])];
    }

    // Routed profile/page: /gaming/<name> (Facebook Gaming pages)
    if (count($segments) >= 2 && strtolower($segments[0]) === 'gaming' && $segments[1] !== '') {
        return ['type' => 'username', 'value' => strtolower($segments[1])];
    }

    // Username / vanity URL style (first path segment)
    $first = strtolower($segments[0] ?? '');
    if ($first !== '' && $first !== 'pages' && $first !== 'people') {
        return ['type' => 'username', 'value' => $first];
    }

    return ['type' => 'none', 'value' => ''];
}

/**
 * True when the monitored URL is a profile/page user id or vanity slug — not site chrome (login, help, etc.).
 */
function fb_profile_monitor_url_looks_like_profile(string $url): bool
{
    $id = fb_profile_identifier_from_url($url);
    if ($id['type'] === 'none') {
        return false;
    }
    if ($id['type'] === 'username') {
        $v = strtolower($id['value']);
        $reserved = [
            'login', 'checkpoint', 'recover', 'reg', 'privacy', 'policies', 'terms', 'help', 'support',
            'about', 'watch', 'marketplace', 'groups', 'gaming', 'events', 'places', 'me', 'settings',
            'notifications', 'messages', 'friends', 'saved', 'memories', 'ads', 'business', 'pages', 'people',
        ];

        return !in_array($v, $reserved, true);
    }

    return true;
}

/**
 * True when a sign-in gate on this URL should count as “reachable” for unsigned checks,
 * including URL shapes {@see fb_profile_identifier_from_url} does not parse yet.
 */
function fb_profile_login_gate_counts_as_reachable_target(string $url): bool
{
    if (fb_profile_monitor_url_looks_like_profile($url)) {
        return true;
    }
    $host = strtolower((string) parse_url($url, PHP_URL_HOST));
    if ($host === '' || (strpos($host, 'facebook.com') === false && strpos($host, 'fb.com') === false)) {
        return false;
    }
    $path = trim((string) parse_url($url, PHP_URL_PATH), '/');
    if ($path === '') {
        return false;
    }
    $first = strtolower(explode('/', $path)[0] ?? '');
    $siteChrome = [
        'login', 'checkpoint', 'recover', 'help', 'support', 'policies', 'terms', 'legal', 'lite',
        'share', 'dialog', 'plugins', 'pixel', 'tr', 'images', 'r.php', 'a.php',
        'groups', 'watch', 'marketplace', 'events', 'gaming', 'places', 'reel', 'reels', 'stories',
    ];
    if ($first === '' || in_array($first, $siteChrome, true)) {
        return false;
    }

    return true;
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
 * Run the headless browser worker (stdin JSON → stdout JSON).
 *
 * @return null Worker not installed or disabled in config — caller may use HTTP fetch.
 * @return array{ok: bool, html: string, visible_text: string, http_code: int, effective_url: string, error?: string}
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
        return [
            'ok'            => false,
            'error'         => 'Could not encode request for browser worker',
            'html'          => '',
            'visible_text'  => '',
            'http_code'     => 0,
            'effective_url' => '',
        ];
    }
    $cmd = escapeshellarg($node) . ' ' . escapeshellarg($script);
    $des = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
    $proc = @proc_open($cmd, $des, $pipes, $root);
    if (!is_resource($proc)) {
        return [
            'ok'            => false,
            'error'         => 'Could not start browser worker (is Node in PATH?)',
            'html'          => '',
            'visible_text'  => '',
            'http_code'     => 0,
            'effective_url' => '',
        ];
    }
    fwrite($pipes[0], $payload);
    fclose($pipes[0]);
    $stdout = (string) stream_get_contents($pipes[1]);
    $stderr = trim((string) stream_get_contents($pipes[2]));
    fclose($pipes[1]);
    fclose($pipes[2]);
    proc_close($proc);

    $fail = static function (string $err) use ($stderr): array {
        if ($err === '' && $stderr !== '') {
            $err = $stderr;
        }
        if ($err === '') {
            $err = 'Browser check did not complete. Install dependencies (npm ci) and Chromium for the monitor worker.';
        }

        return [
            'ok'            => false,
            'error'         => $err,
            'html'          => '',
            'visible_text'  => '',
            'http_code'     => 0,
            'effective_url' => '',
        ];
    };

    if ($stdout === '') {
        return $fail($stderr !== '' ? $stderr : 'Browser worker produced no output');
    }

    $decoded = json_decode($stdout, true);
    if (!is_array($decoded) || empty($decoded['ok'])) {
        $msg = is_array($decoded) ? trim((string) ($decoded['error'] ?? '')) : '';

        return $fail($msg);
    }

    return [
        'ok'            => true,
        'html'          => (string) ($decoded['html'] ?? ''),
        'visible_text'  => (string) ($decoded['visible_text'] ?? ''),
        'http_code'     => (int) ($decoded['http_code'] ?? 0),
        'effective_url' => (string) ($decoded['effective_url'] ?? ''),
    ];
}

/**
 * Decode \\uXXXX sequences (Facebook embeds error copy inside JSON in HTML).
 */
function fb_monitor_decode_json_unicode_escapes(string $s): string
{
    $out = $s;
    for ($i = 0; $i < 8; $i++) {
        $next = preg_replace_callback(
            '/(?<!\\\\)\\\\u([0-9a-fA-F]{4})/',
            static function (array $m): string {
                $cp = hexdec($m[1]);
                if ($cp <= 0) {
                    return $m[0];
                }
                // Surrogates and invalid scalars make mb_chr() return false — that would TypeError the callback (PHP 8+).
                if ($cp >= 0xd800 && $cp <= 0xdfff) {
                    return $m[0];
                }
                if (function_exists('mb_chr')) {
                    $ch = mb_chr($cp, 'UTF-8');
                    if ($ch !== false && $ch !== '') {
                        return $ch;
                    }

                    return $m[0];
                }
                if ($cp < 128) {
                    return chr($cp);
                }

                return $m[0];
            },
            $out
        );
        if ($next === $out) {
            break;
        }
        $out = $next;
    }

    return $out;
}

/**
 * True if HTML/visible text matches Facebook’s “content not available” / restricted / deleted UI.
 * Decodes entities + JSON \\uXXXX so isn&#039;t / embedded JSON copy match; checks Relay markers.
 */
function fb_profile_page_shows_content_unavailable(string $html, string $visibleText): bool
{
    $scan = html_entity_decode($html . "\n" . $visibleText, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $scan = fb_monitor_decode_json_unicode_escapes($scan);
    $lower = strtolower($scan);
    $normalised = str_replace(
        ["\xe2\x80\x99", '&#8217;', '&#x2019;', '&rsquo;', '\u2019'],
        "'",
        $lower
    );

    // Global header often includes login fields even when the *main* area is “content isn’t available” (unsigned view).
    // Use only strong, user-facing phrases (full headline / helper copy) — not the bare substring "content not available" in JS.
    $looksLikeLoginShell = (
        (strpos($lower, 'name="pass"') !== false || strpos($lower, 'id="pass"') !== false)
        && (strpos($lower, 'name="email"') !== false || strpos($lower, 'id="email"') !== false)
    );
    if ($looksLikeLoginShell) {
        $strongUnavailable = [
            "this content isn't available right now",
            'this content isnt available right now',
            "this content isn't available at the moment",
            'this content isnt available at the moment',
            "this content isn't available",
            'this content isnt available',
            "sorry, this page isn't available",
            'the link you followed may be broken',
            "when this happens, it's usually because",
            'only shared it with a small group of people',
            "changed who can see it or it's been deleted",
            'the page you requested cannot be displayed',
        ];
        foreach ($strongUnavailable as $p) {
            if (strpos($normalised, $p) !== false) {
                return true;
            }
        }
        if (preg_match('/this\s+content\s+isn[\x{0027}\x{2019}]?t\s+available(\s+right\s+now)?/iu', $normalised)) {
            return true;
        }
        if (preg_match('/this\s+page\s+isn[\x{0027}\x{2019}]?t\s+available/iu', $normalised)) {
            return true;
        }
        // English error card often pairs these (padlock / restricted profile)
        if (strpos($normalised, 'go to feed') !== false && strpos($normalised, 'go back') !== false && strpos($normalised, 'visit help center') !== false) {
            return true;
        }

        return false;
    }

    $phrases = [
        "this content isn't available right now",
        "this content isn't available at the moment",
        "this content isn't available",
        'this content isnt available right now',
        'this content isnt available at the moment',
        'this content isnt available',
        'page not found',
        "sorry, this page isn't available",
        'the page you requested cannot be displayed',
        'the link you followed may be broken',
        "when this happens, it's usually because",
        'only shared it with a small group of people',
        "changed who can see it or it's been deleted",
        'when this happens, its usually because',
        'changed who can see it or its been deleted',
        // Common variants / “not visible” copy
        "this page isn't available",
        "this page isn't visible",
        "isn't visible right now",
        "can't view this",
        "cannot view this",
        "can't find this account",
        "cannot find this account",
        'no longer available',
        'profile unavailable',
        "this profile can't be viewed",
        "this profile cannot be viewed",
        'page is not visible',
        'content is not visible',
    ];
    foreach ($phrases as $p) {
        if (strpos($normalised, $p) !== false) {
            return true;
        }
    }

    // Flexible: apostrophe / HTML entity / narrow no-break variants in “isn’t”
    if (preg_match('/this\s+content\s+isn[\x{0027}\x{2019}]?t\s+available/iu', $normalised)) {
        return true;
    }
    if (preg_match('/this\s+content\s+is\s+not\s+available/iu', $normalised)) {
        return true;
    }
    if (preg_match('/content\s+not\s+available/iu', $normalised)) {
        return true;
    }
    if (preg_match('/this\s+page\s+isn[\x{0027}\x{2019}]?t\s+(available|visible)/iu', $normalised)) {
        return true;
    }

    // Embedded GraphQL / Relay-style markers (big JSON blobs; avoid generic class names)
    if (preg_match('/"(ProfileCannotBeAccessed|CannotRenderTimeline)"/i', $html . $visibleText)) {
        return true;
    }

    return false;
}

/**
 * Guest / signed-out view: Facebook shows a dialog like “See more from [Name]” with email/password
 * while the profile shell is still loaded behind it — not the same as a bare login.php page.
 */
function fb_profile_guest_see_more_modal_present(string $html, string $visibleText): bool
{
    $scan = html_entity_decode($html . "\n" . $visibleText, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $scan = fb_monitor_decode_json_unicode_escapes($scan);
    $lower = strtolower($scan);

    return preg_match('/see\s+more\s+from\b/i', $lower) === 1;
}

/**
 * True if the browser landed on a URL whose path/query matches the monitored profile identifier.
 * Used after a real browser navigation when HTML/SPA does not repeat "/username" in the body.
 */
function fb_profile_final_url_matches_identifier(string $finalUrl, array $want): bool
{
    if ($want['type'] === 'none') {
        return false;
    }
    $finalUrl = trim($finalUrl);
    if ($finalUrl === '') {
        return false;
    }
    $host = strtolower((string) parse_url($finalUrl, PHP_URL_HOST));
    if ($host === '' || strpos($host, 'facebook.com') === false) {
        return false;
    }

    if ($want['type'] === 'id') {
        $q = (string) parse_url($finalUrl, PHP_URL_QUERY);
        parse_str($q, $qs);
        if (isset($qs['id']) && (string) $qs['id'] === (string) $want['value']) {
            return true;
        }
        $path = ltrim((string) parse_url($finalUrl, PHP_URL_PATH), '/');
        $segs = $path === '' ? [] : array_values(array_filter(explode('/', $path), static fn ($s) => $s !== ''));
        if (count($segs) >= 3 && strtolower($segs[0]) === 'people' && ctype_digit((string) $segs[2])) {
            return (string) $segs[2] === (string) $want['value'];
        }

        return false;
    }

    $path = (string) parse_url($finalUrl, PHP_URL_PATH);
    $path = trim($path, '/');
    $segs = $path === '' ? [] : array_values(array_filter(explode('/', $path), static fn ($s) => $s !== ''));
    $wantLower = strtolower((string) $want['value']);
    if (count($segs) >= 2 && strtolower($segs[0]) === 'people' && strtolower($segs[1]) === $wantLower) {
        return true;
    }
    if (count($segs) >= 2 && strtolower($segs[0]) === 'pages' && strtolower($segs[1]) === $wantLower) {
        return true;
    }
    $first = strtolower((string) ($segs[0] ?? ''));

    return $first !== '' && $first === $wantLower;
}

/**
 * Classify profile from fetched HTML (HTTP or browser worker).
 *
 * @return array{status: string, detail: string, http_code: int, update_last_status?: bool}
 */
function fb_classify_fetched_profile(string $html, string $finalUrl, string $originalUrl, int $code, string $curlErr = '', string $visibleText = '', bool $browserUrlConfirmation = false, bool $anonymousSession = false): array
{
    $originalUrl = trim($originalUrl);
    $finalUrl    = trim($finalUrl);

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

    // Decode entities for login/unavailable checks (raw HTML often has isn&#039;t, etc.).
    $scan = $html . "\n" . $visibleText;
    $lower = strtolower(html_entity_decode($scan, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

    // Unavailable main content must win over “login in header” heuristics (unsigned view often has both).
    if (fb_profile_page_shows_content_unavailable($html, $visibleText)) {
        return ['status' => 'unavailable', 'detail' => 'Facebook returned “content not available” (private/restricted/deleted)', 'http_code' => $code];
    }

    $guestSeeMoreInvite = fb_profile_guest_see_more_modal_present($html, $visibleText);
    $profileMonitor     = fb_profile_monitor_url_looks_like_profile($originalUrl);

    // Redirects to login/checkpoint: with no session cookies, a sign-in wall usually means the profile
    // still exists (vs “content not available” for removed/blocked profiles).
    $finalLower = strtolower($finalUrl);
    if (strpos($finalLower, '/login.php') !== false || strpos($finalLower, '/checkpoint/') !== false) {
        if ($anonymousSession && ($profileMonitor || fb_profile_login_gate_counts_as_reachable_target($originalUrl))) {
            $isCheckpoint = strpos($finalLower, '/checkpoint/') !== false;
            $detail       = $isCheckpoint
                ? 'Verification or checkpoint — Facebook is gating access; the profile may still exist (unsigned check)'
                : 'Facebook redirected to sign-in — profile appears to exist but requires authentication to view (unsigned check)';

            return ['status' => 'active', 'detail' => $detail, 'http_code' => $code];
        }

        $detail = $anonymousSession
            ? 'Facebook requires sign-in to view this URL (unsigned check — add session cookies for private/restricted profiles)'
            : 'Saved session cookies are invalid or expired — Facebook redirected to login or checkpoint';

        return ['status' => 'unknown', 'detail' => $detail, 'http_code' => $code];
    }

    // Login form in body — skip when it is only the guest “See more from …” overlay on a loaded profile.
    if (
        !$guestSeeMoreInvite
        && (strpos($lower, 'name="email"') !== false || strpos($lower, 'id="email"') !== false)
        && (strpos($lower, 'name="pass"') !== false || strpos($lower, 'id="pass"') !== false)
    ) {
        if ($anonymousSession && ($profileMonitor || fb_profile_login_gate_counts_as_reachable_target($originalUrl))) {
            return [
                'status'    => 'active',
                'detail'    => 'Sign-in form shown — Facebook is gating this profile; account appears to exist (unsigned check)',
                'http_code' => $code,
            ];
        }

        $detail = $anonymousSession
            ? 'Login page shown — URL does not look like a profile link (use facebook.com/username or …/people/…/id/)'
            : 'Saved session cookies are invalid or expired — Facebook showed a sign-in page';

        return ['status' => 'unknown', 'detail' => $detail, 'http_code' => $code];
    }

    $normalised = str_replace(
        ["\xe2\x80\x99", '&#8217;', '&#x2019;', '&rsquo;', '\u2019'],
        "'",
        $lower
    );

    if (strpos($normalised, 'sorry, something went wrong') !== false) {
        return ['status' => 'unknown', 'detail' => 'Facebook returned a generic error page — try again later or refresh cookies', 'http_code' => $code];
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
    // Error pages still contain profile links/meta — re-check before marking active.
    if ($linkCount >= 1) {
        if (fb_profile_page_shows_content_unavailable($html, $visibleText)) {
            return [
                'status'    => 'unavailable',
                'detail'    => 'Facebook shows “content not available” (links to this profile still appear in markup)',
                'http_code' => $code,
            ];
        }
        $detail = 'Profile content matches the monitored identifier (' . $linkCount . '× "' . $needle . '") — account appears active';
        if ($onProfileAfterRedirect) {
            $detail = 'Profile page loaded and content matches the monitored profile — account appears active';
        }
        if ($anonymousSession) {
            $detail .= ' (unsigned browser check — public or lightly restricted visibility)';
        }

        return [
            'status'    => 'active',
            'detail'    => $detail,
            'http_code' => $code,
        ];
    }

    // Real browser session: landed on a URL whose path matches the profile; SPA often omits "/user" in static HTML.
    if (
        $browserUrlConfirmation
        && fb_profile_final_url_matches_identifier($finalUrl, $want)
    ) {
        $detail = 'page loaded; identifier not detected in text (inconclusive parse; counted as active)';
        if ($anonymousSession) {
            $detail .= ' (unsigned check)';
        }

        return [
            'status'    => 'active',
            'detail'    => $detail,
            'http_code' => $code,
        ];
    }

    // Guest overlay: “See more from …” + final URL still matches profile — treat as active even if
    // identifier links are sparse in the captured HTML.
    if (
        $guestSeeMoreInvite
        && $want['type'] !== 'none'
        && fb_profile_final_url_matches_identifier($finalUrl, $want)
    ) {
        $detail = 'Public profile loaded; guest “See more from” login gate — account appears active';
        if ($anonymousSession) {
            $detail .= ' (unsigned browser check)';
        }

        return [
            'status'    => 'active',
            'detail'    => $detail,
            'http_code' => $code,
        ];
    }

    // Inconclusive: page loaded but identifier not found (SPA shell, layout changes, restricted HTML).
    // Do not assume active — that produced false “active” for unavailable/hidden profiles.
    // Caller should persist `unknown` to DB (unlike transient network/cookie unknowns).
    $inconcl = 'Could not confirm profile visibility — identifier not found in response (inconclusive parse)';
    if ($anonymousSession) {
        $inconcl .= ' (try adding session cookies if this profile is not public)';
    }

    return [
        'status'             => 'unknown',
        'detail'             => $inconcl,
        'http_code'          => $code,
        'update_last_status' => true,
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
 * Heuristic score: higher = more likely a profile/avatar image (vs cover/banner/random).
 */
function fb_monitor_score_profile_pic_url(string $url): int
{
    $u = strtolower($url);
    $score = 0;
    // Facebook profile photos often use /v/t1.* or /v/t31.* in the CDN path.
    if (preg_match('#/v/t1[\d.]+-[\d.]+/#', $u)) {
        $score += 85;
    }
    if (preg_match('#/v/t31\.#', $u)) {
        $score += 75;
    }
    // Square-ish avatar crops (typical profile sizes).
    if (preg_match('#s(32|40|48|50|100|128|168|180|200|240|320)x\1#', $u)) {
        $score += 55;
    }
    if (preg_match('#s(\d{2,3})x\1#', $u, $mm)) {
        $n = (int) $mm[1];
        if ($n >= 32 && $n <= 480) {
            $score += 35;
        }
    }
    // Wide cover / banner dimensions (deprioritize).
    if (preg_match('#s(720|820|851|960|1080|1200)x#', $u)) {
        $score -= 120;
    }
    if (preg_match('#851x315|820x312|960x#', $u)) {
        $score -= 100;
    }
    if (strpos($u, 'cover') !== false) {
        $score -= 60;
    }

    return $score;
}

/**
 * Prefer <img> whose alt/aria-label clearly refers to the profile photo (not cover).
 */
function fb_monitor_extract_profile_picture_from_imgs(string $html): string
{
    if ($html === '') {
        return '';
    }
    if (!preg_match_all('/<img\b[^>]*>/is', $html, $tags)) {
        return '';
    }
    $hint = '/profile\s*photo|profile\s*picture|foto\s*(del\s*)?perfil|photo\s*de\s*profil|profilbild|photo\s*du\s*profil|zdj(e|ę)cie\s*profilowe|imagem\s*do\s*perfil/i';
    foreach ($tags[0] as $tag) {
        $blob = '';
        if (preg_match('/\balt\s*=\s*["\']([^"\']*)["\']/i', $tag, $a)) {
            $blob .= $a[1] . ' ';
        }
        if (preg_match('/\baria-label\s*=\s*["\']([^"\']*)["\']/i', $tag, $ar)) {
            $blob .= $ar[1] . ' ';
        }
        if ($blob !== '' && preg_match($hint, $blob)) {
            if (preg_match('/\b(?:src|data-src)\s*=\s*["\']([^"\']+)["\']/i', $tag, $s)) {
                $u = fb_monitor_normalize_telegram_photo_url($s[1]);
                if ($u !== '') {
                    return $u;
                }
            }
        }
    }

    return '';
}

/**
 * Collect unique Facebook CDN image URLs from HTML.
 *
 * @return list<string>
 */
function fb_monitor_collect_fbcdn_image_urls(string $html): array
{
    if (!preg_match_all('#https://[a-z0-9.-]+\.fbcdn\.net/[^\s"\'<>]+#i', $html, $matches)) {
        return [];
    }
    $out = [];
    foreach ($matches[0] as $raw) {
        $u = fb_monitor_normalize_telegram_photo_url($raw);
        if ($u !== '') {
            $out[$u] = true;
        }
    }

    return array_keys($out);
}

/**
 * Pick the best-scoring CDN image (profile-like), or '' if none look plausible.
 */
function fb_monitor_best_scored_fbcdn_profile_pic(string $html): string
{
    $urls = fb_monitor_collect_fbcdn_image_urls($html);
    if ($urls === []) {
        return '';
    }
    $bestUrl = '';
    $best    = PHP_INT_MIN;
    foreach ($urls as $u) {
        $s = fb_monitor_score_profile_pic_url($u);
        if ($s > $best) {
            $best    = $s;
            $bestUrl = $u;
        }
    }
    // Require a minimum confidence so we don't grab stickers/random assets.
    if ($best < 8) {
        return '';
    }

    return $bestUrl;
}

/**
 * Fallback: best-scoring plausible profile image from raw CDN URLs (last resort).
 */
function fb_monitor_extract_fbcdn_img_fallback(string $html): string
{
    return fb_monitor_best_scored_fbcdn_profile_pic($html);
}

/**
 * Best-effort profile *picture* URL only (avatar), not cover/og preview when avoidable.
 */
function fb_monitor_extract_page_image(string $html): string
{
    if ($html === '') {
        return '';
    }
    $fromImg = fb_monitor_extract_profile_picture_from_imgs($html);
    if ($fromImg !== '') {
        return $fromImg;
    }
    $fromCdn = fb_monitor_best_scored_fbcdn_profile_pic($html);
    if ($fromCdn !== '') {
        return $fromCdn;
    }
    $allCdn = fb_monitor_collect_fbcdn_image_urls($html);
    if (count($allCdn) === 1) {
        return $allCdn[0];
    }
    // og:image is often cover or generic preview — use only if it scores as profile-like.
    $metaUrls = [];
    foreach (['og:image:secure_url', 'og:image', 'twitter:image'] as $prop) {
        $q = preg_quote($prop, '/');
        if (preg_match('/<meta\s[^>]*property\s*=\s*["\']' . $q . '["\'][^>]*\bcontent\s*=\s*["\']([^"\']+)["\']/is', $html, $m)) {
            $metaUrls[] = $m[1];
        }
        if (preg_match('/<meta\s[^>]*\bcontent\s*=\s*["\']([^"\']+)["\'][^>]*property\s*=\s*["\']' . $q . '["\']/is', $html, $m)) {
            $metaUrls[] = $m[1];
        }
    }
    foreach (['og:image', 'twitter:image'] as $name) {
        $q = preg_quote($name, '/');
        if (preg_match('/<meta\s[^>]*name\s*=\s*["\']' . $q . '["\'][^>]*\bcontent\s*=\s*["\']([^"\']+)["\']/is', $html, $m)) {
            $metaUrls[] = $m[1];
        }
    }
    if (preg_match('/<link\s[^>]*rel\s*=\s*["\']image_src["\'][^>]*\bhref\s*=\s*["\']([^"\']+)["\']/is', $html, $m)) {
        $metaUrls[] = $m[1];
    }
    $bestMeta = '';
    $bestScore = PHP_INT_MIN;
    foreach ($metaUrls as $raw) {
        $u = fb_monitor_normalize_telegram_photo_url($raw);
        if ($u === '') {
            continue;
        }
        $sc = fb_monitor_score_profile_pic_url($u);
        if ($sc > $bestScore) {
            $bestScore = $sc;
            $bestMeta  = $u;
        }
    }
    if ($bestMeta !== '' && $bestScore >= 20) {
        return $bestMeta;
    }

    return '';
}

/**
 * Facebook checker: always visits the URL with no Facebook session cookies (Playwright first, else HTTP).
 * Classification uses unsigned-session rules (e.g. login wall ⇒ active; “content not available” ⇒ unavailable).
 */
function fb_check_profile_url(string $url): array
{
    $url = trim($url);
    $pw  = fb_monitor_try_playwright($url, '');
    if ($pw !== null) {
        if (empty($pw['ok'])) {
            $err = trim((string) ($pw['error'] ?? 'check failed'));

            return [
                'status'        => 'unknown',
                'detail'        => $err !== '' ? ('Browser check: ' . $err) : 'Browser check failed',
                'http_code'     => 0,
                'preview_image' => '',
            ];
        }

        $out = fb_classify_fetched_profile(
            $pw['html'],
            $pw['effective_url'],
            $url,
            $pw['http_code'],
            '',
            $pw['visible_text'] ?? '',
            true,
            true
        );
        $out['preview_image'] = fb_monitor_extract_page_image($pw['html']);

        return $out;
    }

    if (!function_exists('curl_init')) {
        return [
            'status'    => 'unknown',
            'detail'    => 'Browser worker is not available (run npm ci in the project folder) and cURL is missing.',
            'http_code' => 0,
            'preview_image' => '',
        ];
    }

    $fetch = fb_monitor_fetch_facebook_html($url, '');
    $out   = fb_classify_fetched_profile(
        $fetch['html'],
        $fetch['effective_url'],
        $url,
        $fetch['http_code'],
        $fetch['curl_error'],
        '',
        false,
        true
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
    // Link preview comes from the Profile URL below; $previewImageUrl is kept for callers but not sent as sendPhoto.
    $esc = static fn(string $s): string => htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $u = trim($url);
    if ($u === '') {
        return;
    }
    $when = $esc(gmdate('Y-m-d H:i:s') . ' UTC');
    $href = $esc($u);
    $labelTrim = trim($label);
    $labelLine = $labelTrim !== '' ? $esc($labelTrim) : '—';

    // Single message: details first, then Telegram’s link preview card under the URL (like your reference).
    $lines = [
        '✅ <b>Facebook checker</b> — profile accessible again',
        '🎯 Monitored Facebook URL is loading (no longer restricted/unavailable).',
        '📌 <b>Name:</b> ' . $labelLine,
        '🔗 <b>Profile:</b> <a href="' . $href . '">' . $href . '</a>',
        '🕒 <b>Last Checked At:</b> <code>' . $when . '</code>',
        '<i>Trackify · Meta tools</i>',
    ];

    $html = implode("\n", $lines);
    if (strlen($html) > 4000) {
        $html = trackify_trunc_utf8($html, 3990) . '…';
    }

    trackify_send_telegram_html($html, false);
}
