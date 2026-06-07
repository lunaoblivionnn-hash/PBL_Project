<?php
session_start();
require '../login/koneksi.php';

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'siswa'){
    header("Location: ../login/login.php"); exit;
}

if($_SERVER['REQUEST_METHOD'] !== 'POST'){ header("Location: siswa.php"); exit; }

$id_user = $_SESSION['IDUser'] ?? '';
$id_tugas = mysqli_real_escape_string($koneksi, $_POST['id_tugas'] ?? '');

$query_siswa = mysqli_query($koneksi, "SELECT IDSiswa FROM siswa WHERE IDUser='$id_user'");
$id_siswa = mysqli_fetch_assoc($query_siswa)['IDSiswa'] ?? '';

if(empty($id_tugas) || empty($id_siswa)){
    die("<script>alert('Data tugas/siswa tidak valid!'); history.back();</script>");
}

$q_mapel = mysqli_query($koneksi, "SELECT IDMapel, TanggalDibuat FROM tugas WHERE IDTugas='$id_tugas'");
$tugas_db = mysqli_fetch_assoc($q_mapel);
$id_mapel = $tugas_db['IDMapel'] ?? '';
$tgl_buat_tugas = $tugas_db['TanggalDibuat'] ?? '';

if(isset($_FILES['file_tugas']) && $_FILES['file_tugas']['error'] === UPLOAD_ERR_OK){
    $file = $_FILES['file_tugas'];
    if($file['size'] > 50 * 1024 * 1024){ die("<script>alert('Ukuran melebihi 50MB!'); history.back();</script>"); }

    $dir = "../uploads/tugas/";
    if(!is_dir($dir)){ mkdir($dir, 0777, true); }
    $new_filename = uniqid('TGS_') . "_" . str_replace(' ', '_', basename($file['name']));
    
    if(move_uploaded_file($file['tmp_name'], $dir . $new_filename)){
        
        // Cek Kolom FileJawaban
        $q_cek_kolom = mysqli_query($koneksi, "SHOW COLUMNS FROM pengumpulan_tugas LIKE 'FileJawaban'");
        $kolom_file = (mysqli_num_rows($q_cek_kolom) > 0) ? 'FileJawaban' : 'FileKumpul';

        // 1. CEK APAKAH SISWA SUDAH PERNAH MENGUMPULKAN TUGAS INI
        $cek_kumpul = mysqli_query($koneksi, "SELECT IDPengumpulan FROM pengumpulan_tugas WHERE IDTugas='$id_tugas' AND IDSiswa='$id_siswa'");
        
        $baru_pertama_kali = false;

        if (mysqli_num_rows($cek_kumpul) > 0) {
            // Jika sudah mengumpulkan -> UPDATE FILE SAJA (Tidak ada tambahan poin)
            $id_pengumpulan_real = mysqli_fetch_assoc($cek_kumpul)['IDPengumpulan'];
            mysqli_query($koneksi, "UPDATE pengumpulan_tugas SET $kolom_file='$new_filename', TanggalKirim=NOW() WHERE IDPengumpulan='$id_pengumpulan_real'");
            $poin_tugas = 0; 
            $nama_bonus = "Update File (Tanpa Poin Tambahan)";
        } else {
            // Jika belum -> INSERT BARU & BERI POIN
            $baru_pertama_kali = true;
            $q_id = mysqli_query($koneksi, "SELECT IDPengumpulan FROM pengumpulan_tugas ORDER BY IDPengumpulan DESC LIMIT 1");
            $d_id = mysqli_fetch_assoc($q_id);
            if($d_id && !is_numeric($d_id['IDPengumpulan'])) {
                $nomor = (int)substr($d_id['IDPengumpulan'], 1) + 1;
                $id_baru = "P" . str_pad($nomor, 4, "0", STR_PAD_LEFT);
                mysqli_query($koneksi, "INSERT INTO pengumpulan_tugas(IDPengumpulan, IDTugas, IDSiswa, $kolom_file, TanggalKirim, Status) VALUES('$id_baru', '$id_tugas', '$id_siswa', '$new_filename', NOW(), 'belum_dinilai')");
                $id_pengumpulan_real = $id_baru;
            } else {
                mysqli_query($koneksi, "INSERT INTO pengumpulan_tugas(IDTugas, IDSiswa, $kolom_file, TanggalKirim, Status) VALUES('$id_tugas', '$id_siswa', '$new_filename', NOW(), 'belum_dinilai')");
                $id_pengumpulan_real = mysqli_insert_id($koneksi);
            }
        }

        // 2. LOGIKA PEMBERIAN POIN (Hanya jika kumpul pertama kali)
        if ($baru_pertama_kali) {
            $waktu_dibuat = strtotime($tgl_buat_tugas);
            $waktu_kumpul = time(); 
            $selisih_jam = ($waktu_kumpul - $waktu_dibuat) / 3600;

            if($selisih_jam <= 24) {
                $id_aturan = "AT003"; $nama_bonus = "Bonus Kilat";
            } elseif ($selisih_jam <= 48) {
                $id_aturan = "AT004"; $nama_bonus = "Bonus Cepat";
            } else {
                $id_aturan = "AT005"; $nama_bonus = "Bonus Disiplin";
            }

            // Ambil Poin Sebenarnya dari Master
            $q_aturan = mysqli_query($koneksi, "SELECT BesaranPoin FROM master_aturan_poin WHERE IDAturan='$id_aturan'");
            $poin_tugas = mysqli_fetch_assoc($q_aturan)['BesaranPoin'] ?? 10;

            // Catat Riwayat
            mysqli_query($koneksi, "INSERT INTO riwayat_poin (IDSiswa, IDAturan, IDPengumpulan, TanggalWaktu) VALUES ('$id_siswa', '$id_aturan', '$id_pengumpulan_real', NOW())");

            // Update TotalXP
            $q_cek_gami = mysqli_query($koneksi, "SELECT IDGamifikasi FROM gamifikasi WHERE IDSiswa = '$id_siswa'");
            if(mysqli_num_rows($q_cek_gami) > 0) {
                mysqli_query($koneksi, "UPDATE gamifikasi SET TotalPoint = TotalPoint + $poin_tugas WHERE IDSiswa = '$id_siswa'");
            } else {
                $idg_baru = "G" . str_pad(rand(100, 9999), 4, "0", STR_PAD_LEFT);
                mysqli_query($koneksi, "INSERT INTO gamifikasi (IDGamifikasi, IDSiswa, IDLevel, TotalPoint) VALUES ('$idg_baru', '$id_siswa', 'LV001', $poin_tugas)");
            }
        }

        header("Location: mapel.php?id_mapel=" . urlencode($id_mapel) . "&status=sukses_kumpul&bonus=" . urlencode($nama_bonus) . "&poin=" . $poin_tugas);
        exit;
    }
}
?>