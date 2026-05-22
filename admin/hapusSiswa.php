<?php
session_start();
require '../login/koneksi.php';

// Pastikan hanya admin yang bisa melakukan eksekusi ini
if($_SESSION['role'] != 'admin'){
    header("Location: ../login/login.php");
    exit;
}

// Cek apakah ada ID yang dikirim dari URL
if(isset($_GET['id'])) {
    $id_siswa = mysqli_real_escape_string($koneksi, $_GET['id']);

    // 1. Cari IDUser yang menempel pada siswa ini terlebih dahulu
    $query_cek = mysqli_query($koneksi, "SELECT IDUser FROM SISWA WHERE IDSiswa = '$id_siswa'");
    $data = mysqli_fetch_assoc($query_cek);
    
    if($data) {
        $id_user = $data['IDUser'];

        // 2. Hapus data dari tabel SISWA (Profilnya)
        $hapus_siswa = mysqli_query($koneksi, "DELETE FROM SISWA WHERE IDSiswa = '$id_siswa'");

        // 3. Jika profil berhasil dihapus, hapus juga dari tabel USERS (Akun Loginnya)
        if($hapus_siswa) {
            mysqli_query($koneksi, "DELETE FROM USERS WHERE IDUser = '$id_user'");
            
            // Lemparkan kembali ke halaman daftarSiswa dengan status sukses
            header("Location: daftarSiswa.php?status=sukses_hapus");
            exit;
        } else {
            header("Location: daftarSiswa.php?status=gagal_hapus");
            exit;
        }
    } else {
        header("Location: daftarSiswa.php?status=tidak_ditemukan");
        exit;
    }
} else {
    header("Location: daftarSiswa.php");
}
?>