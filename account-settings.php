<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
require_login();

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

$displayName = trim((string) ($_SESSION['user_name'] ?? ''));
if ($displayName === '') {
    $displayName = (string) ($_SESSION['user_email'] ?? 'Account');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account settings — Trackify</title>
    <link rel="stylesheet" href="auth.css">
</head>
<body class="auth-page">
    <main class="auth-card auth-card-wide">
        <h1>Account settings</h1>
        <p class="auth-lead">Signed in as <strong><?= h($displayName) ?></strong><?php
            $em = (string) ($_SESSION['user_email'] ?? '');
            if ($em !== '' && $em !== $displayName) {
                echo ' · ' . h($em);
            }
        ?></p>

        <?php if ($success !== ''): ?>
            <div class="auth-success" role="status"><?= h($success) ?></div>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
            <div class="auth-alert" role="alert"><?= h($error) ?></div>
        <?php endif; ?>

        <h2 style="font-size:1rem;margin:1.25rem 0 0.75rem;font-weight:600">Change password</h2>
        <form method="post" action="" class="auth-form" autocomplete="off">
            <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">

            <label for="current_password">Current password</label>
            <input id="current_password" name="current_password" type="password" autocomplete="current-password" required>

            <label for="new_password">New password</label>
            <input id="new_password" name="new_password" type="password" autocomplete="new-password" required minlength="8">

            <label for="new_password_confirm">Confirm new password</label>
            <input id="new_password_confirm" name="new_password_confirm" type="password" autocomplete="new-password" required minlength="8">

            <button type="submit" class="auth-btn" style="margin-top:0.75rem">Update password</button>
        </form>

        <p class="auth-footer" style="margin-top:1.5rem"><a href="panel.php">← Back to dashboard</a></p>
    </main>
</body>
</html>
