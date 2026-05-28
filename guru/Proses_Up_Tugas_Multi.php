<?php
session_start();
require '../login/koneksi.php';

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'guru') { exit; }
if($_SERVER['REQUEST_METHOD'] !== 'POST') { exit; }

$id_mapel = mysqli_real_escape_string($koneksi, $_POST['id_mapel']);
$nama_topik = mysqli_real_escape_string($koneksi, $_POST['nama_topik']);
$judul = mysqli_real_escape_string($koneksi, $_POST['judul']);
$deskripsi = mysqli_real_escape_string($koneksi, $_POST['deskripsi']);
$deadline = mysqli_real_escape_string($koneksi, $_POST['deadline']);
$poin = mysqli_real_escape_string($koneksi, $_POST['poin_maksimal']);

$tipe_file = isset($_POST['tipe_file']) ? implode(", ", $_POST['tipe_file']) : 'Semua Jenis File';
$tipe_file = mysqli_real_escape_string($koneksi, $tipe_file);

$kelas_array = $_POST['kelas'] ?? [];
if(empty($kelas_array)) { die("<script>alert('Minimal pilih 1 kelas!'); history.back();</script>"); }

foreach($kelas_array as $kls) {
    $kls = mysqli_real_escape_string($koneksi, $kls);
    
    // Cek Topik (Bab) untuk kelas ini
    $q_topik = mysqli_query($koneksi, "SELECT IDTopik FROM topik_mapel WHERE IDMapel='$id_mapel' AND Kelas='$kls' AND NamaTopik='$nama_topik'");
    if(mysqli_num_rows($q_topik) > 0) {
        $id_topik = mysqli_fetch_assoc($q_topik)['IDTopik'];
    } else {
        $q_urut = mysqli_query($koneksi, "SELECT MAX(Urutan) as max_urut FROM topik_mapel WHERE IDMapel='$id_mapel' AND Kelas='$kls'");
        $urut = (mysqli_fetch_assoc($q_urut)['max_urut'] ?? 0) + 1;
        mysqli_query($koneksi, "INSERT INTO topik_mapel (IDMapel, Kelas, NamaTopik, Urutan) VALUES ('$id_mapel', '$kls', '$nama_topik', $urut)");
        $id_topik = mysqli_insert_id($koneksi); 
    }

    // Gen ID Tugas
    $q_idt = mysqli_query($koneksi, "SELECT IDTugas FROM tugas ORDER BY IDTugas DESC LIMIT 1");
    $d_idt = mysqli_fetch_assoc($q_idt);
    $nomor = $d_idt ? (int)substr($d_idt['IDTugas'], 1) + 1 : 1;
    $idt_baru = "T" . str_pad($nomor, 4, "0", STR_PAD_LEFT);

    mysqli_query($koneksi, "INSERT INTO tugas (IDTugas, IDMapel, IDTopik, Judul, Deskripsi, TipeFileDiizinkan, PoinMaksimal, Deadline, TanggalDibuat) 
                            VALUES ('$idt_baru', '$id_mapel', '$id_topik', '$judul', '$deskripsi', '$tipe_file', '$poin', '$deadline', NOW())");
}

header("Location: guru.php?status=sukses_multi");
exit;
?>