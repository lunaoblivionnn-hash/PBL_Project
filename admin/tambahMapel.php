<?php
session_start();
require '../login/koneksi.php';

if($_SESSION['role'] != 'admin'){
    header("Location: ../login/login.php");
    exit;
}

// KODE BARU (Sudah kebal terhadap aturan ketat MySQL):
$query_guru = mysqli_query($koneksi, "SELECT MIN(IDGuru) AS IDGuru, NamaGuru FROM guru GROUP BY NamaGuru ORDER BY NamaGuru ASC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Mapel - Admin LMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        .custom-checkbox-container {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            border: 1px solid #e9ecef;
        }
        .form-check-label { cursor: pointer; font-weight: 500; }
    </style>
</head>
<body class="bg-light">

    <nav class="navbar navbar-dark bg-danger shadow-sm">
        <div class="container-fluid px-4">
            <a class="navbar-brand fw-bold" href="mataPelajaran.php">
                <i class="bi bi-arrow-left-circle me-2"></i> Kembali ke Menu Mapel
            </a>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="card-header bg-white border-bottom p-4">
                        <h4 class="fw-bold mb-0 text-danger">Konfigurasi Mata Pelajaran Baru</h4>
                    </div>
                    <div class="card-body p-4 p-md-5">
                        <form action="proses_tambah_mapel.php" method="POST" enctype="multipart/form-data">
                            
                            <div class="row g-4">
                                <div class="col-md-8">
                                    <label class="form-label fw-bold">Nama Mata Pelajaran <span class="text-danger">*</span></label>
                                    <input type="text" name="nama_mapel" class="form-control form-control-lg bg-light border-0" placeholder="Contoh: Akuntansi Dasar" required>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Tahun Ajaran <span class="text-danger">*</span></label>
                                    <select name="tahun_ajaran" class="form-select form-control-lg bg-light border-0" required>
                                        <option value="2024/2025">2024/2025</option>
                                        <option value="2025/2026" selected>2025/2026</option>
                                    </select>
                                </div>

                                <div class="col-md-12">
                                    <label class="form-label fw-bold">Guru Pengampu</label>
                                    <select name="id_guru" class="form-select bg-light border-0">
                                        <option value="">-- Pilih Guru (Bisa dikosongkan sementara) --</option>
                                        <?php while($g = mysqli_fetch_assoc($query_guru)): ?>
                                            <option value="<?= $g['IDGuru'] ?>"><?= $g['NamaGuru'] ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                    <div class="form-text mt-2"><i class="bi bi-info-circle"></i> Mata pelajaran ini akan muncul otomatis di Dashboard Guru yang dipilih.</div>
                                </div>

                                <div class="col-md-12 mt-4">
                                    <label class="form-label fw-bold">Pilih Kelas yang Mendapat Mapel Ini <span class="text-danger">*</span></label>
                                    <div class="custom-checkbox-container">
                                        <div class="row g-3">
                                            <?php 
                                            $kelas_list = ['X AKL 1', 'X AKL 2', 'XI AKL 1', 'XI AKL 2', 'XII AKL 1', 'XII AKL 2'];
                                            foreach($kelas_list as $index => $k): 
                                            ?>
                                            <div class="col-md-4 col-sm-6">
                                                <div class="form-check">
                                                    <input class="form-check-input border-secondary" type="checkbox" name="kelas[]" value="<?= $k ?>" id="kelas<?= $index ?>">
                                                    <label class="form-check-label w-100" for="kelas<?= $index ?>">
                                                        <?= $k ?>
                                                    </label>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <div class="form-text mt-2 text-primary fw-semibold">Centang beberapa kelas sekaligus untuk mempercepat input data!</div>
                                </div>

                                <div class="col-12 mt-4">
                                    <label class="form-label fw-bold">Deskripsi Singkat Mapel</label>
                                    <textarea name="deskripsi" class="form-control bg-light border-0" rows="3" placeholder="Jelaskan apa yang dipelajari di mapel ini..."></textarea>
                                </div>

                                <div class="col-12 mt-4">
                                    <label class="form-label fw-bold">Cover Mata Pelajaran (Opsional)</label>
                                    <input type="file" name="gambar" id="imgInput" class="form-control" accept="image/*">
                                    <div class="form-text mb-3">
                                        <i class="bi bi-image"></i> Rekomendasi: Gambar lanskap ukuran <strong>700x320 piksel</strong>. Maksimal ukuran file: <strong class="text-danger">2 MB</strong>. Sistem akan memotong bagian tengah gambar secara otomatis agar pas.
                                    </div>
                                    
                                    <div class="mt-2">
                                        <label class="small text-danger fw-bold mb-2 d-none" id="teksPreview">Preview Tampilan di Halaman Kelas:</label><br>
                                        <img id="preview" src="#" alt="Preview" class="d-none shadow-sm" style="width: 100%; max-width: 350px; height: 160px; object-fit: cover; object-position: center; border-radius: 12px; border: 3px solid #fff;">
                                    </div>
                                </div>
                            </div>

                            <div class="mt-5 pt-4 border-top d-flex justify-content-end gap-2">
                                <button type="reset" class="btn btn-light px-4">Reset</button>
                                <button type="submit" class="btn btn-primary px-5 fw-bold shadow">Simpan Mata Pelajaran</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Fitur Preview Gambar
        const imgInput = document.getElementById('imgInput');
        const preview = document.getElementById('preview');
        const teksPreview = document.getElementById('teksPreview');

        imgInput.onchange = evt => {
            const [file] = imgInput.files;
            if (file) {
                preview.src = URL.createObjectURL(file);
                preview.classList.remove('d-none');
                teksPreview.classList.remove('d-none'); // Munculkan teks panduan
            }
        }
    </script>
</body>
</html>