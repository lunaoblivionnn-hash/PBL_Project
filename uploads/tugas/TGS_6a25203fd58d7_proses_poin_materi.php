<?php
session_start();
require '../login/koneksi.php';

header('Content-Type: application/json');

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'siswa'){
    echo json_encode(['status' => 'error', 'pesan' => 'Akses ditolak']); exit;
}

$id_user = $_SESSION['IDUser'];
// Hapus prefix 'materi_' untuk dapatkan ID Asli
$id_materi = mysqli_real_escape_string($koneksi, str_replace('materi_', '', $_POST['id_materi']));

$q_siswa = mysqli_query($koneksi, "SELECT IDSiswa FROM siswa WHERE IDUser='$id_user'");
$id_siswa = mysqli_fetch_assoc($q_siswa)['IDSiswa'] ?? '';

// CEK ANTI-SPAM: Apakah materi ini sudah pernah diklaim?
$q_cek = mysqli_query($koneksi, "SELECT IDRiwayat FROM riwayat_poin WHERE IDSiswa='$id_siswa' AND IDPengumpulan='$id_materi'");
if(mysqli_num_rows($q_cek) > 0) {
    echo json_encode(['status' => 'sudah_klaim']); exit;
}

// 1. TARIK ATURAN DARI DATABASE (AT001 = Baca Materi)
$id_aturan = "AT001"; 
$q_aturan = mysqli_query($koneksi, "SELECT BesaranPoin FROM master_aturan_poin WHERE IDAturan='$id_aturan'");
$poin_materi = mysqli_fetch_assoc($q_aturan)['BesaranPoin'] ?? 20;

// 2. CATAT KE RIWAYAT (Wajib dicatat agar tidak bisa di-klik 2 kali)
mysqli_query($koneksi, "INSERT INTO riwayat_poin (IDSiswa, IDAturan, IDPengumpulan, TanggalWaktu) VALUES ('$id_siswa', '$id_aturan', '$id_materi', NOW())");

// 3. TAMBAHKAN KE TOTAL XP
$q_gami = mysqli_query($koneksi, "SELECT IDGamifikasi FROM gamifikasi WHERE IDSiswa='$id_siswa'");
if(mysqli_num_rows($q_gami) > 0) {
    mysqli_query($koneksi, "UPDATE gamifikasi SET TotalPoint = TotalPoint + $poin_materi WHERE IDSiswa='$id_siswa'");
} else {
    $idg = "G" . str_pad(rand(100, 9999), 4, "0", STR_PAD_LEFT);
    mysqli_query($koneksi, "INSERT INTO gamifikasi (IDGamifikasi, IDSiswa, IDLevel, TotalPoint) VALUES ('$idg', '$id_siswa', 'LV001', $poin_materi)");
}

echo json_encode(['status' => 'sukses', 'poin' => $poin_materi]);
?>