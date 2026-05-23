<?php
session_start();
require '../login/koneksi.php';

// Pastikan yang mengakses adalah guru
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'guru'){
    header("Location: ../login/login.php");
    exit;
}

// Tangkap id_mapel dan kelas dari URL (Sangat Penting!)
$id_mapel = mysqli_real_escape_string($koneksi, $_GET['id_mapel'] ?? '');
$kelas = mysqli_real_escape_string($koneksi, $_GET['kelas'] ?? '');

if(empty($id_mapel) || empty($kelas)){
    echo "<script>alert('Akses tidak valid!'); window.location='guru.php';</script>";
    exit;
}

$nama_mapel = 'Mata Pelajaran';
$query_nama = mysqli_query($koneksi,"SELECT NamaMapel FROM mapel WHERE IDMapel='$id_mapel'");
if($r = mysqli_fetch_assoc($query_nama)) {
    $nama_mapel = $r['NamaMapel'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Materi – LMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        :root { --primary: #4f46e5; --grad: linear-gradient(135deg, #4f46e5, #3730a3); }
        * { font-family: 'Segoe UI', system-ui, sans-serif; }
        body { background: #f8f9fa; }
        .topbar { background: var(--grad); color: #fff; padding: 0 20px; height: 60px; display: flex; align-items: center; gap: 15px; position: sticky; top: 0; z-index: 100; box-shadow: 0 2px 10px rgba(0,0,0,.2); }
        .topbar a { color: #fff; text-decoration: none; transition: 0.2s; }
        .topbar a:hover { opacity: 0.8; }
        .card-form { background: #fff; border-radius: 20px; padding: 30px; box-shadow: 0 10px 30px rgba(0,0,0,.05); border: none; }
        .form-label { font-weight: 700; font-size: 0.8rem; color: #495057; text-transform: uppercase; letter-spacing: 0.5px; }
        .form-control, textarea { border-radius: 10px; background: #f8fafc; border: 2px solid #e9ecef; padding: 12px 15px; font-size: 0.9rem; transition: .3s; }
        .form-control:focus, textarea:focus { background: #fff; border-color: var(--primary); box-shadow: none; }
        .upload-zone { border: 2px dashed #c7d2fe; border-radius: 15px; padding: 35px 20px; text-align: center; cursor: pointer; transition: .3s; background: #f8fafc; }
        .upload-zone:hover, .upload-zone.over { border-color: var(--primary); background: #eef2ff; }
        .btn-submit { background: var(--grad); color: #fff; border-radius: 12px; padding: 14px; font-weight: 700; border: none; transition: .3s; width: 100%; }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(79,70,229,.3); color: #fff; }
        .file-preview { background: #eef2ff; border-radius: 12px; padding: 12px 16px; display: none; align-items: center; gap: 12px; margin-top: 15px; border: 1px solid #c7d2fe; }
        .file-preview.show { display: flex; }
        .type-badge { background: #e0e7ff; color: #4338ca; font-size: 0.7rem; padding: 3px 10px; border-radius: 6px; font-weight: 700; }
    </style>
</head>
<body>

<div class="topbar">
    <a href="kelolaMapel.php?id_mapel=<?= urlencode($id_mapel) ?>&kelas=<?= urlencode($kelas) ?>"><i class="bi bi-arrow-left-circle-fill fs-4"></i></a>
    <div>
        <div style="font-weight:700;font-size:1rem;">Upload Materi Baru</div>
        <div style="font-size:0.75rem;opacity:0.8;"><i class="bi bi-book me-1"></i><?= htmlspecialchars($nama_mapel) ?> • Kelas <?= htmlspecialchars($kelas) ?></div>
    </div>
</div>

<div class="container py-5" style="max-width:600px;">
    <div class="card-form">
        <h5 class="fw-bold mb-4 text-dark"><i class="bi bi-file-earmark-plus-fill text-primary me-2"></i>Detail Dokumen Materi</h5>
        
        <form action="Proses_Up_Materi.php" method="POST" enctype="multipart/form-data" id="frmMateri">
            <input type="hidden" name="id_mapel" value="<?= htmlspecialchars($id_mapel) ?>">
            <input type="hidden" name="kelas" value="<?= htmlspecialchars($kelas) ?>">
            <input type="hidden" name="id_topik" value="<?= htmlspecialchars($_GET['id_topik'] ?? '') ?>">
            
            <div class="mb-4">
                <label class="form-label">Judul Materi Pembelajaran <span class="text-danger">*</span></label>
                <input type="text" name="judul" class="form-control" placeholder="Contoh: Modul 1 - Pengenalan Akuntansi Dasar" required maxlength="100">
            </div>
            
            <div class="mb-4">
                <label class="form-label">Deskripsi / Panduan Singkat</label>
                <textarea name="deskripsi" class="form-control" rows="3" maxlength="300" id="deskInput" placeholder="Tulis panduan atau rangkuman singkat untuk siswa..."></textarea>
                <div class="text-end mt-1 fw-bold" style="font-size:0.75rem; color:#adb5bd;"><span id="cc">0</span>/300 Karakter</div>
            </div>
            
            <div class="mb-5">
                <label class="form-label">File Dokumen Materi <span class="text-danger">*</span></label>
                <div class="upload-zone" id="zone" onclick="document.getElementById('fi').click()">
                    <i class="bi bi-cloud-arrow-up-fill" style="font-size:3rem; color:#a5b4fc;"></i>
                    <div class="fw-bold text-dark mt-2 mb-1">Klik atau seret file ke area ini</div>
                    <div class="text-muted small mb-3">Maksimal ukuran file: 50 MB</div>
                    <div class="d-flex justify-content-center flex-wrap gap-2">
                        <span class="type-badge">PDF</span><span class="type-badge">DOC/DOCX</span><span class="type-badge">PPT/PPTX</span>
                        <span class="type-badge">XLS/XLSX</span><span class="type-badge">JPG/PNG</span><span class="type-badge">MP4</span>
                    </div>
                    <input type="file" name="materi_file" id="fi" class="d-none" required accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.jpg,.jpeg,.png,.mp4" onchange="showFile(this)">
                </div>
                
                <div class="file-preview" id="fp">
                    <i class="bi bi-file-earmark-check-fill text-primary fs-3"></i>
                    <div class="flex-grow-1 overflow-hidden">
                        <div id="fn" class="fw-bold text-dark text-truncate" style="font-size:0.85rem;"></div>
                        <div id="fs" class="text-muted fw-semibold" style="font-size:0.75rem;"></div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-danger border-0 rounded-circle" onclick="clearFile()"><i class="bi bi-trash3-fill"></i></button>
                </div>
            </div>
            
            <div class="d-grid gap-2">
                <button type="submit" class="btn-submit btn shadow-sm" id="btnS"><i class="bi bi-cloud-upload-fill me-2"></i>Mulai Upload Materi</button>
                <a href="kelolaMapel.php?id_mapel=<?= urlencode($id_mapel) ?>&kelas=<?= urlencode($kelas) ?>" class="btn btn-light border fw-bold text-secondary" style="border-radius:12px; padding: 12px;">Batal & Kembali</a>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.getElementById('deskInput').addEventListener('input', function() {
        document.getElementById('cc').textContent = this.value.length;
    });

    function showFile(input) {
        const file = input.files[0];
        if (!file) return;
        
        // Peringatan jika file > 50MB
        if (file.size > 50 * 1024 * 1024) {
            alert('Gagal: Ukuran file melebihi batas 50MB!');
            input.value = '';
            return;
        }
        
        document.getElementById('fn').textContent = file.name;
        document.getElementById('fs').textContent = (file.size / 1024 / 1024).toFixed(2) + ' MB';
        document.getElementById('fp').classList.add('show');
        document.getElementById('zone').style.display = 'none'; // Sembunyikan kotak besar jika file sudah terpilih
    }

    function clearFile() {
        document.getElementById('fi').value = '';
        document.getElementById('fp').classList.remove('show');
        document.getElementById('zone').style.display = 'block'; // Munculkan kembali kotak besar
    }

    // Efek Drag and Drop
    const zone = document.getElementById('zone');
    zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('over'); });
    zone.addEventListener('dragleave', () => zone.classList.remove('over'));
    zone.addEventListener('drop', e => {
        e.preventDefault(); 
        zone.classList.remove('over'); 
        document.getElementById('fi').files = e.dataTransfer.files; 
        showFile(document.getElementById('fi')); 
    });

    // Efek Loading Saat Tombol Ditekan
    document.getElementById('frmMateri').addEventListener('submit', function() {
        const btn = document.getElementById('btnS');
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Sistem Sedang Mengupload...';
        btn.classList.add('opacity-75');
    });
</script>
</body>
</html>