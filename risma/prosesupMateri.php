<?php
session_start();
require '../login/koneksi.php';
if(!isset($_SESSION['role'])||$_SESSION['role']!='guru'){header("Location: ../login/login.php");exit;}
if($_SERVER['REQUEST_METHOD']!=='POST'){header("Location: guru.php");exit;}

$id_mapel  = mysqli_real_escape_string($koneksi,$_POST['id_mapel']??'');
$judul     = mysqli_real_escape_string($koneksi,trim($_POST['judul']??''));
$deskripsi = mysqli_real_escape_string($koneksi,trim($_POST['deskripsi']??''));

if(empty($id_mapel)||empty($judul)){die("<script>alert('Judul dan ID Mapel wajib diisi!');history.back();</script>");}
if(!isset($_FILES['materi_file'])||$_FILES['materi_file']['error']!==UPLOAD_ERR_OK){
    $ec=[1=>'File terlalu besar (server)',2=>'File terlalu besar',3=>'Upload tidak lengkap',4=>'Tidak ada file'];
    die("<script>alert('Error upload: ".($ec[$_FILES['materi_file']['error']]??'Unknown')."');history.back();</script>");
}

// Generate ID
$res=mysqli_query($koneksi,"SELECT IDMateri FROM materi ORDER BY IDMateri DESC LIMIT 1");
$d=mysqli_fetch_assoc($res);
$nomor=$d?(int)substr($d['IDMateri'],1)+1:1;
$id_baru="M".str_pad($nomor,4,"0",STR_PAD_LEFT);

$file=$_FILES['materi_file'];
$ext=strtolower(pathinfo($file['name'],PATHINFO_EXTENSION));
$allowed=['pdf','doc','docx','ppt','pptx','xls','xlsx','jpg','jpeg','png','mp4'];
if(!in_array($ext,$allowed)){die("<script>alert('Tipe file tidak diizinkan!');history.back();</script>");}
if($file['size']>50*1024*1024){die("<script>alert('File melebihi 50MB!');history.back();</script>");}

$dir="../uploads/materi/";
if(!is_dir($dir))mkdir($dir,0755,true);
$fname=$id_baru."_".time().".".$ext;
if(!move_uploaded_file($file['tmp_name'],$dir.$fname)){die("<script>alert('Gagal pindah file. Cek folder uploads/materi/');history.back();</script>");}

$sql="INSERT INTO materi(IDMateri,IDMapel,Judul,Deskripsi,Filepath,TipeFile) VALUES('$id_baru','$id_mapel','$judul','$deskripsi','$fname','$ext')";
if(mysqli_query($koneksi,$sql)){
    header("Location: kelolaMapel.php?id_mapel=$id_mapel&tab=materi&sukses=materi");
}else{
    if(file_exists($dir.$fname))unlink($dir.$fname);
    die("Error DB: ".mysqli_error($koneksi));
}
?>