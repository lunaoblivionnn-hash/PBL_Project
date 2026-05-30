<?php
session_start();
require '../login/koneksi.php';

// 1. Validasi: Pastikan yang mengakses adalah GURU
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'guru'){ 
    header("Location: ../login/login.php"); 
    exit; 
}

if($_SERVER['REQUEST_METHOD'] !== 'POST'){ 
    header("Location: guru.php"); 
    exit; 
}

// 2. Tangkap data dari form modal kelolaMapel.php
$id_mapel  = mysqli_real_escape_string($koneksi, $_POST['id_mapel'] ?? '');
$kelas     = mysqli_real_escape_string($koneksi, $_POST['kelas'] ?? '');
$id_topik  = mysqli_real_escape_string($koneksi, $_POST['id_topik'] ?? '');
$judul     = mysqli_real_escape_string($koneksi, trim($_POST['judul'] ?? ''));
$deskripsi = mysqli_real_escape_string($koneksi, trim($_POST['deskripsi'] ?? ''));
$deadline  = mysqli_real_escape_string($koneksi, $_POST['deadline'] ?? '');
$poin      = (int)($_POST['poin_maksimal'] ?? 100);

// Gabungkan checkbox tipe file menjadi satu string
$tipe_file = isset($_POST['tipe_file']) ? implode(", ", $_POST['tipe_file']) : 'Semua Jenis File';
$tipe_file = mysqli_real_escape_string($koneksi, $tipe_file);

// 3. Generate ID Tugas Baru (Contoh: T0001)
$q_idt = mysqli_query($koneksi, "SELECT IDTugas FROM tugas ORDER BY IDTugas DESC LIMIT 1");
$d_idt = mysqli_fetch_assoc($q_idt);
$nomor = $d_idt ? (int)substr($d_idt['IDTugas'], 1) + 1 : 1;
$id_baru = "T" . str_pad($nomor, 4, "0", STR_PAD_LEFT);

// 4. Masukkan struktur tugas ke Database
$sql = "INSERT INTO tugas (IDTugas, IDMapel, IDTopik, Judul, Deskripsi, Deadline, PoinMaksimal, TipeFileDiizinkan, TanggalDibuat)
        VALUES ('$id_baru', '$id_mapel', '$id_topik', '$judul', '$deskripsi', '$deadline', $poin, '$tipe_file', NOW())";

if(mysqli_query($koneksi, $sql)) {
    // 5. Kembali ke halaman Mapel dengan notifikasi sukses
    header("Location: kelolaMapel.php?id_mapel=$id_mapel&kelas=" . urlencode($kelas) . "&status=sukses_tugas");
    exit;
} else {
    die("Error Database: " . mysqli_error($koneksi));
}
?>