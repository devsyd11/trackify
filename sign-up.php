<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: panel.php');
    exit;
}

$error = '';
$fullName = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['_csrf'] ?? null)) {
        $error = 'Invalid session. Please try again.';
    } else {
        $fullName = trim((string) ($_POST['full_name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');

        if ($fullName === '' || mb_strlen($fullName) < 2) {
            $error = 'Please enter your full name (at least 2 characters).';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } elseif (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters.';
        } elseif ($password !== $passwordConfirm) {
            $error = 'Passwords do not match.';
        } else {
            try {
                $pdo = db();
                $stmt = $pdo->prepare(
                    'INSERT INTO users (full_name, email, password_hash) VALUES (:full_name, :email, :password_hash)'
                );
                $stmt->execute([
                    ':full_name' => $fullName,
                    ':email' => $email,
                    ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
                ]);
                $_SESSION['user_id'] = (int) $pdo->lastInsertId();
                $_SESSION['user_email'] = $email;
                $_SESSION['user_name'] = $fullName;
                header('Location: panel.php');
                exit;
            } catch (PDOException $e) {
                if ((int) $e->errorInfo[1] === 1062) {
                    $error = 'An account with this email already exists.';
                } else {
                    $error = 'Could not create account. Check database configuration.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign up — Trackify</title>
    <link rel="stylesheet" href="auth.css">
</head>
<body class="auth-page">
    <main class="auth-card">
        <div class="auth-brand">
            <img src="logos/trackify_logo.png" width="280" height="80" alt="Trackify">
        </div>
        <h1>Create account</h1>
        <p class="auth-lead">Full name, email, and a secure password.</p>

        <?php if ($error !== ''): ?>
            <div class="auth-alert" role="alert"><?= h($error) ?></div>
        <?php endif; ?>

        <form method="post" action="" class="auth-form" novalidate>
            <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">

            <label for="full_name">Full name</label>
            <input id="full_name" name="full_name" type="text" autocomplete="name" required
                   value="<?= h($fullName) ?>">

            <label for="email">Email</label>
            <input id="email" name="email" type="email" autocomplete="email" required
                   value="<?= h($email) ?>">

            <label for="password">Password</label>
            <input id="password" name="password" type="password" autocomplete="new-password" required minlength="8">

            <label for="password_confirm">Confirm password</label>
            <input id="password_confirm" name="password_confirm" type="password" autocomplete="new-password" required minlength="8">

            <button type="submit" class="auth-btn">Sign up</button>
        </form>

        <p class="auth-footer">Already have an account? <a href="sign-in.php">Sign in</a></p>
    </main>
</body>
</html>
