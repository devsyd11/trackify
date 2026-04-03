<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: panel.php');
    exit;
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['_csrf'] ?? null)) {
        $error = 'Invalid session. Please try again.';
    } else {
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } elseif ($password === '') {
            $error = 'Please enter your password.';
        } else {
            try {
                $pdo = db();
                $stmt = $pdo->prepare('SELECT id, full_name, email, password_hash FROM users WHERE email = :email LIMIT 1');
                $stmt->execute([':email' => $email]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password_hash'])) {
                    $_SESSION['user_id'] = (int) $user['id'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_name'] = $user['full_name'];
                    header('Location: panel.php');
                    exit;
                }
                $error = 'Invalid email or password.';
            } catch (PDOException $e) {
                $error = 'Could not sign in. Check database configuration.';
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
    <title>Sign in — Trackify</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="auth.css">
</head>
<body class="auth-page">
    <div class="auth-page-inner">
        <main class="auth-card">
            <header class="auth-header">
                <div class="auth-brand">
                    <img src="logos/trackify_logo.png" width="280" height="80" alt="Trackify">
                </div>
                <h1>Sign in</h1>
                <p class="auth-lead">Use the email and password you registered with.</p>
            </header>

            <?php if ($error !== ''): ?>
                <div class="auth-alert" role="alert"><?= h($error) ?></div>
            <?php endif; ?>

            <form method="post" action="" class="auth-form" novalidate>
                <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">

                <div class="auth-field">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" autocomplete="email" required placeholder="you@example.com"
                           value="<?= h($email) ?>">
                </div>

                <div class="auth-field">
                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" autocomplete="current-password" required placeholder="••••••••">
                </div>

                <button type="submit" class="auth-btn">Sign in</button>
            </form>

            <div class="auth-divider">
                <p class="auth-footer">No account? <a href="sign-up.php">Create one</a></p>
            </div>
        </main>
    </div>
</body>
</html>
