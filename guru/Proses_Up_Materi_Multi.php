<?php
session_start();
require '../login/koneksi.php';

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'guru') { exit; }
if($_SERVER['REQUEST_METHOD'] !== 'POST') { exit; }

$id_mapel = mysqli_real_escape_string($koneksi, $_POST['id_mapel']);
$nama_topik = mysqli_real_escape_string($koneksi, $_POST['nama_topik']);
$judul = mysqli_real_escape_string($koneksi, $_POST['judul']);
$deskripsi = mysqli_real_escape_string($koneksi, $_POST['deskripsi']);
$kelas_array = $_POST['kelas'] ?? [];

if(empty($kelas_array)) {
    die("<script>alert('Minimal pilih 1 kelas!'); history.back();</script>");
}

// 1. Upload Fisik File Menggunakan NAMA ASLI FILE
$filename = "";
if(isset($_FILES['materi_file']) && $_FILES['materi_file']['error'] == 0){
    $filename = str_replace(' ', '_', basename($_FILES['materi_file']['name']));
    move_uploaded_file($_FILES['materi_file']['tmp_name'], "../dokumen_materi/" . $filename);
}

// 2. Looping: Masukkan ke Database Masing-Masing Kelas
foreach($kelas_array as $kls) {
    $kls = mysqli_real_escape_string($koneksi, $kls);
    
    // Cek Topik
    $q_topik = mysqli_query($koneksi, "SELECT IDTopik FROM topik_mapel WHERE IDMapel='$id_mapel' AND Kelas='$kls' AND NamaTopik='$nama_topik'");
    if(mysqli_num_rows($q_topik) > 0) {
        $id_topik = mysqli_fetch_assoc($q_topik)['IDTopik'];
    } else {
        $q_urut = mysqli_query($koneksi, "SELECT MAX(Urutan) as max_urut FROM topik_mapel WHERE IDMapel='$id_mapel' AND Kelas='$kls'");
        $urut = (mysqli_fetch_assoc($q_urut)['max_urut'] ?? 0) + 1;
        mysqli_query($koneksi, "INSERT INTO topik_mapel (IDMapel, Kelas, NamaTopik, Urutan) VALUES ('$id_mapel', '$kls', '$nama_topik', $urut)");
        $id_topik = mysqli_insert_id($koneksi); 
    }

    // Generate ID Materi Baru
    $q_idm = mysqli_query($koneksi, "SELECT IDMateri FROM materi ORDER BY IDMateri DESC LIMIT 1");
    $d_idm = mysqli_fetch_assoc($q_idm);
    $nomor = $d_idm ? (int)substr($d_idm['IDMateri'], 1) + 1 : 1;
    $idm_baru = "M" . str_pad($nomor, 4, "0", STR_PAD_LEFT);

    // Tembakkan ke database
    mysqli_query($koneksi, "INSERT INTO materi (IDMateri, IDMapel, IDTopik, Judul, Deskripsi, Filepath, TanggalUpload) 
                            VALUES ('$idm_baru', '$id_mapel', '$id_topik', '$judul', '$deskripsi', '$filename', NOW())");
}

header("Location: guru.php?status=sukses_multi");
exit;
?>