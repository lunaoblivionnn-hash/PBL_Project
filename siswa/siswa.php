<?php
session_start();
require '../login/koneksi.php';

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'siswa'){
    header("Location: ../login/login.php");
    exit;
}

// 1. IDENTIFIKASI IDUSER
$id_user = isset($_SESSION['IDUser']) ? $_SESSION['IDUser'] : '';

if(empty($id_user)) {
    $ses_username = isset($_SESSION['username']) ? $_SESSION['username'] : (isset($_SESSION['Username']) ? $_SESSION['Username'] : '');
    $cek_user = mysqli_query($koneksi, "SELECT IDUser FROM users WHERE Username = '$ses_username'");
    if($data_user = mysqli_fetch_assoc($cek_user)){
        $id_user = $data_user['IDUser'];
        $_SESSION['IDUser'] = $id_user; 
    }
}

$query_status_sandi = mysqli_query($koneksi, "SELECT WajibUbahPassword FROM users WHERE IDUser = '$id_user'");
$status_sandi = mysqli_fetch_assoc($query_status_sandi);
$wajib_ubah = isset($status_sandi['WajibUbahPassword']) ? $status_sandi['WajibUbahPassword'] : 0;

// 2. AMBIL DATA SISWA & PERBAIKAN BUG NAMA
$query_siswa = mysqli_query($koneksi, "SELECT * FROM siswa WHERE IDUser = '$id_user'");
$data_siswa = mysqli_fetch_assoc($query_siswa);

$id_siswa = $data_siswa['IDSiswa'] ?? '';
$kelas_siswa = $data_siswa['Kelas'] ?? '';
$xp_siswa = $data_siswa['TotalXP'] ?? 0;

// Menghindari bug "Undefined array key Nama"
$nama_lengkap = $data_siswa['Nama'] ?? $data_siswa['NamaSiswa'] ?? 'Siswa';
$nama_depan = htmlspecialchars(explode(' ', trim($nama_lengkap))[0]);

