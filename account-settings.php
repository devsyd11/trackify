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

dashboard_shell_begin('Account settings', 'settings', $userNavName, $userNavInitial, true);
?>
                    <div class="settings-page">
                        <div id="account-settings-toast" class="toast" role="status" aria-live="polite"></div>
                        <div class="settings-layout">
                            <aside class="settings-sidebar" aria-label="Settings sections">
                                <h1 class="settings-sidebar-title">Account settings</h1>
                                <nav class="settings-nav" role="tablist" aria-label="Settings categories">
                                    <button type="button" class="settings-nav-tab" role="tab" id="settings-tab-password" aria-controls="settings-panel-password" aria-selected="true">Change password</button>
                                    <button type="button" class="settings-nav-tab" role="tab" id="settings-tab-notifications" aria-controls="settings-panel-notifications" aria-selected="false" tabindex="-1">Notifications</button>
                                    <button type="button" class="settings-nav-tab" role="tab" id="settings-tab-facebook" aria-controls="settings-panel-facebook" aria-selected="false" tabindex="-1">Facebook</button>
                                </nav>
                            </aside>

                            <div class="settings-content">
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

                            <div class="settings-telegram-actions">
                                <button type="button" class="btn btn-secondary" id="telegram_test_btn">Test now</button>
                                <button type="button" class="btn btn-primary" id="telegram_save_btn">Save</button>
                            </div>
                        </div>
                    </div>

                    <div id="settings-panel-facebook" role="tabpanel" aria-labelledby="settings-tab-facebook" class="settings-tab-panel" hidden>
                        <div class="card">
                            <h2>Facebook cookies (global)</h2>
                            <p class="settings-telegram-status" id="fb_cookie_status" aria-live="polite">Loading…</p>
                            <div id="fbCookieFields"></div>
                            <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-top:10px">
                                <button type="button" class="btn btn-secondary" id="fb_cookie_add_btn">Add cookie</button>
                            </div>

                            <p class="settings-alert" id="fb_cookie_error" role="alert" hidden></p>

                            <div class="settings-telegram-actions">
                                <button type="button" class="btn btn-primary" id="fb_cookie_save_btn">Save</button>
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
                        var tabFb = document.getElementById('settings-tab-facebook');
                        var panelPwd = document.getElementById('settings-panel-password');
                        var panelNotify = document.getElementById('settings-panel-notifications');
                        var panelFb = document.getElementById('settings-panel-facebook');
                        if (!tabPwd || !tabNotify || !tabFb || !panelPwd || !panelNotify || !panelFb) return;

                        var toastEl = document.getElementById('account-settings-toast');
                        var toastHideTimer = null;
                        function showToast(msg) {
                            if (!toastEl) return;
                            toastEl.textContent = String(msg);
                            toastEl.classList.add('show');
                            if (toastHideTimer) clearTimeout(toastHideTimer);
                            toastHideTimer = setTimeout(function () {
                                toastEl.classList.remove('show');
                                toastHideTimer = null;
                            }, 2800);
                        }
                        <?php if ($success !== ''): ?>
                        showToast(<?= json_encode($success, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>);
                        <?php endif; ?>

                        function activateTab(which) {
                            var isPwd    = which === 'password';
                            var isNotify = which === 'notifications';
                            var isFb     = which === 'facebook';
                            tabPwd.setAttribute('aria-selected', isPwd ? 'true' : 'false');
                            tabNotify.setAttribute('aria-selected', isNotify ? 'true' : 'false');
                            tabFb.setAttribute('aria-selected', isFb ? 'true' : 'false');
                            tabPwd.tabIndex    = isPwd    ? 0 : -1;
                            tabNotify.tabIndex = isNotify ? 0 : -1;
                            tabFb.tabIndex     = isFb ? 0 : -1;
                            panelPwd.hidden    = !isPwd;
                            panelNotify.hidden = !isNotify;
                            panelFb.hidden     = !isFb;
                        }
                        tabPwd.addEventListener('click', function () { activateTab('password'); });
                        tabNotify.addEventListener('click', function () { activateTab('notifications'); });
                        tabFb.addEventListener('click', function () { activateTab('facebook'); });

                        var statusEl = document.getElementById('telegram_config_status');
                        var enableEl = document.getElementById('telegram_enabled');
                        var tokEl = document.getElementById('telegram_bot_token');
                        var chatEl = document.getElementById('telegram_chat_id');
                        var errEl = document.getElementById('telegram_settings_error');
                        var saveBtn = document.getElementById('telegram_save_btn');
                        var testBtn = document.getElementById('telegram_test_btn');
                        if (statusEl && enableEl && tokEl && chatEl && errEl && saveBtn && testBtn) {

                        function setErr(msg) {
                            if (msg) {
                                errEl.textContent = String(msg);
                                errEl.hidden = false;
                            } else {
                                errEl.textContent = '';
                                errEl.hidden = true;
                            }
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
                                showToast(data.message || 'Successfully saved.');
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
                                    showToast(data.message || 'Test message sent.');
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

                        }

                        // Facebook cookies (global)
                        var fbStatus = document.getElementById('fb_cookie_status');
                        var fbErr = document.getElementById('fb_cookie_error');
                        var fbFields = document.getElementById('fbCookieFields');
                        var fbAddBtn = document.getElementById('fb_cookie_add_btn');
                        var fbSave = document.getElementById('fb_cookie_save_btn');
                        if (fbStatus && fbErr && fbFields && fbAddBtn && fbSave) {
                            function fbSetErr(msg) {
                                if (msg) {
                                    fbErr.textContent = String(msg);
                                    fbErr.hidden = false;
                                } else {
                                    fbErr.textContent = '';
                                    fbErr.hidden = true;
                                }
                            }
                            function fbSetStatus(msg) {
                                fbStatus.textContent = String(msg || '');
                            }
                            function makeCookieField(index, value) {
                                var wrap = document.createElement('div');
                                wrap.className = 'fb-cookie-field';
                                wrap.style.marginTop = index === 0 ? '12px' : '10px';
                                var lab = document.createElement('label');
                                lab.textContent = 'Cookie field ' + (index + 1);
                                var ta = document.createElement('textarea');
                                ta.rows = 3;
                                ta.style.width = '100%';
                                ta.style.resize = 'vertical';
                                ta.placeholder = index === 0 ? 'c_user=...; xs=...' : 'Backup cookie set';
                                ta.value = value || '';
                                ta.setAttribute('data-fb-cookie', '1');
                                var actions = document.createElement('div');
                                actions.style.display = 'flex';
                                actions.style.justifyContent = 'flex-end';
                                actions.style.marginTop = '6px';
                                if (index > 0) {
                                    var rm = document.createElement('button');
                                    rm.type = 'button';
                                    rm.className = 'btn btn-secondary';
                                    rm.textContent = 'Remove';
                                    rm.style.padding = '8px 12px';
                                    rm.addEventListener('click', function () {
                                        wrap.remove();
                                        refreshLabels();
                                    });
                                    actions.appendChild(rm);
                                }
                                wrap.appendChild(lab);
                                wrap.appendChild(ta);
                                wrap.appendChild(actions);
                                return wrap;
                            }
                            function refreshLabels() {
                                var i = 0;
                                Array.prototype.forEach.call(fbFields.querySelectorAll('.fb-cookie-field'), function (w) {
                                    var lab = w.querySelector('label');
                                    if (lab) lab.textContent = 'Cookie field ' + (i + 1);
                                    i++;
                                });
                            }
                            function getCookieValues() {
                                var out = [];
                                fbFields.querySelectorAll('textarea[data-fb-cookie="1"]').forEach(function (ta) {
                                    out.push((ta.value || '').trim());
                                });
                                return out;
                            }
                            function setCookieValues(values) {
                                fbFields.innerHTML = '';
                                var list = Array.isArray(values) ? values : [];
                                if (list.length === 0) list = [''];
                                list.forEach(function (v, idx) {
                                    fbFields.appendChild(makeCookieField(idx, v));
                                });
                            }
                            async function loadFbCookies() {
                                try {
                                    var res = await fetch(API + '?action=fb_cookies_config', { credentials: 'same-origin' });
                                    var data = await res.json().catch(function () { return {}; });
                                    if (data.status !== 'success') {
                                        fbSetStatus('Status: could not load cookies.');
                                        return;
                                    }
                                    var arr = Array.isArray(data.cookies) ? data.cookies : [];
                                    setCookieValues(arr);
                                    var vals = getCookieValues();
                                    var hasAny = vals.some(function (v) { return !!v; });
                                    fbSetStatus(hasAny ? 'Status: cookies saved.' : 'Status: no cookies set (unsigned checks).');
                                } catch (e) {
                                    fbSetStatus('Status: could not load cookies (network error).');
                                }
                            }
                            fbAddBtn.addEventListener('click', function () {
                                var idx = fbFields.querySelectorAll('.fb-cookie-field').length;
                                fbFields.appendChild(makeCookieField(idx, ''));
                                refreshLabels();
                                var last = fbFields.querySelector('.fb-cookie-field:last-child textarea');
                                if (last) last.focus();
                            });
                            fbSave.addEventListener('click', async function () {
                                fbSetErr('');
                                var prev = fbSave.textContent;
                                fbSave.disabled = true;
                                fbSave.textContent = 'Saving…';
                                try {
                                    var cookies = getCookieValues();
                                    var res = await fetch(API + '?action=fb_cookies_save', {
                                        method: 'POST',
                                        headers: { 'Content-Type': 'application/json' },
                                        body: JSON.stringify({ cookies: cookies }),
                                        credentials: 'same-origin'
                                    });
                                    var data = await res.json().catch(function () { return {}; });
                                    if (data.status !== 'success') {
                                        fbSetErr(data.message || 'Could not save');
                                        return;
                                    }
                                    var hasAny = cookies.some(function (v) { return !!v; });
                                    fbSetStatus(hasAny ? 'Status: cookies saved.' : 'Status: no cookies set (unsigned checks).');
                                    showToast(data.message || 'Saved.');
                                } catch (e) {
                                    fbSetErr('Network error — try again');
                                } finally {
                                    fbSave.disabled = false;
                                    fbSave.textContent = prev;
                                }
                            });
                            void loadFbCookies();
                        }

                    })();
                    </script>
<?php
dashboard_shell_end();
