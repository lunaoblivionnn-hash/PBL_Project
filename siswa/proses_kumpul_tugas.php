<?php
session_start();
require '../login/koneksi.php';

// Pastikan yang masuk adalah siswa
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'siswa'){
    header("Location: ../login/login.php"); exit;
}

if($_SERVER['REQUEST_METHOD'] !== 'POST'){
    header("Location: siswa.php"); exit;
}

$id_user = $_SESSION['IDUser'] ?? '';
$id_tugas = mysqli_real_escape_string($koneksi, $_POST['id_tugas'] ?? '');

$query_siswa = mysqli_query($koneksi, "SELECT * FROM siswa WHERE IDUser='$id_user'");
$siswa = mysqli_fetch_assoc($query_siswa);
$id_siswa = $siswa['IDSiswa'] ?? '';

if(empty($id_tugas) || empty($id_siswa)){
    die("<script>alert('Akses tertolak: Data tugas atau siswa tidak ditemukan!'); history.back();</script>");
}

$q_mapel = mysqli_query($koneksi, "SELECT IDMapel FROM tugas WHERE IDTugas='$id_tugas'");
$tugas_db = mysqli_fetch_assoc($q_mapel);
$id_mapel = $tugas_db['IDMapel'] ?? '';

if(isset($_FILES['file_tugas']) && $_FILES['file_tugas']['error'] === UPLOAD_ERR_OK){
    $file = $_FILES['file_tugas'];
    $file_size = $file['size'];
    
    if($file_size > 50 * 1024 * 1024){
        die("<script>alert('GAGAL: Ukuran file melebihi batas 50MB!'); history.back();</script>");
    }

    $dir = "../uploads/tugas/";
    if(!is_dir($dir)){ mkdir($dir, 0777, true); }

    // PENAMAAN FILE ASLI MURNI
    $new_filename = str_replace(' ', '_', basename($file['name']));
    
    if(move_uploaded_file($file['tmp_name'], $dir . $new_filename)){
        
        $q_cek_kolom = mysqli_query($koneksi, "SHOW COLUMNS FROM pengumpulan_tugas LIKE 'FileJawaban'");
        $kolom_file = (mysqli_num_rows($q_cek_kolom) > 0) ? 'FileJawaban' : 'FileKumpul';

        $q_id = mysqli_query($koneksi, "SELECT IDPengumpulan FROM pengumpulan_tugas ORDER BY IDPengumpulan DESC LIMIT 1");
        $d_id = mysqli_fetch_assoc($q_id);
        
        if($d_id && !is_numeric($d_id['IDPengumpulan'])) {
            $nomor = (int)substr($d_id['IDPengumpulan'], 1) + 1;
            $id_baru = "P" . str_pad($nomor, 4, "0", STR_PAD_LEFT);
            $sql = "INSERT INTO pengumpulan_tugas(IDPengumpulan, IDTugas, IDSiswa, $kolom_file, TanggalKirim) 
                    VALUES('$id_baru', '$id_tugas', '$id_siswa', '$new_filename', NOW())";
        } else {
            $sql = "INSERT INTO pengumpulan_tugas(IDTugas, IDSiswa, $kolom_file, TanggalKirim) 
                    VALUES('$id_tugas', '$id_siswa', '$new_filename', NOW())";
        }
        
        if(mysqli_query($koneksi, $sql)){
            $id_pengumpulan_real = isset($id_baru) ? $id_baru : mysqli_insert_id($koneksi);

            // ALOKASI POIN KECEPATAN SESUAI EXCEL
            $q_tgl = mysqli_query($koneksi, "SELECT TanggalDibuat FROM tugas WHERE IDTugas='$id_tugas'");
            $tgl_buat = mysqli_fetch_assoc($q_tgl)['TanggalDibuat'];
            
            $waktu_dibuat = strtotime($tgl_buat);
            $waktu_kumpul = time(); 
            $selisih_jam = ($waktu_kumpul - $waktu_dibuat) / 3600;

            // Sesuai dengan ID Aturan di tabel master_aturan_poin (Misal: AT003, AT004, dst)
            if($selisih_jam <= 24) {
                $poin_tugas = 50; $nama_bonus = "Bonus Kilat"; $id_aturan = "AT003";
            } elseif ($selisih_jam <= 48) {
                $poin_tugas = 20; $nama_bonus = "Bonus Cepat"; $id_aturan = "AT004";
            } else {
                $poin_tugas = 10; $nama_bonus = "Bonus Disiplin"; $id_aturan = "AT005";
            }

            // Memasukkan ke tabel Gamifikasi (pakai LV001)
            $q_cek_gami = mysqli_query($koneksi, "SELECT IDGamifikasi FROM gamifikasi WHERE IDSiswa = '$id_siswa'");
            if(mysqli_num_rows($q_cek_gami) > 0) {
                mysqli_query($koneksi, "UPDATE gamifikasi SET TotalPoint = TotalPoint + $poin_tugas WHERE IDSiswa = '$id_siswa'");
            } else {
                $idg_baru = "G" . str_pad(rand(100, 9999), 4, "0", STR_PAD_LEFT);
                mysqli_query($koneksi, "INSERT INTO gamifikasi (IDGamifikasi, IDSiswa, IDLevel, TotalPoint) VALUES ('$idg_baru', '$id_siswa', 'LV001', $poin_tugas)");
            }

            // KODE SAKTI: Masuk ke Riwayat Poin sesuai kolom asli di Database-mu (Hanya IDSiswa, IDAturan, IDPengumpulan, TanggalWaktu)
            mysqli_query($koneksi, "INSERT INTO riwayat_poin (IDSiswa, IDAturan, IDPengumpulan, TanggalWaktu) 
                                    VALUES ('$id_siswa', '$id_aturan', '$id_pengumpulan_real', NOW())");

            header("Location: mapel.php?id_mapel=" . urlencode($id_mapel) . "&status=sukses_kumpul&bonus=" . urlencode($nama_bonus) . "&poin=" . $poin_tugas);
            exit;
        } else {
            die("Error Database: " . mysqli_error($koneksi));
        }
    } else {
        die("<script>alert('GAGAL: Terjadi kesalahan saat memindahkan file!'); history.back();</script>");
    }
} else {
    $ec = $_FILES['file_tugas']['error'] ?? 4;
    $errors = [1=>'File melampaui batas server', 2=>'File terlalu besar', 3=>'Upload terputus', 4=>'Tidak ada file yang dipilih'];
    $pesan = $errors[$ec] ?? 'Upload Error';
    die("<script>alert('GAGAL: $pesan'); history.back();</script>");
}
?>