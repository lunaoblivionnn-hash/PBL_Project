<?php
session_start();
require '../login/koneksi.php';

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'guru'){ header("Location: ../login/login.php"); exit; }
if($_SERVER['REQUEST_METHOD'] !== 'POST'){ header("Location: guru.php"); exit; }

$id_mapel  = mysqli_real_escape_string($koneksi, $_POST['id_mapel'] ?? '');
$kelas     = mysqli_real_escape_string($koneksi, $_POST['kelas'] ?? ''); 
$id_topik  = mysqli_real_escape_string($koneksi, $_POST['id_topik'] ?? 'NULL'); 
$judul     = mysqli_real_escape_string($koneksi, trim($_POST['judul'] ?? ''));
$deskripsi = mysqli_real_escape_string($koneksi, trim($_POST['deskripsi'] ?? ''));
$deadline  = $_POST['deadline'] ?? '';
$poin      = max(10, min(1000, (int)($_POST['poin_maksimal'] ?? 100)));

// Tangkap Array Checkbox Tipe File dan gabungkan menjadi string (Contoh: "PDF, Word/Excel")
$tipe_file_arr = isset($_POST['tipe_file']) ? $_POST['tipe_file'] : ['Bebas (Semua File)'];
$tipe_file_str = mysqli_real_escape_string($koneksi, implode(', ', $tipe_file_arr));

if(empty($id_mapel) || empty($judul) || empty($deadline) || empty($kelas)){
    die("<script>alert('Akses tertolak: Judul, Kelas, Deadline, dan ID Mapel wajib ada!'); history.back();</script>");
}

// Generate ID Tugas
$res = mysqli_query($koneksi, "SELECT IDTugas FROM tugas ORDER BY IDTugas DESC LIMIT 1");
$d = mysqli_fetch_assoc($res);
$nomor = $d ? (int)substr($d['IDTugas'], 1) + 1 : 1;
$id_baru = "T" . str_pad($nomor, 4, "0", STR_PAD_LEFT);
$dl_mysql = date('Y-m-d H:i:s', strtotime($deadline));

// Eksekusi Simpan dengan TanggalDibuat (NOW) dan TipeFileDiizinkan
$sql = "INSERT INTO tugas(IDTugas, IDMapel, IDTopik, Judul, Deskripsi, TanggalDibuat, Deadline, TipeFileDiizinkan, PoinMaksimal) 
        VALUES('$id_baru', '$id_mapel', '$id_topik', '$judul', '$deskripsi', NOW(), '$dl_mysql', '$tipe_file_str', $poin)";

if(mysqli_query($koneksi, $sql)){
    // Notifikasi ke siswa
    $res_siswa = mysqli_query($koneksi, "SELECT u.IDUser FROM users u JOIN siswa s ON u.IDUser=s.IDUser WHERE u.Status='Aktif'");
    while($s = mysqli_fetch_assoc($res_siswa)){
        $jn = "Tugas Baru: $judul";
        $pm = "Guru memberikan tugas baru. Deadline: " . date('d M Y H:i', strtotime($deadline));
        mysqli_query($koneksi, "INSERT INTO notifikasi(IDUser, JudulNotif, Pesan, IsRead, CreatedAt) VALUES('{$s['IDUser']}', '$jn', '$pm', 0, NOW())");
    }
    header("Location: kelolaMapel.php?id_mapel=" . urlencode($id_mapel) . "&kelas=" . urlencode($kelas) . "&status=sukses_tugas");
    exit;
} else {
    die("Error Database: " . mysqli_error($koneksi));
}
?>