<?php
session_start();
require '../login/koneksi.php';

// Pastikan respon berupa JSON agar Javascript bisa membacanya dengan rapi
header('Content-Type: application/json');

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'siswa'){
    echo json_encode(['status' => 'error', 'pesan' => 'Akses ditolak']); exit;
}

$id_user = $_SESSION['IDUser'];
// Hapus kata 'materi_' untuk mendapatkan ID asli (contoh: M0001)
$id_materi = mysqli_real_escape_string($koneksi, str_replace('materi_', '', $_POST['id_materi']));

$q_siswa = mysqli_query($koneksi, "SELECT IDSiswa FROM siswa WHERE IDUser='$id_user'");
$id_siswa = mysqli_fetch_assoc($q_siswa)['IDSiswa'] ?? '';

// CEK ANTI-SPAM: Apakah poin materi ini sudah pernah diklaim siswa ini?
// (Kita gunakan IDPengumpulan untuk menumpang menyimpan IDMateri sebagai penanda unik)
$q_cek = mysqli_query($koneksi, "SELECT IDRiwayat FROM riwayat_poin WHERE IDSiswa='$id_siswa' AND IDPengumpulan='$id_materi'");

if(mysqli_num_rows($q_cek) > 0) {
    // Jika sudah pernah, hentikan proses (Mencegah eksploitasi klik berkali-kali)
    echo json_encode(['status' => 'sudah_klaim']); exit;
}

// Beri poin (Misal 5 XP untuk membaca materi)
$poin_materi = 5; 
$id_aturan = "AT006"; // Sesuaikan dengan ID Aturan "Membaca Materi" di excelmu jika ada

// 1. Update Gamifikasi Siswa
$q_gami = mysqli_query($koneksi, "SELECT IDGamifikasi FROM gamifikasi WHERE IDSiswa='$id_siswa'");
if(mysqli_num_rows($q_gami) > 0) {
    mysqli_query($koneksi, "UPDATE gamifikasi SET TotalPoint = TotalPoint + $poin_materi WHERE IDSiswa='$id_siswa'");
} else {
    $idg = "G" . str_pad(rand(100, 9999), 4, "0", STR_PAD_LEFT);
    mysqli_query($koneksi, "INSERT INTO gamifikasi (IDGamifikasi, IDSiswa, IDLevel, TotalPoint) VALUES ('$idg', '$id_siswa', 'LV001', $poin_materi)");
}

// 2. Catat Riwayat Poin
mysqli_query($koneksi, "INSERT INTO riwayat_poin (IDSiswa, IDAturan, IDPengumpulan, TanggalWaktu) VALUES ('$id_siswa', '$id_aturan', '$id_materi', NOW())");

echo json_encode(['status' => 'sukses', 'poin' => $poin_materi]);
?>