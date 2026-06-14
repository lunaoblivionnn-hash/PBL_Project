<?php
// Mencegah output HTML/Error bocor merusak format JSON
ob_start();
session_start();
require '../login/koneksi.php';

function kirimRespon($status, $pesan, $id_mapel = '', $id_kuis = '') {
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['status' => $status, 'pesan' => $pesan, 'id_mapel' => $id_mapel, 'id_kuis' => $id_kuis]);
    exit;
}

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'siswa'){
    kirimRespon('error', 'Sesi telah habis, silakan login ulang.');
}

if($_SERVER['REQUEST_METHOD'] !== 'POST') {
    kirimRespon('error', 'Metode pengiriman tidak valid.');
}

$id_user = $_SESSION['IDUser'];
$id_kuis = mysqli_real_escape_string($koneksi, $_POST['id_kuis'] ?? '');
$id_mapel = mysqli_real_escape_string($koneksi, $_POST['id_mapel'] ?? '');
$jawaban_siswa = $_POST['jawaban'] ?? []; 

$q_siswa = mysqli_query($koneksi, "SELECT IDSiswa FROM siswa WHERE IDUser='$id_user'");
$id_siswa = mysqli_fetch_assoc($q_siswa)['IDSiswa'] ?? '';

if(empty($id_siswa) || empty($id_kuis)) {
    kirimRespon('error', 'Data identitas kuis atau siswa hilang.');
}

// -------------------------------------------------------------
// PERBAIKAN: CARI ATAU BUAT 'IDNILAI' DULU SEBAGAI KUNCI UTAMA
// -------------------------------------------------------------
$q_cek_nilai = mysqli_query($koneksi, "SELECT IDNilai FROM kuis_nilai WHERE IDKuis='$id_kuis' AND IDSiswa='$id_siswa'");
if(mysqli_num_rows($q_cek_nilai) > 0) {
    $id_nilai = mysqli_fetch_assoc($q_cek_nilai)['IDNilai'];
} else {
    // Jika belum ada rekaman sama sekali, buat baru
    mysqli_query($koneksi, "INSERT INTO kuis_nilai (IDKuis, IDSiswa, WaktuMulai) VALUES ('$id_kuis', '$id_siswa', NOW())");
    $id_nilai = mysqli_insert_id($koneksi);
}

// =========================================================================
// SISTEM PENILAIAN OTOMATIS & PEREKAMAN JAWABAN FISIK
// =========================================================================
$poin_didapat = 0;
$total_poin_maksimal = 0;
$jumlah_benar = 0;
$jumlah_salah = 0;

$q_soal = mysqli_query($koneksi, "SELECT IDSoal, TipeSoal, Poin, KunciJawaban FROM kuis_soal WHERE IDKuis='$id_kuis'");
while($soal = mysqli_fetch_assoc($q_soal)) {
    $id_soal = $soal['IDSoal'];
    $tipe = $soal['TipeSoal'];
    $poin = (int)$soal['Poin'];
    
    $total_poin_maksimal += $poin;
    $jawaban_dikirim = $jawaban_siswa[$id_soal] ?? null;
    $benar = false;
    
    // Pengecekan Kebenaran Jawaban
    if(in_array($tipe, ['pilgan', 'dropdown'])) {
        $q_kunci = mysqli_query($koneksi, "SELECT IDOpsi FROM kuis_opsi WHERE IDSoal='$id_soal' AND IsBenar='1'");
        $kunci = mysqli_fetch_assoc($q_kunci)['IDOpsi'] ?? '';
        if($jawaban_dikirim == $kunci) $benar = true;
        
    } elseif($tipe == 'checkbox') {
        $q_kunci = mysqli_query($koneksi, "SELECT IDOpsi FROM kuis_opsi WHERE IDSoal='$id_soal' AND IsBenar='1'");
        $kunci_arr = [];
        while($k = mysqli_fetch_assoc($q_kunci)) { $kunci_arr[] = $k['IDOpsi']; }
        
        if(is_array($jawaban_dikirim)) {
            sort($kunci_arr); sort($jawaban_dikirim);
            if($kunci_arr == $jawaban_dikirim) $benar = true;
        }
    } elseif(in_array($tipe, ['singkat', 'paragraf'])) {
        $kunci_teks = strtolower(trim($soal['KunciJawaban']));
        $jawab_teks = strtolower(trim($jawaban_dikirim));
        if(!empty($kunci_teks) && $kunci_teks == $jawab_teks) $benar = true;
    }
    
    // Hitung Poin
    if($benar) {
        $poin_didapat += $poin;
        $jumlah_benar++;
    } else {
        $jumlah_salah++; 
    }

    // Perekaman ke Tabel kuis_jawaban
    $jawaban_simpan = is_array($jawaban_dikirim) ? implode(',', $jawaban_dikirim) : $jawaban_dikirim;
    $jawaban_simpan = mysqli_real_escape_string($koneksi, $jawaban_simpan ?? '');
    
    $is_benar_val = $benar ? 1 : 0;
    
    // PERBAIKAN: Gunakan IDNilai (Bukan IDKuis dan IDSiswa lagi)
    mysqli_query($koneksi, "DELETE FROM kuis_jawaban WHERE IDNilai='$id_nilai' AND IDSoal='$id_soal'");
    mysqli_query($koneksi, "INSERT INTO kuis_jawaban (IDNilai, IDSoal, JawabanTeks, IsBenar) VALUES ('$id_nilai', '$id_soal', '$jawaban_simpan', '$is_benar_val')");
}

$nilai_akhir = ($total_poin_maksimal > 0) ? round(($poin_didapat / $total_poin_maksimal) * 100) : 0;

// Simpan Total ke kuis_nilai
mysqli_query($koneksi, "UPDATE kuis_nilai SET NilaiAkhir='$nilai_akhir', Benar='$jumlah_benar', Salah='$jumlah_salah', WaktuSelesai=NOW() WHERE IDNilai='$id_nilai'");

kirimRespon('sukses', 'Ujian berhasil diselesaikan.', $id_mapel, $id_kuis);
?>