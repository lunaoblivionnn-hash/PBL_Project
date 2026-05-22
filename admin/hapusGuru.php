<?php
session_start();
require '../login/koneksi.php';

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'admin' || !isset($_GET['id'])) {
    header("Location: admin.php");
    exit;
}

$id_guru = mysqli_real_escape_string($koneksi, $_GET['id']);

// Cari IDUser-nya terlebih dahulu agar bisa menghapus akun utamanya
$query_guru = mysqli_query($koneksi, "SELECT IDUser FROM guru WHERE IDGuru = '$id_guru'");
if(mysqli_num_rows($query_guru) > 0) {
    $data = mysqli_fetch_assoc($query_guru);
    $id_user = $data['IDUser'];
    
    // Hapus dari tabel users (Otomatis menghapus data di tabel guru karena foreign key CASCADE)
    mysqli_query($koneksi, "DELETE FROM users WHERE IDUser = '$id_user'");
    header("Location: admin.php?status=sukses_hapus_guru");
    exit;
} else {
    header("Location: admin.php?status=gagal_hapus");
    exit;
}
?>