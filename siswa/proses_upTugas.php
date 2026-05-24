<?php
session_start();
require '../login/koneksi.php';

// Pastikan yang mengakses adalah siswa
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'siswa'){
    header("Location: ../login/login.php"); exit;
}

if (isset($_POST['submit_tugas'])) {
    $id_tugas = mysqli_real_escape_string($koneksi, $_POST['id_tugas']);
    $id_user = $_SESSION['IDUser'];

    // 1. Ambil IDSiswa berdasarkan IDUser session
    $query_siswa = mysqli_query($koneksi, "SELECT IDSiswa FROM siswa WHERE IDUser='$id_user'");
    $siswa = mysqli_fetch_assoc($query_siswa);
    $id_siswa = $siswa['IDSiswa'] ?? '';

    // 2. Ambil informasi file yang diunggah (SINKRON dengan name="file_jawaban" di tugas.php)
    $file_name = $_FILES['file_jawaban']['name'] ?? '';
    $file_tmp  = $_FILES['file_jawaban']['tmp_name'] ?? '';
    $file_error = $_FILES['file_jawaban']['error'] ?? 1;

    if ($file_error === 0) {
        // Ambil ekstensi file
        $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
        
        // Buat nama file unik baru agar tidak bentrok
        $new_file_name = "TUGAS_" . uniqid() . "." . $file_ext;
        
        // Tentukan folder tujuan penyimpanan berkas
        $target_dir = "../uploads/tugas/";
        
        // Pastikan folder tujuan sudah ada
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $target_file = $target_dir . $new_file_name;

        if (move_uploaded_file($file_tmp, $target_file)) {
            
            // Set zona waktu pengiriman tugas
            date_default_timezone_set('Asia/Jakarta');
            $tanggal_kirim = date('Y-m-d H:i:s');

            // Cek apakah siswa sudah pernah mengumpulkan tugas ini sebelumnya
            $cek_kumpul = mysqli_query($koneksi, "SELECT * FROM pengumpulan_tugas WHERE IDTugas='$id_tugas' AND IDSiswa='$id_siswa'");
            
            // PERBAIKAN: Nama kolom disesuaikan menjadi FileJawaban dan TanggalKirim sesuai database kamu
            if (mysqli_num_rows($cek_kumpul) > 0) {
                // Jika sudah ada, lakukan UPDATE (timpa file lama)
                $query_aksi = "UPDATE pengumpulan_tugas SET 
                                FileJawaban='$new_file_name', 
                                TanggalKirim='$tanggal_kirim' 
                                WHERE IDTugas='$id_tugas' AND IDSiswa='$id_siswa'";
            } else {
                // Jika belum ada, lakukan INSERT baru
                $query_aksi = "INSERT INTO pengumpulan_tugas (IDTugas, IDSiswa, FileJawaban, TanggalKirim, Nilai, Status) 
                                VALUES ('$id_tugas', '$id_siswa', '$new_file_name', '$tanggal_kirim', NULL, 'Belum Selesai')";
            }

            if (mysqli_query($koneksi, $query_aksi)) {
                echo "<script>
                        alert('Tugas berhasil dikumpulkan!');
                        window.location.href = 'tugas.php?id_tugas=$id_tugas';
                      </script>";
            } else {
                echo "<script>
                        alert('Gagal menyimpan data ke database.');
                        window.location.href = 'tugas.php?id_tugas=$id_tugas';
                      </script>";
            }
        } else {
            echo "<script>
                    alert('Gagal mengunggah berkas ke server. Periksa hak akses folder uploads/tugas/.');
                    window.location.href = 'tugas.php?id_tugas=$id_tugas';
                  </script>";
        }
    } else {
        echo "<script>
                alert('Terjadi kesalahan pada file yang diunggah.');
                window.location.href = 'tugas.php?id_tugas=$id_tugas';
              </script>";
    }
} else {
    header("Location: tugas.php");
    exit;
}
?>