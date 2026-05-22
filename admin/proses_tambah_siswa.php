<?php
session_start();

// Panggil koneksi database
require '../login/koneksi.php';

// 1. Tangkap semua data dari form
$nisn           = $_POST['nisn'];
$password_input = $_POST['password'];
$id_siswa_input = $_POST['id_siswa']; 
$nama_siswa     = $_POST['nama_siswa'];
$kelas          = $_POST['kelas'];
$no_telp        = $_POST['no_telp'];
$email          = $_POST['email'];

// 2. Tentukan Password default
$password = empty($password_input) ? 'siswa123' : $password_input;

// 1. Tangkap status checkbox (1 jika dicentang, 0 jika tidak)
$force_pass = isset($_POST['force_password_change']) ? 1 : 0;

// ... (bagian generator ID tetap sama) ...

// =====================================================================
// 3. GENERATOR ID USER (ANTI DUPLIKAT / 100% AMAN)
// =====================================================================
$angka_user = 1;
while(true) {
    // Membuat format US001, US002, dst
    $id_user = "US" . sprintf("%03d", $angka_user); 
    
    // Cek ke database, apakah ID ini sudah ada yang pakai?
    $cek_id = mysqli_query($koneksi, "SELECT IDUser FROM USERS WHERE IDUser = '$id_user'");
    
    // Jika tidak ada yang pakai (0 baris), hentikan pencarian! ID ini siap digunakan.
    if(mysqli_num_rows($cek_id) == 0) {
        break; 
    }
    // Jika sudah dipakai, tambah 1 dan cari lagi
    $angka_user++;
}

// =====================================================================
// 4. GENERATOR ID SISWA (ANTI DUPLIKAT / 100% AMAN)
// =====================================================================
if (empty($id_siswa_input)) {
    // Kita mulai pencarian dari S2601
    $angka_siswa = 2601;
    while(true) {
        $id_siswa = "S" . $angka_siswa; // Menghasilkan S2601, S2602, dst
        
        $cek_siswa = mysqli_query($koneksi, "SELECT IDSiswa FROM SISWA WHERE IDSiswa = '$id_siswa'");
        if(mysqli_num_rows($cek_siswa) == 0) {
            break; // Ketemu yang kosong!
        }
        $angka_siswa++;
    }
} else {
    // Jika admin mengisi manual di form
    $id_siswa = $id_siswa_input;
}

// =====================================================================
// 5. PROSES SIMPAN KE DATABASE
// =====================================================================

// A. Simpan ke tabel USERS (Tambahkan WajibUbahPassword di sini)
$query_user = "INSERT INTO USERS (IDUser, Username, Password, Role, WajibUbahPassword) 
               VALUES ('$id_user', '$nisn', '$password', 'siswa', '$force_pass')";
$simpan_user = mysqli_query($koneksi, $query_user);

if ($simpan_user) {
    // B. Simpan ke tabel SISWA
    $query_profil = "INSERT INTO SISWA (IDSiswa, IDUser, NamaSiswa, NISN, Kelas, Email, NoTelp) 
                     VALUES ('$id_siswa', '$id_user', '$nama_siswa', '$nisn', '$kelas', '$email', '$no_telp')";
// ... lanjutannya tetap sama ...
    $simpan_profil = mysqli_query($koneksi, $query_profil);

    // Jika penyimpanan profil siswa berhasil
    if ($simpan_profil) {
        
        header("Location: daftarSiswa.php?status=sukses_tambah");
        exit;

    } else {
        echo "Gagal menyimpan data profil siswa: " . mysqli_error($koneksi);
    }

} else {
    echo "Gagal membuat akun login: " . mysqli_error($koneksi);
}
?>