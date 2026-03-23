<?php
declare(strict_types=1);

define('TRACKIFY_PANEL', true);
require __DIR__ . '/bootstrap.php';
require_login();

$userNavName = trim((string) ($_SESSION['user_name'] ?? ''));
if ($userNavName === '') {
    $userNavName = (string) ($_SESSION['user_email'] ?? 'Account');
}
$userNavInitial = function_exists('mb_substr')
    ? mb_strtoupper(mb_substr($userNavName, 0, 1, 'UTF-8'), 'UTF-8')
    : strtoupper(substr($userNavName, 0, 1) ?: '?');

require __DIR__ . '/panel_view.php';
