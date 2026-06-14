<?php
session_start();
require '../login/koneksi.php';

// Pastikan yang masuk adalah siswa
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

// =========================================================
// PROSES SIMPAN SANDI BARU (DARI MODAL WAJIB UBAH)
// =========================================================
if (isset($_POST['simpan_sandi_wajib'])) {
    $sandi_baru = mysqli_real_escape_string($koneksi, $_POST['password_baru']);
    
    // Update password dan matikan sakelar wajib ubah (jadi 0)
    mysqli_query($koneksi, "UPDATE users SET Password = '$sandi_baru', WajibUbahPassword = 0 WHERE IDUser = '$id_user'");
    
    // Refresh halaman agar pop-up hilang
    header("Location: " . $_SERVER['PHP_SELF'] . "?status=sandi_diperbarui");
    exit;
}

$query_status_sandi = mysqli_query($koneksi, "SELECT WajibUbahPassword FROM users WHERE IDUser = '$id_user'");
$status_sandi = mysqli_fetch_assoc($query_status_sandi);
$wajib_ubah = isset($status_sandi['WajibUbahPassword']) ? $status_sandi['WajibUbahPassword'] : 0;

// 2. AMBIL DATA SISWA
$query_siswa = mysqli_query($koneksi, "SELECT * FROM siswa WHERE IDUser = '$id_user'");
$data_siswa = mysqli_fetch_assoc($query_siswa);

$id_siswa = $data_siswa['IDSiswa'] ?? '';
$kelas_siswa = $data_siswa['Kelas'] ?? '';
$nama_lengkap = $data_siswa['Nama'] ?? $data_siswa['NamaSiswa'] ?? 'Siswa';
$nama_depan = htmlspecialchars(explode(' ', trim($nama_lengkap))[0]);

