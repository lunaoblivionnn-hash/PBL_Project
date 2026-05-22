<?php
session_start();
require '../login/koneksi.php';

if($_SESSION['role'] != 'admin' || !isset($_GET['id']) || !isset($_GET['kelas'])){
    header("Location: mataPelajaran.php");
    exit;
}

$id_mapel = mysqli_real_escape_string($koneksi, $_GET['id']);
$kelas = $_GET['kelas'];

// Ambil data mapel yang mau diedit
$query_mapel = mysqli_query($koneksi, "SELECT * FROM mapel WHERE IDMapel = '$id_mapel'");
$data = mysqli_fetch_assoc($query_mapel);

if(!$data){
    echo "<script>alert('Data tidak ditemukan!'); window.location='mataPelajaran.php';</script>";
    exit;
}

// Ambil daftar guru untuk dropdown
$query_guru = mysqli_query($koneksi, "SELECT IDGuru, NamaGuru FROM guru ORDER BY NamaGuru ASC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Mapel - Admin LMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body class="bg-light">

    <nav class="navbar navbar-dark bg-danger shadow-sm">
        <div class="container-fluid px-4">
            <a class="navbar-brand fw-bold" href="detailKelas.php?kelas=<?= urlencode($kelas) ?>">
                <i class="bi bi-arrow-left-circle me-2"></i> Kembali ke Kelas <?= htmlspecialchars($kelas) ?>
            </a>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="card-header bg-white border-bottom p-4">
                        <h4 class="fw-bold mb-0 text-primary">Edit Mata Pelajaran</h4>
                    </div>
                    <div class="card-body p-4 p-md-5">
                        <form action="proses_edit_mapel.php" method="POST" enctype="multipart/form-data">
                            
                            <input type="hidden" name="id_mapel" value="<?= $data['IDMapel'] ?>">
                            

                            <div class="row g-4">
                                <div class="col-md-8">
                                    <label class="form-label fw-bold">Nama Mata Pelajaran</label>
                                    <input type="text" name="nama_mapel" class="form-control form-control-lg bg-light border-0" value="<?= $data['NamaMapel'] ?>" required>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Tahun Ajaran</label>
                                    <select name="tahun_ajaran" class="form-select form-control-lg bg-light border-0" required>
                                        <option value="2024/2025" <?= ($data['TahunAjaran'] == '2024/2025') ? 'selected' : '' ?>>2024/2025</option>
                                        <option value="2025/2026" <?= ($data['TahunAjaran'] == '2025/2026') ? 'selected' : '' ?>>2025/2026</option>
                                    </select>
                                </div>

                                <div class="col-md-12">
                                    <label class="form-label fw-bold">Guru Pengampu</label>
                                    <select name="id_guru" class="form-select bg-light border-0">
                                        <option value="">-- Pilih Guru (Bisa dikosongkan sementara) --</option>
                                        <?php while($g = mysqli_fetch_assoc($query_guru)): ?>
                                            <option value="<?= $g['IDGuru'] ?>" <?= ($data['IDGuru'] == $g['IDGuru']) ? 'selected' : '' ?>>
                                                <?= $g['NamaGuru'] ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="col-md-12 mt-3">
                                    <label class="form-label fw-bold">Pilih Kelas yang Mendapat Mapel Ini <span class="text-danger">*</span></label>
                                    <div class="p-3 bg-light border rounded">
                                        <div class="row g-3">
                                            <?php 
                                            $kelas_list = ['X AKL 1', 'X AKL 2', 'XI AKL 1', 'XI AKL 2', 'XII AKL 1', 'XII AKL 2'];
                                            
                                            // Terjemahkan data kelas dari database (JSON) menjadi array
                                            $kelas_terpilih = json_decode($data['Kelas'], true);
                                            
                                            // Fallback: Jika data lama di database belum JSON, jadikan array manual
                                            if(!is_array($kelas_terpilih)) { 
                                                $kelas_terpilih = [$data['Kelas']]; 
                                            }

                                            foreach($kelas_list as $index => $k): 
                                                // Cek apakah kelas ini ada di dalam array database
                                                $is_checked = in_array($k, $kelas_terpilih) ? 'checked' : '';
                                            ?>
                                            <div class="col-md-4 col-sm-6">
                                                <div class="form-check">
                                                    <input class="form-check-input border-secondary" type="checkbox" name="kelas[]" value="<?= $k ?>" id="kelas<?= $index ?>" <?= $is_checked ?>>
                                                    <label class="form-check-label w-100" for="kelas<?= $index ?>"><?= $k ?></label>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <label class="form-label fw-bold">Deskripsi Singkat Mapel</label>
                                    <textarea name="deskripsi" class="form-control bg-light border-0" rows="3"><?= $data['Deskripsi'] ?></textarea>
                                </div>

                                <div class="col-12 mt-4">
                                    <label class="form-label fw-bold">Ganti Cover (Biarkan kosong jika tidak ingin ganti)</label>
                                    <input type="file" name="gambar" id="imgInput" class="form-control" accept="image/*">
                                    <div class="form-text mb-3">
                                        <i class="bi bi-image"></i> Rekomendasi: Gambar lanskap ukuran <strong>700x320 piksel</strong>. Maksimal ukuran file: <strong class="text-danger">2 MB</strong>.
                                    </div>
                                    
                                    <div class="mt-3">
                                        <label class="small text-danger fw-bold mb-2" id="teksPreview">Tampilan Cover Saat Ini:</label><br>
                                        <?php if(!empty($data['Gambar']) && file_exists("../image/mapel/" . $data['Gambar'])): ?>
                                            <img id="preview" src="../image/mapel/<?= $data['Gambar'] ?>" alt="Preview" class="shadow-sm" style="width: 100%; max-width: 350px; height: 160px; object-fit: cover; object-position: center; border-radius: 12px; border: 3px solid #fff;">
                                        <?php else: ?>
                                            <img id="preview" src="#" alt="Preview" class="d-none shadow-sm" style="width: 100%; max-width: 350px; height: 160px; object-fit: cover; object-position: center; border-radius: 12px; border: 3px solid #fff;">
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-5 pt-4 border-top d-flex justify-content-end gap-2">
                                <a href="detailKelas.php?kelas=<?= urlencode($kelas) ?>" class="btn btn-light px-4">Batal</a>
                                <button type="submit" class="btn btn-primary px-5 fw-bold shadow">Update Mapel</button>
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
                teksPreview.innerHTML = "Preview Cover Baru:"; // Ganti teks jika pilih gambar baru
            }
        }
    </script>
</body>
</html>