<?php
session_start();
require '../login/koneksi.php';

// Pastikan yang masuk adalah akun dengan role siswa
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'siswa'){
    header("Location: ../login/login.php");
    exit;
}

// Ambil IDMapel dari URL
$id_mapel = isset($_GET['id_mapel']) ? mysqli_real_escape_string($koneksi, $_GET['id_mapel']) : '';

if(empty($id_mapel)) {
    header("Location: siswa.php");
    exit;
}

// 1. IDENTIFIKASI IDUSER & DATA SISWA
$id_user = isset($_SESSION['IDUser']) ? $_SESSION['IDUser'] : '';
$query_siswa = mysqli_query($koneksi, "SELECT * FROM siswa WHERE IDUser = '$id_user'");
$data_siswa = mysqli_fetch_assoc($query_siswa);

// 2. AMBIL DETAIL MATA PELAJARAN & GURU
$query_detail_mapel = mysqli_query($koneksi, "
    SELECT m.*, g.NamaGuru 
    FROM mapel m 
    LEFT JOIN guru g ON m.IDGuru = g.IDGuru 
    WHERE m.IDMapel = '$id_mapel'
");
$data_mapel = mysqli_fetch_assoc($query_detail_mapel);

// Jika IDMapel tidak ditemukan di database, kembalikan ke dashboard
if(!$data_mapel) {
    header("Location: siswa.php");
    exit;
}

// 3. AMBIL DAFTAR TOPIK UNTUK MAPEL INI (Diurutkan berdasarkan kolom 'Urutan')
$query_topik = mysqli_query($koneksi, "SELECT * FROM topik_mapel WHERE IDMapel = '$id_mapel' ORDER BY Urutan ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($data_mapel['NamaMapel']) ?> - LMS SMKN 1 Wongsorejo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #dc3545, #9b1c26);
            --header-gradient: linear-gradient(135deg, #1e1e2f, #111119);
        }
        
        html, body {
            background-color: #f4f6f9; 
            color: #333; 
            font-family: 'Segoe UI', system-ui, sans-serif;
        }
        
        .navbar-custom { 
            background: var(--primary-gradient) !important; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.1); 
        }

        .sidebar {
            background-color: #fff !important;
            box-shadow: 4px 0 12px rgba(0,0,0,0.05);
            border-radius: 0px 12px 12px 0px;
            padding: 20px 15px;
            min-height: calc(100vh - 56px);
        }

        .sidebar .nav-link {
            color: #495057 !important;
            font-weight: 500;
            border-radius: 8px;
            margin-bottom: 4px;
        }

        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background: rgba(220, 53, 69, 0.1) !important;
            color: #dc3545 !important;
        }

        .mapel-header-card {
            background: var(--header-gradient) !important;
            color: white !important;
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }

        .topik-card {
            border: none;
            border-radius: 16px;
            background-color: #fff;
            box-shadow: 0 4px 15px rgba(0,0,0,0.03);
            margin-bottom: 25px;
        }

        .topik-title {
            color: #1e1e2f;
            font-weight: 700;
            border-bottom: 2px solid #f4f6f9;
            padding-bottom: 12px;
        }

        .item-materi {
            background-color: #f8f9fa;
            border-left: 4px solid #0d6efd; /* Biru untuk Materi */
            border-radius: 8px;
            transition: all 0.2s;
        }

        .item-materi:hover {
            background-color: #f1f3f5;
            transform: translateX(4px);
        }

        .item-tugas {
            background-color: #fff5f5;
            border-left: 4px solid #dc3545; /* Merah untuk Tugas */
            border-radius: 8px;
            transition: all 0.2s;
        }

        .item-tugas:hover {
            background-color: #ffebe0;
            transform: translateX(4px);
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom sticky-top py-2">
        <div class="container-fluid px-4">
            <a class="navbar-brand fw-bold d-flex align-items-center" href="siswa.php">
                <span class="fs-5">🎓 LMS SMKN 1 Wongsorejo</span>
            </a>
            <div class="d-flex align-items-center gap-3">
                <div class="text-end text-white d-none d-md-block">
                    <h6 class="mb-0 fw-bold small"><?= htmlspecialchars($data_siswa['NamaSiswa'] ?? 'Siswa') ?></h6>
                    <small class="text-white-50 text-uppercase d-block" style="font-size: 0.65rem;"><?= htmlspecialchars($data_siswa['Kelas'] ?? '') ?></small>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-0">
        <div class="row g-0">
            
            <nav class="col-md-3 col-lg-2 d-md-block sidebar d-none d-md-block">
                <div class="position-sticky">
                    <ul class="nav flex-column">
                        <li class="nav-item"><a class="nav-link" href="siswa.php"><i class="bi bi-house-door me-2"></i>Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link active" href="siswa.php"><i class="bi bi-book me-2"></i>Mata Pelajaran</a></li>
                        <li class="nav-item"><a class="nav-link" href="kalender.php"><i class="bi bi-calendar-event me-2"></i>Jadwal</a></li>
                        <li class="nav-item"><a class="nav-link" href="gamifikasi.php"><i class="bi bi-trophy me-2"></i>Gamifikasi</a></li>
                    </ul>
                </div>
            </nav>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                
                <a href="siswa.php" class="btn btn-sm btn-outline-secondary rounded-pill mb-3 px-3">
                    <i class="bi bi-arrow-left me-1"></i> Kembali ke Dashboard
                </a>

                <div class="card mapel-header-card p-4 mb-4">
                    <span class="badge bg-danger mb-2 px-3 py-2 rounded-pill align-self-start small fw-bold">RUANG KELAS</span>
                    <h2 class="fw-bold text-white mb-1"><?= htmlspecialchars($data_mapel['NamaMapel']) ?></h2>
                    <p class="text-white-50 small mb-2"><i class="bi bi-person-workspace me-1"></i> Pengajar: <strong><?= htmlspecialchars($data_mapel['NamaGuru'] ?? 'Belum Ditentukan') ?></strong></p>
                    <p class="text-white-50 mb-0 italic" style="font-size: 0.9rem;"><i class="bi bi-info-circle me-1"></i> <?= htmlspecialchars($data_mapel['Deskripsi'] ?? 'Tidak ada deskripsi mata pelajaran.') ?></p>
                </div>

                <h4 class="fw-bold text-dark mb-4"><i class="bi bi-card-list text-danger me-2"></i>Materi & Tugas Pembelajaran</h4>

                <?php if(mysqli_num_rows($query_topik) == 0): ?>
                    <div class="card text-center p-5 border-0 shadow-sm rounded-4">
                        <i class="bi bi-folder-x text-muted fs-1 mb-2"></i>
                        <p class="text-muted mb-0">Belum ada topik materi pembelajaran yang dibagikan untuk kelas ini.</p>
                    </div>
                <?php else: ?>
                    <?php while($topik = mysqli_fetch_assoc($query_topik)): ?>
                        <?php $id_topik = $topik['IDTopik']; ?>
                        
                        <div class="card topik-card p-4">
                            <h5 class="topik-title d-flex align-items-center justify-content-between">
                                <span><i class="bi bi-tags-fill text-danger me-2"></i><?= htmlspecialchars($topik['NamaTopik']) ?></span>
                                <span class="badge bg-light text-dark border small rounded-pill px-2.5 py-1" style="font-size: 0.75rem;">Topik Ke-<?= htmlspecialchars($topik['Urutan']) ?></span>
                            </h5>

                            <div class="row g-3 mt-1">
                                <div class="col-md-6">
                                    <h6 class="fw-bold text-primary mb-3"><i class="bi bi-file-earmark-text me-1"></i> Materi Bacaan</h6>
                                    <?php 
                                    $query_materi = mysqli_query($koneksi, "SELECT * FROM materi WHERE IDTopik = '$id_topik' AND IDMapel = '$id_mapel'");
                                    if(mysqli_num_rows($query_materi) == 0):
                                    ?>
                                        <p class="text-muted small ps-2 italic"> Tidak ada materi di topik ini.</p>
                                    <?php else: ?>
                                        <?php while($materi = mysqli_fetch_assoc($query_materi)): ?>
                                            <div class="item-materi p-3 mb-2 d-flex align-items-center justify-content-between">
                                                <div class="d-flex align-items-center gap-3">
                                                    <i class="bi bi-file-earmark-pdf-fill text-danger fs-4"></i>
                                                    <div>
                                                        <h6 class="mb-0 fw-semibold text-dark" style="font-size: 0.95rem;"><?= htmlspecialchars($materi['Judul']) ?></h6>
                                                        <small class="text-muted d-block" style="font-size: 0.75rem;"><?= htmlspecialchars($materi['Deskripsi'] ?? 'Klik untuk membaca materi') ?></small>
                                                    </div>
                                                </div>
                                                <a href="../guru/uploads/<?= htmlspecialchars($materi['Filepath']) ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3" target="_blank">
                                                    <i class="bi bi-eye me-1"></i> Baca
                                                </a>
                                            </div>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </div>

                                <div class="col-md-6">
                                    <h6 class="fw-bold text-danger mb-3"><i class="bi bi-clipboard-check me-1"></i> Tugas Siswa</h6>
                                    <?php 
                                    $query_tugas = mysqli_query($koneksi, "SELECT * FROM tugas WHERE IDTopik = '$id_topik' AND IDMapel = '$id_mapel'");
                                    if(mysqli_num_rows($query_tugas) == 0):
                                    ?>
                                        <p class="text-muted small ps-2 italic"> Tidak ada tugas di topik ini.</p>
                                    <?php else: ?>
                                        <?php while($tugas = mysqli_fetch_assoc($query_tugas)): ?>
                                            <div class="item-tugas p-3 mb-2 d-flex align-items-center justify-content-between">
                                                <div class="d-flex align-items-center gap-3">
                                                    <i class="bi bi-collection-play-fill text-danger fs-4"></i>
                                                    <div>
                                                        <h6 class="mb-0 fw-semibold text-dark" style="font-size: 0.95rem;"><?= htmlspecialchars($tugas['Judul']) ?></h6>
                                                        <small class="text-danger d-block fw-medium" style="font-size: 0.75rem;">
                                                            <i class="bi bi-alarm me-1"></i>Deadline: <?= date('d M Y, H:i', strtotime($tugas['Deadline'])) ?> Wib
                                                        </small>
                                                    </div>
                                                </div>
                                                <a href="kerjakan_tugas.php?id_tugas=<?= urlencode($tugas['IDTugas']) ?>" class="btn btn-sm btn-danger text-white rounded-pill px-3" style="background: var(--primary-gradient); border:none;">
                                                    Buka <i class="bi bi-arrow-right ms-1"></i>
                                                </a>
                                            </div>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                    <?php endwhile; ?>
                <?php endif; ?>

            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>