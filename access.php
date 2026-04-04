<?php
declare(strict_types=1);

function trackify_config(): array
{
    static $cfg = null;
    if (is_array($cfg)) {
        return $cfg;
    }
    $cfg = require __DIR__ . '/config.php';
    return is_array($cfg) ? $cfg : [];
}

function trackify_client_ip(array $server, array $config): string
{
    $remoteAddr = (string) ($server['REMOTE_ADDR'] ?? '');
    $trusted = $config['trusted_proxies'] ?? [];
    $trusted = is_array($trusted) ? $trusted : [];

    $isTrustedProxy = $remoteAddr !== '' && in_array($remoteAddr, $trusted, true);

    if ($isTrustedProxy) {
        $cfConnectingIp = trim((string) ($server['HTTP_CF_CONNECTING_IP'] ?? ''));
        if ($cfConnectingIp !== '') {
            return $cfConnectingIp;
        }

        $xff = trim((string) ($server['HTTP_X_FORWARDED_FOR'] ?? ''));
        if ($xff !== '') {
            $first = trim(explode(',', $xff, 2)[0]);
            if ($first !== '') {
                return $first;
            }
        }
    }

    return $remoteAddr;
}

function trackify_ip_whitelist_allows(string $clientIp, array $config): bool
{
    $enabled = (bool) ($config['ip_whitelist_enabled'] ?? false);
    if (!$enabled) {
        return true;
    }

    $list = $config['ip_whitelist'] ?? [];
    if (!is_array($list) || $list === []) {
        return false;
    }

    $clientIp = trim($clientIp);
    if ($clientIp === '') {
        return false;
    }

    foreach ($list as $allowed) {
        $allowed = trim((string) $allowed);
        if ($allowed === '') {
            continue;
        }
        if ($clientIp === $allowed) {
            return true;
        }
        if (str_contains($allowed, '/')) {
            if (trackify_ip_in_cidr($clientIp, $allowed)) {
                return true;
            }
        }
    }

    return false;
}

function trackify_ip_in_cidr(string $ip, string $cidr): bool
{
    [$subnet, $maskBits] = array_pad(explode('/', $cidr, 2), 2, '');
    $subnet = trim($subnet);
    $maskBits = trim($maskBits);
    if ($subnet === '' || $maskBits === '' || !ctype_digit($maskBits)) {
        return false;
    }

    $maskBitsInt = (int) $maskBits;
    $ipBin = @inet_pton($ip);
    $subnetBin = @inet_pton($subnet);
    if ($ipBin === false || $subnetBin === false) {
        return false;
    }
    if (strlen($ipBin) !== strlen($subnetBin)) {
        return false;
    }

    $maxBits = strlen($ipBin) * 8;
    if ($maskBitsInt < 0 || $maskBitsInt > $maxBits) {
        return false;
    }

    $bytes = intdiv($maskBitsInt, 8);
    $bits = $maskBitsInt % 8;

    if ($bytes > 0) {
        if (substr($ipBin, 0, $bytes) !== substr($subnetBin, 0, $bytes)) {
            return false;
        }
    }

    if ($bits === 0) {
        return true;
    }

    $mask = chr((0xFF << (8 - $bits)) & 0xFF);
    return (($ipBin[$bytes] ?? "\0") & $mask) === (($subnetBin[$bytes] ?? "\0") & $mask);
}

