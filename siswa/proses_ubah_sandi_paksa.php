<?php
session_start();
require '../login/koneksi.php';

// Set header agar output berupa JSON murni yang bisa dibaca JavaScript
header('Content-Type: application/json');

if(!isset($_SESSION['IDUser']) || !isset($_POST['password_baru'])) {
    echo json_encode(['status' => 'gagal', 'pesan' => 'Akses tidak sah.']);
    exit;
}

$id_user = $_SESSION['IDUser'];
$password_baru = mysqli_real_escape_string($koneksi, $_POST['password_baru']);

// Menyimpan teks polos tanpa hash dan mematikan penanda kolom WajibUbahPassword menjadi 0
$query_update = mysqli_query($koneksi, "UPDATE users SET Password = '$password_baru', WajibUbahPassword = 0 WHERE IDUser = '$id_user'");

if($query_update) {
    echo json_encode(['status' => 'sukses']);
} else {
    echo json_encode(['status' => 'gagal', 'pesan' => mysqli_error($koneksi)]);
}
exit;
?>