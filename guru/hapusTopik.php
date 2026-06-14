<?php
session_start();
require '../login/koneksi.php';

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'guru'){ exit; }

$id_topik = mysqli_real_escape_string($koneksi, $_GET['id']);
$id_mapel = mysqli_real_escape_string($koneksi, $_GET['id_mapel']);
$kelas = mysqli_real_escape_string($koneksi, $_GET['kelas']);

// Menghapus bab/topik
mysqli_query($koneksi, "DELETE FROM topik_mapel WHERE IDTopik='$id_topik'");

header("Location: kelolaMapel.php?id_mapel=$id_mapel&kelas=" . urlencode($kelas) . "&pesan=topik_dihapus");
?>