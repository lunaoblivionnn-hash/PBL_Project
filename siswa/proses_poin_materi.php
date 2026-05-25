<?php
session_start();
require '../login/koneksi.php';

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'siswa' || !isset($_POST['id_materi'])){
    exit("0");
}

$id_user = $_SESSION['IDUser'];
$q_siswa = mysqli_query($koneksi, "SELECT IDSiswa FROM siswa WHERE IDUser='$id_user'");
$id_siswa = mysqli_fetch_assoc($q_siswa)['IDSiswa'];

// Ambil Aturan Poin Membaca Materi (A002) dari master_aturan_poin
$q_aturan = mysqli_query($koneksi, "SELECT Poin FROM master_aturan_poin WHERE IDAturan = 'A002'");
$poin_materi = mysqli_fetch_assoc($q_aturan)['Poin'] ?? 20; // Fallback 20 XP

// 1. Update total poin di tabel gamifikasi
$cek_gami = mysqli_query($koneksi, "SELECT IDGamifikasi FROM gamifikasi WHERE IDSiswa = '$id_siswa'");
if(mysqli_num_rows($cek_gami) > 0){
    mysqli_query($koneksi, "UPDATE gamifikasi SET TotalPoint = TotalPoint + $poin_materi WHERE IDSiswa = '$id_siswa'");
} else {
    $idg_baru = "G" . str_pad(rand(100, 9999), 4, "0", STR_PAD_LEFT);
    mysqli_query($koneksi, "INSERT INTO gamifikasi (IDGamifikasi, IDSiswa, IDLevel, TotalPoint) VALUES ('$idg_baru', '$id_siswa', 'L001', $poin_materi)");
}

// 2. Masukkan ke riwayat_poin secara dinamis
$columns_riwayat = [];
$res_cols = mysqli_query($koneksi, "SHOW COLUMNS FROM riwayat_poin");
while($c = mysqli_fetch_assoc($res_cols)) { $columns_riwayat[] = $c['Field']; }

$fields = []; $vals = [];
if(in_array('IDRiwayat', $columns_riwayat)) { 
    $idr = "R" . str_pad(rand(1000, 99999), 5, "0", STR_PAD_LEFT);
    $fields[] = 'IDRiwayat'; $vals[] = "'$idr'"; 
}
if(in_array('IDSiswa', $columns_riwayat)) { $fields[] = 'IDSiswa'; $vals[] = "'$id_siswa'"; }
if(in_array('IDAturan', $columns_riwayat)) { $fields[] = 'IDAturan'; $vals[] = "'A002'"; }
if(in_array('IDPengumpulan', $columns_riwayat)) { $fields[] = 'IDPengumpulan'; $vals[] = "NULL"; } // NULL karena bukan tugas
if(in_array('Poin', $columns_riwayat)) { $fields[] = 'Poin'; $vals[] = "$poin_materi"; }
if(in_array('Tanggal', $columns_riwayat)) { $fields[] = 'Tanggal'; $vals[] = "NOW()"; }

mysqli_query($koneksi, "INSERT INTO riwayat_poin (" . implode(',', $fields) . ") VALUES (" . implode(',', $vals) . ")");

// Kembalikan angka poin ke AJAX JavaScript agar dibaca real-time oleh SweetAlert
echo $poin_materi;
?>