<?php
session_start();
require '../login/koneksi.php';

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'guru'){
    header("Location: ../login/login.php");
    exit;
}

if($_SERVER['REQUEST_METHOD'] !== 'POST'){
    header("Location: guru.php");
    exit;
}

$id_mapel  = mysqli_real_escape_string($koneksi, $_POST['id_mapel'] ?? '');
$kelas     = mysqli_real_escape_string($koneksi, $_POST['kelas'] ?? ''); // Tangkap parameter kelas
$judul     = mysqli_real_escape_string($koneksi, trim($_POST['judul'] ?? ''));
$deskripsi = mysqli_real_escape_string($koneksi, trim($_POST['deskripsi'] ?? ''));

if(empty($id_mapel) || empty($judul) || empty($kelas)){
    die("<script>alert('Akses tertolak: Judul, Kelas, dan ID Mapel wajib ada!'); history.back();</script>");
}

if(!isset($_FILES['materi_file']) || $_FILES['materi_file']['error'] !== UPLOAD_ERR_OK){
    $ec = [1=>'File melampaui batas server', 2=>'File terlalu besar', 3=>'Upload terputus', 4=>'Tidak ada file yang dipilih'];
    die("<script>alert('Upload Gagal: ".($ec[$_FILES['materi_file']['error']] ?? 'Error tidak diketahui')."'); history.back();</script>");
}

// 1. GENERATE ID MATERI OTOMATIS (M0001)
$res = mysqli_query($koneksi, "SELECT IDMateri FROM materi ORDER BY IDMateri DESC LIMIT 1");
$d = mysqli_fetch_assoc($res);
$nomor = $d ? (int)substr($d['IDMateri'], 1) + 1 : 1;
$id_baru = "M" . str_pad($nomor, 4, "0", STR_PAD_LEFT);

// 2. VALIDASI KEAMANAN FILE
$file = $_FILES['materi_file'];
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowed = ['pdf','doc','docx','ppt','pptx','xls','xlsx','jpg','jpeg','png','mp4'];

if(!in_array($ext, $allowed)){
    die("<script>alert('GAGAL: Tipe file .$ext tidak diizinkan oleh sistem!'); history.back();</script>");
}

if($file['size'] > 50 * 1024 * 1024){
    die("<script>alert('GAGAL: Ukuran file melebihi batas 50MB!'); history.back();</script>");
}

// 3. PERSIAPAN FOLDER PENAMPUNG
// Kita simpan di folder 'dokumen_materi' di luar agar lebih rapi dari gambar
$dir = "../dokumen_materi/";
if(!is_dir($dir)){
    mkdir($dir, 0777, true); // Buat folder otomatis jika belum ada
}

// 4. PINDAHKAN FILE & SIMPAN KE DATABASE
$fname = $id_baru . "_" . time() . "." . $ext;
if(move_uploaded_file($file['tmp_name'], $dir . $fname)){
    
    // Simpan ke database
    $id_topik = mysqli_real_escape_string($koneksi, $_POST['id_topik'] ?? 'NULL');

    $sql="INSERT INTO materi(IDMateri, IDMapel, IDTopik, Judul, Deskripsi, Filepath, TipeFile, TanggalUpload) 
        VALUES('$id_baru', '$id_mapel', '$id_topik', '$judul', '$deskripsi', '$fname', '$ext', NOW())";
                
    if(mysqli_query($koneksi, $sql)){
        // KEMBALI KE HALAMAN KELOLA MAPEL DENGAN PARAMETER LENGKAP
        header("Location: kelolaMapel.php?id_mapel=" . urlencode($id_mapel) . "&kelas=" . urlencode($kelas) . "&status=sukses_materi");
        exit;
    } else {
        // Hapus file fisik jika query database gagal
        if(file_exists($dir . $fname)) { unlink($dir . $fname); }
        die("Error Sistem Database: " . mysqli_error($koneksi));
    }
} else {
    die("<script>alert('Gagal memindahkan file ke server. Pastikan permission folder XAMPP diizinkan.'); history.back();</script>");
}
?>