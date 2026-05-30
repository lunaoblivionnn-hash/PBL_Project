<?php
session_start();
require '../login/koneksi.php';

if (isset($_POST['upload_csv'])) {
    $file = $_FILES['file_csv']['tmp_name'];
    
    // =====================================================================
    // TANGKAP STATUS WAJIB UBAH PASSWORD DARI CHECKBOX FORM
    // =====================================================================
    $wajib_ubah = isset($_POST['force_password_change_csv']) ? 1 : 0;

    // Buka file CSV untuk dibaca
    if (($handle = fopen($file, "r")) !== FALSE) {
        
        // SOLUSI UNIVERSAL: DETEKSI PEMISAH (DELIMITER) OTOMATIS
        $firstLine = fgets($handle); // Ambil baris pertama (header)
        $delimiter = (strpos($firstLine, ';') !== FALSE) ? ';' : ',';
        
        // Kembalikan kursor pembaca ke awal file
        rewind($handle);

        // Lewati baris pertama (judul kolom)
        fgetcsv($handle, 1000, $delimiter); 

        // Looping membaca setiap baris data siswa
        while (($data = fgetcsv($handle, 1000, $delimiter)) !== FALSE) {
            
            // Tangkap data sesuai urutan kolom
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

            // GENERATOR ID USER (US0001) - AUTO
            $angka_user = 1;
            while(true) {
                $id_user = "US" . sprintf("%03d", $angka_user); 
                $cek_id = mysqli_query($koneksi, "SELECT IDUser FROM USERS WHERE IDUser = '$id_user'");
                if(mysqli_num_rows($cek_id) == 0) break; 
                $angka_user++;
            }

            // GENERATOR ID SISWA (IS001) - AUTO
            $angka_siswa = 1;
            while(true) {
                $id_siswa = "IS" . sprintf("%03d", $angka_siswa); 
                $cek_siswa = mysqli_query($koneksi, "SELECT IDSiswa FROM SISWA WHERE IDSiswa = '$id_siswa'");
                if(mysqli_num_rows($cek_siswa) == 0) break; 
                $angka_siswa++;
            }

            // =====================================================================
            // SIMPAN KE DATABASE (DENGAN WAJIB UBAH PASSWORD)
            // =====================================================================
            $query_user = "INSERT INTO USERS (IDUser, Username, Password, Role, Status, WajibUbahPassword) 
                           VALUES ('$id_user', '$username', '$password', 'siswa', 'Aktif', '$wajib_ubah')";
            
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