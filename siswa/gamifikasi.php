<?php
session_start();
require '../login/koneksi.php';

// Pastikan yang masuk adalah siswa
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'siswa'){
    header("Location: ../login/login.php"); exit;
}

$id_user = $_SESSION['IDUser'] ?? '';

// 1. Ambil Data Siswa
$query_siswa = mysqli_query($koneksi, "SELECT * FROM siswa WHERE IDUser = '$id_user'");
$data_siswa = mysqli_fetch_assoc($query_siswa);
$id_siswa = $data_siswa['IDSiswa'] ?? '';
$kelas_siswa = $data_siswa['Kelas'] ?? '';
$nama_lengkap = $data_siswa['Nama'] ?? $data_siswa['NamaSiswa'] ?? 'Siswa';

// 2. Ambil Aturan Poin dari Database
$query_rules = mysqli_query($koneksi, "SELECT * FROM master_aturan_poin ORDER BY IDAturan ASC");

// 3. Ambil Master Level dari Database
$query_ranks = mysqli_query($koneksi, "SELECT * FROM master_level ORDER BY BatasPoin ASC");

// 4. Ambil Total Poin Siswa Saat Ini
$query_gami = mysqli_query($koneksi, "SELECT TotalPoint FROM gamifikasi WHERE IDSiswa = '$id_siswa'");
$poin_siswa = (mysqli_num_rows($query_gami) > 0) ? mysqli_fetch_assoc($query_gami)['TotalPoint'] : 0;

// Kosmetik Tampilan (Icon & Warna)
$icons = ['bi-book-half', 'bi-journal-check', 'bi-lightning-charge-fill', 'bi-stopwatch-fill', 'bi-calendar-check-fill', 'bi-star-fill'];
$colors = ['primary', 'success', 'warning', 'info', 'secondary', 'danger'];
$badges = ['🥉', '🥈', '🥈', '🥇', '🥇', '🏅', '🏅', '💎', '💎', '🌟', '🌟', '🌟', '👑'];

