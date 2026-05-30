<?php
session_start();
require '../login/koneksi.php';

if ($_SESSION['role'] != 'admin') { header("Location: ../login/login.php"); exit; }

if (isset($_POST['upload_csv'])) {
    $file = $_FILES['file_csv']['tmp_name'];
    $wajib_ubah = isset($_POST['force_password_change_csv']) ? 1 : 0;

    // =========================================================
    // 1. AMBIL DAFTAR MAPEL ASLI DARI DATABASE
    // =========================================================
    $master_mapel = [];
    $q_mapel = mysqli_query($koneksi, "SELECT DISTINCT NamaMapel FROM mapel");
    if($q_mapel) {
        while($row = mysqli_fetch_assoc($q_mapel)){
            $master_mapel[] = trim($row['NamaMapel']);
        }
    }
    
    // Daftar Kelas (Sesuai dengan rumpun yang ada di LMS Wongsorejo)
    $master_kelas = ['X AKL 1', 'X AKL 2', 'XI AKL 1', 'XI AKL 2', 'XII AKL 1', 'XII AKL 2'];

    // Ubah semua ke huruf kecil untuk alat deteksi auto-correct (anti besar-kecil error)
    $lower_kelas = array_map('strtolower', $master_kelas);
    $lower_mapel = array_map('strtolower', $master_mapel);

    if (($handle = fopen($file, "r")) !== FALSE) {
        $firstLine = fgets($handle); 
        $delimiter = (strpos($firstLine, ';') !== FALSE) ? ';' : ',';
        rewind($handle);
        fgetcsv($handle, 1000, $delimiter); // Lewati judul kolom

        $baris_excel = 2; 
        $ada_error = false;
        $pesan_error = [];
        $data_valid = []; 
        
        // Alat pendeteksi NIP Ganda di dalam file Excel
        $nips_di_csv = [];

        // =========================================================
        // PUTARAN 1: FASE SCANNING KETAT (CEK SEMUA ERROR)
        // =========================================================
        while (($data = fgetcsv($handle, 1000, $delimiter)) !== FALSE) {
            $nip       = str_replace(' ', '', trim($data[0] ?? '')); 
            $password  = empty(trim($data[1] ?? '')) ? 'guru123' : trim($data[1]);
            $nama      = trim($data[2] ?? ''); 
            $raw_mapel = trim($data[3] ?? ''); 
            $email     = trim($data[4] ?? '');
            $notelp    = trim($data[5] ?? '');

            // Abaikan jika baris benar-benar kosong melompong
            if (empty($nip) && empty($nama)) { $baris_excel++; continue; } 

            $error_baris_ini = [];
            
            // CEK NIP KOSONG & GANDA
            if (empty($nip)) {
                $error_baris_ini[] = "NIP tidak boleh kosong";
            } else {
                // Cek apakah NIP ini ditulis lebih dari 1 kali di Excel ini
                if (in_array($nip, $nips_di_csv)) {
                    $error_baris_ini[] = "NIP '$nip' terdeteksi GANDA di dalam file CSV ini";
                } else {
                    $nips_di_csv[] = $nip; // Masukkan ke catatan untuk dicek baris berikutnya
                }

                // Cek apakah NIP ini sudah ada di Database
                $cek_nip = mysqli_query($koneksi, "SELECT NIP_NUPTK FROM guru WHERE NIP_NUPTK = '".mysqli_real_escape_string($koneksi, $nip)."'");
                if (mysqli_num_rows($cek_nip) > 0) {
                    $error_baris_ini[] = "NIP '$nip' sudah terdaftar di sistem database";
                }
            }

            // CEK FORMAT KELAS & MAPEL
            $akses_guru = [];
            if (!empty($raw_mapel)) {
                $items = explode(',', $raw_mapel);
                foreach ($items as $item) {
                    $parts = explode('-', $item);
                    if (count($parts) == 2) {
                        $kelas_input = strtolower(trim($parts[0]));
                        $mapel_input = strtolower(trim($parts[1]));
                        
                        $idx_kelas = array_search($kelas_input, $lower_kelas);
                        $idx_mapel = array_search($mapel_input, $lower_mapel);

                        // Munculkan Error Typo / Tidak dikenali
                        if ($idx_kelas === false) $error_baris_ini[] = "Kelas '".trim($parts[0])."' tidak valid";
                        if ($idx_mapel === false) $error_baris_ini[] = "Mapel '".trim($parts[1])."' belum ditambahkan di database";

                        // Jika aman, susun ke array Json
                        if ($idx_kelas !== false && $idx_mapel !== false) {
                            $kelas_asli = $master_kelas[$idx_kelas]; 
                            $mapel_asli = $master_mapel[$idx_mapel];
                            if (!isset($akses_guru[$kelas_asli])) $akses_guru[$kelas_asli] = [];
                            $akses_guru[$kelas_asli][] = $mapel_asli;
                        }
                    } else {
                        $error_baris_ini[] = "Format Mengajar salah (Kurang tanda strip '-' pada '$item')";
                    }
                }
            }

            // REKAP ERROR PER BARIS
            if (!empty($error_baris_ini)) {
                $ada_error = true;
                $pesan_error[] = "<b>Baris $baris_excel:</b> " . implode(" | ", $error_baris_ini);
            } else {
                // Jika 100% mulus tanpa 1 pun error, masukkan ke keranjang siap simpan
                $data_valid[] = [
                    'nip' => $nip, 'password' => $password, 'nama' => $nama,
                    'akses' => $akses_guru, 'email' => $email, 'notelp' => $notelp
                ];
            }
            $baris_excel++;
        }
        fclose($handle);

        // =========================================================
        // JIKA ADA 1 SAJA ERROR: BLOKIR & TAMPILKAN LAPORAN
        // =========================================================
        if ($ada_error) {
            // Tampilkan maksimal 12 error agar popup muat dan detail terlihat jelas
            $gabungan_error = implode("<br><br>", array_slice($pesan_error, 0, 12));
            if (count($pesan_error) > 12) $gabungan_error .= "<br><br><b>...dan " . (count($pesan_error) - 12) . " baris error lainnya.</b>";
            
            $_SESSION['error_csv_guru'] = $gabungan_error; 
            header("Location: daftarGuru.php?status=error_csv");
            exit;
        }

        // =========================================================
        // PUTARAN 2: JIKA 100% MULUS, MASUKKAN KE DATABASE
        // =========================================================
        foreach ($data_valid as $d) {
            $nip = mysqli_real_escape_string($koneksi, $d['nip']);
            $password = mysqli_real_escape_string($koneksi, $d['password']);
            $nama = mysqli_real_escape_string($koneksi, $d['nama']);
            $email = mysqli_real_escape_string($koneksi, $d['email']);
            $notelp = mysqli_real_escape_string($koneksi, $d['notelp']);
            $json_mapel = mysqli_real_escape_string($koneksi, json_encode($d['akses']));

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

            // Eksekusi Simpan
            mysqli_query($koneksi, "INSERT INTO users (IDUser, Username, Password, Role, Status, WajibUbahPassword) VALUES ('$id_user', '$nip', '$password', 'guru', 'Aktif', '$wajib_ubah')");
            mysqli_query($koneksi, "INSERT INTO guru (IDGuru, IDUser, NamaGuru, NIP_NUPTK, Email, NoTelp, MataPelajaran) VALUES ('$id_guru', '$id_user', '$nama', '$nip', '$email', '$notelp', '$json_mapel')");
        }
        
        header("Location: daftarGuru.php?status=sukses_upload");
        exit;
    }
}
?>