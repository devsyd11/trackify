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

$displayName = $userNavName;
$displayEmail = (string) ($_SESSION['user_email'] ?? '');

dashboard_shell_begin('Account settings', 'settings', $userNavName, $userNavInitial);
?>
                    <h1 class="dashboard-page-title">Account settings</h1>
                    <p class="dashboard-page-lead">Signed in as <strong><?= h($displayName) ?></strong><?php
                        if ($displayEmail !== '' && $displayEmail !== $displayName) {
                            echo ' · ' . h($displayEmail);
                        }
                    ?></p>

                    <?php if ($success !== ''): ?>
                        <div class="settings-success" role="status"><?= h($success) ?></div>
                    <?php endif; ?>
                    <?php if ($error !== ''): ?>
                        <div class="settings-alert" role="alert"><?= h($error) ?></div>
                    <?php endif; ?>

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
<?php
dashboard_shell_end();