// 3. SINKRONISASI DATA GAMIFIKASI
$query_gami = mysqli_query($koneksi, "
    SELECT g.TotalPoint, ml.LevelAngka, ml.Gelar 
    FROM gamifikasi g
    JOIN master_level ml ON g.TotalPoint >= ml.BatasPoin 
    WHERE g.IDSiswa = '$id_siswa'
    ORDER BY ml.BatasPoin DESC LIMIT 1
");

if($query_gami && mysqli_num_rows($query_gami) > 0){
    $data_gami = mysqli_fetch_assoc($query_gami);
    $poin_siswa = $data_gami['TotalPoint'] ?? 0;
    $level_siswa = $data_gami['LevelAngka'] ?? 1;
    $gelar = $data_gami['Gelar'] ?? 'Beginner Accountant';
} else {
    // Jika siswa baru, ambil level terendah
    $q_lvl_min = mysqli_query($koneksi, "SELECT LevelAngka, Gelar FROM master_level ORDER BY BatasPoin ASC LIMIT 1");
    $d_min = mysqli_fetch_assoc($q_lvl_min);
    $poin_siswa = 0;
    $level_siswa = $d_min['LevelAngka'] ?? 1;
    $gelar = $d_min['Gelar'] ?? 'Beginner Accountant';
}
// Alias untuk dipanggil di HTML
$xp_siswa = $poin_siswa;

// 4. PERINGKAT KELAS
$query_rank = mysqli_query($koneksi, "
    SELECT s.IDSiswa, IFNULL(g.TotalPoint, 0) as TotalPoint
    FROM siswa s 
    LEFT JOIN gamifikasi g ON s.IDSiswa = g.IDSiswa 
    WHERE s.Kelas = '$kelas_siswa'
    ORDER BY TotalPoint DESC
");
$rank_siswa = '-';
$rank_counter = 1;
if ($query_rank) {
    while($row = mysqli_fetch_assoc($query_rank)) {
        if($row['IDSiswa'] == $id_siswa) { $rank_siswa = $rank_counter; break; }
        $rank_counter++;
    }
}

// 5. KALKULASI TUGAS (HANYA DARI TOPIK YANG VALID DI KELAS INI)
$q_tugas_pending = mysqli_query($koneksi, "
    SELECT COUNT(t.IDTugas) as total_pending 
    FROM tugas t
    JOIN topik_mapel tm ON t.IDTopik = tm.IDTopik
    WHERE tm.Kelas = '$kelas_siswa' 
    AND t.IDTugas NOT IN (SELECT IDTugas FROM pengumpulan_tugas WHERE IDSiswa = '$id_siswa')
");
$tugas_pending = (mysqli_fetch_assoc($q_tugas_pending)['total_pending']) ?? 0;

$q_tugas_selesai = mysqli_query($koneksi, "
    SELECT COUNT(pt.IDPengumpulan) as total_selesai 
    FROM pengumpulan_tugas pt
    JOIN tugas t ON pt.IDTugas = t.IDTugas
    JOIN topik_mapel tm ON t.IDTopik = tm.IDTopik
    WHERE pt.IDSiswa = '$id_siswa' AND tm.Kelas = '$kelas_siswa'
");
$tugas_selesai = (mysqli_fetch_assoc($q_tugas_selesai)['total_selesai']) ?? 0;

// 6. SISTEM QUOTE ACAK
$quotes_fallback = [
    "Pendidikan adalah senjata paling mematikan di dunia, karena dengannya Anda dapat mengubah dunia. - Nelson Mandela",
    "Orang bijak belajar ketika mereka bisa. Orang bodoh belajar ketika mereka terpaksa. - Arthur Wellesley",
    "Jangan pernah berhenti belajar, karena hidup tak pernah berhenti mengajarkan. - Anonim",
    "Hiduplah seolah engkau mati besok. Belajarlah seolah engkau hidup selamanya. - Mahatma Gandhi"
];

$quote_teks = "";
$q_quote = @mysqli_query($koneksi, "SELECT TeksQuote, Tokoh FROM quotes ORDER BY RAND() LIMIT 1");
if($q_quote && mysqli_num_rows($q_quote) > 0) {
    $row_q = mysqli_fetch_assoc($q_quote);
    $quote_teks = $row_q['TeksQuote'] . " - " . $row_q['Tokoh'];
} else {
    $quote_teks = $quotes_fallback[array_rand($quotes_fallback)];
}

// 7. AMBIL DAFTAR MAPEL
$query_mapel = mysqli_query($koneksi, "
    SELECT m.*, g.NamaGuru 
    FROM mapel m 
    LEFT JOIN guru g ON m.IDGuru = g.IDGuru 
    WHERE m.Kelas LIKE '%\"$kelas_siswa\"%'
");
$total_mapel = mysqli_num_rows($query_mapel);
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
            --primary: #1e1b4b;          /* Midnight Blue Peakt */
            --primary-dark: #100f28;     
            --primary-light: #e0e7ff;    /* Biru tipis untuk hover */
            --secondary: #3b82f6;        /* Slate Blue untuk aksen */
            --gradient-primary: linear-gradient(135deg, #1e1b4b, #312e81);
            --gradient-card: linear-gradient(135deg, #312e81, #1e1b4b);
            --text-dark: #1e293b;
            --text-muted: #64748b;
        }
        
        body { background-color: #f8fafc; color: var(--text-dark); font-family: 'Segoe UI', system-ui, sans-serif; overflow-x: hidden; display: flex; flex-direction: column; min-height: 100vh; }
        
        .navbar-custom { background: var(--gradient-primary) !important; box-shadow: 0 4px 20px rgba(30, 27, 75, 0.3); padding: 12px 0; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar { background-color: #fff; box-shadow: 4px 0 20px rgba(0,0,0,0.03); padding: 25px 15px; z-index: 100; min-height: calc(100vh - 70px); }
        .sidebar .nav-link { color: var(--text-muted); font-weight: 600; padding: 12px 20px; border-radius: 12px; margin-bottom: 8px; transition: all 0.3s ease; }
        .sidebar .nav-link:hover { background-color: #f8fafc; color: var(--secondary); transform: translateX(5px); }
        .sidebar .nav-link.active { background-color: var(--primary-light); color: var(--primary); }

        /* HERO CARD (Welcome Banner) */
        .hero-card { background: var(--gradient-card); border-radius: 20px; border: none; padding: 40px 45px; color: white; position: relative; overflow: hidden; box-shadow: 0 20px 40px rgba(30, 27, 75, 0.2); }
        .hero-card::after { content: ''; position: absolute; top: -50px; right: -50px; width: 350px; height: 350px; background: radial-gradient(circle, rgba(59, 130, 246, 0.2) 0%, transparent 70%); border-radius: 50%; }
        .quote-box { background: rgba(255,255,255,0.08); backdrop-filter: blur(8px); border-left: 4px solid var(--secondary); padding: 18px 20px; border-radius: 0 16px 16px 0; margin-top: 25px; max-width: 700px;}

        /* KOTAK STATISTIK */
        .stat-card { border-radius: 20px; transition: all 0.3s ease; position: relative; overflow: hidden; }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 15px 30px rgba(0,0,0,0.06) !important; }
        .stat-card::before { content: ''; position: absolute; top: -20px; right: -20px; width: 100px; height: 100px; border-radius: 50%; opacity: 0.08; transition: 0.5s ease; }
        .stat-card:hover::before { transform: scale(1.5); }
        
        .stat-rank::before { background: radial-gradient(circle, #fbbf24 0%, transparent 70%); }
        .stat-pending::before { background: radial-gradient(circle, #ef4444 0%, transparent 70%); }
        .stat-done::before { background: radial-gradient(circle, #10b981 0%, transparent 70%); }
        .stat-xp::before { background: radial-gradient(circle, #3b82f6 0%, transparent 70%); }

        .icon-circle { width: 55px; height: 55px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.6rem; margin-bottom: 15px; }

        /* MAPEL TOOLBAR */
        .toolbar-mapel { background: #fff; border-radius: 16px; padding: 15px; box-shadow: 0 2px 15px rgba(0,0,0,0.02); margin-bottom: 25px; display: flex; flex-wrap: wrap; gap: 15px; justify-content: space-between; align-items: center;}
        .search-box { position: relative; flex-grow: 1; max-width: 350px; }
        .search-box i { position: absolute; left: 18px; top: 50%; transform: translateY(-50%); color: var(--text-muted); }
        .search-box input { padding-left: 45px; border-radius: 50px; border: 1px solid #e2e8f0; background: #f8fafc; padding-top: 10px; padding-bottom: 10px;}
        .search-box input:focus { border-color: var(--secondary); box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.15); background: #fff;}
        .view-btn { border: 1px solid #e2e8f0; background: #fff; color: var(--text-muted); border-radius: 10px; padding: 8px 14px; transition: 0.2s; }
        .view-btn.active { background: var(--primary-light); color: var(--primary); border-color: var(--primary-light); }

        /* MAPEL CARDS (REVISI CLICKABLE AREA & HILANGKAN TOMBOL) */
        .mapel-card { background: #fff; border-radius: 20px; border: 1px solid #f1f5f9; box-shadow: 0 8px 25px rgba(0,0,0,0.02); transition: all 0.3s ease; overflow: hidden; cursor: pointer; text-decoration: none; display: flex; flex-direction: column; height: 100%; color: inherit;}
        .mapel-card:hover { transform: translateY(-8px); box-shadow: 0 20px 40px rgba(30, 27, 75, 0.08); border-color: #e2e8f0;}
        .mapel-cover { height: 150px; background-color: #cbd5e1; position: relative; overflow: hidden; }
        .mapel-cover img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.6s ease; }
        .mapel-card:hover .mapel-cover img { transform: scale(1.08); }
        
        .badge-kelas { position: absolute; top: 15px; left: 15px; background: rgba(255,255,255,0.95); color: var(--primary); backdrop-filter: blur(5px); padding: 6px 14px; border-radius: 20px; font-size: 0.75rem; font-weight: 800; box-shadow: 0 4px 10px rgba(0,0,0,0.1);}
        
        /* Progress Bar Modern */
        .progress-container { background: #f8fafc; padding: 12px 15px; border-radius: 12px; border: 1px solid #f1f5f9; margin-top: auto;}
        .progress { height: 8px; background-color: #e2e8f0; box-shadow: inset 0 1px 2px rgba(0,0,0,0.05); border-radius: 10px;}
        .progress-bar { background: linear-gradient(90deg, var(--secondary), var(--primary)); border-radius: 10px; transition: width 1s ease;}

        /* MODE DAFTAR (LIST VIEW CSS) - VERTICAL STACKED INFO */
        #courseContainer.list-mode { display: flex; flex-direction: column; gap: 15px; }
        #courseContainer.list-mode .col-mapel { width: 100%; max-width: 100%; padding: 0; margin-bottom: 0; }
        
        #courseContainer.list-mode .mapel-card { 
            flex-direction: row; height: 140px !important; align-items: center; 
            border-radius: 20px; padding: 0; background: #fff; overflow: hidden;
        }
        
        #courseContainer.list-mode .mapel-cover { 
            width: 240px; height: 100%; border-radius: 0; flex-shrink: 0;
        }
        
        #courseContainer.list-mode .badge-kelas { top: 15px; left: 15px; }
        
        /* Mengubah layout body card menjadi susunan vertikal ke bawah */
        #courseContainer.list-mode .card-body { 
            padding: 20px 30px !important; 
            flex-direction: column !important; 
            align-items: flex-start !important; 
            justify-content: center !important; 
            width: 100%; gap: 12px; /* Jarak antara teks info dan progress bar */
        }
        
        #courseContainer.list-mode .info-wrapper { 
            width: 100%; max-width: 100%; margin-bottom: 0;
        }
        
        #courseContainer.list-mode .mapel-title { 
            font-size: 1.3rem; margin-bottom: 4px; 
        }
        
        /* Progress bar melebar sempurna di bawah teks nama guru */
        #courseContainer.list-mode .progress-container { 
            width: 100%; max-width: 100%; margin-top: 0 !important; 
            background: #f8fafc; padding: 10px 15px; border-radius: 12px; 
            border: 1px solid #f1f5f9;
        }

        /* Penyesuaian responsif saat dibuka di handphone */
        @media (max-width: 768px) { 
            #courseContainer.list-mode .mapel-card { flex-direction: column; height: auto !important; }
            #courseContainer.list-mode .mapel-cover { width: 100%; height: 150px; min-height: 140px; }
            #courseContainer.list-mode .card-body { padding: 20px !important; gap: 15px; }
        }

        .footer { margin-top: auto; background-color: #ffffff; border-top: 1px solid #e2e8f0; padding: 25px 0; color: var(--text-muted); font-size: 0.9rem; }
    </style>
</head>
<body>

    <?php include 'komponen_navbar.php'; ?>

    <div class="container-fluid px-0 flex-grow-1">
        <div class="row g-0">
        <?php include 'komponen_sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-lg-5 px-4 py-5 pb-5">
                
            <div class="hero-card mb-5">
                    <div class="position-relative z-1 row align-items-center">
                        <div class="col-lg-8">
                            <!-- PERBAIKAN: Menggunakan background transparan tipis agar teks tahun ajaran putihnya kelihatan jelas -->
                            <span class="badge rounded-pill px-3 py-2 mb-3 fw-bold border border-white border-opacity-25" style="background: rgba(255, 255, 255, 0.15); color: #ffffff; letter-spacing: 1px; font-size: 0.75rem;">TAHUN AJARAN 2025/2026</span>
                            <h1 class="fw-bold mb-2 display-6" style="letter-spacing: -0.5px;">Halo, <?= $nama_depan ?>! 👋</h1>
                            <p class="fs-6 text-white-50 mb-0 fw-light">Terus kumpulkan XP dan jadilah yang terbaik di papan peringkat kelasmu.</p>
                        </div>
                        <div class="col-lg-4 mt-4 mt-lg-0 text-lg-end">
                            <a href="gamifikasi.php" class="text-decoration-none"> 
                                <div class="bg-white bg-opacity-10 border border-white border-opacity-25 rounded-4 p-3 d-inline-block text-start transition shadow-sm" style="backdrop-filter: blur(10px);">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="bg-warning text-dark rounded-circle d-flex justify-content-center align-items-center fw-bold fs-4 shadow-sm" style="width: 55px; height: 55px;">
                                            <?= $level_siswa ?>
                                        </div>
                                        <div>
                                            <h6 class="mb-0 text-warning fw-bold text-uppercase" style="letter-spacing: 0.5px;"><?= $gelar ?></h6>
                                            <small class="text-white-50">Level & Rank Saat Ini</small>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="row g-3 mb-5">
                    
                    <div class="col-6 col-lg-3">
                        <a href="peringkat_kelas.php" class="text-decoration-none">
                            <div class="card stat-card stat-rank border-0 shadow-sm h-100 bg-white">
                                <div class="card-body p-4 d-flex align-items-center gap-3">
                                    <div class="icon-circle bg-warning bg-opacity-10 text-warning m-0"><i class="bi bi-trophy-fill"></i></div>
                                    <div>
                                        <h6 class="text-muted fw-bold mb-1" style="font-size: 0.7rem; letter-spacing: 1px;">PERINGKAT KELAS</h6>
                                        <h3 class="text-dark fw-bold mb-0">#<?= $rank_siswa ?></h3>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>

                    <div class="col-6 col-lg-3">
                        <a href="total_exp.php" class="text-decoration-none">
                            <div class="card stat-card stat-xp border-0 shadow-sm h-100 bg-white">
                                <div class="card-body p-4 d-flex align-items-center gap-3">
                                    <div class="icon-circle bg-primary bg-opacity-10 text-primary m-0"><i class="bi bi-lightning-charge-fill"></i></div>
                                    <div>
                                        <h6 class="text-muted fw-bold mb-1" style="font-size: 0.7rem; letter-spacing: 1px;">TOTAL XP</h6>
                                        <h3 class="text-dark fw-bold mb-0"><?= number_format($poin_siswa, 0, ',', '.') ?></h3>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>

                    <div class="col-6 col-lg-3">
                        <a href="tugas_belum_selesai.php" class="text-decoration-none">
                            <div class="card stat-card stat-pending border-0 shadow-sm h-100 bg-white">
                                <div class="card-body p-4 d-flex align-items-center gap-3">
                                    <div class="icon-circle bg-danger bg-opacity-10 text-danger m-0"><i class="bi bi-journal-x"></i></div>
                                    <div>
                                        <h6 class="text-muted fw-bold mb-1" style="font-size: 0.7rem; letter-spacing: 1px;">BELUM SELESAI</h6>
                                        <h3 class="text-dark fw-bold mb-0"><?= $tugas_pending ?> <span class="fs-6 text-muted fw-normal">Tugas</span></h3>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>

                    <div class="col-6 col-lg-3">
                        <a href="tugas_sudah_selesai.php" class="text-decoration-none">
                            <div class="card stat-card stat-done border-0 shadow-sm h-100 bg-white">
                                <div class="card-body p-4 d-flex align-items-center gap-3">
                                    <div class="icon-circle bg-success bg-opacity-10 text-success m-0"><i class="bi bi-journal-check"></i></div>
                                    <div>
                                        <h6 class="text-muted fw-bold mb-1" style="font-size: 0.7rem; letter-spacing: 1px;">SUDAH SELESAI</h6>
                                        <h3 class="text-dark fw-bold mb-0"><?= $tugas_selesai ?> <span class="fs-6 text-muted fw-normal">Tugas</span></h3>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>

                </div>

                <div class="d-flex align-items-center mb-4 pb-2 border-bottom">
                    <h4 class="fw-bold text-dark mb-0 me-auto"><i class="bi bi-bookshelf text-primary me-2"></i>Kelas Pelajaran</h4>
                </div>

                <div class="toolbar-mapel">
                    <div class="search-box">
                        <i class="bi bi-search"></i>
                        <input type="text" id="searchMapel" class="form-control" placeholder="Cari mata pelajaran..." onkeyup="filterMapel()">
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <select id="sortMapel" class="form-select border-0 bg-light fw-semibold text-secondary px-3" style="width: auto;" onchange="sortMapel()">
                            <option value="az">A - Z</option>
                            <option value="za">Z - A</option>
                        </select>
                        <div class="btn-group shadow-sm" role="group">
                            <button type="button" class="btn view-btn active" id="btnGrid" onclick="setView('grid')"><i class="bi bi-grid-fill"></i></button>
                            <button type="button" class="btn view-btn" id="btnList" onclick="setView('list')"><i class="bi bi-list-task"></i></button>
                        </div>
                    </div>
                </div>

                <div class="row g-4" id="courseContainer">
                    <?php if($total_mapel == 0): ?>
                        <div class="col-12" id="emptyStateMapel">
                            <div class="card mapel-card p-5 text-center border border-dashed shadow-none bg-transparent">
                                <i class="bi bi-folder-x text-muted mb-3" style="font-size: 4rem;"></i>
                                <h4 class="fw-bold text-dark">Belum Ada Kelas</h4>
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

                            // HITUNG TUGAS
                            $q_tot_tugas = mysqli_query($koneksi, "SELECT COUNT(t.IDTugas) as tot FROM tugas t JOIN topik_mapel tm ON t.IDTopik = tm.IDTopik WHERE t.IDMapel = '$id_mapel_loop' AND tm.Kelas = '$kelas_siswa'");
                            $tot_tugas = mysqli_fetch_assoc($q_tot_tugas)['tot'] ?? 0;
                            
                            $q_selesai = mysqli_query($koneksi, "SELECT COUNT(pt.IDPengumpulan) as sel FROM pengumpulan_tugas pt JOIN tugas t ON pt.IDTugas = t.IDTugas JOIN topik_mapel tm ON t.IDTopik = tm.IDTopik WHERE t.IDMapel = '$id_mapel_loop' AND pt.IDSiswa = '$id_siswa' AND tm.Kelas = '$kelas_siswa'");
                            $tot_selesai = mysqli_fetch_assoc($q_selesai)['sel'] ?? 0;

                            // HITUNG MATERI 
                            $q_tot_materi = mysqli_query($koneksi, "SELECT COUNT(m.IDMateri) as tot FROM materi m JOIN topik_mapel tm ON m.IDTopik = tm.IDTopik WHERE m.IDMapel = '$id_mapel_loop' AND tm.Kelas = '$kelas_siswa'");
                            $tot_materi = mysqli_fetch_assoc($q_tot_materi)['tot'] ?? 0;

                            $q_materi_list = mysqli_query($koneksi, "SELECT m.IDMateri FROM materi m JOIN topik_mapel tm ON m.IDTopik = tm.IDTopik WHERE m.IDMapel = '$id_mapel_loop' AND tm.Kelas = '$kelas_siswa'");
                            $materi_ids = [];
                            while($m_row = mysqli_fetch_assoc($q_materi_list)) {
                                $materi_ids[] = 'materi_' . $m_row['IDMateri'];
                            }
                            $materi_ids_str = implode(',', $materi_ids);
                        ?>
                            <div class="col-md-6 col-xl-4 col-mapel" 
                                 data-title="<?= strtolower($row['NamaMapel']) ?>"
                                 data-total-tugas="<?= $tot_tugas ?>"
                                 data-selesai-tugas="<?= $tot_selesai ?>"
                                 data-total-materi="<?= $tot_materi ?>"
                                 data-materi-ids="<?= $materi_ids_str ?>">
                                 
                                <a href="mapel.php?id_mapel=<?= $row['IDMapel'] ?>" class="mapel-card">
                                    <div class="mapel-cover">
                                        <img src="<?= $cover_img ?>" alt="Cover Mapel">
                                        <span class="badge-kelas"><i class="bi bi-diagram-3-fill text-primary me-1"></i> <?= htmlspecialchars($kelas_siswa) ?></span>
                                    </div>
                                    <div class="card-body p-4 d-flex flex-column">
                                        <div class="info-wrapper">
                                            <h5 class="fw-bold text-dark mb-1 text-truncate mapel-title" title="<?= htmlspecialchars($row['NamaMapel']) ?>"><?= htmlspecialchars($row['NamaMapel']) ?></h5>
                                            <p class="small text-muted mb-3 flex-grow-1"><i class="bi bi-person-video3 me-2 text-primary"></i> <?= htmlspecialchars($row['NamaGuru'] ?? 'Belum Ditentukan') ?></p>
                                        </div>
                                        
                                        <?php if(($tot_tugas + $tot_materi) > 0): ?>
                                        <div class="progress-container mt-auto">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <span class="small text-muted fw-semibold" style="font-size: 0.75rem;">Progress Belajar</span>
                                                <span class="small fw-bold text-primary" style="font-size: 0.75rem;">0%</span>
                                            </div>
                                            <div class="progress" style="height: 6px; background-color: #e2e8f0; box-shadow: none;">
                                                <div class="progress-bar bg-primary rounded-pill" role="progressbar" style="width: 0%"></div>
                                            </div>
                                        </div>
                                        <?php else: ?>
                                        <div class="mt-auto">
                                            <span class="badge bg-light text-muted border border-secondary-subtle w-100 py-2 d-block">
                                                <i class="bi bi-info-circle me-1"></i> Belum ada materi/tugas
                                            </span>
                                        </div>
                                        <?php endif; ?>
                                        
                                    </div>
                                </a>
                            </div>
                        <?php endwhile; ?>
                        
                        <div class="col-12 d-none" id="noResultMapel">
                            <div class="card mapel-card p-5 text-center border border-dashed shadow-none bg-transparent">
                                <i class="bi bi-search text-muted mb-3" style="font-size: 2.5rem;"></i>
                                <h5 class="fw-bold text-secondary">Mata pelajaran tidak ditemukan</h5>
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
                <i class="bi bi-mortarboard-fill text-primary fs-4"></i>
                <h6 class="fw-bold text-dark mb-0">LMS SMKN 1 Wongsorejo</h6>
            </div>
            <p class="mb-0">© <?= date('Y') ?> Hak Cipta Dilindungi. Dikembangkan oleh Tim PBL V.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
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
            cards.forEach(card => container.appendChild(card));
        }

        function sinkronisasiProgressBarLMS() {
            document.querySelectorAll('.col-mapel').forEach(card => {
                const totalTugas = parseInt(card.getAttribute('data-total-tugas')) || 0;
                const selesaiTugas = parseInt(card.getAttribute('data-selesai-tugas')) || 0;
                const totalMateri = parseInt(card.getAttribute('data-total-materi')) || 0;
                const materiIdsStr = card.getAttribute('data-materi-ids') || '';
                
                let selesaiMateri = 0;
                if (materiIdsStr) {
                    materiIdsStr.split(',').forEach(id => {
                        if (localStorage.getItem(id) === 'selesai') {
                            selesaiMateri++;
                        }
                    });
                }
                
                const totalItem = totalTugas + totalMateri;
                const totalSelesai = selesaiTugas + selesaiMateri;
                const persentase = totalItem > 0 ? Math.round((totalSelesai / totalItem) * 100) : 0;
                
                const progressBars = card.querySelectorAll('.progress-bar');
                const progressTexts = card.querySelectorAll('.progress-container .text-primary');
                
                progressBars.forEach(bar => bar.style.width = persentase + '%');
                progressTexts.forEach(text => text.innerText = persentase + '%');
            });
        }

        document.addEventListener('DOMContentLoaded', sinkronisasiProgressBarLMS);
        
    </script> 

    <?php if($wajib_ubah == 1): ?>
    <div class="modal fade" id="modalWajibSandi" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <form method="POST" action="">
                    <div class="modal-header bg-danger text-white border-0 p-4">
                        <h5 class="modal-title fw-bold"><i class="bi bi-shield-lock-fill me-2"></i>Keamanan Akun</h5>
                    </div>
                    <div class="modal-body p-4">
                        <div class="alert alert-warning small border-warning border-opacity-50 text-dark">
                            <i class="bi bi-exclamation-triangle-fill me-2 text-warning"></i>
                            Demi keamanan, Admin mewajibkan Anda untuk mengubah kata sandi default. Silakan buat kata sandi baru untuk melanjutkan ke Dashboard.
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-secondary">Kata Sandi Baru</label>
                            <input type="password" name="password_baru" class="form-control form-control-lg" placeholder="Minimal 6 karakter..." required minlength="6">
                        </div>
                    </div>
                    <div class="modal-footer bg-light border-0 p-3 rounded-bottom-4">
                        <button type="submit" name="simpan_sandi_wajib" class="btn btn-primary fw-bold px-4 w-100 py-2">Simpan Sandi & Masuk <i class="bi bi-arrow-right ms-2"></i></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var modalSandi = new bootstrap.Modal(document.getElementById('modalWajibSandi'));
            modalSandi.show();
        });
    </script>
    <?php endif; ?>

    <?php if(isset($_GET['status']) && $_GET['status'] == 'sandi_diperbarui'): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({ title: 'Akses Terbuka!', text: 'Kata sandi berhasil diperbarui. Selamat datang!', icon: 'success', confirmButtonColor: '#4f46e5', timer: 3000, showConfirmButton: false });
            window.history.replaceState(null, null, window.location.pathname);
        });
    </script>
    <?php endif; ?>
</body>
</html>