<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: panel.php');
} else {
    header('Location: sign-in.php');
}
exit;
