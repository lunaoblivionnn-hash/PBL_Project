<?php
// Mencegah error "session already started" saat file ini di-include oleh file lain
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../login/koneksi.php';

// =========================================================================
// BAGIAN A: SENSOR AJAX UNTUK "BACA MATERI" (Tandai Selesai)
// =========================================================================
if(isset($_POST['id_materi']) && isset($_SESSION['role']) && $_SESSION['role'] == 'siswa') {
    $id_materi = mysqli_real_escape_string($koneksi, $_POST['id_materi']);
    $id_user = $_SESSION['IDUser'];
    
    // Cari IDSiswa
    $q_siswa = mysqli_query($koneksi, "SELECT IDSiswa FROM siswa WHERE IDUser='$id_user'");
    $id_siswa = mysqli_fetch_assoc($q_siswa)['IDSiswa'] ?? '';

    if($id_siswa) {
        // Cek apakah siswa sudah pernah dapat poin dari materi ini
        $cek = mysqli_query($koneksi, "SELECT * FROM riwayat_gamifikasi WHERE IDSiswa='$id_siswa' AND JenisAktivitas='Baca Materi' AND KeteranganTambahan='IDMateri_$id_materi'");
        
        if(mysqli_num_rows($cek) > 0) {
            echo json_encode(['status' => 'sudah_klaim']);
            exit;
        }

        // Ambil besaran poin dari master aturan
        $q_poin = mysqli_query($koneksi, "SELECT BesaranPoin FROM master_aturan_poin WHERE JenisAktivitas='Baca Materi'");
        $poin = mysqli_fetch_assoc($q_poin)['BesaranPoin'] ?? 20; // Default 20 jika gagal ambil db

        // Masukkan ke riwayat dan tambahkan saldo poin siswa
        mysqli_query($koneksi, "INSERT INTO riwayat_gamifikasi (IDSiswa, JenisAktivitas, PoinDidapat, Waktu, KeteranganTambahan) VALUES ('$id_siswa', 'Baca Materi', '$poin', NOW(), 'IDMateri_$id_materi')");
        mysqli_query($koneksi, "UPDATE gamifikasi SET TotalPoint = TotalPoint + $poin WHERE IDSiswa='$id_siswa'");

        echo json_encode(['status' => 'sukses', 'poin' => $poin]);
        exit;
    }
}

// =========================================================================
// BAGIAN B: KUMPULAN FUNGSI POIN UNTUK TUGAS & KUIS
// =========================================================================

// 1. Fungsi Konversi Nilai Tugas & Bonus Sempurna
function berikan_poin_tugas($koneksi, $id_siswa, $id_tugas, $nilai_guru) {
    // Cek apakah poin nilai sudah masuk sebelumnya
    $cek_gami = mysqli_query($koneksi, "SELECT * FROM riwayat_gamifikasi WHERE IDSiswa='$id_siswa' AND JenisAktivitas='Nilai Tugas' AND KeteranganTambahan='IDTugas_$id_tugas'");
    if(mysqli_num_rows($cek_gami) > 0) return; // Jika sudah pernah dikonversi, batalkan

    if($nilai_guru > 0) {
        $poin_murni = (int)$nilai_guru;
        mysqli_query($koneksi, "INSERT INTO riwayat_gamifikasi (IDSiswa, JenisAktivitas, PoinDidapat, Waktu, KeteranganTambahan) VALUES ('$id_siswa', 'Nilai Tugas', '$poin_murni', NOW(), 'IDTugas_$id_tugas')");
        mysqli_query($koneksi, "UPDATE gamifikasi SET TotalPoint = TotalPoint + $poin_murni WHERE IDSiswa='$id_siswa'");

        // BONUS SEMPURNA JIKA NILAI 100
        if($nilai_guru == 100) {
            $q_bonus = mysqli_query($koneksi, "SELECT BesaranPoin FROM master_aturan_poin WHERE JenisAktivitas='Bonus Sempurna'");
            $poin_sempurna = mysqli_fetch_assoc($q_bonus)['BesaranPoin'] ?? 20;
            
            mysqli_query($koneksi, "INSERT INTO riwayat_gamifikasi (IDSiswa, JenisAktivitas, PoinDidapat, Waktu, KeteranganTambahan) VALUES ('$id_siswa', 'Bonus Sempurna', '$poin_sempurna', NOW(), 'IDTugas_$id_tugas')");
            mysqli_query($koneksi, "UPDATE gamifikasi SET TotalPoint = TotalPoint + $poin_sempurna WHERE IDSiswa='$id_siswa'");
            
            $_SESSION['popup_gamifikasi'] = "Wow! Kamu mendapat Nilai 100 dan meraih +$poin_murni XP murni beserta +$poin_sempurna XP Bonus Sempurna!";
        } else {
            $_SESSION['popup_gamifikasi'] = "Tugasmu telah dinilai! Kamu mendapatkan +$poin_murni XP murni dari tugasmu.";
        }
    }
}

