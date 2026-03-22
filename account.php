<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: panel.html');
} else {
    header('Location: sign-in.php');
}
exit;
