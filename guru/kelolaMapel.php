<?php
session_start();
require '../login/koneksi.php';

// Pastikan yang mengakses adalah Guru
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'guru'){
    header("Location: ../login/login.php");
    exit;
}

// Tangkap parameter dari URL
if(!isset($_GET['id_mapel']) || !isset($_GET['kelas'])){
    echo "<script>alert('Akses tidak valid!'); window.location='guru.php';</script>";
    exit;
}

$id_mapel = mysqli_real_escape_string($koneksi, $_GET['id_mapel']);
$kelas    = mysqli_real_escape_string($koneksi, $_GET['kelas']);

// Proses jika Guru melakukan Update Deskripsi (Komentar Mapel)
if(isset($_POST['simpan_deskripsi'])){
    $deskripsi_baru = mysqli_real_escape_string($koneksi, $_POST['deskripsi_baru']);
    $update_desk = mysqli_query($koneksi, "UPDATE mapel SET Deskripsi = '$deskripsi_baru' WHERE IDMapel = '$id_mapel'");
    
    if($update_desk){
        $pesan_sukses = "Deskripsi mata pelajaran berhasil diperbarui!";
    }
}

// Ambil data detail Mata Pelajaran saat ini
$query_mapel = mysqli_query($koneksi, "SELECT * FROM mapel WHERE IDMapel = '$id_mapel'");
$mapel = mysqli_fetch_assoc($query_mapel);

if(!$mapel){
    echo "<script>alert('Mata Pelajaran tidak ditemukan!'); window.location='guru.php';</script>";
    exit;
}

