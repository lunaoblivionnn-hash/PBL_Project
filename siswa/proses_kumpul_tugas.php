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

// 1. Ambil Data Siswa (ID Siswa)
$query_siswa = mysqli_query($koneksi, "SELECT * FROM siswa WHERE IDUser='$id_user'");
$siswa = mysqli_fetch_assoc($query_siswa);
$id_siswa = $siswa['IDSiswa'] ?? '';

if(empty($id_tugas) || empty($id_siswa)){
    die("<script>alert('Akses tertolak: Data tugas atau siswa tidak ditemukan!'); history.back();</script>");
}

// 2. Ambil IDMapel dari Tabel Tugas (Untuk redirect kembali)
$q_mapel = mysqli_query($koneksi, "SELECT IDMapel FROM tugas WHERE IDTugas='$id_tugas'");
$tugas_db = mysqli_fetch_assoc($q_mapel);
$id_mapel = $tugas_db['IDMapel'] ?? '';

// 3. Proses File Upload
if(isset($_FILES['file_tugas']) && $_FILES['file_tugas']['error'] === UPLOAD_ERR_OK){
    $file = $_FILES['file_tugas'];
    $file_name = $file['name'];
    $tmp_name = $file['tmp_name'];
    $file_size = $file['size'];
    
    // Cek batas ukuran (50MB)
    if($file_size > 50 * 1024 * 1024){
        die("<script>alert('GAGAL: Ukuran file melebihi batas 50MB!'); history.back();</script>");
    }

    $ext = pathinfo($file_name, PATHINFO_EXTENSION);
    
    // Direktori Simpan
    $dir = "../uploads/tugas/";
    if(!is_dir($dir)){
        mkdir($dir, 0777, true);
    }

    // Penamaan file yang aman dan rapi
    $new_filename = $id_tugas . "_" . preg_replace('/[^a-zA-Z0-9]/', '', $id_siswa) . "_" . time() . "." . $ext;
    
    if(move_uploaded_file($tmp_name, $dir . $new_filename)){
        
        // Simpan ke database: tabel `pengumpulan_tugas`
        // Deteksi struktur kolom terlebih dahulu (FileJawaban atau FileKumpul)
        $q_cek_kolom = mysqli_query($koneksi, "SHOW COLUMNS FROM pengumpulan_tugas LIKE 'FileJawaban'");
        $kolom_file = (mysqli_num_rows($q_cek_kolom) > 0) ? 'FileJawaban' : 'FileKumpul';

        // Deteksi Format ID Pengumpulan (IDPengumpulan)
        $q_id = mysqli_query($koneksi, "SELECT IDPengumpulan FROM pengumpulan_tugas ORDER BY IDPengumpulan DESC LIMIT 1");
        $d_id = mysqli_fetch_assoc($q_id);
        
        // Auto Generate ID Pengumpulan jika formatnya String Varchar (Contoh: P0001)
        if($d_id && !is_numeric($d_id['IDPengumpulan'])) {
            $nomor = (int)substr($d_id['IDPengumpulan'], 1) + 1;
            $id_baru = "P" . str_pad($nomor, 4, "0", STR_PAD_LEFT);
            $sql = "INSERT INTO pengumpulan_tugas(IDPengumpulan, IDTugas, IDSiswa, $kolom_file, TanggalKirim) 
                    VALUES('$id_baru', '$id_tugas', '$id_siswa', '$new_filename', NOW())";
        } else {
            // Jika Auto Increment atau belum ada data
            $sql = "INSERT INTO pengumpulan_tugas(IDTugas, IDSiswa, $kolom_file, TanggalKirim) 
                    VALUES('$id_tugas', '$id_siswa', '$new_filename', NOW())";
        }
        
        if(mysqli_query($koneksi, $sql)){
            // Ambil ID Pengumpulan yang baru saja dimasukkan (baik Varchar maupun Auto Increment)
            $id_pengumpulan_real = isset($id_baru) ? $id_baru : mysqli_insert_id($koneksi);

            // =========================================================================
            // LAKUKAN ALOKASI POIN KECEPATAN SESUAI EXCEL (EARNING RULES)
            // =========================================================================
            // Ambil waktu tugas diposting
            $q_tgl = mysqli_query($koneksi, "SELECT TanggalDibuat FROM tugas WHERE IDTugas='$id_tugas'");
            $tgl_buat = mysqli_fetch_assoc($q_tgl)['TanggalDibuat'];
            
            $waktu_dibuat = strtotime($tgl_buat);
            $waktu_kumpul = time(); // Waktu saat tombol kumpul ditekan
            $selisih_jam = ($waktu_kumpul - $waktu_dibuat) / 3600;

            // Logika sesuai Excel:
            if($selisih_jam <= 24) {
                $poin_tugas = 50; $nama_bonus = "Bonus Kilat"; $id_aturan = "A003";
            } elseif ($selisih_jam <= 48) {
                $poin_tugas = 20; $nama_bonus = "Bonus Cepat"; $id_aturan = "A004";
            } else {
                $poin_tugas = 10; $nama_bonus = "Bonus Disiplin"; $id_aturan = "A005";
            }

            // Update akumulasi total poin siswa di tabel gamifikasi
            $q_cek_gami = mysqli_query($koneksi, "SELECT IDGamifikasi FROM gamifikasi WHERE IDSiswa = '$id_siswa'");
            if(mysqli_num_rows($q_cek_gami) > 0) {
                mysqli_query($koneksi, "UPDATE gamifikasi SET TotalPoint = TotalPoint + $poin_tugas WHERE IDSiswa = '$id_siswa'");
            } else {
                $idg_baru = "G" . str_pad(rand(100, 9999), 4, "0", STR_PAD_LEFT);
                mysqli_query($koneksi, "INSERT INTO gamifikasi (IDGamifikasi, IDSiswa, IDLevel, TotalPoint) VALUES ('$idg_baru', '$id_siswa', 'L001', $poin_tugas)");
            }

            // Catat ke Riwayat Poin
            $idr = "R" . str_pad(rand(1000, 99999), 5, "0", STR_PAD_LEFT);
            mysqli_query($koneksi, "INSERT INTO riwayat_poin (IDRiwayat, IDSiswa, IDAturan, IDPengumpulan, Poin, Tanggal) 
                                    VALUES ('$idr', '$id_siswa', '$id_aturan', '$id_pengumpulan_real', $poin_tugas, NOW())");
            // =========================================================================

            // Redirect kembali ke mapel.php membawa notifikasi jenis bonus yang didapat!
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