// Kamus Deskripsi Manual untuk Aturan Poin
$desc_map = [
    'Baca Materi' => 'Otomatis didapat saat kamu membuka dan menandai materi selesai.',
    'Nilai Tugas' => 'Dikonversi dari nilai murni hasil pengerjaan tugasmu.',
    'Bonus Kilat' => 'Kumpulkan tugas super cepat (< 24 jam sejak diposting).',
    'Bonus Cepat' => 'Kumpulkan tugas dengan cepat (< 48 jam sejak diposting).',
    'Bonus Disiplin' => 'Kumpulkan tugas tepat waktu (sebelum batas deadline).',
    'Bonus Sempurna' => 'Tambahan XP apresiasi jika kamu mendapat nilai 100.'
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pusat Gamifikasi - LMS Wongsorejo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    
    <style>
        :root { --primary: #4f46e5; --text-dark: #1e293b; --text-muted: #64748b; }
        body { background-color: #f8fafc; font-family: 'Segoe UI', system-ui, sans-serif; display: flex; flex-direction: column; min-height: 100vh;}
        
        .navbar-custom { background: linear-gradient(135deg, #4f46e5, #0ea5e9) !important; box-shadow: 0 4px 15px rgba(79, 70, 229, 0.2); padding: 10px 0; }
        .sidebar { background-color: #fff; box-shadow: 2px 0 20px rgba(0,0,0,0.03); padding: 25px 15px; z-index: 100; min-height: calc(100vh - 70px); }
        .sidebar .nav-link { color: var(--text-muted); font-weight: 600; padding: 12px 20px; border-radius: 12px; margin-bottom: 8px; transition: all 0.3s ease; }
        .sidebar .nav-link:hover { background-color: #f1f5f9; color: var(--primary); transform: translateX(5px); }
        .sidebar .nav-link.active { background-color: #e0e7ff; color: var(--primary); }
        
        .breadcrumb-modern { font-size: 0.9rem; font-weight: 600; color: var(--text-muted); margin-bottom: 20px; }
        .breadcrumb-modern a { color: var(--primary); text-decoration: none; transition: 0.2s; }
        
        .hero-banner { background: linear-gradient(135deg, #1e1b4b, #312e81); border-radius: 20px; color: white; padding: 40px; position: relative; overflow: hidden; box-shadow: 0 10px 30px rgba(30,27,75,0.15); margin-bottom: 30px;}
        .hero-banner::after { content:''; position:absolute; top:-50%; right:-20%; width: 400px; height: 400px; background: radial-gradient(circle, rgba(14,165,233,0.3) 0%, transparent 70%); border-radius:50%; }
        
        .master-card { border: none; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.03); background: #fff; border: 1px solid #e2e8f0; height: 100%; }
        .master-card-header { background: #fff; border-bottom: 1px solid #f1f5f9; padding: 1.5rem; border-radius: 16px 16px 0 0; }
        
        .rule-card { border: 1px solid #e2e8f0; border-radius: 12px; background: #fff; margin-bottom: 12px; transition: 0.3s; border-left: 4px solid transparent; }
        .rule-card:hover { transform: translateY(-3px); box-shadow: 0 8px 15px rgba(0,0,0,0.05); }
        .rule-card.border-primary { border-left-color: #0d6efd !important; }
        .rule-card.border-success { border-left-color: #198754 !important; }
        .rule-card.border-warning { border-left-color: #ffc107 !important; }
        .rule-card.border-info { border-left-color: #0dcaf0 !important; }
        .rule-card.border-secondary { border-left-color: #6c757d !important; }
        .rule-card.border-danger { border-left-color: #dc3545 !important; }
        
        .icon-circle { width: 45px; height: 45px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; }
        
        .rank-table th { background-color: #f8fafc; color: #475569; font-weight: 700; text-transform: uppercase; font-size: 0.8rem; padding: 15px;}
        .rank-table td { vertical-align: middle; padding: 15px; font-weight: 600; color: #334155; border-bottom: 1px solid #f1f5f9; }
        .rank-table tr.active-rank td { background-color: #f0fdf4; border-color: #86efac; }
        
        .level-badge { background: #1e293b; color: white; padding: 6px 14px; border-radius: 50px; font-size: 0.75rem; font-weight: bold; letter-spacing: 0.5px;}
        .poin-badge { background: #e0e7ff; color: #4f46e5; padding: 6px 12px; border-radius: 8px; font-weight: 800; font-size: 0.8rem;}
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom sticky-top">
        <div class="container-fluid px-4">
            <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="siswa.php">
                <i class="bi bi-mortarboard-fill fs-4"></i> LMS Wongsorejo
            </a>
            <div class="d-none d-lg-flex align-items-center gap-3">
                <div class="text-end text-white">
                    <h6 class="mb-0 fw-bold small text-nowrap" style="font-size: 1.1rem"><?= htmlspecialchars($nama_lengkap) ?></h6>
                    <span class="badge bg-white bg-opacity-25 rounded-pill mt-1"><i class="bi bi-building me-1"></i><?= htmlspecialchars($kelas_siswa) ?></span>
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
                        <li class="nav-item"><a class="nav-link" href="siswa.php"><i class="bi bi-grid-1x2-fill me-3 fs-5 align-middle"></i> Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link" href="kalender.php"><i class="bi bi-calendar2-week-fill me-3 fs-5 align-middle"></i> Jadwal & Agenda</a></li>
                        <li class="nav-item mt-4 mb-2"><div class="text-muted small fw-bold px-3 uppercase" style="letter-spacing: 1px;">PRESTASI</div></li>
                        <li class="nav-item"><a class="nav-link active text-warning" href="gamifikasi.php"><i class="bi bi-trophy-fill me-3 fs-5 align-middle"></i> Gamifikasi</a></li>
                    </ul>
                </div>
            </nav>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-4 py-4 pb-5">
                
                <div class="breadcrumb-modern">
                    <i class="bi bi-house-door-fill me-1"></i> <a href="siswa.php">Dashboard</a> <i class="bi bi-chevron-right mx-2 text-muted" style="font-size: 0.7rem;"></i> Pusat Gamifikasi
                </div>

                <div class="hero-banner">
                    <div class="position-relative z-1">
                        <h2 class="fw-bold mb-2"><i class="bi bi-controller text-warning me-2"></i> Sistem Gamifikasi LMS</h2>
                        <p class="mb-0 text-white-50" style="max-width: 700px; line-height: 1.6;">
                            Kumpulkan <strong>Experience Points (XP)</strong> sebanyak mungkin dari aktivitas belajarmu! XP yang terkumpul akan meningkatkan Level-mu dan membuka Gelar Akademik eksklusif yang akan dipamerkan di Papan Peringkat Kelas.
                        </p>
                    </div>
                </div>

                <div class="row g-4">
                    <!-- KOLOM 1: ATURAN POIN -->
                    <div class="col-lg-5">
                        <div class="master-card">
                            <div class="master-card-header">
                                <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-stars text-warning me-2"></i> Cara Mendapatkan XP</h5>
                            </div>
                            <div class="card-body p-4">
                                <?php 
                                $i = 0;
                                while($rule = mysqli_fetch_assoc($query_rules)): 
                                    $icon = $icons[$i % count($icons)];
                                    $color = $colors[$i % count($colors)];
                                    $judul_aktivitas = htmlspecialchars($rule['JenisAktivitas']);
                                    $deskripsi = isset($desc_map[$judul_aktivitas]) ? $desc_map[$judul_aktivitas] : "Aktivitas belajar positif.";
                                ?>
                                <div class="rule-card border-<?= $color ?>">
                                    <div class="p-3 d-flex align-items-center">
                                        <div class="icon-circle bg-<?= $color ?> bg-opacity-10 text-<?= $color ?> me-3 flex-shrink-0">
                                            <i class="bi <?= $icon ?>"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-start mb-1">
                                                <h6 class="fw-bold mb-0 text-dark"><?= $judul_aktivitas ?></h6>
                                                <span class="badge bg-<?= $color ?> bg-opacity-10 text-<?= $color ?> border border-<?= $color ?>-subtle rounded-pill">
                                                    <?= (stripos($judul_aktivitas, 'Nilai') !== false) ? 'Sesuai Skor' : '+' . $rule['BesaranPoin'] . ' XP' ?>
                                                </span>
                                            </div>
                                            <p class="text-muted small mb-0 lh-sm"><?= $deskripsi ?></p>
                                        </div>
                                    </div>
                                </div>
                                <?php $i++; endwhile; ?>
                            </div>
                        </div>
                    </div>

                    <!-- KOLOM 2: PAPAN LEVEL -->
                    <div class="col-lg-7">
                        <div class="master-card">
                            <div class="master-card-header d-flex justify-content-between align-items-center">
                                <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-ladder text-success me-2"></i> Papan Jenjang Level</h5>
                                <span class="badge bg-light text-dark border shadow-sm">Poinmu: <?= number_format($poin_siswa, 0, ',', '.') ?> XP</span>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table rank-table mb-0">
                                        <thead>
                                            <tr>
                                                <th class="text-center" width="15%">Level</th>
                                                <th width="55%">Gelar Akademik</th>
                                                <th class="text-end pe-4" width="30%">Syarat Poin</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $baris = 0;
                                            $batas_atas = -1;
                                            // Array ranks diubah ke array biasa agar bisa melihat baris selanjutnya
                                            $all_ranks = [];
                                            while($r = mysqli_fetch_assoc($query_ranks)){ $all_ranks[] = $r; }
                                            
                                            for ($j=0; $j < count($all_ranks); $j++) { 
                                                $rank = $all_ranks[$j];
                                                $badge = $badges[$baris % count($badges)];
                                                
                                                $batas_bawah = $rank['BatasPoin'];
                                                $batas_atas = isset($all_ranks[$j+1]) ? $all_ranks[$j+1]['BatasPoin'] : 9999999;
                                                
                                                // Tandai hijau jika XP siswa berada di rentang level ini
                                                $is_active = ($poin_siswa >= $batas_bawah && $poin_siswa < $batas_atas) ? 'active-rank' : '';
                                            ?>
                                            <tr class="<?= $is_active ?>">
                                                <td class="text-center">
                                                    <span class="level-badge <?= $is_active ? 'bg-success text-white shadow-sm' : '' ?>">LVL <?= $rank['LevelAngka'] ?></span>
                                                </td>
                                                <td>
                                                    <span class="fs-5 me-2"><?= $badge ?></span> 
                                                    <span class="<?= $is_active ? 'fw-bold text-success' : 'fw-semibold text-dark' ?>"><?= htmlspecialchars($rank['Gelar']) ?></span>
                                                    <?php if($is_active) echo '<span class="badge bg-success ms-2" style="font-size:0.6rem;">Posisi-mu</span>'; ?>
                                                </td>
                                                <td class="text-end pe-4">
                                                    <span class="poin-badge <?= $is_active ? 'bg-success bg-opacity-10 text-success' : '' ?>"><i class="bi bi-gem me-1"></i> <?= number_format($rank['BatasPoin'], 0, ',', '.') ?> XP</span>
                                                </td>
                                            </tr>
                                            <?php $baris++; } ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>