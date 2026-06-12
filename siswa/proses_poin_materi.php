<?php
// PASTIKAN TIDAK ADA SATUPUN SPASI ATAU BARIS KOSONG SEBELUM TAG <?php INI
ob_start();
session_start();
require '../login/koneksi.php';

// Fungsi untuk memastikan respon HANYA JSON
function json_die($status, $pesan = '', $poin = 0) {
    ob_end_clean(); // Hapus semua sampah output sebelumnya
    header('Content-Type: application/json');
    echo json_encode(['status' => $status, 'pesan' => $pesan, 'poin' => $poin]);
    exit;
}

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'siswa'){
    json_die('error', 'Akses ditolak');
}

$id_user = $_SESSION['IDUser'];
$id_materi = mysqli_real_escape_string($koneksi, str_replace('materi_', '', $_POST['id_materi']));

$q_siswa = mysqli_query($koneksi, "SELECT IDSiswa FROM siswa WHERE IDUser='$id_user'");
$id_siswa = mysqli_fetch_assoc($q_siswa)['IDSiswa'] ?? '';

// CEK ANTI-SPAM
$q_cek = mysqli_query($koneksi, "SELECT IDRiwayat FROM riwayat_poin WHERE IDSiswa='$id_siswa' AND IDPengumpulan='$id_materi'");
if(mysqli_num_rows($q_cek) > 0) {
    json_die('sudah_klaim', 'Sudah diklaim');
}

// AMBIL POIN (AT001 = Baca Materi)
$q_aturan = mysqli_query($koneksi, "SELECT BesaranPoin FROM master_aturan_poin WHERE IDAturan='AT001'");
$poin_materi = (int)(mysqli_fetch_assoc($q_aturan)['BesaranPoin'] ?? 20);

// CATAT RIWAYAT
mysqli_query($koneksi, "INSERT INTO riwayat_poin (IDSiswa, IDAturan, IDPengumpulan, TanggalWaktu) VALUES ('$id_siswa', 'AT001', '$id_materi', NOW())");

// UPDATE GAMIFIKASI
mysqli_query($koneksi, "UPDATE gamifikasi SET TotalPoint = TotalPoint + $poin_materi WHERE IDSiswa = '$id_siswa'");

json_die('sukses', 'XP Berhasil!', $poin_materi);
?>