<?php
session_start();
require '../login/koneksi.php';

if ($_SESSION['role'] != 'admin') {
    header("Location: ../login/login.php");
    exit;
}

if (isset($_POST['upload_csv'])) {
    $file = $_FILES['file_csv']['tmp_name'];
    
    // TANGKAP STATUS WAJIB UBAH PASSWORD DARI CHECKBOX FORM
    $wajib_ubah = isset($_POST['force_password_change_csv']) ? 1 : 0;

    // Daftar Kelas & Mapel yang SAH di sekolahmu (Sebagai acuan kebenaran)
    $master_kelas = ['X AKL 1', 'X AKL 2', 'XI AKL 1', 'XI AKL 2', 'XII AKL 1', 'XII AKL 2'];
    $master_mapel = ['Akuntansi Dasar', 'Ekonomi Bisnis', 'Matematika', 'Bahasa Indonesia', 'Bahasa Inggris', 'Pendidikan Agama'];

    // Buat versi huruf kecil semua untuk alat deteksi auto-correct
    $lower_kelas = array_map('strtolower', $master_kelas);
    $lower_mapel = array_map('strtolower', $master_mapel);

    $berhasil = 0;
    $gagal_format = 0;

    if (($handle = fopen($file, "r")) !== FALSE) {
        $firstLine = fgets($handle); 
        $delimiter = (strpos($firstLine, ';') !== FALSE) ? ';' : ',';
        rewind($handle);
        fgetcsv($handle, 1000, $delimiter); 

        while (($data = fgetcsv($handle, 1000, $delimiter)) !== FALSE) {
            $nip       = mysqli_real_escape_string($koneksi, trim($data[0])); 
            $password  = empty(trim($data[1])) ? '' : mysqli_real_escape_string($koneksi, trim($data[1]));
            $nama      = mysqli_real_escape_string($koneksi, trim($data[2])); 
            $raw_mapel = trim($data[3]); 
            $email     = mysqli_real_escape_string($koneksi, trim($data[4]));
            $notelp    = mysqli_real_escape_string($koneksi, trim($data[5]));

            if (empty($nip)) continue;

            // Set password default jika dikosongkan di Excel
            if (empty($password)) $password = 'guru123';

            $akses_guru = [];
            $baris_ini_valid = true; // Anggap benar dulu
            
            if (!empty($raw_mapel)) {
                $items = explode(',', $raw_mapel);
                foreach ($items as $item) {
                    $parts = explode('-', $item);
                    
                    if (count($parts) == 2) {
                        $kelas_input = strtolower(trim($parts[0]));
                        $mapel_input = strtolower(trim($parts[1]));
                        
                        // Cek apakah ketikan admin ada di daftar Master?
                        $idx_kelas = array_search($kelas_input, $lower_kelas);
                        $idx_mapel = array_search($mapel_input, $lower_mapel);

                        // Jika Valid (Typo huruf kecil/besar dimaafkan)
                        if ($idx_kelas !== false && $idx_mapel !== false) {
                            $kelas_asli = $master_kelas[$idx_kelas]; // Ambil penulisan yang benar
                            $mapel_asli = $master_mapel[$idx_mapel];
                            
                            if (!isset($akses_guru[$kelas_asli])) {
                                $akses_guru[$kelas_asli] = [];
                            }
                            $akses_guru[$kelas_asli][] = $mapel_asli;
                        } else {
                            // Jika kelas/mapel ngawur (tidak terdaftar)
                            $baris_ini_valid = false; 
                        }
                    } else {
                        // Jika tidak ada tanda strip (-)
                        $baris_ini_valid = false;
                    }
                }
            }

            // Jika ada format ngawur di baris ini, lewati (jangan simpan ke DB), dan hitung sebagai gagal
            if (!$baris_ini_valid && !empty($raw_mapel)) {
                $gagal_format++;
                continue; 
            }

            $json_mapel = mysqli_real_escape_string($koneksi, json_encode($akses_guru));

            // Generator ID (US001 & GR001)
            $angka_user = 1;
            while(true) {
                $id_user = "US" . sprintf("%03d", $angka_user); 
                if(mysqli_num_rows(mysqli_query($koneksi, "SELECT IDUser FROM users WHERE IDUser = '$id_user'")) == 0) break; 
                $angka_user++;
            }
            $angka_guru = 1;
            while(true) {
                $id_guru = "GR" . sprintf("%03d", $angka_guru); 
                if(mysqli_num_rows(mysqli_query($koneksi, "SELECT IDGuru FROM guru WHERE IDGuru = '$id_guru'")) == 0) break; 
                $angka_guru++;
            }

            // Eksekusi Simpan dengan status $wajib_ubah
            $query_user = "INSERT INTO users (IDUser, Username, Password, Role, Status, WajibUbahPassword) VALUES ('$id_user', '$nip', '$password', 'guru', 'Aktif', '$wajib_ubah')";
            if(mysqli_query($koneksi, $query_user)) {
                $query_profil = "INSERT INTO guru (IDGuru, IDUser, NamaGuru, NIP_NUPTK, Email, NoTelp, MataPelajaran) VALUES ('$id_guru', '$id_user', '$nama', '$nip', '$email', '$notelp', '$json_mapel')";
                mysqli_query($koneksi, $query_profil);
                $berhasil++;
            }
        }
        fclose($handle);

        // Lempar hasil ke URL
        header("Location: daftarGuru.php?status=info_upload&ok=$berhasil&fail=$gagal_format");
        exit;
    }
}
?>