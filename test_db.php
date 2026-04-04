<?php
$host = "localhost";
$user = "root";        // Use the user you just created
$pass = "";    // Use the password you just set
$db   = "trackify_auth";    // Use the name created by your SQL file

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
