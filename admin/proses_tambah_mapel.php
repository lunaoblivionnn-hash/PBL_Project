<?php
session_start();
require '../login/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_mapel   = mysqli_real_escape_string($koneksi, $_POST['nama_mapel']);
    $tahun_ajaran = mysqli_real_escape_string($koneksi, $_POST['tahun_ajaran']);
    $deskripsi    = mysqli_real_escape_string($koneksi, $_POST['deskripsi']);

    // Validasi: Pastikan admin mencentang minimal 1 kelas
    if (!isset($_POST['kelas']) || empty($_POST['kelas'])) {
        echo "<script>alert('GAGAL: Anda harus mencentang minimal satu kelas!'); window.history.back();</script>";
        exit;
    }
    
    // 1. Tangkap array checkbox kelas dari form dan langsung jadikan teks JSON
    $kelas_array = $_POST['kelas']; 
    $kelas_json  = mysqli_real_escape_string($koneksi, json_encode($kelas_array));

    // 2. Penanganan Guru (Bisa NULL jika dikosongkan)
    $id_guru_input = $_POST['id_guru'];
    if (empty($id_guru_input)) {
        $id_guru_sql = "NULL"; 
    } else {
        $id_guru_aman = mysqli_real_escape_string($koneksi, $id_guru_input);
        $id_guru_sql  = "'$id_guru_aman'"; 
    }

    // 3. PROSES UPLOAD GAMBAR (Hanya diupload 1x)
    $nama_file = "";
    if(isset($_FILES['gambar']['name']) && !empty($_FILES['gambar']['name'])) {
        $nama_file = time() . "_" . preg_replace("/[^a-zA-Z0-9.]/", "_", $_FILES['gambar']['name']);
        move_uploaded_file($_FILES['gambar']['tmp_name'], "../image/mapel/" . $nama_file);
    }

    // 4. Generator ID Mapel (MP001) - Cukup dijalankan 1x di luar loop
    $angka = 1;
    while(true) {
        $id_mapel = "MP" . sprintf("%03d", $angka); 
        if(mysqli_num_rows(mysqli_query($koneksi, "SELECT IDMapel FROM mapel WHERE IDMapel = '$id_mapel'")) == 0) break; 
        $angka++;
    }

    // 5. EKSEKUSI INSERT - Cukup 1 KALI saja ke database menggunakan data JSON
    $query = "INSERT INTO mapel (IDMapel, IDGuru, Kelas, TahunAjaran, NamaMapel, Deskripsi, Gambar) 
              VALUES ('$id_mapel', $id_guru_sql, '$kelas_json', '$tahun_ajaran', '$nama_mapel', '$deskripsi', '$nama_file')";
    
    if(mysqli_query($koneksi, $query)) {
        // Jika sukses, kembalikan ke halaman menu mapel
        header("Location: mataPelajaran.php?status=sukses_tambah");
        exit;
    } else {
        echo "Gagal menyimpan data mata pelajaran: " . mysqli_error($koneksi);
    }
} else {
    header("Location: mataPelajaran.php");
    exit;
}
?>