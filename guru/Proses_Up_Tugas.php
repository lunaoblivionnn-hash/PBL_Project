<?php
session_start();
require '../login/koneksi.php';

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'siswa'){
    header("Location: ../login/login.php"); exit;
}

if (isset($_POST['submit_tugas'])) {
    $id_tugas = mysqli_real_escape_string($koneksi, $_POST['id_tugas']);
    $id_user = $_SESSION['IDUser'];

    $query_siswa = mysqli_query($koneksi, "SELECT IDSiswa FROM siswa WHERE IDUser='$id_user'");
    $siswa = mysqli_fetch_assoc($query_siswa);
    $id_siswa = $siswa['IDSiswa'] ?? '';

    $file_name = $_FILES['file_jawaban']['name'] ?? '';
    $file_tmp  = $_FILES['file_jawaban']['tmp_name'] ?? '';
    $file_error = $_FILES['file_jawaban']['error'] ?? 1;

    if ($file_error === 0) {
        
        // ==========================================
        // PENAMAAN FILE ASLI MURNI
        // ==========================================
        $new_file_name = str_replace(' ', '_', basename($file_name));
        $destination = '../uploads/tugas/' . $new_file_name;

        if (move_uploaded_file($file_tmp, $destination)) {
            $tanggal_kirim = date('Y-m-d H:i:s');

            // Cek struktur kolom (FileJawaban atau FileKumpul)
            $q_cek = mysqli_query($koneksi, "SHOW COLUMNS FROM pengumpulan_tugas LIKE 'FileJawaban'");
            $kolom = (mysqli_num_rows($q_cek) > 0) ? 'FileJawaban' : 'FileKumpul';

            $query_cek = "SELECT * FROM pengumpulan_tugas WHERE IDTugas='$id_tugas' AND IDSiswa='$id_siswa'";
            $result_cek = mysqli_query($koneksi, $query_cek);

            if (mysqli_num_rows($result_cek) > 0) {
                // Update jika sudah pernah kumpul
                $query_aksi = "UPDATE pengumpulan_tugas 
                               SET $kolom='$new_file_name', TanggalKirim='$tanggal_kirim', Status='Selesai' 
                               WHERE IDTugas='$id_tugas' AND IDSiswa='$id_siswa'";
                mysqli_query($koneksi, $query_aksi);
            } else {
                // Insert baru
                $query_aksi = "INSERT INTO pengumpulan_tugas (IDTugas, IDSiswa, $kolom, TanggalKirim, Status) 
                               VALUES ('$id_tugas', '$id_siswa', '$new_file_name', '$tanggal_kirim', 'Selesai')";
                mysqli_query($koneksi, $query_aksi);
                
                // Tambahan Poin Otomatis Gamifikasi (A001)
                $q_at = mysqli_query($koneksi, "SELECT Poin FROM master_aturan_poin WHERE IDAturan = 'A001'");
                $poin = mysqli_fetch_assoc($q_at)['Poin'] ?? 50;

                $q_gami = mysqli_query($koneksi, "SELECT IDGamifikasi FROM gamifikasi WHERE IDSiswa = '$id_siswa'");
                if(mysqli_num_rows($q_gami) > 0) {
                    mysqli_query($koneksi, "UPDATE gamifikasi SET TotalPoint = TotalPoint + $poin WHERE IDSiswa = '$id_siswa'");
                } else {
                    $idg = "G" . str_pad(rand(100, 9999), 4, "0", STR_PAD_LEFT);
                    mysqli_query($koneksi, "INSERT INTO gamifikasi VALUES ('$idg', '$id_siswa', 'L001', $poin)");
                }
            }

            echo "<script>alert('Tugas berhasil dikumpulkan!'); window.location.href = 'tugas.php?id_tugas=$id_tugas';</script>";
        } else {
            echo "<script>alert('Gagal mengunggah berkas ke server.'); window.location.href = 'tugas.php?id_tugas=$id_tugas';</script>";
        }
    } else {
        echo "<script>alert('Terjadi kesalahan pada file yang diunggah.'); window.location.href = 'tugas.php?id_tugas=$id_tugas';</script>";
    }
}
?>