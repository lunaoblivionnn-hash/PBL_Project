<?php
session_start();
require '../login/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['kelas_target'])) {
    
    $kelas_target = mysqli_real_escape_string($koneksi, $_POST['kelas_target']);
    $count_updated = 0;

    // Cek apakah ada siswa yang dicentang oleh admin
    if (isset($_POST['selected_siswa']) && is_array($_POST['selected_siswa'])) {
        
        foreach ($_POST['selected_siswa'] as $id_siswa) {
            $id_siswa_aman = mysqli_real_escape_string($koneksi, $id_siswa);
            
            // 1. CEK DULU KELAS SISWA SAAT INI
            $cek_kelas = mysqli_query($koneksi, "SELECT Kelas FROM siswa WHERE IDSiswa = '$id_siswa_aman'");
            $data_kelas = mysqli_fetch_assoc($cek_kelas);

            // 2. JIKA KELASNYA SUDAH SAMA DENGAN TARGET, ABAIKAN & LEWATI (SKIP)
            if ($data_kelas['Kelas'] == $kelas_target) {
                continue; 
            }
            
            // 3. JIKA BERBEDA, BARU EKSEKUSI PEMINDAHAN KELAS
            $query_update = "UPDATE siswa SET Kelas = '$kelas_target' WHERE IDSiswa = '$id_siswa_aman'";
            
            if (mysqli_query($koneksi, $query_update)) {
                $count_updated++; // Angka hanya bertambah jika benar-benar ada yang dipindah
            }
        }
    }

    // Kembalikan ke halaman mata pelajaran dengan sinyal sukses
    header("Location: mataPelajaran.php?status=sukses_tambah_siswa&jumlah=" . $count_updated . "&kelas=" . urlencode($kelas_target));
    exit;

} else {
    header("Location: mataPelajaran.php");
    exit;
}
?>