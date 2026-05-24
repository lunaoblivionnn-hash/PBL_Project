<?php
session_start();
require '../login/koneksi.php';

// Pastikan yang masuk adalah siswa
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'siswa'){
    header("Location: ../login/login.php");
    exit;
}

// Ambil IDMapel dari URL
$id_mapel = isset($_GET['id_mapel']) ? mysqli_real_escape_string($koneksi, $_GET['id_mapel']) : '';

<<<<<<< HEAD
if(empty($id_mapel)){ header("Location: siswa.php"); exit; }

// 1. Ambil Data Siswa
$query_siswa = mysqli_query($koneksi, "SELECT * FROM siswa WHERE IDUser='$id_user'");
$siswa = mysqli_fetch_assoc($query_siswa);
$id_siswa = $siswa['IDSiswa'] ?? '';
$nama_lengkap = $siswa['Nama'] ?? $siswa['NamaSiswa'] ?? 'Siswa';

// 2. Ambil Data Mapel
$query_mapel = mysqli_query($koneksi, "
=======
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
>>>>>>> b8223fc37e7ab4ea2558937f3c5c7abcdf960251
    SELECT m.*, g.NamaGuru 
    FROM mapel m 
    LEFT JOIN guru g ON m.IDGuru = g.IDGuru 
    WHERE m.IDMapel = '$id_mapel'
");
<<<<<<< HEAD
if(mysqli_num_rows($query_mapel) == 0){ header("Location: siswa.php"); exit; }
$mapel = mysqli_fetch_assoc($query_mapel);

// 3. Ambil Daftar Topik/Bab
$q_topik = mysqli_query($koneksi, "SELECT * FROM topik_mapel WHERE IDMapel = '$id_mapel' ORDER BY Urutan ASC");
$daftar_topik = [];
while($t = mysqli_fetch_assoc($q_topik)) { $daftar_topik[] = $t; }

// 4. Ambil Data Tugas yang Sudah Dikumpulkan Siswa Ini
$q_kumpul = mysqli_query($koneksi, "SELECT IDTugas FROM pengumpulan_tugas WHERE IDSiswa = '$id_siswa'");
$tugas_selesai = [];
while($tk = mysqli_fetch_assoc($q_kumpul)) { $tugas_selesai[] = $tk['IDTugas']; }
=======
$data_mapel = mysqli_fetch_assoc($query_detail_mapel);

// Jika IDMapel tidak ditemukan di database, kembalikan ke dashboard
if(!$data_mapel) {
    header("Location: siswa.php");
    exit;
}

// 3. AMBIL DAFTAR TOPIK UNTUK MAPEL INI (Diurutkan berdasarkan kolom 'Urutan')
$query_topik = mysqli_query($koneksi, "SELECT * FROM topik_mapel WHERE IDMapel = '$id_mapel' ORDER BY Urutan ASC");
>>>>>>> b8223fc37e7ab4ea2558937f3c5c7abcdf960251
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<<<<<<< HEAD
    <title><?= htmlspecialchars($mapel['NamaMapel']) ?> - Ruang Kelas LMS</title>
=======
    <title><?= htmlspecialchars($data_mapel['NamaMapel']) ?> - LMS SMKN 1 Wongsorejo</title>
>>>>>>> b8223fc37e7ab4ea2558937f3c5c7abcdf960251
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    
    <style>
        :root {
<<<<<<< HEAD
            --primary: #4f46e5;
            --primary-dark: #3730a3;
            --primary-light: #e0e7ff;
            --secondary: #0ea5e9;
            --gradient-primary: linear-gradient(135deg, #4f46e5, #0ea5e9);
            --bg-light: #f8fafc;
            --text-dark: #1e293b;
        }
        
        body { background-color: var(--bg-light); color: var(--text-dark); font-family: 'Segoe UI', system-ui, sans-serif; overflow-x: hidden; }
        
        /* NAVBAR */
        .navbar-custom { background: var(--gradient-primary) !important; box-shadow: 0 2px 10px rgba(0,0,0,0.1); padding: 10px 0; z-index: 1030;}
        
        /* LAYOUT UTAMA */
        #wrapper { display: flex; width: 100%; align-items: stretch; min-height: calc(100vh - 66px); }
        
        /* SIDEBAR KIRI (Daftar Isi Bab) */
        #sidebar-course {
            min-width: 300px; max-width: 300px;
            background: #fff; border-right: 1px solid #e2e8f0;
            position: sticky; top: 66px; height: calc(100vh - 66px);
            overflow-y: auto; padding: 20px 15px;
=======
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
>>>>>>> b8223fc37e7ab4ea2558937f3c5c7abcdf960251
        }
        .course-index-title { font-size: 0.8rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 15px; padding-left: 10px; }
        .index-item {
            display: block; padding: 12px 15px; color: #475569; text-decoration: none;
            border-radius: 10px; font-weight: 600; font-size: 0.9rem; transition: 0.2s; margin-bottom: 5px;
        }
        .index-item:hover { background: var(--primary-light); color: var(--primary); }
        .index-item.active { background: var(--primary); color: #fff; box-shadow: 0 4px 10px rgba(79, 70, 229, 0.2); }

<<<<<<< HEAD
        /* KONTEN TENGAH */
        #main-content { width: 100%; padding: 40px; }
        .page-title { font-weight: 800; font-size: 2.2rem; color: var(--text-dark); margin-bottom: 5px; text-transform: uppercase;}
        .page-subtitle { color: #64748b; font-size: 1rem; margin-bottom: 40px; }

        /* ACCORDION / SECTION KONTEN */
        .section-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; margin-bottom: 25px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); overflow: hidden;}
        .section-header { padding: 20px 25px; cursor: pointer; display: flex; align-items: center; justify-content: space-between; transition: 0.2s; }
        .section-header:hover { background: #f8fafc; }
        .section-title { font-weight: 700; color: var(--text-dark); font-size: 1.25rem; margin: 0; display: flex; align-items: center;}
        .section-title i { color: #cbd5e1; margin-right: 15px; font-size: 1.4rem; transition: 0.3s;}
        .section-header[aria-expanded="true"] .section-title i { transform: rotate(90deg); color: var(--primary); }
        
        .section-body { padding: 0 25px 25px 25px; }

        /* ITEM KONTEN (Materi & Tugas) */
        .content-item {
            display: flex; align-items: center; justify-content: space-between;
            padding: 15px 20px; border: 1px solid #e2e8f0; border-radius: 12px;
            margin-top: 15px; transition: 0.2s; background: #fff;
        }
        .content-item:hover { border-color: #cbd5e1; box-shadow: 0 4px 12px rgba(0,0,0,0.03); transform: translateY(-2px); }
        
        .content-icon {
            width: 45px; height: 45px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.4rem; margin-right: 20px; flex-shrink: 0;
        }
        .icon-materi { background: #e0e7ff; color: var(--primary); }
        .icon-tugas { background: #dcfce7; color: #10b981; }

        .content-info { flex-grow: 1; }
        .content-title { font-weight: 700; color: var(--text-dark); margin-bottom: 3px; font-size: 1.05rem; }
        .content-meta { font-size: 0.8rem; color: #64748b; font-weight: 500; }

        /* TOMBOL TANDAI SELESAI */
        .btn-selesai {
            border: 2px solid #cbd5e1; color: #64748b; background: transparent;
            border-radius: 8px; font-weight: 700; font-size: 0.85rem;
            padding: 8px 16px; transition: 0.3s; white-space: nowrap;
=======
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
>>>>>>> b8223fc37e7ab4ea2558937f3c5c7abcdf960251
        }
        .btn-selesai:hover { border-color: #10b981; color: #10b981; background: #f0fdf4; }
        .btn-selesai.done { background: #10b981; border-color: #10b981; color: #fff; box-shadow: 0 4px 10px rgba(16, 185, 129, 0.3); }

        @media (max-width: 992px) { #sidebar-course { display: none; } #main-content { padding: 20px; } }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom sticky-top">
        <div class="container-fluid px-4">
<<<<<<< HEAD
            <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="siswa.php">
                <i class="bi bi-mortarboard-fill fs-4"></i> LMS Wongsorejo
            </a>
            <div class="d-flex align-items-center gap-3">
                <a href="siswa.php" class="btn btn-light btn-sm rounded-pill fw-bold px-3 text-primary shadow-sm">
                    <i class="bi bi-arrow-left me-1"></i> Kembali ke Dashboard
                </a>
=======
            <a class="navbar-brand fw-bold d-flex align-items-center" href="siswa.php">
                <span class="fs-5">🎓 LMS SMKN 1 Wongsorejo</span>
            </a>
            <div class="d-flex align-items-center gap-3">
                <div class="text-end text-white d-none d-md-block">
                    <h6 class="mb-0 fw-bold small"><?= htmlspecialchars($data_siswa['NamaSiswa'] ?? 'Siswa') ?></h6>
                    <small class="text-white-50 text-uppercase d-block" style="font-size: 0.65rem;"><?= htmlspecialchars($data_siswa['Kelas'] ?? '') ?></small>
                </div>
>>>>>>> b8223fc37e7ab4ea2558937f3c5c7abcdf960251
            </div>
        </div>
    </nav>

    <div id="wrapper">
        
        <nav id="sidebar-course">
            <div class="course-index-title">DAFTAR ISI KELAS</div>
            
<<<<<<< HEAD
            <?php foreach($daftar_topik as $index => $tp): ?>
                <a href="#section-<?= $tp['IDTopik'] ?>" class="index-item <?= $index == 0 ? 'active' : '' ?>" onclick="setActiveSidebar(this)">
                    <?= htmlspecialchars($tp['NamaTopik']) ?>
                </a>
            <?php endforeach; ?>
            
            <?php if(empty($daftar_topik)): ?>
                <div class="text-muted small italic ps-2">Belum ada bab dibuat oleh guru.</div>
            <?php endif; ?>
        </nav>

        <main id="main-content">
            
            <h1 class="page-title"><?= htmlspecialchars($mapel['NamaMapel']) ?></h1>
            <div class="page-subtitle"><i class="bi bi-person-video3 me-2"></i>Guru Pengampu: <strong class="text-primary"><?= htmlspecialchars($mapel['NamaGuru'] ?? 'Belum Ditentukan') ?></strong></div>

            <?php foreach($daftar_topik as $index => $tp): 
                $id_topik = $tp['IDTopik'];
                $is_first = ($index == 0); // Buka otomatis bab pertama
            ?>
            
            <div class="section-card" id="section-<?= $id_topik ?>">
                <div class="section-header" data-bs-toggle="collapse" data-bs-target="#collapse<?= $id_topik ?>" aria-expanded="<?= $is_first ? 'true' : 'false' ?>">
                    <h3 class="section-title"><i class="bi bi-chevron-right"></i> <?= htmlspecialchars($tp['NamaTopik']) ?></h3>
                    <span class="text-primary small fw-bold" style="font-size: 0.85rem;">Buka / Tutup</span>
=======
            <nav class="col-md-3 col-lg-2 d-md-block sidebar d-none d-md-block">
                <div class="position-sticky">
                    <ul class="nav flex-column">
                        <li class="nav-item"><a class="nav-link" href="siswa.php"><i class="bi bi-house-door me-2"></i>Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link active" href="siswa.php"><i class="bi bi-book me-2"></i>Mata Pelajaran</a></li>
                        <li class="nav-item"><a class="nav-link" href="kalender.php"><i class="bi bi-calendar-event me-2"></i>Jadwal</a></li>
                        <li class="nav-item"><a class="nav-link" href="gamifikasi.php"><i class="bi bi-trophy me-2"></i>Gamifikasi</a></li>
                    </ul>
>>>>>>> b8223fc37e7ab4ea2558937f3c5c7abcdf960251
                </div>
                
                <div id="collapse<?= $id_topik ?>" class="collapse <?= $is_first ? 'show' : '' ?>">
                    <div class="section-body">
                        
                        <?php 
                        $ada_konten = false;

<<<<<<< HEAD
                        // 1. TAMPILKAN MATERI
                        $q_materi = mysqli_query($koneksi, "SELECT * FROM materi WHERE IDMapel='$id_mapel' AND IDTopik='$id_topik'");
                        while($mt = mysqli_fetch_assoc($q_materi)): $ada_konten = true; 
                        ?>
                            <div class="content-item">
                                <div class="d-flex align-items-center flex-grow-1" style="cursor: pointer;" onclick="window.open('../dokumen_materi/<?= htmlspecialchars($mt['Filepath']) ?>', '_blank')">
                                    <div class="content-icon icon-materi"><i class="bi bi-file-earmark-text-fill"></i></div>
                                    <div class="content-info">
                                        <div class="content-title"><?= htmlspecialchars($mt['Judul']) ?></div>
                                        <div class="content-meta">Materi Pembelajaran • PDF/Dokumen</div>
                                    </div>
                                </div>
                                <button class="btn-selesai" onclick="toggleSelesai(this, 'materi_<?= $mt['IDMateri'] ?>')">
                                    <i class="bi bi-circle me-1"></i> Tandai Selesai
                                </button>
                            </div>
                        <?php endwhile; ?>

                        // 2. TAMPILKAN TUGAS
                        <?php 
                        $q_tugas = mysqli_query($koneksi, "SELECT * FROM tugas WHERE IDMapel='$id_mapel' AND IDTopik='$id_topik'");
                        while($tg = mysqli_fetch_assoc($q_tugas)): 
                            $ada_konten = true; 
                            $sudah_kumpul = in_array($tg['IDTugas'], $tugas_selesai);
                        ?>
                            <div class="content-item">
                                <div class="d-flex align-items-center flex-grow-1" style="cursor: pointer;" onclick="window.location.href='tugas.php?id_tugas=<?= $tg['IDTugas'] ?>'">
                                    <div class="content-icon icon-tugas"><i class="bi bi-journal-check"></i></div>
                                    <div class="content-info">
                                        <div class="content-title"><?= htmlspecialchars($tg['Judul']) ?></div>
                                        <div class="content-meta text-danger"><i class="bi bi-clock-history me-1"></i> Tenggat: <?= date('d M Y, H:i', strtotime($tg['Deadline'])) ?></div>
                                    </div>
                                </div>
                                
                                <?php if($sudah_kumpul): ?>
                                    <button class="btn-selesai done" disabled style="cursor: default;">
                                        <i class="bi bi-check-circle-fill me-1"></i> Telah Dikumpulkan
                                    </button>
                                <?php else: ?>
                                    <button class="btn-selesai" onclick="window.location.href='tugas.php?id_tugas=<?= $tg['IDTugas'] ?>'">
                                        <i class="bi bi-pencil-square me-1"></i> Kerjakan Tugas
                                    </button>
                                <?php endif; ?>
                            </div>
                        <?php endwhile; ?>

                        <?php if(!$ada_konten): ?>
                            <div class="text-center py-4">
                                <div class="text-muted small p-3 bg-light rounded border border-dashed">Belum ada materi atau tugas di bab ini.</div>
                            </div>
                        <?php endif; ?>

                    </div>
                </div>
            </div>
            
            <?php endforeach; ?>
=======
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
>>>>>>> b8223fc37e7ab4ea2558937f3c5c7abcdf960251

        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Fitur mengubah warna menu sidebar saat di klik
        function setActiveSidebar(element) {
            document.querySelectorAll('.index-item').forEach(el => el.classList.remove('active'));
            element.classList.add('active');
        }

        // Fitur Efek Animasi "Tandai Selesai" untuk Materi
        function toggleSelesai(btn, idItem) {
            // Cek apakah sudah hijau
            let isDone = btn.classList.contains('done');
            
            if(!isDone) {
                // Berubah jadi Hijau Selesai
                btn.classList.add('done');
                btn.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> Selesai';
                
                // Simpan state di LocalStorage browser (sebagai simulasi sebelum masuk database)
                localStorage.setItem(idItem, 'selesai');

                // Notifikasi Pemanis
                Swal.fire({
                    title: 'Hebat!',
                    text: 'Anda telah menyelesaikan materi ini. Progress belajar Anda meningkat!',
                    icon: 'success',
                    toast: true,
                    position: 'bottom-end',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true
                });
            } else {
                // Batal Selesai
                btn.classList.remove('done');
                btn.innerHTML = '<i class="bi bi-circle me-1"></i> Tandai Selesai';
                localStorage.removeItem(idItem);
            }
        }

        // Jalankan saat halaman dimuat: Cek memori browser untuk materi yang sudah ditandai selesai
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.btn-selesai').forEach(btn => {
                let idItem = btn.getAttribute('onclick');
                if(idItem && idItem.includes('toggleSelesai')) {
                    // Ekstrak ID dari string onclick
                    let match = idItem.match(/'([^']+)'/);
                    if(match && localStorage.getItem(match[1]) === 'selesai') {
                        btn.classList.add('done');
                        btn.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> Selesai';
                    }
                }
            });
        });
    </script>
</body>
</html>