<?php

declare(strict_types=1);

/**
 * Opens HTML document with shared dashboard sidebar + top bar.
 *
 * @param string $pageTitle   Browser tab title (without suffix)
 * @param string $navActive   trackify | phone | ip | settings
 * @param string $userNavName Display name in header menu
 * @param string $userNavInitial Single character avatar letter
 */
function dashboard_shell_begin(string $pageTitle, string $navActive, string $userNavName, string $userNavInitial): void
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
            <a href="panel.php" class="side-nav-logo" title="Trackify — Home" aria-label="Trackify — Home">
                <img src="logos/trackify_logo.png" width="120" height="48" alt="">
            </a>
            <a href="panel.php" class="side-nav-item<?= $act('trackify') ?>" title="Trackify">🛰</a>
            <a href="panel.php" class="side-nav-item<?= $act('phone') ?>" title="Phone number lookup">☎</a>
            <a href="panel.php" class="side-nav-item<?= $act('ip') ?>" title="IP lookup">🌐</a>
            <div class="side-nav-spacer" aria-hidden="true"></div>
            <a href="account-settings.php" class="side-nav-item<?= $act('settings') ?>" title="Account settings">⚙</a>
            <div class="side-nav-disclaimer-wrap">
                <button type="button" class="disclaimer-link disclaimer-link--rail" onclick="openDisclaimer()" title="Disclaimer" aria-label="Disclaimer">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
                </button>
            </div>
        </nav>

        <div class="dashboard-shell-inner">
            <header class="top-nav">
                <div class="top-nav-left">
                    <a href="panel.php" class="top-nav-brand" title="Trackify" aria-label="Trackify">
                        <img src="logos/trackify_logo.png" width="220" height="48" alt="Trackify">
                    </a>
                </div>
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
