<?php
session_start();
require '../login/koneksi.php';

// Pastikan hanya admin yang bisa mengakses file ini dan data ID dikirim
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'admin' || !isset($_POST['ids'])) {
    header("Location: daftarGuru.php");
    exit;
}

$ids = $_POST['ids'];

if(is_array($ids) && count($ids) > 0) {
    // 1. Bersihkan semua IDGuru dari input POST untuk mencegah SQL Injection
    $cleaned_ids = array_map(function($id) use ($koneksi) {
        return "'" . mysqli_real_escape_string($koneksi, $id) . "'";
    }, $ids);
    
    $ids_string = implode(",", $cleaned_ids);
    
    // 2. Cari semua IDUser yang terikat dengan IDGuru yang terpilih
    $query_user = mysqli_query($koneksi, "SELECT IDUser FROM guru WHERE IDGuru IN ($ids_string)");
    
    $user_ids = [];
    while($row = mysqli_fetch_assoc($query_user)) {
        if(!empty($row['IDUser'])) {
            $user_ids[] = "'" . mysqli_real_escape_string($koneksi, $row['IDUser']) . "'";
        }
    }
    
    if(count($user_ids) > 0) {
        $user_ids_string = implode(",", $user_ids);
        
        // 3. Matikan foreign key checks sementara agar proses hapus aman tanpa error constraint
        mysqli_query($koneksi, "SET FOREIGN_KEY_CHECKS = 0;");
        
        // 4. Hapus akun login di tabel users dan profil di tabel guru sekaligus
        $hapus_users = mysqli_query($koneksi, "DELETE FROM users WHERE IDUser IN ($user_ids_string)");
        $hapus_guru  = mysqli_query($koneksi, "DELETE FROM guru WHERE IDGuru IN ($ids_string)");
        
        // 5. Hidupkan kembali foreign key checks
        mysqli_query($koneksi, "SET FOREIGN_KEY_CHECKS = 1;");
        
        if($hapus_users && $hapus_guru) {
            // Berhasil, lempar kembali ke daftarGuru.php dengan status sukses
            header("Location: daftarGuru.php?status=sukses_hapus");
            exit;
        }
    }
}

// Jika gagal, lempar kembali dengan status gagal
header("Location: daftarGuru.php?status=gagal_hapus");
exit;
?>