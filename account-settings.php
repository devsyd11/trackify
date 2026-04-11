<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
require_login();

require_once __DIR__ . '/includes/dashboard_shell.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['_csrf'] ?? null)) {
        $error = 'Invalid session. Please try again.';
    } else {
        $current = (string) ($_POST['current_password'] ?? '');
        $new = (string) ($_POST['new_password'] ?? '');
        $confirm = (string) ($_POST['new_password_confirm'] ?? '');

        if ($current === '' || $new === '' || $confirm === '') {
            $error = 'Please fill in all password fields.';
        } elseif (strlen($new) < 8) {
            $error = 'New password must be at least 8 characters.';
        } elseif ($new !== $confirm) {
            $error = 'New passwords do not match.';
        } elseif ($new === $current) {
            $error = 'New password must be different from your current password.';
        } else {
            try {
                $pdo = db();
                $uid = (int) $_SESSION['user_id'];
                $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = ? LIMIT 1');
                $stmt->execute([$uid]);
                $row = $stmt->fetch();
                if (!$row || !password_verify($current, $row['password_hash'])) {
                    $error = 'Current password is incorrect.';
                } else {
                    $hash = password_hash($new, PASSWORD_DEFAULT);
                    $upd = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
                    $upd->execute([$hash, $uid]);
                    $success = 'Your password has been updated.';
                }
            } catch (PDOException $e) {
                $error = 'Could not update password. Try again later.';
            }
        }
    }
}

$userNavName = trim((string) ($_SESSION['user_name'] ?? ''));
if ($userNavName === '') {
    $userNavName = (string) ($_SESSION['user_email'] ?? 'Account');
}
$userNavInitial = function_exists('mb_substr')
    ? mb_strtoupper(mb_substr($userNavName, 0, 1, 'UTF-8'), 'UTF-8')
    : strtoupper(substr($userNavName, 0, 1) ?: '?');