// 3. AMBIL DATA GAMIFIKASI DARI DATABASE (Tabel gamifikasi & master_level)
$query_gami = mysqli_query($koneksi, "
    SELECT g.TotalPoint, ml.LevelAngka, ml.Gelar 
    FROM gamifikasi g 
    LEFT JOIN master_level ml ON g.IDLevel = ml.IDLevel 
    WHERE g.IDSiswa = '$id_siswa'
");

if($data_gami = mysqli_fetch_assoc($query_gami)){
    $xp_siswa = $data_gami['TotalPoint'] ?? 0;
    $level_siswa = $data_gami['LevelAngka'] ?? 1;
    $gelar = $data_gami['Gelar'] ?? 'Beginner Accountant';
} else {
    // Default jika siswa baru dan belum masuk tabel gamifikasi
    $xp_siswa = 0; $level_siswa = 1; $gelar = 'Beginner Accountant';
}

// 4. KALKULASI RANKING GLOBAL LEADERBOARD
$q_rank = mysqli_query($koneksi, "SELECT IDSiswa FROM gamifikasi ORDER BY TotalPoint DESC");
$global_rank = '-';
$rank_counter = 1;
if ($q_rank) {
    while($row = mysqli_fetch_assoc($q_rank)) {
        if($row['IDSiswa'] == $id_siswa) { $global_rank = $rank_counter; break; }
        $rank_counter++;
    }
}

// 5. SISTEM QUOTE ACAK (Bisa dihubungkan ke database nantinya)
$quotes_fallback = [
    "Pendidikan adalah senjata paling mematikan di dunia, karena dengannya Anda dapat mengubah dunia. - Nelson Mandela",
    "Orang bijak belajar ketika mereka bisa. Orang bodoh belajar ketika mereka terpaksa. - Arthur Wellesley",
    "Jangan pernah berhenti belajar, karena hidup tak pernah berhenti mengajarkan. - Anonim",
    "Hiduplah seolah engkau mati besok. Belajarlah seolah engkau hidup selamanya. - Mahatma Gandhi"
];

// Coba ambil dari database jika tabel quotes ada (Jika tidak ada/error, gunakan fallback)
$quote_teks = "";
$q_quote = @mysqli_query($koneksi, "SELECT TeksQuote, Tokoh FROM quotes ORDER BY RAND() LIMIT 1");
if($q_quote && mysqli_num_rows($q_quote) > 0) {
    $row_q = mysqli_fetch_assoc($q_quote);
    $quote_teks = $row_q['TeksQuote'] . " - " . $row_q['Tokoh'];
} else {
    $quote_teks = $quotes_fallback[array_rand($quotes_fallback)];
}

// 6. AMBIL DAFTAR MAPEL
$query_mapel = mysqli_query($koneksi, "
    SELECT m.*, g.NamaGuru 
    FROM mapel m 
    LEFT JOIN guru g ON m.IDGuru = g.IDGuru 
    WHERE m.Kelas LIKE '%\"$kelas_siswa\"%'
");
$total_mapel = mysqli_num_rows($query_mapel);

// 7. AMBIL TUGAS PENDING
$q_tugas_pending = mysqli_query($koneksi, "
    SELECT COUNT(t.IDTugas) as total_pending 
    FROM tugas t
    JOIN mapel m ON t.IDMapel = m.IDMapel
    WHERE m.Kelas LIKE '%\"$kelas_siswa\"%' 
    AND t.IDTugas NOT IN (SELECT IDTugas FROM pengumpulan_tugas WHERE IDSiswa = '$id_siswa')
");
$tugas_pending = (mysqli_fetch_assoc($q_tugas_pending)['total_pending']) ?? 0;

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Siswa - LMS SMKN 1 Wongsorejo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        :root {
            --primary: #4f46e5;
            --primary-dark: #3730a3;
            --primary-light: #e0e7ff;
            --secondary: #0ea5e9;
            --gradient-primary: linear-gradient(135deg, #4f46e5, #0ea5e9);
            --gradient-card: linear-gradient(135deg, #1e1b4b, #312e81);
            --text-dark: #1e293b;
            --text-muted: #64748b;
        }
        
        body { background-color: #f8fafc; color: var(--text-dark); font-family: 'Segoe UI', system-ui, sans-serif; overflow-x: hidden; display: flex; flex-direction: column; min-height: 100vh; }
        
        .navbar-custom { background: var(--gradient-primary) !important; box-shadow: 0 4px 15px rgba(79, 70, 229, 0.2); padding: 10px 0; }
        .sidebar { background-color: #fff; box-shadow: 2px 0 20px rgba(0,0,0,0.03); padding: 25px 15px; z-index: 100; min-height: calc(100vh - 70px); }
        .sidebar .nav-link { color: var(--text-muted); font-weight: 600; padding: 12px 20px; border-radius: 12px; margin-bottom: 8px; transition: all 0.3s ease; }
        .sidebar .nav-link:hover { background-color: #f1f5f9; color: var(--primary); transform: translateX(5px); }
        .sidebar .nav-link.active { background-color: var(--primary-light); color: var(--primary); }

        /* HERO CARD (Welcome Banner) */
        .hero-card { background: var(--gradient-card); border-radius: 24px; border: none; padding: 35px 40px; color: white; position: relative; overflow: hidden; box-shadow: 0 15px 30px rgba(30, 27, 75, 0.15); }
        .hero-card::after { content: ''; position: absolute; top: -50px; right: -50px; width: 300px; height: 300px; background: radial-gradient(circle, rgba(14, 165, 233, 0.3) 0%, transparent 70%); border-radius: 50%; }
        .quote-box { background: rgba(255,255,255,0.1); backdrop-filter: blur(5px); border-left: 4px solid var(--secondary); padding: 15px; border-radius: 0 12px 12px 0; margin-top: 20px; max-width: 700px;}

        /* KOTAK STATISTIK */
        .stat-box { background: #fff; border-radius: 16px; padding: 20px; display: flex; align-items: center; gap: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); border: 1px solid #f1f5f9; transition: transform 0.3s; cursor: pointer; text-decoration: none; color: inherit; }
        .stat-box:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(79,70,229,0.1); color: inherit; }
        .stat-icon { width: 55px; height: 55px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }

        /* MAPEL TOOLBAR */
        .toolbar-mapel { background: #fff; border-radius: 12px; padding: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.02); margin-bottom: 20px; display: flex; flex-wrap: wrap; gap: 15px; justify-content: space-between; align-items: center;}
        .search-box { position: relative; flex-grow: 1; max-width: 300px; }
        .search-box i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--text-muted); }
        .search-box input { padding-left: 40px; border-radius: 50px; border: 1px solid #e2e8f0; background: #f8fafc; }
        .search-box input:focus { border-color: var(--primary); box-shadow: 0 0 0 0.2rem rgba(79, 70, 229, 0.25); background: #fff;}
        .view-btn { border: 1px solid #e2e8f0; background: #fff; color: var(--text-muted); border-radius: 8px; padding: 6px 12px; transition: 0.2s; }
        .view-btn.active { background: var(--primary-light); color: var(--primary); border-color: var(--primary-light); }

        /* MAPEL CARDS */
        .mapel-card { background: #fff; border-radius: 20px; border: none; box-shadow: 0 10px 25px rgba(0,0,0,0.03); transition: all 0.3s; overflow: hidden; }
        .mapel-card:hover { transform: translateY(-6px); box-shadow: 0 15px 35px rgba(79, 70, 229, 0.1); }
        .mapel-cover { height: 140px; background-color: #cbd5e1; position: relative; overflow: hidden; }
        .mapel-cover img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s; }
        .mapel-card:hover .mapel-cover img { transform: scale(1.05); }
        .badge-kelas { position: absolute; top: 15px; left: 15px; background: rgba(255,255,255,0.9); color: var(--primary-dark); backdrop-filter: blur(5px); padding: 5px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; }
        .btn-masuk { background: var(--primary-light); color: var(--primary); border: none; border-radius: 12px; font-weight: 700; padding: 10px; transition: 0.3s; }
        .btn-masuk:hover { background: var(--primary); color: #fff; }

        /* MODE DAFTAR (LIST VIEW CSS - Gaya Google Classroom) */
        #courseContainer.list-mode .col-mapel { width: 100%; flex: 0 0 100%; max-width: 100%; padding: 0 10px; }
        #courseContainer.list-mode .mapel-card { 
            flex-direction: row; height: auto !important; align-items: center; 
            border-radius: 0; box-shadow: none; border-bottom: 1px solid #e2e8f0; 
            padding: 15px 5px; background: transparent; cursor: pointer;
        }
        #courseContainer.list-mode .mapel-card:hover { transform: none; box-shadow: none; background: #f8fafc; }
        #courseContainer.list-mode .mapel-cover { width: 130px; height: 85px; border-radius: 8px; flex-shrink: 0; }
        #courseContainer.list-mode .badge-kelas { display: none; } /* Sembunyikan badge di mode list */
        #courseContainer.list-mode .card-body { padding: 0 0 0 20px !important; flex-direction: row !important; align-items: center; justify-content: space-between; }
        #courseContainer.list-mode .info-wrapper { flex-grow: 1; }
        #courseContainer.list-mode .mapel-title { color: #1a73e8 !important; font-size: 1.1rem; margin-bottom: 4px !important; }
        #courseContainer.list-mode .text-muted.small { font-size: 0.85rem; color: #3c4043 !important; }
        #courseContainer.list-mode .text-muted.small i { display: none; } /* Sembunyikan icon guru */
        #courseContainer.list-mode .btn-wrapper { display: none; } /* Sembunyikan tombol mulai belajar */
        #courseContainer.list-mode .progress-container { display: none; } /* Sembunyikan progress bar di mode list */

        @media (max-width: 768px) { 
            .sidebar { display: none; } 
            #courseContainer.list-mode .mapel-card { flex-direction: column; height: auto !important; }
            #courseContainer.list-mode .mapel-cover { width: 100%; height: 140px; border-radius: 16px 16px 0 0; }
            #courseContainer.list-mode .card-body { flex-direction: column !important; align-items: flex-start; }
            #courseContainer.list-mode .btn-wrapper { width: 100%; margin-top: 15px !important; }
        }

        /* FOOTER */
        .footer { margin-top: auto; background-color: #ffffff; border-top: 1px solid #e2e8f0; padding: 25px 0; color: var(--text-muted); font-size: 0.9rem; }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom sticky-top">
        <div class="container-fluid px-4">
            <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="siswa.php">
                <i class="bi bi-mortarboard-fill fs-4"></i>
                <span class="tracking-wide">LMS Wongsorejo</span>
            </a>
            
            <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#mobileMenu">
                <i class="bi bi-list fs-1"></i>
            </button>

            <div class="collapse navbar-collapse justify-content-end" id="mobileMenu">
                <ul class="navbar-nav d-lg-none mb-3 mt-2 border-top pt-3">
                    <li class="nav-item"><a class="nav-link active fw-bold text-white" href="siswa.php"><i class="bi bi-house-door me-2"></i>Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link text-white" href="tugas.php"><i class="bi bi-book me-2"></i>Mata Pelajaran & Tugas</a></li>
                    <li class="nav-item"><a class="nav-link text-white" href="kalender.php"><i class="bi bi-calendar-event me-2"></i>Jadwal Pelajaran</a></li>
                    <li class="nav-item"><a class="nav-link text-white" href="gamifikasi.php"><i class="bi bi-trophy me-2"></i>Pusat Gamifikasi</a></li>
                    <li class="nav-item mt-2"><a class="nav-link text-danger fw-bold bg-white rounded text-center" href="../login/logout.php">Keluar Akun</a></li>
                </ul>

                <div class="d-none d-lg-flex align-items-center gap-3">
                    <div class="text-end text-white">
                        <h6 class="mb-0 fw-bold small text-nowrap" style="font-size: 1.1rem"><?= $nama_lengkap ?></h6>
                        <span class="badge bg-white bg-opacity-25 rounded-pill mt-1"><i class="bi bi-building me-1"></i><?= htmlspecialchars($kelas_siswa) ?></span>
                    </div>
                    <div class="dropdown">
                        <a href="#" class="d-block" data-bs-toggle="dropdown" aria-expanded="false">
                            <img src="https://ui-avatars.com/api/?name=<?= urlencode($nama_lengkap) ?>&background=fff&color=4f46e5" class="rounded-circle border border-2 border-white shadow-sm" style="width: 42px; height: 42px;">
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg rounded-4 mt-2 p-2">
                            <li><a class="dropdown-item rounded-3 py-2 text-danger fw-bold" href="../login/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Keluar Sistem</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-0 flex-grow-1">
        <div class="row g-0">
            <nav class="col-md-3 col-lg-2 d-none d-md-block sidebar">
                <div class="position-sticky top-0">
                    <div class="text-muted small fw-bold mb-3 px-3 uppercase" style="letter-spacing: 1px;">MENU AKADEMIK</div>
                    <ul class="nav flex-column">
                        <li class="nav-item"><a class="nav-link active" href="siswa.php"><i class="bi bi-grid-1x2-fill me-3 fs-5 align-middle"></i> Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link" href="tugas.php"><i class="bi bi-journal-bookmark-fill me-3 fs-5 align-middle"></i> Ruang Kelas</a></li>
                        <li class="nav-item"><a class="nav-link" href="kalender.php"><i class="bi bi-calendar2-week-fill me-3 fs-5 align-middle"></i> Jadwal & Agenda</a></li>
                        <li class="nav-item mt-4 mb-2"><div class="text-muted small fw-bold px-3 uppercase" style="letter-spacing: 1px;">PRESTASI</div></li>
                        <li class="nav-item"><a class="nav-link text-warning" href="gamifikasi.php"><i class="bi bi-trophy-fill me-3 fs-5 align-middle"></i> Gamifikasi</a></li>
                    </ul>
                </div>
            </nav>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-4 py-4 pb-5">
                
                <div class="hero-card mb-4">
                    <div class="position-relative z-1 row align-items-center">
                        <div class="col-lg-8">
                            <span class="badge bg-white text-primary rounded-pill px-3 py-2 mb-3 fw-bold shadow-sm">TAHUN AJARAN 2025/2026</span>
                            <h1 class="fw-bold mb-1 display-6">Halo, <?= $nama_depan ?>! 👋</h1>
                            <p class="fs-6 opacity-75 mb-0">Terus kumpulkan XP dan jadilah yang terbaik di papan peringkat!</p>
                            
                            <div class="quote-box">
                                <i class="bi bi-quote fs-3 text-white-50 position-absolute" style="top: -10px; left: 5px;"></i>
                                <p class="mb-0 small fw-semibold ps-3 fst-italic">"<?= htmlspecialchars($quote_teks) ?>"</p>
                            </div>
                        </div>
                        <div class="col-lg-4 mt-4 mt-lg-0 text-lg-end">
                            <div class="bg-white bg-opacity-10 border border-white border-opacity-25 rounded-4 p-3 d-inline-block text-start" style="backdrop-filter: blur(10px);">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="bg-warning text-dark rounded-circle d-flex justify-content-center align-items-center fw-bold fs-4 shadow-sm" style="width: 55px; height: 55px;">
                                        <?= $level_siswa ?>
                                    </div>
                                    <div>
                                        <h6 class="mb-0 text-warning fw-bold text-uppercase" style="letter-spacing: 1px;"><?= $gelar ?></h6>
                                        <small class="text-white-50">Level Saat Ini</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <a href="leaderboard.php" class="stat-box text-decoration-none">
                            <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="bi bi-globe-americas"></i></div>
                            <div>
                                <h3 class="fw-bold mb-0 text-dark">#<?= $global_rank ?></h3>
                                <span class="text-muted small fw-semibold">Peringkat Global (Leaderboard)</span>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="tugas.php" class="stat-box">
                            <div class="stat-icon bg-danger bg-opacity-10 text-danger"><i class="bi bi-file-earmark-excel-fill"></i></div>
                            <div>
                                <h3 class="fw-bold mb-0 text-dark"><?= $tugas_pending ?></h3>
                                <span class="text-muted small fw-semibold">Tugas Belum Selesai</span>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="gamifikasi.php" class="stat-box">
                            <div class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-lightning-charge-fill"></i></div>
                            <div>
                                <h3 class="fw-bold mb-0 text-dark"><?= $xp_siswa ?> XP</h3>
                                <span class="text-muted small fw-semibold">Total Poin Pengalaman</span>
                            </div>
                        </a>
                    </div>
                </div>

                <div class="toolbar-mapel">
                    <div class="search-box">
                        <i class="bi bi-search"></i>
                        <input type="text" id="searchMapel" class="form-control form-control-sm" placeholder="Cari mata pelajaran..." onkeyup="filterMapel()">
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <select id="sortMapel" class="form-select form-select-sm border-0 bg-light fw-semibold text-secondary" style="width: auto;" onchange="sortMapel()">
                            <option value="az">Urutkan: A - Z</option>
                            <option value="za">Urutkan: Z - A</option>
                        </select>
                        <div class="btn-group" role="group">
                            <button type="button" class="btn view-btn active" id="btnGrid" onclick="setView('grid')"><i class="bi bi-grid-fill"></i></button>
                            <button type="button" class="btn view-btn" id="btnList" onclick="setView('list')"><i class="bi bi-list-task"></i></button>
                        </div>
                    </div>
                </div>

                <div class="row g-4" id="courseContainer">
                    <?php if($total_mapel == 0): ?>
                        <div class="col-12" id="emptyStateMapel">
                            <div class="card mapel-card p-5 text-center border border-dashed shadow-none">
                                <i class="bi bi-folder-x text-muted mb-3" style="font-size: 3rem;"></i>
                                <h5 class="fw-bold text-dark">Belum Ada Kelas</h5>
                                <p class="text-muted mb-0">Kelasmu saat ini belum ditautkan dengan mata pelajaran apapun oleh Admin.</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php 
                        $default_covers = [
                            'https://images.unsplash.com/photo-1434030216411-0b793f4b4173?auto=format&fit=crop&q=80&w=600',
                            'https://images.unsplash.com/photo-1554224155-8d04cb21cd6c?auto=format&fit=crop&q=80&w=600',
                            'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?auto=format&fit=crop&q=80&w=600',
                            'https://images.unsplash.com/photo-1460925895917-afdab827c52f?auto=format&fit=crop&q=80&w=600'
                        ];

                        while($row = mysqli_fetch_assoc($query_mapel)): 
                            $id_mapel_loop = $row['IDMapel'];
                            $idx = strlen($row['NamaMapel']) % 4;
                            $cover_img = $default_covers[$idx];
                            if(!empty($row['Gambar']) && file_exists("../image/mapel/" . $row['Gambar'])) {
                                $cover_img = "../image/mapel/" . $row['Gambar'];
                            }

                            // HITUNG PERSENTASE PROGRESS (Berdasarkan Tugas yang Selesai vs Total Tugas di Mapel ini)
                            $q_tot_tugas = mysqli_query($koneksi, "SELECT COUNT(IDTugas) as tot FROM tugas WHERE IDMapel = '$id_mapel_loop'");
                            $tot_tugas = mysqli_fetch_assoc($q_tot_tugas)['tot'] ?? 0;
                            
                            $q_selesai = mysqli_query($koneksi, "SELECT COUNT(pt.IDPengumpulan) as sel FROM pengumpulan_tugas pt JOIN tugas t ON pt.IDTugas = t.IDTugas WHERE t.IDMapel = '$id_mapel_loop' AND pt.IDSiswa = '$id_siswa'");
                            $tot_selesai = mysqli_fetch_assoc($q_selesai)['sel'] ?? 0;

                            $persentase = ($tot_tugas > 0) ? round(($tot_selesai / $tot_tugas) * 100) : 0;
                        ?>
                            <div class="col-md-6 col-xl-4 col-mapel" data-title="<?= strtolower($row['NamaMapel']) ?>">
                                <div class="card mapel-card h-100 d-flex flex-column" onclick="if(document.getElementById('courseContainer').classList.contains('list-mode')) window.location.href='mapel.php?id_mapel=<?= $row['IDMapel'] ?>'">
                                    <div class="mapel-cover">
                                        <img src="<?= $cover_img ?>" alt="Cover Mapel">
                                        <span class="badge-kelas"><i class="bi bi-diagram-3-fill me-1 text-primary"></i> <?= htmlspecialchars($kelas_siswa) ?></span>
                                    </div>
                                    <div class="card-body p-4 d-flex flex-column">
                                        <div class="info-wrapper">
                                            <h5 class="fw-bold text-dark mb-1 text-truncate mapel-title" title="<?= htmlspecialchars($row['NamaMapel']) ?>"><?= htmlspecialchars($row['NamaMapel']) ?></h5>
                                            <p class="small text-muted mb-3 flex-grow-1"><i class="bi bi-person-video3 me-1"></i> <?= htmlspecialchars($row['Kelas'] ?? '') ?> • <?= htmlspecialchars($row['NamaGuru'] ?? 'Belum Ditentukan') ?></p>
                                        </div>
                                        
                                        <div class="progress-container mb-3 mt-auto">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <span class="small text-muted fw-semibold" style="font-size: 0.75rem;">Progress Belajar</span>
                                                <span class="small fw-bold text-primary" style="font-size: 0.75rem;"><?= $persentase ?>%</span>
                                            </div>
                                            <div class="progress" style="height: 6px; background-color: #e2e8f0; box-shadow: none;">
                                                <div class="progress-bar bg-primary rounded-pill" role="progressbar" style="width: <?= $persentase ?>%"></div>
                                            </div>
                                        </div>

                                        <div class="btn-wrapper mt-auto">
                                            <a href="mapel.php?id_mapel=<?= $row['IDMapel'] ?>" class="btn btn-masuk w-100 d-block text-center">
                                                Mulai Belajar <i class="bi bi-arrow-right-circle-fill ms-1"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                        
                        <div class="col-12 d-none" id="noResultMapel">
                            <div class="card mapel-card p-5 text-center border border-dashed shadow-none bg-transparent">
                                <i class="bi bi-search text-muted mb-3" style="font-size: 2rem;"></i>
                                <h6 class="fw-bold text-secondary">Mata pelajaran tidak ditemukan</h6>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

            </main>
        </div>
    </div>

    <footer class="footer text-center">
        <div class="container">
            <div class="d-flex justify-content-center align-items-center gap-2 mb-2">
                <i class="bi bi-mortarboard-fill text-primary fs-5"></i>
                <h6 class="fw-bold text-dark mb-0">LMS SMKN 1 Wongsorejo</h6>
            </div>
            <p class="mb-0">© <?= date('Y') ?> Hak Cipta Dilindungi. Dikembangkan oleh Tim PBL V.</p>
        </div>
    </footer>

    <?php if($wajib_ubah == 1): ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
            title: 'Keamanan Akun!',
            text: 'Anda masih menggunakan kata sandi bawaan. Silakan perbarui sandi Anda sekarang untuk menjaga keamanan akun.',
            icon: 'warning',
            allowOutsideClick: false,
            allowEscapeKey: false,
            confirmButtonText: 'Simpan Sandi Baru',
            confirmButtonColor: '#4f46e5',
            input: 'password',
            inputPlaceholder: 'Ketik sandi rahasia Anda...',
            inputAttributes: { autocapitalize: 'off', autocorrect: 'off' },
            inputValidator: (value) => {
                if (!value) { return 'Sandi baru tidak boleh kosong!'; }
                if (value.length < 5) { return 'Sandi minimal 5 karakter!'; }
            },
            showLoaderOnConfirm: true,
            preConfirm: (password) => {
                return fetch('proses_ubah_sandi_paksa.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'password_baru=' + encodeURIComponent(password)
                })
                .then(async response => {
                    const text = await response.text();
                    try {
                        const data = JSON.parse(text);
                        if (data.status === 'sukses') return data;
                        else { Swal.showValidationMessage('Gagal: ' + data.pesan); return false; }
                    } catch (e) {
                        Swal.showValidationMessage('Error Sistem: ' + text.substring(0, 50)); return false;
                    }
                }).catch(error => { Swal.showValidationMessage('Koneksi Terputus: ' + error.message); return false; });
            }
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({ title: 'Akses Terbuka!', text: 'Sandi berhasil diamankan.', icon: 'success', timer: 1500, showConfirmButton: false })
                .then(() => { location.reload(); });
            }
        });
    });
    </script>
    <?php endif; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // FUNGSI UBAH TAMPILAN (GRID / LIST)
        function setView(mode) {
            const container = document.getElementById('courseContainer');
            const btnGrid = document.getElementById('btnGrid');
            const btnList = document.getElementById('btnList');

            if (mode === 'list') {
                container.classList.add('list-mode');
                btnList.classList.add('active');
                btnGrid.classList.remove('active');
            } else {
                container.classList.remove('list-mode');
                btnGrid.classList.add('active');
                btnList.classList.remove('active');
            }
        }

        // FUNGSI PENCARIAN MAPEL
        function filterMapel() {
            let input = document.getElementById('searchMapel').value.toLowerCase();
            let cards = document.getElementsByClassName('col-mapel');
            let hasResult = false;

            for (let i = 0; i < cards.length; i++) {
                let title = cards[i].getAttribute('data-title');
                if (title.includes(input)) {
                    cards[i].classList.remove('d-none');
                    hasResult = true;
                } else {
                    cards[i].classList.add('d-none');
                }
            }

            let noResultBox = document.getElementById('noResultMapel');
            if(noResultBox) {
                if(!hasResult && cards.length > 0) noResultBox.classList.remove('d-none');
                else noResultBox.classList.add('d-none');
            }
        }

        // FUNGSI PENGURUTAN MAPEL (A-Z / Z-A)
        function sortMapel() {
            let sortType = document.getElementById('sortMapel').value;
            let container = document.getElementById('courseContainer');
            let cards = Array.from(document.getElementsByClassName('col-mapel'));

            cards.sort(function(a, b) {
                let titleA = a.getAttribute('data-title');
                let titleB = b.getAttribute('data-title');
                
                if (sortType === 'az') return titleA.localeCompare(titleB);
                else return titleB.localeCompare(titleA);
            });

            // Kosongkan container lalu masukkan kembali sesuai urutan
            cards.forEach(card => container.appendChild(card));
        }
    </script>
</body>
</html>