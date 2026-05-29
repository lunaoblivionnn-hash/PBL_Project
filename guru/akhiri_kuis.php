<?php
session_start();
require '../login/koneksi.php';

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'guru') { 
    header("Location: ../login/login.php"); exit; 
}

$id_kuis = mysqli_real_escape_string($koneksi, $_GET['id'] ?? '');
$id_mapel = mysqli_real_escape_string($koneksi, $_GET['mapel'] ?? '');
$kelas = mysqli_real_escape_string($koneksi, $_GET['kelas'] ?? '');

if(!empty($id_kuis)) {
    // Ubah status ujian menjadi 'Draft' agar siswa tidak bisa mengaksesnya lagi
    mysqli_query($koneksi, "UPDATE kuis SET Status = 'Draft' WHERE IDKuis = '$id_kuis'");
}

header("Location: kelolaMapel.php?id_mapel=$id_mapel&kelas=" . urlencode($kelas) . "&status=sukses_tutup");
exit;
?>