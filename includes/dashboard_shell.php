<?php

declare(strict_types=1);

/**
 * Opens HTML document with shared dashboard sidebar + top bar.
 *
 * @param string $pageTitle   Browser tab title (without suffix)
 * @param string $navActive   trackify | phone | ip | exiftool | fbmonitor | settings
 * @param string $userNavName Display name in header menu
 * @param string $userNavInitial Single character avatar letter
 * @param bool   $hideTopNavBrand When true, omit the large logo in the top bar (sidebar still shows branding)
 */
function dashboard_shell_begin(string $pageTitle, string $navActive, string $userNavName, string $userNavInitial, bool $hideTopNavBrand = false): void
{
    $act = static function (string $key) use ($navActive): string {
        return $navActive === $key ? ' active' : '';
    };
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($pageTitle) ?> — Trackify</title>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="dashboard_shell.css">
</head>
<body class="dashboard-shell-body">
    <div class="app-shell">
        <nav class="side-nav" aria-label="Main navigation">
            <div class="side-nav-brand">
                <a href="panel.php" class="side-nav-logo" title="Trackify — Home" aria-label="Trackify — Home">
                    <img src="logos/trackify_logo.png" width="120" height="48" alt="">
                </a>
                <span class="side-nav-product">Trackify</span>
            </div>
            <div class="side-nav-section-label" role="presentation">Workspace</div>
            <a href="panel.php" class="side-nav-item<?= $act('trackify') ?>" title="Dashboard">
                <span class="side-nav-item-icon" aria-hidden="true">🛰</span>
                <span class="side-nav-item-label">Dashboard</span>
            </a>
            <a href="panel.php?view=phone" class="side-nav-item<?= $act('phone') ?>" title="Phone lookup">
                <span class="side-nav-item-icon" aria-hidden="true">☎</span>
                <span class="side-nav-item-label">Phone lookup</span>
            </a>
            <a href="panel.php?view=ip" class="side-nav-item<?= $act('ip') ?>" title="IP lookup">
                <span class="side-nav-item-icon" aria-hidden="true">🌐</span>
                <span class="side-nav-item-label">IP lookup</span>
            </a>
            <a href="panel.php?view=saveinfo" class="side-nav-item" title="Sniffer">
                <span class="side-nav-item-icon" aria-hidden="true">🔑</span>
                <span class="side-nav-item-label">Sniffer</span>
            </a>
            <a href="panel.php?view=exiftool" class="side-nav-item<?= $act('exiftool') ?>" title="EXIF tool">
                <span class="side-nav-item-icon" aria-hidden="true">📷</span>
                <span class="side-nav-item-label">EXIF tool</span>
            </a>
            <div class="side-nav-group<?= $navActive === 'fbmonitor' ? ' has-active-child' : '' ?>">
                <span class="side-nav-group-label" role="presentation">
                    <svg class="side-nav-meta-logo" width="18" height="18" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
                        <path fill="#0082FB" d="M6.915 4.03c-1.968 0-3.683 1.28-4.871 3.113C.704 9.208 0 11.883 0 14.449c0 .706.07 1.369.21 1.973a6.624 6.624 0 0 0 .265.86 5.297 5.297 0 0 0 .371.761c.696 1.159 1.818 1.927 3.593 1.927 1.497 0 2.633-.671 3.965-2.444.76-1.012 1.144-1.626 2.663-4.32l.756-1.339.186-.325c.061.1.121.196.183.3l2.152 3.595c.724 1.21 1.665 2.556 2.47 3.314 1.046.987 1.992 1.22 3.06 1.22 1.075 0 1.876-.355 2.455-.843a3.743 3.743 0 0 0 .81-.973c.542-.939.861-2.127.861-3.745 0-2.72-.681-5.357-2.084-7.45-1.282-1.912-2.957-2.93-4.716-2.93-1.047 0-2.088.467-3.053 1.308-.652.57-1.257 1.29-1.82 2.05-.69-.875-1.335-1.547-1.958-2.056-1.182-.966-2.315-1.303-3.454-1.303zm10.16 2.053c1.147 0 2.188.758 2.992 1.999 1.132 1.748 1.647 4.195 1.647 6.4 0 1.548-.368 2.9-1.839 2.9-.58 0-1.027-.23-1.664-1.004-.496-.601-1.343-1.878-2.832-4.358l-.617-1.028a44.908 44.908 0 0 0-1.255-1.98c.07-.109.141-.224.211-.327 1.12-1.667 2.118-2.602 3.358-2.602zm-10.201.553c1.265 0 2.058.791 2.675 1.446.307.327.737.871 1.234 1.579l-1.02 1.566c-.757 1.163-1.882 3.017-2.837 4.338-1.191 1.649-1.81 1.817-2.486 1.817-.524 0-1.038-.237-1.383-.794-.263-.426-.464-1.13-.464-2.046 0-2.221.63-4.535 1.66-6.088.454-.687.964-1.226 1.533-1.533a2.264 2.264 0 0 1 1.088-.285z"/>
                    </svg>
                    Meta tools
                </span>
                <div class="side-nav-submenu" role="group" aria-label="Meta tools">
                    <a href="panel.php?view=fbmonitor" class="side-nav-item side-nav-item--sub<?= $navActive === 'fbmonitor' ? ' active' : '' ?>" title="Facebook checker">
                        <span class="side-nav-item-icon" aria-hidden="true">
                            <svg class="side-nav-fb-logo" width="18" height="18" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
                                <path fill="#1877F2" d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                            </svg>
                        </span>
                        <span class="side-nav-item-label">Facebook checker</span>
                    </a>
                </div>
            </div>
            <div class="side-nav-spacer" aria-hidden="true"></div>
            <div class="side-nav-section-label" role="presentation">Account</div>
            <a href="account-settings.php" class="side-nav-item<?= $act('settings') ?>" title="Settings">
                <span class="side-nav-item-icon" aria-hidden="true">⚙</span>
                <span class="side-nav-item-label">Settings</span>
            </a>
            <div class="side-nav-disclaimer-wrap">
                <button type="button" class="side-nav-item" onclick="openDisclaimer()" title="Disclaimer">
                    <span class="side-nav-item-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
                    </span>
                    <span class="side-nav-item-label">Disclaimer</span>
                </button>
            </div>
        </nav>

        <div class="dashboard-shell-inner">
            <header class="top-nav<?= $hideTopNavBrand ? ' top-nav--no-brand' : '' ?>">
                <?php if (!$hideTopNavBrand): ?>
                <div class="top-nav-left">
                    <a href="panel.php" class="top-nav-brand" title="Trackify" aria-label="Trackify">
                        <img src="logos/trackify_logo.png" width="220" height="48" alt="Trackify">
                    </a>
                </div>
                <?php endif; ?>
                <div class="user-nav" id="userNav">
                    <button type="button" class="user-nav-trigger" id="userNavTrigger" aria-expanded="false" aria-haspopup="true" aria-controls="userNavMenu">
                        <span class="user-nav-avatar" aria-hidden="true"><?= h($userNavInitial) ?></span>
                        <span class="user-nav-name"><?= h($userNavName) ?></span>
                        <svg class="user-nav-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
                    </button>
                    <div class="user-nav-dropdown" id="userNavMenu" role="menu" hidden>
                        <a href="account-settings.php" class="user-nav-item" role="menuitem"<?= $navActive === 'settings' ? ' aria-current="page"' : '' ?>>Account settings</a>
                        <a href="logout.php" class="user-nav-item user-nav-item--danger" role="menuitem">Log out</a>
                    </div>
                </div>
            </header>

            <div class="dashboard-main">
                <div class="dashboard-main-inner">
    <?php
}

