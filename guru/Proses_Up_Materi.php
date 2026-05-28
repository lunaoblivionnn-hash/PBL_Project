<?php
session_start();
require '../login/koneksi.php';

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'guru'){
    header("Location: ../login/login.php"); exit;
}
if($_SERVER['REQUEST_METHOD'] !== 'POST'){
    header("Location: guru.php"); exit;
}

$id_mapel  = mysqli_real_escape_string($koneksi, $_POST['id_mapel'] ?? '');
$kelas     = mysqli_real_escape_string($koneksi, $_POST['kelas'] ?? '');
$id_topik  = mysqli_real_escape_string($koneksi, $_POST['id_topik'] ?? 'NULL');
$judul     = mysqli_real_escape_string($koneksi, trim($_POST['judul'] ?? ''));
$deskripsi = mysqli_real_escape_string($koneksi, trim($_POST['deskripsi'] ?? ''));

if(empty($id_mapel) || empty($judul) || empty($kelas)){
    die("<script>alert('Akses tertolak: Judul, Kelas, dan ID Mapel wajib ada!'); history.back();</script>");
}

if(!isset($_FILES['materi_file']) || $_FILES['materi_file']['error'] !== UPLOAD_ERR_OK){
    die("<script>alert('Upload Gagal, file tidak terbaca!'); history.back();</script>");
}

$file = $_FILES['materi_file'];
if($file['size'] > 50 * 1024 * 1024){
    die("<script>alert('GAGAL: Ukuran melebihi batas 50MB!'); history.back();</script>");
}

$dir = "../dokumen_materi/";
if(!is_dir($dir)){ mkdir($dir, 0777, true); }

// ==========================================
// PENAMAAN FILE ASLI MURNI
// ==========================================
$fname = str_replace(' ', '_', basename($file['name']));
$ext = pathinfo($fname, PATHINFO_EXTENSION);

if(move_uploaded_file($file['tmp_name'], $dir . $fname)){
    
    // Generate ID Materi Baru
    $q_id = mysqli_query($koneksi, "SELECT IDMateri FROM materi ORDER BY IDMateri DESC LIMIT 1");
    $d_id = mysqli_fetch_assoc($q_id);
    $nomor = $d_id ? (int)substr($d_id['IDMateri'], 1) + 1 : 1;
    $id_baru = "M" . str_pad($nomor, 4, "0", STR_PAD_LEFT);

    // Simpan ke DB dengan nama asli
    $sql="INSERT INTO materi(IDMateri, IDMapel, IDTopik, Judul, Deskripsi, Filepath, TipeFile, TanggalUpload) 
          VALUES('$id_baru', '$id_mapel', '$id_topik', '$judul', '$deskripsi', '$fname', '$ext', NOW())";
                
    if(mysqli_query($koneksi, $sql)){
        header("Location: kelolaMapel.php?id_mapel=$id_mapel&kelas=".urlencode($kelas)."&status=sukses"); exit;
    } else {
        die("Error Database: " . mysqli_error($koneksi));
    }
} else {
    die("<script>alert('GAGAL memindahkan file ke server!'); history.back();</script>");
}
?>