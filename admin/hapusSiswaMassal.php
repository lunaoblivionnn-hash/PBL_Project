<?php
session_start();
require '../login/koneksi.php';

if ($_SESSION['role'] != 'admin' || !isset($_POST['ids'])) {
    header("Location: daftarSiswa.php");
    exit;
}

$ids = $_POST['ids']; // Ini berisi array ID Siswa
$berhasil = 0;

foreach ($ids as $id) {
    $id_siswa = mysqli_real_escape_string($koneksi, $id);

    // 1. Ambil IDUser agar akun loginnya ikut terhapus
    $query_user = mysqli_query($koneksi, "SELECT IDUser FROM SISWA WHERE IDSiswa = '$id_siswa'");
    $data = mysqli_fetch_assoc($query_user);

    if ($data) {
        $id_user = $data['IDUser'];
        
        // 2. Hapus dari tabel SISWA
        if (mysqli_query($koneksi, "DELETE FROM SISWA WHERE IDSiswa = '$id_siswa'")) {
            // 3. Hapus dari tabel USERS
            mysqli_query($koneksi, "DELETE FROM USERS WHERE IDUser = '$id_user'");
            $berhasil++;
        }
    }
}

// Lempar balik dengan informasi jumlah yang terhapus
header("Location: daftarSiswa.php?status=sukses_hapus_massal&jml=$berhasil");
exit;