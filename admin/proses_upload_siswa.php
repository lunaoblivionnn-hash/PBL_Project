<?php
session_start();
require '../login/koneksi.php';

if (isset($_POST['upload_csv'])) {
    $file = $_FILES['file_csv']['tmp_name'];

    // Buka file CSV untuk dibaca
    if (($handle = fopen($file, "r")) !== FALSE) {
        
        // =====================================================================
        // SOLUSI UNIVERSAL: DETEKSI PEMISAH (DELIMITER) OTOMATIS
        // =====================================================================
        $firstLine = fgets($handle); // Ambil baris pertama (header)
        // Cek apakah ada karakter titik koma (;) di baris pertama
        $delimiter = (strpos($firstLine, ';') !== FALSE) ? ';' : ',';
        
        // Kembalikan kursor pembaca ke awal file agar baris pertama bisa dibaca ulang oleh fgetcsv
        rewind($handle);

        // Lewati baris pertama (judul kolom: username, password, nama, dll)
        fgetcsv($handle, 1000, $delimiter); 

        // Looping membaca setiap baris data siswa
        while (($data = fgetcsv($handle, 1000, $delimiter)) !== FALSE) {
            
            // Tangkap data sesuai urutan kolom: username, password, nama, kelas, email, notelp
            $username = mysqli_real_escape_string($koneksi, $data[0]); 
            $password = mysqli_real_escape_string($koneksi, $data[1]);
            $nama     = mysqli_real_escape_string($koneksi, $data[2]); 
            $kelas    = mysqli_real_escape_string($koneksi, $data[3]);
            $email    = mysqli_real_escape_string($koneksi, $data[4]);
            $notelp   = mysqli_real_escape_string($koneksi, $data[5]);

            // Jika baris kosong (username tidak ada), lewati
            if (empty($username)) continue;
            
            // Set password default jika dikosongkan di Excel
            if (empty($password)) $password = 'siswa123';

            // =====================================================================
            // GENERATOR ID USER (US0001) - AUTO
            // =====================================================================
            $angka_user = 1;
            while(true) {
                $id_user = "US" . sprintf("%03d", $angka_user); 
                $cek_id = mysqli_query($koneksi, "SELECT IDUser FROM USERS WHERE IDUser = '$id_user'");
                if(mysqli_num_rows($cek_id) == 0) break; 
                $angka_user++;
            }

            // =====================================================================
            // GENERATOR ID SISWA (IS001) - AUTO
            // =====================================================================
            $angka_siswa = 1;
            while(true) {
                $id_siswa = "IS" . sprintf("%03d", $angka_siswa); 
                $cek_siswa = mysqli_query($koneksi, "SELECT IDSiswa FROM SISWA WHERE IDSiswa = '$id_siswa'");
                if(mysqli_num_rows($cek_siswa) == 0) break; 
                $angka_siswa++;
            }

            // =====================================================================
            // SIMPAN KE DATABASE
            // =====================================================================
            // Status otomatis 'Aktif' karena sudah kita set DEFAULT di database
            $query_user = "INSERT INTO USERS (IDUser, Username, Password, Role, Status) 
                           VALUES ('$id_user', '$username', '$password', 'siswa', 'Aktif')";
            
            if(mysqli_query($koneksi, $query_user)) {
                $query_profil = "INSERT INTO SISWA (IDSiswa, IDUser, NamaSiswa, NISN, Kelas, Email, NoTelp) 
                                 VALUES ('$id_siswa', '$id_user', '$nama', '$username', '$kelas', '$email', '$notelp')";
                mysqli_query($koneksi, $query_profil);
            }
        }

        fclose($handle);
        // Kembali ke daftar siswa dengan notif sukses
        header("Location: daftarSiswa.php?status=sukses_upload");
        exit;
    } else {
        echo "Gagal membuka file CSV.";
    }
}
?>