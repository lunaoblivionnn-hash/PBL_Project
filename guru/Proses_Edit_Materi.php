<?php
session_start();
require '../login/koneksi.php';

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'guru'){ exit; }

$id_mapel = mysqli_real_escape_string($koneksi, $_POST['id_mapel']);
$kelas = mysqli_real_escape_string($koneksi, $_POST['kelas']);
$id_materi = mysqli_real_escape_string($koneksi, $_POST['id_materi']);
$judul = mysqli_real_escape_string($koneksi, $_POST['judul']);
$deskripsi = mysqli_real_escape_string($koneksi, $_POST['deskripsi']);

// Jika guru upload file baru
if(isset($_FILES['materi_file']) && $_FILES['materi_file']['error'] == 0) {
    $nama_file = $_FILES['materi_file']['name'];
    $tmp_file = $_FILES['materi_file']['tmp_name'];
    $nama_file_baru = "MATERI_" . time() . "_" . rand(10,99) . "_" . preg_replace("/[^a-zA-Z0-9.-]/", "_", $nama_file);
    
    // Hapus file lama (Opsional, jika ingin menghemat penyimpanan)
    $q_lama = mysqli_query($koneksi, "SELECT Filepath FROM materi WHERE IDMateri='$id_materi'");
    $file_lama = mysqli_fetch_assoc($q_lama)['Filepath'];
    if(file_exists("../dokumen_materi/".$file_lama)) { unlink("../dokumen_materi/".$file_lama); }

    move_uploaded_file($tmp_file, "../dokumen_materi/".$nama_file_baru);
    mysqli_query($koneksi, "UPDATE materi SET Judul='$judul', Deskripsi='$deskripsi', Filepath='$nama_file_baru' WHERE IDMateri='$id_materi'");
} else {
    // Jika hanya ganti judul/deskripsi
    mysqli_query($koneksi, "UPDATE materi SET Judul='$judul', Deskripsi='$deskripsi' WHERE IDMateri='$id_materi'");
}

header("Location: kelolaMapel.php?id_mapel=$id_mapel&kelas=" . urlencode($kelas) . "&pesan=materi_diedit");
?>