<?php
session_start();
require '../login/koneksi.php';

if($_SESSION['role'] != 'admin'){
    header("Location: ../login/login.php");
    exit;
}

if(isset($_GET['id']) && isset($_GET['kelas'])) {
    $id_mapel = mysqli_real_escape_string($koneksi, $_GET['id']);
    $kelas = $_GET['kelas'];

    // Hapus dari database
    $query = "DELETE FROM mapel WHERE IDMapel = '$id_mapel'";
    
    if(mysqli_query($koneksi, $query)) {
        // Lempar kembali ke detail kelas dengan notif sukses
        header("Location: detailKelas.php?kelas=" . urlencode($kelas) . "&status=sukses_hapus");
        exit;
    } else {
        echo "Gagal menghapus mata pelajaran: " . mysqli_error($koneksi);
    }
} else {
    header("Location: mataPelajaran.php");
}
?>