function dashboard_shell_end(): void
{
    ?>
                </div>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="disclaimerModal" onclick="closeDisclaimer(event)">
        <div class="modal-content" onclick="event.stopPropagation()">
            <h2 class="modal-title">Disclaimer</h2>
            <div class="modal-body">
                <p>This tool is intended for educational and authorized security testing purposes only.</p>
                <p>Use of this software to track, monitor, or collect information from individuals without their explicit consent may violate privacy laws and regulations in your jurisdiction.</p>
                <p>You are solely responsible for ensuring your use complies with all applicable laws. The developers assume no liability for misuse of this software.</p>
            </div>
            <button type="button" class="modal-close" onclick="closeDisclaimer()">I Understand</button>
        </div>
    </div>

    <script>
        (function initUserNav() {
            var nav = document.getElementById('userNav');
            var trigger = document.getElementById('userNavTrigger');
            var menu = document.getElementById('userNavMenu');
            if (!nav || !trigger || !menu) return;
            function closeMenu() {
                trigger.setAttribute('aria-expanded', 'false');
                menu.hidden = true;
            }
            function toggleMenu() {
                var open = trigger.getAttribute('aria-expanded') === 'true';
                if (open) closeMenu();
                else {
                    trigger.setAttribute('aria-expanded', 'true');
                    menu.hidden = false;
                }
            }
            trigger.addEventListener('click', function (e) {
                e.stopPropagation();
                toggleMenu();
            });
            nav.addEventListener('click', function (e) { e.stopPropagation(); });
            document.addEventListener('click', function () { closeMenu(); });
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') closeMenu();
            });
        })();

        function openDisclaimer() {
            document.getElementById('disclaimerModal').classList.add('show');
        }
        function closeDisclaimer(event) {
            if (!event || event.target === event.currentTarget || (event.target && event.target.classList && event.target.classList.contains('modal-close'))) {
                document.getElementById('disclaimerModal').classList.remove('show');
            }
        }
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                var dm = document.getElementById('disclaimerModal');
                if (dm && dm.classList.contains('show')) dm.classList.remove('show');
            }
        });
    </script>
</body>
</html>
    <?php
}
