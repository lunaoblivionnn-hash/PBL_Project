<?php
session_start();
require '../login/koneksi.php';

if($_SESSION['role'] != 'admin'){
    header("Location: ../login/login.php");
    exit;
}

if(isset($_GET['id']) && isset($_GET['kelas'])) {
    $id_mapel = mysqli_real_escape_string($koneksi, $_GET['id']);
    $kelas = $_GET['kelas'];

    // MESIN SAPU OTOMATIS: BERSIHKAN DATA MAPEL DI JSON GURU SEBELUM DIHAPUS
    $q_old_mapel = mysqli_query($koneksi, "SELECT NamaMapel FROM mapel WHERE IDMapel = '$id_mapel'");
    if($r_old = mysqli_fetch_assoc($q_old_mapel)) {
        $nama_mapel_dihapus = $r_old['NamaMapel'];
        
        // Ambil seluruh data guru untuk diperiksa JSON-nya satu per satu
        $q_all_guru = mysqli_query($koneksi, "SELECT IDGuru, MataPelajaran FROM guru");
        while($g = mysqli_fetch_assoc($q_all_guru)) {
            $id_g = $g['IDGuru'];
            // Decode JSON menjadi Array PHP
            $json_arr = json_decode($g['MataPelajaran'], true);
            
            if(is_array($json_arr)) {
                $perubahan = false;
                
                // Cek setiap kelas yang diampu oleh guru ini
                foreach($json_arr as $k => $daftar_mapel) {
                    // Jika mapel yang akan dihapus ada di list guru ini, cabut!
                    if(($key = array_search($nama_mapel_dihapus, $daftar_mapel)) !== false) {
                        unset($json_arr[$k][$key]);
                        // Tata ulang urutan indeks (misal [0, 2] menjadi [0, 1]) agar JSON tidak error
                        $json_arr[$k] = array_values($json_arr[$k]); 
                        $perubahan = true;
                    }
                    // Jika setelah mapel dicabut, kelasnya jadi kosong, hapus sekalian kelasnya
                    if(empty($json_arr[$k])) {
                        unset($json_arr[$k]);
                        $perubahan = true;
                    }
                }
                
                // Jika ada data yang berhasil dicabut, simpan ulang JSON yang baru ke database guru
                if($perubahan) {
                    // Jika guru jadi tidak mengajar apa-apa lagi, set jadi array kosong {}
                    $json_clean_str = empty($json_arr) ? '{}' : mysqli_real_escape_string($koneksi, json_encode($json_arr));
                    mysqli_query($koneksi, "UPDATE guru SET MataPelajaran = '$json_clean_str' WHERE IDGuru = '$id_g'");
                }
            }
        }
    }

    // Hapus mapel dari database utama
    $query = "DELETE FROM mapel WHERE IDMapel = '$id_mapel'";
    
    if(mysqli_query($koneksi, $query)) {
        // Lempar kembali ke detail kelas dengan notif sukses
        header("Location: detailKelas.php?kelas=" . urlencode($kelas) . "&status=sukses_hapus");
        exit;
    } else {
        echo "Gagal menghapus mata pelajaran: " . mysqli_error($koneksi);
    }
} else {
    header("Location: mataPelajaran.php");
}
?>