// 2. Fungsi Bonus Cepat Tanggap (Kilat, Cepat, Disiplin)
function cek_bonus_waktu_tugas($koneksi, $id_siswa, $id_tugas, $tgl_kumpul) {
    $q_tugas = mysqli_query($koneksi, "SELECT TanggalDibuat, Deadline FROM tugas WHERE IDTugas='$id_tugas'");
    $dt_tugas = mysqli_fetch_assoc($q_tugas);
    
    $posting = strtotime($dt_tugas['TanggalDibuat']);
    $deadline = strtotime($dt_tugas['Deadline']);
    $kumpul = strtotime($tgl_kumpul);
    
    $selisih_jam_posting = ($kumpul - $posting) / 3600;

    $jenis_bonus = '';
    if($selisih_jam_posting <= 24) $jenis_bonus = 'Bonus Kilat';
    elseif($selisih_jam_posting <= 48) $jenis_bonus = 'Bonus Cepat';
    elseif($kumpul <= $deadline) $jenis_bonus = 'Bonus Disiplin';

    if($jenis_bonus != '') {
        $q_bonus = mysqli_query($koneksi, "SELECT BesaranPoin FROM master_aturan_poin WHERE JenisAktivitas='$jenis_bonus'");
        $poin_waktu = mysqli_fetch_assoc($q_bonus)['BesaranPoin'] ?? 10;
        
        mysqli_query($koneksi, "INSERT INTO riwayat_gamifikasi (IDSiswa, JenisAktivitas, PoinDidapat, Waktu, KeteranganTambahan) VALUES ('$id_siswa', '$jenis_bonus', '$poin_waktu', NOW(), 'IDTugas_$id_tugas')");
        mysqli_query($koneksi, "UPDATE gamifikasi SET TotalPoint = TotalPoint + $poin_waktu WHERE IDSiswa='$id_siswa'");
        
        return ['jenis' => $jenis_bonus, 'poin' => $poin_waktu];
    }
    return false;
}

// 3. Fungsi Konversi Nilai Kuis & Ujian
function konversi_poin_kuis($koneksi, $id_siswa, $id_kuis, $nilai_akhir) {
    // Mencegah ganda saat ditarik ulang (Memakai label Nilai Tugas agar terbaca di klasemen)
    $cek_gami = mysqli_query($koneksi, "SELECT * FROM riwayat_gamifikasi WHERE IDSiswa='$id_siswa' AND JenisAktivitas='Nilai Tugas' AND KeteranganTambahan='IDKuis_$id_kuis'");
    if(mysqli_num_rows($cek_gami) > 0) return; 

    $poin_murni = (int)$nilai_akhir;
    mysqli_query($koneksi, "INSERT INTO riwayat_gamifikasi (IDSiswa, JenisAktivitas, PoinDidapat, Waktu, KeteranganTambahan) VALUES ('$id_siswa', 'Nilai Tugas', '$poin_murni', NOW(), 'IDKuis_$id_kuis')");
    mysqli_query($koneksi, "UPDATE gamifikasi SET TotalPoint = TotalPoint + $poin_murni WHERE IDSiswa='$id_siswa'");

    if($nilai_akhir == 100) {
        $q_bonus = mysqli_query($koneksi, "SELECT BesaranPoin FROM master_aturan_poin WHERE JenisAktivitas='Bonus Sempurna'");
        $poin_sempurna = mysqli_fetch_assoc($q_bonus)['BesaranPoin'] ?? 20;
        
        mysqli_query($koneksi, "INSERT INTO riwayat_gamifikasi (IDSiswa, JenisAktivitas, PoinDidapat, Waktu, KeteranganTambahan) VALUES ('$id_siswa', 'Bonus Sempurna', '$poin_sempurna', NOW(), 'IDKuis_$id_kuis')");
        mysqli_query($koneksi, "UPDATE gamifikasi SET TotalPoint = TotalPoint + $poin_sempurna WHERE IDSiswa='$id_siswa'");
        
        $_SESSION['popup_gamifikasi'] = "Hebat! Nilai kuis 100! Kamu dapat +$poin_murni XP murni dan +$poin_sempurna XP Bonus Sempurna!";
    } else {
        $_SESSION['popup_gamifikasi'] = "Nilai ujian telah diumumkan! Kamu meraih +$poin_murni XP dari evaluasi ini.";
    }
}
?>