function trackify_enforce_ip_whitelist(array $server, bool $asJson = false): void
{
    $config = trackify_config();
    $clientIp = trackify_client_ip($server, $config);
    if (trackify_ip_whitelist_allows($clientIp, $config)) {
        return;
    }

    $message = (string) ($config['ip_block_message'] ?? 'Access is blocked. Please contact the owner of this site to have access.');

    http_response_code(403);
    if ($asJson) {
        header('Content-Type: application/json');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Access-Control-Allow-Origin: *');
        echo json_encode(['status' => 'error', 'message' => $message], JSON_UNESCAPED_SLASHES);
        exit;
    }

    header('Content-Type: text/html; charset=UTF-8');
    $safeMessage = htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $clientIpEsc = htmlspecialchars($clientIp, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Access blocked · Trackify</title><style>
        :root{color-scheme:dark}
        *{box-sizing:border-box}
        body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;background:#020817;background-image:radial-gradient(circle at 0 0,rgba(88,166,255,.18),transparent 55%),radial-gradient(circle at 100% 100%,rgba(163,113,247,.16),transparent 55%);color:#e6edf3;font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;padding:24px}
        .shell{max-width:880px;width:100%;display:flex;justify-content:center}
        .card{width:100%;background:rgba(10,15,24,.94);border:1px solid rgba(56,68,86,.9);border-radius:18px;padding:24px 22px 22px;box-shadow:0 18px 45px rgba(0,0,0,.72);position:relative;overflow:hidden}
        .card::before{content:"";position:absolute;inset:-120px;opacity:.32;background:radial-gradient(circle at 0 0,rgba(88,166,255,.35),transparent 60%),radial-gradient(circle at 100% 100%,rgba(163,113,247,.3),transparent 60%);pointer-events:none;mix-blend-mode:screen}
        .card-inner{position:relative;display:flex;flex-direction:column;gap:16px}
        .eyebrow{display:inline-flex;align-items:center;gap:8px;font-size:11px;text-transform:uppercase;letter-spacing:.12em;color:#8b949e;background:rgba(13,148,136,.08);border-radius:999px;padding:3px 9px 4px;border:1px solid rgba(45,212,191,.32);width:max-content}
        .pill-dot{width:7px;height:7px;border-radius:999px;background:radial-gradient(circle at 30% 30%,#bbf7d0,#22c55e)}
        .headline{display:flex;align-items:flex-start;justify-content:space-between;gap:12px}
        h1{margin:0;font-size:20px;font-weight:600;letter-spacing:.02em}
        .ip-tag{font-size:12px;color:#8b949e;padding:3px 9px;border-radius:999px;background:rgba(15,23,42,.9);border:1px solid rgba(148,163,184,.35);max-width:52%;text-overflow:ellipsis;overflow:hidden;white-space:nowrap}
        .body-copy{margin:0;color:#9ca3af;font-size:14px;line-height:1.6}
        .body-copy strong{color:#e5e7eb;font-weight:500}
        .actions{display:flex;flex-wrap:wrap;gap:10px;margin-top:2px}
        a.button{display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:7px 13px;border-radius:999px;font-size:13px;font-weight:500;text-decoration:none;border:1px solid rgba(56,189,248,.6);color:#e0f2fe;background:linear-gradient(135deg,#0ea5e9,#22c55e);box-shadow:0 0 0 1px rgba(15,23,42,.9),0 14px 30px rgba(8,47,73,.65)}
        a.button span.icon{display:inline-block;width:15px;height:15px;border-radius:999px;background:radial-gradient(circle at 30% 30%,#f9fafb,#e5e7eb);color:#020617;font-size:11px;line-height:15px;text-align:center;font-weight:600}
        a.button:hover{filter:brightness(1.05);border-color:rgba(56,189,248,.9)}
        .secondary{font-size:12px;color:#6b7280}
        .secondary a{color:#93c5fd;text-decoration:none}
        .secondary a:hover{text-decoration:underline}
        @media (max-width:640px){
            body{padding:16px}
            .shell{max-width:100%}
            .card{padding:18px 16px 16px;border-radius:14px}
            .card-inner{gap:14px}
            h1{font-size:17px;line-height:1.25}
            .headline{flex-direction:column;align-items:flex-start}
            .ip-tag{max-width:100%}
            .body-copy{font-size:13.5px}
            .actions{width:100%}
            a.button{width:100%;padding:10px 14px}
        }
    </style></head><body><div class="shell"><main class="card"><div class="card-inner"><div class="eyebrow"><span class="pill-dot"></span><span>Restricted access</span></div><div class="headline"><h1>Access to this dashboard is blocked</h1><div class="ip-tag">Your IP: ' . $clientIpEsc . '</div></div><p class="body-copy">' . $safeMessage . '</p><div class="actions"><a class="button" href="https://x.com/devsyd11" target="_blank" rel="noopener noreferrer"><span class="icon">X</span><span>Contact the site owner on X</span></a></div><p class="secondary">If you believe you should have access, please reach out via X at <a href="https://x.com/devsyd11" target="_blank" rel="noopener noreferrer">@devsyd11</a> and include your IP address.</p></div></main></div></body></html>';
    exit;
}

