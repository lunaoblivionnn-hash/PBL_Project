<?php
$host = "127.0.0.1";
$user = "root";
$pass = "balqistnayla";
$db   = "lms_wongsorejo";

$koneksi = mysqli_connect($host, $user, $pass, $db);

if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}
?>