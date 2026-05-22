<?php
session_start();
require '../login/koneksi.php';

// Pastikan hanya admin yang bisa akses
if ($_SESSION['role'] != 'admin') {
    header("Location: ../login/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Tangkap data dari form
    $nip        = mysqli_real_escape_string($koneksi, $_POST['nip']);
    $nama_guru  = mysqli_real_escape_string($koneksi, $_POST['nama_guru']);
    $no_telp    = mysqli_real_escape_string($koneksi, $_POST['no_telp']);
    $email      = mysqli_real_escape_string($koneksi, $_POST['email']);
    
    $pass_input = $_POST['password'];
    $password   = empty($pass_input) ? 'guru123' : mysqli_real_escape_string($koneksi, $pass_input);
    $force_pass = isset($_POST['force_password_change']) ? 1 : 0;

    // =====================================================================
    // 1. PENGOLAHAN DATA KELAS & MAPEL MENJADI JSON
    // =====================================================================
    $data_akses = ""; // Kosong jika admin tidak mencentang apa-apa

    if (isset($_POST['kelas_diampu']) && !empty($_POST['kelas_diampu'])) {
        $akses_guru = [];
        foreach ($_POST['kelas_diampu'] as $kelas) {
            // Ambil mapel yang dicentang pada kelas tersebut
            if (isset($_POST['mapel_diampu'][$kelas])) {
                $akses_guru[$kelas] = $_POST['mapel_diampu'][$kelas];
            }
        }
        // Ubah array menjadi format teks JSON
        $data_akses = mysqli_real_escape_string($koneksi, json_encode($akses_guru));
    }

    // =====================================================================
    // 2. GENERATOR ID USER & ID GURU (Maksimal 5 Karakter)
    // =====================================================================
    $angka_user = 1;
    while(true) {
        $id_user = "UG" . sprintf("%03d", $angka_user); 
        $cek_id = mysqli_query($koneksi, "SELECT IDUser FROM users WHERE IDUser = '$id_user'");
        if(mysqli_num_rows($cek_id) == 0) break; 
        $angka_user++;
    }

    $angka_guru = 1;
    while(true) {
        $id_guru = "IG" . sprintf("%03d", $angka_guru); 
        $cek_guru = mysqli_query($koneksi, "SELECT IDGuru FROM guru WHERE IDGuru = '$id_guru'");
        if(mysqli_num_rows($cek_guru) == 0) break; 
        $angka_guru++;
    }

    // =====================================================================
    // 3. SIMPAN KE DATABASE (SESUAI STRUKTUR LMS_WONGSOREJO)
    // =====================================================================
    
    // Simpan akun ke tabel users
    $query_user = "INSERT INTO users (IDUser, Username, Password, Role, Status, WajibUbahPassword) 
                   VALUES ('$id_user', '$nip', '$password', 'guru', 'Aktif', '$force_pass')";
    
    if (mysqli_query($koneksi, $query_user)) {
        
        // Simpan profil ke tabel guru (Perhatikan: NIP_NUPTK)
        $query_guru = "INSERT INTO guru (IDGuru, IDUser, NamaGuru, NIP_NUPTK, Email, NoTelp, MataPelajaran) 
                       VALUES ('$id_guru', '$id_user', '$nama_guru', '$nip', '$email', '$no_telp', '$data_akses')";
        
        if (mysqli_query($koneksi, $query_guru)) {
            // Jika sukses, kembali ke daftar guru
            header("Location: daftarGuru.php?status=sukses_tambah");
            exit;
        } else {
            echo "Gagal simpan profil guru: " . mysqli_error($koneksi);
        }
    } else {
        echo "Gagal simpan akun login: " . mysqli_error($koneksi);
    }
}
?>