$nama_mapel = $mapel['NamaMapel'];
$deskripsi_mapel = !empty($mapel['Deskripsi']) ? $mapel['Deskripsi'] : 'Belum ada deskripsi atau panduan untuk mata pelajaran ini.';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($nama_mapel) ?> - Kelola Kelas LMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-light: #eef2ff;
            --sidebar-width: 280px;
        }
        body { background-color: #f8f9fa; overflow-x: hidden; font-family: 'Segoe UI', system-ui, sans-serif; }
        
        /* NAVBAR CUSTOM */
        .navbar-custom { background: #fff; box-shadow: 0 2px 10px rgba(0,0,0,0.05); z-index: 1030; }
        .btn-toggle { font-size: 1.5rem; color: #495057; cursor: pointer; border: none; background: transparent; padding: 0 15px; }
        .btn-toggle:hover { color: var(--primary); }

        /* LAYOUT SIDEBAR & MAIN CONTENT */
        #wrapper { display: flex; width: 100%; align-items: stretch; min-height: calc(100vh - 60px); }
        
        /* SIDEBAR DESAIN */
        #sidebar {
            min-width: var(--sidebar-width);
            max-width: var(--sidebar-width);
            background: #fff;
            border-right: 1px solid #e5e7eb;
            transition: all 0.3s;
            z-index: 1000;
        }
        #sidebar.collapsed { margin-left: calc(-1 * var(--sidebar-width)); }
        .sidebar-menu { padding: 15px 10px; list-style: none; margin: 0; }
        .sidebar-menu li a {
            display: flex; align-items: center; padding: 12px 20px; color: #4b5563; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 0.9rem; transition: 0.2s; margin-bottom: 5px;
        }
        .sidebar-menu li a:hover { background: #f3f4f6; color: var(--primary); }
        .sidebar-menu li a.active { background: var(--primary); color: #fff; box-shadow: 0 4px 10px rgba(79, 70, 229, 0.3); }
        .sidebar-menu li a i { font-size: 1.2rem; margin-right: 12px; }

        /* MAIN CONTENT DESAIN */
        #main-content { width: 100%; padding: 30px; transition: all 0.3s; }
        
        /* DESKRIPSI MAPEL BOX */
        .desc-box { background: var(--primary-light); border: 1px solid rgba(79, 70, 229, 0.2); border-radius: 15px; padding: 20px; position: relative; }
        .btn-edit-desc { position: absolute; top: 15px; right: 15px; background: #fff; border: 1px solid #d1d5db; color: #4b5563; border-radius: 8px; padding: 5px 12px; font-size: 0.8rem; font-weight: 600; transition: 0.2s; }
        .btn-edit-desc:hover { background: var(--primary); color: #fff; border-color: var(--primary); }

        /* ACCORDION (Desain ala Moodle) */
        .accordion-item { border: 1px solid #e5e7eb; border-radius: 12px !important; overflow: hidden; margin-bottom: 15px; box-shadow: 0 2px 5px rgba(0,0,0,0.02); }
        .accordion-button { font-weight: 700; color: #374151; background-color: #fff; padding: 18px 20px; box-shadow: none !important; }
        .accordion-button:not(.collapsed) { background-color: #f9fafb; color: var(--primary); border-bottom: 1px solid #e5e7eb; }
        .accordion-button::after { background-size: 1rem; }
        .accordion-body { background: #fff; padding: 20px; }
        
        .resource-item { display: flex; align-items: center; padding: 12px; border: 1px solid #e5e7eb; border-radius: 8px; margin-bottom: 10px; transition: 0.2s; cursor: pointer; background: #fff; }
        .resource-item:hover { border-color: var(--primary); box-shadow: 0 4px 10px rgba(79, 70, 229, 0.1); transform: translateX(5px); }
        .icon-box { width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; margin-right: 15px; }

        @media (max-width: 768px) {
            #sidebar { position: fixed; height: 100%; box-shadow: 5px 0 15px rgba(0,0,0,0.1); margin-left: calc(-1 * var(--sidebar-width)); }
            #sidebar.show-mobile { margin-left: 0; }
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-custom sticky-top py-2">
        <div class="container-fluid">
            <div class="d-flex align-items-center">
                <button class="btn-toggle" id="sidebarToggle" title="Sembunyikan/Tampilkan Menu">
                    <i class="bi bi-list"></i>
                </button>
                <a class="navbar-brand fw-bold ms-2 text-dark fs-5" href="#">
                    LMS <span class="text-primary">Wongsorejo</span>
                </a>
            </div>
            
            <div class="d-flex align-items-center gap-3">
                <a href="guru.php" class="btn btn-outline-secondary btn-sm rounded-pill fw-bold px-3">
                    <i class="bi bi-box-arrow-left me-1"></i> Kembali ke Dashboard
                </a>
            </div>
        </div>
    </nav>

    <div id="wrapper">
        
        <nav id="sidebar">
            <div class="p-4 border-bottom">
                <h6 class="fw-bold mb-1 text-primary text-truncate" title="<?= htmlspecialchars($nama_mapel) ?>"><?= htmlspecialchars($nama_mapel) ?></h6>
                <span class="badge bg-dark bg-opacity-10 text-dark border"><i class="bi bi-building me-1"></i> Kelas <?= htmlspecialchars($kelas) ?></span>
            </div>
            <ul class="sidebar-menu">
                <li><a href="#" class="active"><i class="bi bi-journal-text"></i> Materi Pembelajaran</a></li>
                <li><a href="#"><i class="bi bi-card-checklist"></i> Tugas Siswa</a></li>
                <li><a href="#"><i class="bi bi-ui-checks"></i> Kuis / Ujian</a></li>
                <li><a href="#"><i class="bi bi-clipboard-data"></i> Rekap Penilaian</a></li>
                <li><hr class="dropdown-divider my-2"></li>
                <li><a href="#"><i class="bi bi-people"></i> Daftar Siswa</a></li>
                <li><a href="#"><i class="bi bi-gear"></i> Pengaturan Mapel</a></li>
            </ul>
        </nav>

        <main id="main-content">
            
            <div class="mb-4">
                <h2 class="fw-bold text-dark mb-3"><?= htmlspecialchars($nama_mapel) ?></h2>
                
                <div class="desc-box shadow-sm">
                    <h6 class="fw-bold text-primary mb-2"><i class="bi bi-info-circle-fill me-2"></i>Informasi & Panduan Mapel</h6>
                    <p class="mb-0 text-secondary" style="line-height: 1.6; font-size: 0.95rem;">
                        <?= nl2br(htmlspecialchars($deskripsi_mapel)) ?>
                    </p>
                    
                    <button class="btn-edit-desc shadow-sm" data-bs-toggle="modal" data-bs-target="#modalEditDeskripsi">
                        <i class="bi bi-pencil-square me-1"></i> Edit
                    </button>
                </div>
            </div>

            <div class="d-flex justify-content-end mb-3">
                <button class="btn btn-primary fw-bold shadow-sm rounded-pill px-4">
                    <i class="bi bi-plus-lg me-1"></i> Tambah Materi / Tugas Baru
                </button>
            </div>

            <div class="accordion" id="accordionMateri">
                
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseUmum">
                            <i class="bi bi-megaphone-fill me-2 text-warning"></i> Umum / Pengumuman
                        </button>
                    </h2>
                    <div id="collapseUmum" class="accordion-collapse collapse show">
                        <div class="accordion-body">
                            <div class="resource-item">
                                <div class="icon-box bg-warning bg-opacity-10 text-warning"><i class="bi bi-chat-square-text-fill"></i></div>
                                <div>
                                    <div class="fw-bold text-dark mb-1">Forum Diskusi Kelas</div>
                                    <div class="small text-muted">Tempat tanya jawab umum seputar mata pelajaran ini.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseModul1">
                            Bab 1: Pendahuluan dan Konsep Dasar
                        </button>
                    </h2>
                    <div id="collapseModul1" class="accordion-collapse collapse">
                        <div class="accordion-body">
                            <div class="resource-item">
                                <div class="icon-box bg-danger bg-opacity-10 text-danger"><i class="bi bi-file-earmark-pdf-fill"></i></div>
                                <div>
                                    <div class="fw-bold text-dark mb-1">Modul Pembelajaran Bab 1</div>
                                    <div class="small text-muted">Dokumen PDF • 2.5 MB</div>
                                </div>
                            </div>
                            <div class="resource-item">
                                <div class="icon-box bg-success bg-opacity-10 text-success"><i class="bi bi-journal-check"></i></div>
                                <div>
                                    <div class="fw-bold text-dark mb-1">Tugas 1: Analisis Studi Kasus</div>
                                    <div class="small text-danger fw-semibold">Jatuh Tempo: Jumat, 29 Mei 2026</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseModul2">
                            Bab 2: Praktik Aplikasi
                        </button>
                    </h2>
                    <div id="collapseModul2" class="accordion-collapse collapse">
                        <div class="accordion-body text-center py-4">
                            <p class="text-muted mb-0">Belum ada materi atau tugas yang diunggah pada sesi ini.</p>
                        </div>
                    </div>
                </div>

            </div>
            
        </main>
    </div>

    <div class="modal fade" id="modalEditDeskripsi" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header bg-primary text-white p-4 border-0">
                    <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>Edit Informasi Mata Pelajaran</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="" method="POST">
                    <div class="modal-body p-4 bg-light">
                        <div class="alert alert-info border-0 small">
                            <i class="bi bi-info-circle-fill me-2"></i> Tuliskan sambutan, deskripsi singkat, atau kontrak belajar untuk kelas ini. Tulisan ini akan dilihat oleh siswa saat mereka masuk ke mapel ini.
                        </div>
                        <label class="form-label fw-bold">Teks Deskripsi / Komentar:</label>
                        <textarea name="deskripsi_baru" class="form-control shadow-sm border-0" rows="6" required><?= htmlspecialchars($mapel['Deskripsi'] ?? '') ?></textarea>
                    </div>
                    <div class="modal-footer border-0 p-3 bg-white d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-light px-4 border" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="simpan_deskripsi" class="btn btn-primary px-5 fw-bold shadow-sm">
                            <i class="bi bi-save me-1"></i> Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Fitur Sembunyikan/Tampilkan Sidebar (Toggle)
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            if (window.innerWidth <= 768) {
                // Untuk Mobile
                sidebar.classList.toggle('show-mobile');
            } else {
                // Untuk Desktop
                sidebar.classList.toggle('collapsed');
            }
        });
    </script>

    <?php if(isset($pesan_sukses)): ?>
    <script>
        Swal.fire({
            title: 'Berhasil!',
            text: '<?= $pesan_sukses ?>',
            icon: 'success',
            confirmButtonColor: '#4f46e5',
            timer: 2500
        });
        // Hilangkan history POST agar tidak tersubmit ulang saat refresh
        window.history.replaceState(null, null, window.location.href);
    </script>
    <?php endif; ?>

</body>
</html>