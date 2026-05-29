<?php
session_start();
require '../login/koneksi.php';

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'guru') { exit; }
if($_SERVER['REQUEST_METHOD'] !== 'POST') { exit; }

$id_mapel = mysqli_real_escape_string($koneksi, $_POST['id_mapel']);
$mode = $_POST['mode'] ?? 'single';
$kelas_string = $_POST['kelas_string'] ?? '';
$id_topik_single = mysqli_real_escape_string($koneksi, $_POST['id_topik'] ?? '');
$nama_topik_multi = mysqli_real_escape_string($koneksi, $_POST['nama_topik'] ?? '');

$judul_kuis = mysqli_real_escape_string($koneksi, $_POST['judul_kuis']);
$deskripsi_kuis = mysqli_real_escape_string($koneksi, $_POST['deskripsi_kuis']);
$durasi_menit = (int)$_POST['durasi_menit'];
$deadline = mysqli_real_escape_string($koneksi, $_POST['deadline']);

$kelas_array = explode(',', $kelas_string);

// 1. Kumpulkan data Topik/Bab untuk setiap kelas
$kuis_targets = [];

if ($mode == 'multi') {
    foreach($kelas_array as $kls) {
        $kls = trim($kls);
        if(empty($kls)) continue;
        
        // Cek apakah Topik sudah ada di kelas tersebut, jika belum, buat otomatis
        $q_cek_topik = mysqli_query($koneksi, "SELECT IDTopik FROM topik_mapel WHERE IDMapel='$id_mapel' AND Kelas='$kls' AND NamaTopik='$nama_topik_multi'");
        if(mysqli_num_rows($q_cek_topik) > 0) {
            $id_tp = mysqli_fetch_assoc($q_cek_topik)['IDTopik'];
        } else {
            $q_urut = mysqli_query($koneksi, "SELECT MAX(Urutan) as max_urut FROM topik_mapel WHERE IDMapel='$id_mapel' AND Kelas='$kls'");
            $urut = (mysqli_fetch_assoc($q_urut)['max_urut'] ?? 0) + 1;
            mysqli_query($koneksi, "INSERT INTO topik_mapel (IDMapel, Kelas, NamaTopik, Urutan) VALUES ('$id_mapel', '$kls', '$nama_topik_multi', $urut)");
            $id_tp = mysqli_insert_id($koneksi);
        }
        $kuis_targets[] = ['id_topik' => $id_tp, 'kelas' => $kls];
    }
} else {
    $kuis_targets[] = ['id_topik' => $id_topik_single, 'kelas' => $kelas_string];
}

$soal_array = $_POST['soal'] ?? [];

// 2. Proses Upload Gambar (Hanya 1 kali upload agar server hemat memori)
$gambar_terupload = [];
foreach($soal_array as $id_form => $s) {
    $file_key = "soal_gambar_" . $id_form;
    $gambar_terupload[$id_form] = 'NULL';
    
    if(isset($_FILES[$file_key]) && $_FILES[$file_key]['error'] == 0) {
        $ext = strtolower(pathinfo($_FILES[$file_key]['name'], PATHINFO_EXTENSION));
        if(in_array($ext, ['jpg','jpeg','png','gif'])) {
            $nama_gambar = "IMG_SOAL_" . time() . "_" . rand(100,999) . "." . $ext;
            $dir = "../uploads/quiz/";
            if(!is_dir($dir)) mkdir($dir, 0777, true);
            if(move_uploaded_file($_FILES[$file_key]['tmp_name'], $dir . $nama_gambar)) {
                $gambar_terupload[$id_form] = "'$nama_gambar'";
            }
        }
    }
}

// 3. Gandakan & Sebarkan Kuis ke seluruh Kelas yang dicentang
foreach($kuis_targets as $target) {
    $id_tp = $target['id_topik'];
    
    $q_id = mysqli_query($koneksi, "SELECT IDKuis FROM kuis ORDER BY IDKuis DESC LIMIT 1");
    $d_id = mysqli_fetch_assoc($q_id);
    $nomor = $d_id ? (int)substr($d_id['IDKuis'], 1) + 1 : 1;
    $id_kuis = "Q" . str_pad($nomor, 4, "0", STR_PAD_LEFT);
    
    $sql_kuis = "INSERT INTO kuis (IDKuis, IDMapel, IDTopik, Judul, Deskripsi, DurasiMenit, Deadline, TanggalDibuat)
                 VALUES ('$id_kuis', '$id_mapel', '$id_tp', '$judul_kuis', '$deskripsi_kuis', $durasi_menit, '$deadline', NOW())";
                 
    if(mysqli_query($koneksi, $sql_kuis)) {
        $urutan = 1;
        foreach($soal_array as $id_form => $s) {
            $teks_soal = mysqli_real_escape_string($koneksi, $s['teks_soal']);
            $tipe_soal = mysqli_real_escape_string($koneksi, $s['tipe_soal']);
            $poin = (int)$s['poin'];
            $wajib = isset($s['wajib']) ? 1 : 0;
            $nama_gambar_db = $gambar_terupload[$id_form];
            
            $kunci_teks = "";
            if(in_array($tipe_soal, ['singkat', 'paragraf'])) {
                $kunci_teks = mysqli_real_escape_string($koneksi, $s['kunci_teks'] ?? '');
            }

            $sql_soal = "INSERT INTO kuis_soal (IDKuis, TipeSoal, Pertanyaan, Gambar, Poin, Wajib, KunciJawaban, Urutan)
                         VALUES ('$id_kuis', '$tipe_soal', '$teks_soal', $nama_gambar_db, $poin, $wajib, '$kunci_teks', $urutan)";

            if(mysqli_query($koneksi, $sql_soal)) {
                $id_soal = mysqli_insert_id($koneksi);

                if(in_array($tipe_soal, ['pilgan', 'checkbox', 'dropdown']) && !empty($s['opsi'])) {
                    $kunci_opsi = $s['kunci_opsi'] ?? null;
                    $is_kunci_array = is_array($kunci_opsi);

                    foreach($s['opsi'] as $index => $opsi_teks) {
                        if(trim($opsi_teks) != '') {
                            $opsi_bersih = mysqli_real_escape_string($koneksi, $opsi_teks);
                            $is_benar = 0;
                            if($is_kunci_array && in_array($index, $kunci_opsi)) { $is_benar = 1; } 
                            elseif (!$is_kunci_array && $kunci_opsi !== null && $kunci_opsi == $index) { $is_benar = 1; }
                            
                            mysqli_query($koneksi, "INSERT INTO kuis_opsi (IDSoal, TeksOpsi, IsBenar) VALUES ('$id_soal', '$opsi_bersih', '$is_benar')");
                        }
                    }
                }
            }
            $urutan++;
        }
    }
}

if ($mode == 'multi') {
    header("Location: guru.php?status=sukses_multi");
} else {
    header("Location: kelolaMapel.php?id_mapel=$id_mapel&kelas=" . urlencode($kelas_string) . "&status=sukses_quiz");
}
exit;
?>