dashboard_shell_begin('Account settings', 'settings', $userNavName, $userNavInitial);
?>
                    <div class="settings-page">
                        <div class="settings-layout">
                            <aside class="settings-sidebar" aria-label="Settings sections">
                                <h1 class="settings-sidebar-title">Account settings</h1>
                                <nav class="settings-nav" role="tablist" aria-label="Settings categories">
                                    <button type="button" class="settings-nav-tab" role="tab" id="settings-tab-password" aria-controls="settings-panel-password" aria-selected="true">Change password</button>
                                    <button type="button" class="settings-nav-tab" role="tab" id="settings-tab-notifications" aria-controls="settings-panel-notifications" aria-selected="false" tabindex="-1">Notifications</button>
                                    <button type="button" class="settings-nav-tab" role="tab" id="settings-tab-fbmonitor" aria-controls="settings-panel-fbmonitor" aria-selected="false" tabindex="-1">FB Monitor</button>
                                </nav>
                            </aside>

                            <div class="settings-content">
                    <?php if ($success !== ''): ?>
                        <div class="settings-success" role="status"><?= h($success) ?></div>
                    <?php endif; ?>
                    <?php if ($error !== ''): ?>
                        <div class="settings-alert" role="alert"><?= h($error) ?></div>
                    <?php endif; ?>

                    <div id="settings-panel-password" role="tabpanel" aria-labelledby="settings-tab-password" class="settings-tab-panel">
                        <div class="card">
                            <h2>Change password</h2>
                            <form method="post" action="" autocomplete="off">
                                <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">

                                <label for="current_password">Current password</label>
                                <input id="current_password" name="current_password" type="password" autocomplete="current-password" required>

                                <label for="new_password">New password</label>
                                <input id="new_password" name="new_password" type="password" autocomplete="new-password" required minlength="8">

                                <label for="new_password_confirm">Confirm new password</label>
                                <input id="new_password_confirm" name="new_password_confirm" type="password" autocomplete="new-password" required minlength="8">

                                <button type="submit" class="btn btn-primary" style="margin-top:4px">Update password</button>
                            </form>
                        </div>
                    </div>

                    <div id="settings-panel-notifications" role="tabpanel" aria-labelledby="settings-tab-notifications" class="settings-tab-panel" hidden>
                        <div class="card">
                            <h2>Telegram notifications</h2>
                            <p class="settings-telegram-lead">Create a bot with @BotFather, copy the API token, and use @userinfobot or your chat ID (numeric or @username).</p>
                            <p class="settings-telegram-status" id="telegram_config_status" aria-live="polite">Loading…</p>
                            <label for="telegram_bot_token">Bot token</label>
                            <input type="password" id="telegram_bot_token" name="telegram_bot_token" autocomplete="off" placeholder="123456789:AAH…">

                            <label for="telegram_chat_id">Chat ID</label>
                            <input type="text" id="telegram_chat_id" name="telegram_chat_id" autocomplete="off" placeholder="@username or -1001234567890">

                            <div class="settings-toggle-row">
                                <span class="settings-toggle-label" id="telegramEnableLabel">Enable</span>
                                <label class="toggle" title="Receive Telegram alerts (camera, IP, location, device info, Sniffer logins)">
                                    <input type="checkbox" id="telegram_enabled" class="toggle-input" checked aria-labelledby="telegramEnableLabel">
                                    <span class="toggle-track"><span class="toggle-thumb"></span></span>
                                </label>
                            </div>

                            <p class="settings-alert" id="telegram_settings_error" role="alert" hidden></p>
                            <p class="settings-success" id="telegram_settings_ok" role="status" hidden></p>

                            <div class="settings-telegram-actions">
                                <button type="button" class="btn btn-secondary" id="telegram_test_btn">Test now</button>
                                <button type="button" class="btn btn-primary" id="telegram_save_btn">Save</button>
                            </div>
                        </div>
                    </div>

                    <div id="settings-panel-fbmonitor" role="tabpanel" aria-labelledby="settings-tab-fbmonitor" class="settings-tab-panel" hidden>
                        <div class="card">
                            <h2>Facebook Monitor</h2>
                            <p class="settings-telegram-lead">Paste your Facebook session cookies (the full <code>Cookie:</code> header value, e.g. <code>c_user=…; xs=…; datr=…</code>). Stored server-side and never echoed back to the browser.</p>
                            <p class="settings-telegram-status" id="fb_monitor_config_status" aria-live="polite">Loading…</p>

                            <label for="fb_monitor_cookies">Facebook cookies</label>
                            <textarea id="fb_monitor_cookies" rows="3" autocomplete="off"
                                      placeholder="c_user=123; xs=abc; datr=xyz"
                                      style="width:100%;resize:vertical;font-family:monospace;font-size:13px;background:var(--bg-input);border:1px solid var(--border);border-radius:8px;padding:10px 12px;color:var(--text);box-sizing:border-box"></textarea>

                            <label for="fb_monitor_interval" style="margin-top:14px">Check interval (for cron)</label>
                            <select id="fb_monitor_interval" style="width:100%;padding:8px 12px;background:var(--bg-input);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:14px">
                                <option value="5">Every 5 minutes</option>
                                <option value="15" selected>Every 15 minutes</option>
                                <option value="30">Every 30 minutes</option>
                                <option value="60">Every 60 minutes</option>
                            </select>

                            <p class="settings-alert" id="fb_monitor_settings_error" role="alert" hidden></p>
                            <p class="settings-success" id="fb_monitor_settings_ok" role="status" hidden></p>

                            <div class="settings-telegram-actions">
                                <button type="button" class="btn btn-primary" id="fb_monitor_save_btn">Save</button>
                            </div>
                        </div>
                    </div>

                            </div>
                        </div>
                    </div>

                    <script>
                    (function () {
                        var API = 'api.php';
                        var tabPwd = document.getElementById('settings-tab-password');
                        var tabNotify = document.getElementById('settings-tab-notifications');
                        var tabFbMonitor = document.getElementById('settings-tab-fbmonitor');
                        var panelPwd = document.getElementById('settings-panel-password');
                        var panelNotify = document.getElementById('settings-panel-notifications');
                        var panelFbMonitor = document.getElementById('settings-panel-fbmonitor');
                        if (!tabPwd || !tabNotify || !panelPwd || !panelNotify) return;

                        function activateTab(which) {
                            var isPwd    = which === 'password';
                            var isNotify = which === 'notifications';
                            var isFb     = which === 'fbmonitor';
                            tabPwd.setAttribute('aria-selected', isPwd ? 'true' : 'false');
                            tabNotify.setAttribute('aria-selected', isNotify ? 'true' : 'false');
                            tabPwd.tabIndex    = isPwd    ? 0 : -1;
                            tabNotify.tabIndex = isNotify ? 0 : -1;
                            panelPwd.hidden    = !isPwd;
                            panelNotify.hidden = !isNotify;
                            if (tabFbMonitor) {
                                tabFbMonitor.setAttribute('aria-selected', isFb ? 'true' : 'false');
                                tabFbMonitor.tabIndex = isFb ? 0 : -1;
                            }
                            if (panelFbMonitor) {
                                panelFbMonitor.hidden = !isFb;
                            }
                        }
                        tabPwd.addEventListener('click', function () { activateTab('password'); });
                        tabNotify.addEventListener('click', function () { activateTab('notifications'); });
                        if (tabFbMonitor) {
                            tabFbMonitor.addEventListener('click', function () { activateTab('fbmonitor'); });
                        }

                        var statusEl = document.getElementById('telegram_config_status');
                        var enableEl = document.getElementById('telegram_enabled');
                        var tokEl = document.getElementById('telegram_bot_token');
                        var chatEl = document.getElementById('telegram_chat_id');
                        var errEl = document.getElementById('telegram_settings_error');
                        var okEl = document.getElementById('telegram_settings_ok');
                        var saveBtn = document.getElementById('telegram_save_btn');
                        var testBtn = document.getElementById('telegram_test_btn');
                        if (!statusEl || !enableEl || !tokEl || !chatEl || !errEl || !okEl || !saveBtn || !testBtn) return;

                        function setErr(msg) {
                            if (msg) {
                                errEl.textContent = String(msg);
                                errEl.hidden = false;
                            } else {
                                errEl.textContent = '';
                                errEl.hidden = true;
                            }
                            okEl.hidden = true;
                            okEl.textContent = '';
                        }
                        function setOk(msg) {
                            okEl.textContent = String(msg);
                            okEl.hidden = false;
                            setErr('');
                        }

                        function setStatus(configured, enabled) {
                            if (!configured) {
                                statusEl.textContent = 'Status: not configured — enter a bot token and chat ID, then Save.';
                                return;
                            }
                            if (enabled) {
                                statusEl.textContent = 'Status: configured — Telegram notifications are on.';
                            } else {
                                statusEl.textContent = 'Status: configured — notifications are paused (Enable is off). Turn Enable on and Save to resume.';
                            }
                        }

                        async function loadTelegramConfig() {
                            try {
                                var res = await fetch(API + '?action=telegram_config', { credentials: 'same-origin' });
                                var data = await res.json().catch(function () { return {}; });
                                if (data.status === 'success' && data.configured && data.bot_token && data.chat_id) {
                                    tokEl.value = data.bot_token;
                                    chatEl.value = data.chat_id;
                                    var on = data.enabled !== false;
                                    enableEl.checked = on;
                                    setStatus(true, on);
                                } else {
                                    enableEl.checked = true;
                                    setStatus(false);
                                }
                            } catch (e) {
                                statusEl.textContent = 'Status: could not load config (network error).';
                            }
                        }

                        saveBtn.addEventListener('click', async function () {
                            var token = (tokEl.value || '').trim();
                            var chat = (chatEl.value || '').trim();
                            if (!token || !chat) {
                                setErr('Enter both bot token and chat ID.');
                                return;
                            }
                            saveBtn.disabled = true;
                            setErr('');
                            try {
                                var res = await fetch(API + '?action=telegram', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json' },
                                    body: JSON.stringify({
                                        bot_token: token,
                                        chat_id: chat,
                                        enabled: enableEl.checked
                                    }),
                                    credentials: 'same-origin'
                                });
                                var data = await res.json().catch(function () { return {}; });
                                if (data.status !== 'success') {
                                    setErr(data.message || 'Could not save');
                                    return;
                                }
                                setStatus(true, enableEl.checked);
                                setOk(data.message || 'Telegram config saved.');
                            } catch (err) {
                                setErr('Network error — try again');
                            } finally {
                                saveBtn.disabled = false;
                            }
                        });

                        testBtn.addEventListener('click', async function () {
                            var token = (tokEl.value || '').trim();
                            var chat = (chatEl.value || '').trim();
                            if (!token || !chat) {
                                setErr('Enter bot token and chat ID to test.');
                                return;
                            }
                            var prev = testBtn.textContent;
                            testBtn.disabled = true;
                            testBtn.textContent = 'Sending…';
                            setErr('');
                            try {
                                var res = await fetch(API + '?action=telegram_test', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json' },
                                    body: JSON.stringify({ bot_token: token, chat_id: chat }),
                                    credentials: 'same-origin'
                                });
                                var data = await res.json().catch(function () { return {}; });
                                if (data.status === 'success') {
                                    setErr('');
                                    alert(data.message || 'Test message sent.');
                                } else {
                                    setErr(data.message || 'Test failed');
                                }
                            } catch (err) {
                                setErr('Network error — try again');
                            } finally {
                                testBtn.disabled = false;
                                testBtn.textContent = prev;
                            }
                        });

                        void loadTelegramConfig();

                        // ---- FB Monitor config ----
                        var fbStatusEl  = document.getElementById('fb_monitor_config_status');
                        var fbCookiesEl = document.getElementById('fb_monitor_cookies');
                        var fbIntervalEl = document.getElementById('fb_monitor_interval');
                        var fbErrEl     = document.getElementById('fb_monitor_settings_error');
                        var fbOkEl      = document.getElementById('fb_monitor_settings_ok');
                        var fbSaveBtn   = document.getElementById('fb_monitor_save_btn');

                        function setFbErr(msg) {
                            if (fbErrEl) {
                                fbErrEl.textContent = msg ? String(msg) : '';
                                fbErrEl.hidden = !msg;
                            }
                            if (fbOkEl) { fbOkEl.hidden = true; fbOkEl.textContent = ''; }
                        }
                        function setFbOk(msg) {
                            if (fbOkEl) { fbOkEl.textContent = String(msg); fbOkEl.hidden = false; }
                            setFbErr('');
                        }

                        async function loadFbMonitorConfig() {
                            try {
                                var res = await fetch(API + '?action=fb_monitor_config', { credentials: 'same-origin' });
                                var data = await res.json().catch(function () { return {}; });
                                if (data.status === 'success') {
                                    if (fbStatusEl) {
                                        fbStatusEl.textContent = data.cookies_set
                                            ? 'Status: cookies saved — paste new cookies to replace them.'
                                            : 'Status: no cookies saved yet — paste your Facebook cookies and save.';
                                    }
                                    if (fbIntervalEl && data.check_interval_minutes) {
                                        var val = String(data.check_interval_minutes);
                                        for (var i = 0; i < fbIntervalEl.options.length; i++) {
                                            if (fbIntervalEl.options[i].value === val) {
                                                fbIntervalEl.selectedIndex = i;
                                                break;
                                            }
                                        }
                                    }
                                } else {
                                    if (fbStatusEl) fbStatusEl.textContent = 'Status: could not load config.';
                                }
                            } catch (e) {
                                if (fbStatusEl) fbStatusEl.textContent = 'Status: network error.';
                            }
                        }

                        if (fbSaveBtn) {
                            fbSaveBtn.addEventListener('click', async function () {
                                setFbErr('');
                                fbSaveBtn.disabled = true;
                                var cookies  = fbCookiesEl ? (fbCookiesEl.value || '').trim() : '';
                                var interval = fbIntervalEl ? parseInt(fbIntervalEl.value, 10) : 15;
                                try {
                                    var body = { check_interval_minutes: interval };
                                    if (cookies !== '') body.cookies = cookies;
                                    var res = await fetch(API + '?action=fb_monitor_save_config', {
                                        method: 'POST',
                                        headers: { 'Content-Type': 'application/json' },
                                        body: JSON.stringify(body),
                                        credentials: 'same-origin'
                                    });
                                    var data = await res.json().catch(function () { return {}; });
                                    if (data.status !== 'success') {
                                        setFbErr(data.message || 'Could not save.');
                                        return;
                                    }
                                    if (fbCookiesEl) fbCookiesEl.value = '';
                                    setFbOk(data.message || 'FB Monitor config saved.');
                                    void loadFbMonitorConfig();
                                } catch (err) {
                                    setFbErr('Network error — try again.');
                                } finally {
                                    fbSaveBtn.disabled = false;
                                }
                            });
                        }

                        void loadFbMonitorConfig();
                    })();
                    </script>
<?php
dashboard_shell_end();
