<?php
session_start();
require '../login/koneksi.php';
if(!isset($_SESSION['role'])||$_SESSION['role']!='guru'){header("Location: ../login/login.php");exit;}
if($_SERVER['REQUEST_METHOD']!=='POST'){header("Location: guru.php");exit;}

$id_mapel  = mysqli_real_escape_string($koneksi,$_POST['id_mapel']??'');
$judul     = mysqli_real_escape_string($koneksi,trim($_POST['judul']??''));
$deskripsi = mysqli_real_escape_string($koneksi,trim($_POST['deskripsi']??''));
$deadline  = $_POST['deadline']??'';
$poin      = max(10,min(1000,(int)($_POST['poin_maksimal']??100)));

if(empty($id_mapel)||empty($judul)||empty($deadline)){die("<script>alert('Data tidak lengkap!');history.back();</script>");}
if(strtotime($deadline)<=time()){die("<script>alert('Deadline harus di masa depan!');history.back();</script>");}

$res=mysqli_query($koneksi,"SELECT IDTugas FROM tugas ORDER BY IDTugas DESC LIMIT 1");
$d=mysqli_fetch_assoc($res);
$nomor=$d?(int)substr($d['IDTugas'],1)+1:1;
$id_baru="T".str_pad($nomor,4,"0",STR_PAD_LEFT);
$dl_mysql=date('Y-m-d H:i:s',strtotime($deadline));

$sql="INSERT INTO tugas(IDTugas,IDMapel,Judul,Deskripsi,Deadline,PoinMaksimal) VALUES('$id_baru','$id_mapel','$judul','$deskripsi','$dl_mysql',$poin)";
if(!mysqli_query($koneksi,$sql)){die("Error DB: ".mysqli_error($koneksi));}

// Notifikasi ke semua siswa
$res_siswa=mysqli_query($koneksi,"SELECT u.IDUser FROM users u JOIN siswa s ON u.IDUser=s.IDUser WHERE u.Status='Aktif'");
while($s=mysqli_fetch_assoc($res_siswa)){
    $jn=mysqli_real_escape_string($koneksi,"Tugas Baru: $judul");
    $pm=mysqli_real_escape_string($koneksi,"Guru telah memberikan tugas baru \"$judul\". Deadline: ".date('d M Y H:i',strtotime($deadline)));
    mysqli_query($koneksi,"INSERT INTO notifikasi(IDUser,JudulNotif,Pesan,IsRead,CreatedAt) VALUES('{$s['IDUser']}','$jn','$pm',0,NOW())");
}

header("Location: kelolaMapel.php?id_mapel=$id_mapel&tab=tugas&sukses=tugas");
?>