<?php
session_start();
require '../login/koneksi.php';

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'guru'){ header("Location: ../login/login.php"); exit; }
if($_SERVER['REQUEST_METHOD'] !== 'POST'){ header("Location: guru.php"); exit; }

$id_tugas  = mysqli_real_escape_string($koneksi, $_POST['id_tugas'] ?? '');
$id_mapel  = mysqli_real_escape_string($koneksi, $_POST['id_mapel'] ?? '');
$kelas     = mysqli_real_escape_string($koneksi, $_POST['kelas'] ?? ''); 
$judul     = mysqli_real_escape_string($koneksi, trim($_POST['judul'] ?? ''));
$deskripsi = mysqli_real_escape_string($koneksi, trim($_POST['deskripsi'] ?? ''));
$deadline  = $_POST['deadline'] ?? '';
$poin      = max(10, min(1000, (int)($_POST['poin_maksimal'] ?? 100)));

// Tangkap Array Checkbox Tipe File dan gabungkan (Contoh: "PDF, Word, Google Doc (Link)")
$tipe_file_arr = isset($_POST['tipe_file']) ? $_POST['tipe_file'] : ['Bebas (Semua File)'];
$tipe_file_str = mysqli_real_escape_string($koneksi, implode(', ', $tipe_file_arr));

if(empty($id_tugas) || empty($id_mapel) || empty($judul) || empty($deadline) || empty($kelas)){
    die("<script>alert('Akses tertolak: Data tidak lengkap!'); history.back();</script>");
}

$dl_mysql = date('Y-m-d H:i:s', strtotime($deadline));

// Eksekusi Update
$sql = "UPDATE tugas SET 
            Judul = '$judul', 
            Deskripsi = '$deskripsi', 
            Deadline = '$dl_mysql', 
            TipeFileDiizinkan = '$tipe_file_str', 
            PoinMaksimal = $poin 
        WHERE IDTugas = '$id_tugas'";

if(mysqli_query($koneksi, $sql)){
    header("Location: kelolaMapel.php?id_mapel=" . urlencode($id_mapel) . "&kelas=" . urlencode($kelas) . "&status=sukses_edit");
    exit;
} else {
    die("Error Database: " . mysqli_error($koneksi));
}
?>