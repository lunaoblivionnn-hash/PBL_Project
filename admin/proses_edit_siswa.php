<?php
session_start();
require '../login/koneksi.php';

if (isset($_POST['simpan_perubahan'])) {
    
    // 1. Tangkap semua data dari form dengan aman
    $id_siswa    = mysqli_real_escape_string($koneksi, $_POST['id_siswa']);
    $id_user     = mysqli_real_escape_string($koneksi, $_POST['id_user']);
    $nisn_lama   = mysqli_real_escape_string($koneksi, $_POST['nisn_lama']);
    $nisn_baru   = mysqli_real_escape_string($koneksi, $_POST['nisn']);
    $nama_siswa  = mysqli_real_escape_string($koneksi, $_POST['nama_siswa']);
    $kelas       = mysqli_real_escape_string($koneksi, $_POST['kelas']);
    $no_telp     = mysqli_real_escape_string($koneksi, $_POST['no_telp']);
    $email       = mysqli_real_escape_string($koneksi, $_POST['email']);
    $password    = mysqli_real_escape_string($koneksi, $_POST['password']);
    
    // Tangkap checkbox dan status dari select option
    $force_pass  = isset($_POST['force_password_change']) ? 1 : 0;
    $status_baru = mysqli_real_escape_string($koneksi, $_POST['status_akun']);

    // 2. CEK DUPLIKAT NISN (Jika admin mengubah NISN, pastikan NISN baru belum dipakai orang lain)
    if ($nisn_baru != $nisn_lama) {
        $cek_nisn = mysqli_query($koneksi, "SELECT Username FROM USERS WHERE Username = '$nisn_baru'");
        if(mysqli_num_rows($cek_nisn) > 0) {
            echo "<script>alert('GAGAL! NISN $nisn_baru sudah digunakan siswa lain.'); window.history.back();</script>";
            exit;
        }
    }

    // 3. UPDATE TABEL SISWA (Profil)
    $query_update_profil = "UPDATE SISWA SET 
                            NamaSiswa = '$nama_siswa',
                            NISN = '$nisn_baru',
                            Kelas = '$kelas',
                            Email = '$email',
                            NoTelp = '$no_telp'
                            WHERE IDSiswa = '$id_siswa'";
    $update_profil = mysqli_query($koneksi, $query_update_profil);

    // 4. UPDATE TABEL USERS (Akun Login & Status)
    if ($update_profil) {
        
        // Logika: Jika form password kosong, jangan ubah sandi lama!
        if (empty($password)) {
            $query_update_user = "UPDATE USERS SET 
                                  Username = '$nisn_baru',
                                  WajibUbahPassword = '$force_pass',
                                  Status = '$status_baru' 
                                  WHERE IDUser = '$id_user'";
        } else {
            $query_update_user = "UPDATE USERS SET 
                                  Username = '$nisn_baru',
                                  Password = '$password',
                                  WajibUbahPassword = '$force_pass',
                                  Status = '$status_baru'
                                  WHERE IDUser = '$id_user'";
        }
        
        $update_user = mysqli_query($koneksi, $query_update_user);

        if($update_user) {
            // Berhasil Update, lempar kembali ke halaman Daftar Siswa
            header("Location: daftarSiswa.php?status=sukses_edit");
            exit;
        } else {
            echo "Gagal Update Data Login: " . mysqli_error($koneksi);
        }

    } else {
        echo "Gagal Update Data Profil: " . mysqli_error($koneksi);
    }
} else {
    // Jika ada yang mencoba akses file ini secara langsung lewat URL
    header("Location: admin.php");
    exit;
}
?>