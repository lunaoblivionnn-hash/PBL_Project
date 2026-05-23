<?php
session_start();
require '../login/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_mapel     = mysqli_real_escape_string($koneksi, $_POST['id_mapel']);
    
    // 1. TANGKAP ARRAY KELAS DAN JADIKAN JSON
    if (isset($_POST['kelas']) && is_array($_POST['kelas'])) {
        $kelas_array = $_POST['kelas'];
        $kelas_json  = mysqli_real_escape_string($koneksi, json_encode($kelas_array));
        // Ambil kelas pertama untuk redirect URL
        $kelas_redirect = $kelas_array[0]; 
    } else {
        // Fallback jika error/tidak ada yg dicentang
        $kelas_json = '[]';
        $kelas_redirect = isset($_POST['kelas']) && !is_array($_POST['kelas']) ? $_POST['kelas'] : '';
    }
    
    $nama_mapel   = mysqli_real_escape_string($koneksi, $_POST['nama_mapel']);
    $tahun_ajaran = mysqli_real_escape_string($koneksi, $_POST['tahun_ajaran']);
    $deskripsi    = mysqli_real_escape_string($koneksi, $_POST['deskripsi']);
    
    // Tangani Guru
    $id_guru_input = $_POST['id_guru'];
    if (empty($id_guru_input)) {
        $id_guru_sql = "NULL"; 
    } else {
        $id_guru_aman = mysqli_real_escape_string($koneksi, $id_guru_input);
        $id_guru_sql = "'$id_guru_aman'"; 
    }

    // FIX: TAMBAHKAN PEMBARUAN KOLOM 'Kelas'
    $query_update = "UPDATE mapel SET 
                     NamaMapel = '$nama_mapel', 
                     Kelas = '$kelas_json',
                     TahunAjaran = '$tahun_ajaran', 
                     Deskripsi = '$deskripsi', 
                     IDGuru = $id_guru_sql";

    // LOGIKA UPLOAD GAMBAR
    if(isset($_FILES['gambar']['name']) && !empty($_FILES['gambar']['name'])) {
        if ($_FILES['gambar']['error'] !== UPLOAD_ERR_OK) {
            echo "<script>alert('GAGAL UPLOAD: Ukuran gambar terlalu besar atau ada error file! (Maks 2MB)'); window.history.back();</script>";
            exit;
        }

        $nama_file = time() . "_" . preg_replace("/[^a-zA-Z0-9.]/", "_", $_FILES['gambar']['name']);
        $path_tujuan = "../image/mapel/" . $nama_file;
        
        if(move_uploaded_file($_FILES['gambar']['tmp_name'], $path_tujuan)) {
            $query_update .= ", Gambar = '$nama_file'";
        } else {
            echo "<script>alert('GAGAL UPLOAD: Folder tujuan tidak ditemukan! Pastikan folder image/mapel sudah dibuat.'); window.history.back();</script>";
            exit;
        }
    }

    $query_update .= " WHERE IDMapel = '$id_mapel'";

    if(mysqli_query($koneksi, $query_update)) {
        // FIX: Redirect menggunakan kelas pertama yang ditemukan di array
        header("Location: detailKelas.php?kelas=" . urlencode($kelas_redirect) . "&status=sukses_edit");
        exit;
    } else {
        echo "Error Update: " . mysqli_error($koneksi);
    }
} else {
    header("Location: mataPelajaran.php");
}
?>