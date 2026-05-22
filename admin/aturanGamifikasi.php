<?php
session_start();
require '../login/koneksi.php';

if($_SESSION['role'] != 'admin'){
    header("Location: ../login/login.php");
    exit;
}

// AMBIL DATA DARI TABEL DATABASE ASLI LOKAL
$query_rules = mysqli_query($koneksi, "SELECT * FROM master_aturan_poin ORDER BY IDAturan ASC");
$query_ranks = mysqli_query($koneksi, "SELECT * FROM master_level ORDER BY BatasPoin ASC");

// Kosmetik Tampilan & Kamus Deskripsi
$icons = ['bi-book-half', 'bi-bullseye', 'bi-lightning-charge-fill', 'bi-stopwatch-fill', 'bi-calendar-check-fill', 'bi-star-fill'];
$colors = ['primary', 'danger', 'warning', 'info', 'success', 'warning'];
$badges = ['🥉', '🥈', '🥈', '🥇', '🥇', '🏅', '🏅', '💎', '💎', '🌟', '🌟', '🌟', '👑'];

// Kamus deskripsi manual karena di database tidak ada kolom deskripsi
$desc_map = [
    'Baca Materi' => 'Otomatis didapat saat siswa membuka/mendownload materi.',
    'Nilai Tugas' => 'Sesuai input nilai murni dari Guru di sistem.',
    'Bonus Kilat' => 'Mengumpulkan tugas dalam < 24 jam sejak diposting.',
    'Bonus Cepat' => 'Mengumpulkan tugas dalam < 48 jam sejak diposting.',
    'Bonus Disiplin' => 'Mengumpulkan tugas setelah lewat 48 jam (sebelum deadline).',
    'Bonus Sempurna' => 'Tambahan apresiasi jika siswa mendapatkan Nilai Sempurna (100).'
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aturan Gamifikasi - Admin LMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; }
        .header-red { background: #dc3545; color: white; padding: 2rem 0 5rem 0; margin: 0; position: relative; }
        .header-red h2 { margin: 0; font-weight: 700; font-size: 1.7rem; letter-spacing: -0.5px; }
        .main-content { margin-top: -2.8rem; position: relative; z-index: 10; }
        .master-card { border: none; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); background: #fff; overflow: hidden; }
        .master-card-header { background: #fff; border-bottom: 1px solid #e9ecef; padding: 1.25rem 1.5rem; }
        .rule-card { border: 1px solid #e9ecef; border-radius: 10px; background: #fff; border-left: 5px solid transparent; }
        .rule-card.border-primary { border-left-color: #0d6efd !important; }
        .rule-card.border-danger { border-left-color: #dc3545 !important; }
        .rule-card.border-warning { border-left-color: #ffc107 !important; }
        .rule-card.border-info { border-left-color: #0dcaf0 !important; }
        .rule-card.border-success { border-left-color: #198754 !important; }
        .icon-circle { width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
        .rank-table th { background-color: #f8f9fa; color: #495057; font-weight: 700; text-transform: uppercase; font-size: 0.85rem; padding: 15px;}
        .rank-table td { vertical-align: middle; padding: 12px 15px; font-weight: 500; }
        .level-badge { background: #212529; color: white; padding: 5px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: bold; }
        .poin-badge { background: rgba(220,53,69,0.1); color: #dc3545; padding: 5px 10px; border-radius: 8px; font-weight: bold; }

        /* DARK MODE OVERRIDES */
        [data-bs-theme="dark"] .master-card, [data-bs-theme="dark"] .master-card-header { background-color: #1e1e1e !important; border-color: #333 !important; }
        [data-bs-theme="dark"] .rule-card { background-color: #2b2b2b !important; border-color: #444 !important; }
        [data-bs-theme="dark"] .rank-table th { background-color: #2b2b2b !important; color: #adb5bd !important; border-bottom: 2px solid #444 !important; }
        [data-bs-theme="dark"] .rank-table td { border-bottom: 1px solid #333 !important; color: #e0e0e0 !important; }
        [data-bs-theme="dark"] .rank-table tr:hover td { background-color: #252525 !important; }
        [data-bs-theme="dark"] .level-badge { background-color: #444 !important; color: #fff !important; }
        [data-bs-theme="dark"] .poin-badge { background: rgba(220,53,69,0.2) !important; color: #ff6b6b !important; }
        [data-bs-theme="dark"] .text-muted { color: #adb5bd !important; }
        [data-bs-theme="dark"] .text-dark { color: #e0e0e0 !important; }
        [data-bs-theme="dark"] .alert-info { background-color: rgba(13, 202, 240, 0.1) !important; color: #0dcaf0 !important; border: none !important; }
    </style>
</head>
<body class="bg-light">

    <nav class="navbar navbar-expand-lg navbar-dark bg-danger sticky-top shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="admin.php"><i class="bi bi-shield-lock-fill me-2"></i> PANEL ADMIN LMS</a>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <?php include 'sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-0">
                <div class="header-red">
                    <div class="container">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="pe-3">
                                <h2><i class="bi bi-controller me-2"></i> Aturan Gamifikasi</h2>
                                <p class="mb-0 text-white-50 small mt-2" style="max-width: 800px; text-align: justify; line-height: 1.5;">
                                    Sistem Gamifikasi ini dirancang khusus untuk meningkatkan motivasi belajar siswa Akuntansi (AKL). Akumulasi poin ini akan otomatis menaikkan Level dan memberikan Gelar Akademik unik dari level Beginner hingga Challenger!
                                </p>
                            </div>
                            <a href="editGamifikasi.php" class="btn btn-light text-danger fw-bold rounded-pill px-4 shadow-sm flex-shrink-0 mt-1">
                                <i class="bi bi-sliders me-2"></i> Edit Parameter
                            </a>
                        </div>
                    </div>
                </div>

                <div class="container main-content pb-5">
                    
                    <?php if(isset($_GET['status']) && $_GET['status'] == 'sukses_update'): ?>
                        <div class="alert alert-success border-0 shadow-sm mb-4 d-flex align-items-center">
                            <i class="bi bi-check-circle-fill fs-4 me-2"></i>
                            <strong>Berhasil!</strong> Parameter gamifikasi kurikulum telah diperbarui di database.
                        </div>
                    <?php endif; ?>

                    <div class="row g-4">
                        <div class="col-lg-5">
                            <div class="master-card h-100 d-flex flex-column">
                                <div class="master-card-header">
                                    <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-stars text-warning me-2"></i> Earning Rules (Perolehan Poin)</h5>
                                </div>
                                
                                <div class="card-body p-4 flex-grow-1">
                                    <div class="d-flex flex-column gap-3">
                                        <?php 
                                        $i = 0;
                                        while($rule = mysqli_fetch_assoc($query_rules)): 
                                            $icon = $icons[$i % count($icons)];
                                            $color = $colors[$i % count($colors)];
                                            
                                            // Ambil deskripsi dari kamus, jika tidak ada, beri default
                                            $judul_aktivitas = $rule['JenisAktivitas'];
                                            $deskripsi = isset($desc_map[$judul_aktivitas]) ? $desc_map[$judul_aktivitas] : "Poin apresiasi untuk aktivitas $judul_aktivitas.";
                                        ?>
                                        <div class="rule-card border-<?= $color ?>">
                                            <div class="p-3 d-flex align-items-center">
                                                <div class="icon-circle bg-<?= $color ?> bg-opacity-10 text-<?= $color ?> me-3 flex-shrink-0">
                                                    <i class="bi <?= $icon ?>"></i>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <h6 class="fw-bold mb-1 text-dark d-flex justify-content-between align-items-center">
                                                        <?= $judul_aktivitas ?>
                                                        <span class="badge bg-<?= $color ?> rounded-pill fs-6 px-3 shadow-sm">
                                                            <?= (stripos($judul_aktivitas, 'Nilai') !== false) ? '0 - ' . $rule['BesaranPoin'] : '+' . $rule['BesaranPoin'] ?> XP
                                                        </span>
                                                    </h6>
                                                    <p class="text-muted small mb-0 lh-sm"><?= $deskripsi ?></p>
                                                </div>
                                            </div>
                                        </div>
                                        <?php $i++; endwhile; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-7">
                            <div class="master-card h-100 d-flex flex-column">
                                <div class="master-card-header">
                                    <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-trophy-fill text-warning me-2"></i> Ranking System (Gelar & Level)</h5>
                                </div>
                                
                                <div class="card-body p-0 flex-grow-1">
                                    <div class="table-responsive">
                                        <table class="table rank-table table-hover mb-0">
                                            <thead>
                                                <tr>
                                                    <th class="text-center" width="15%">Level</th>
                                                    <th width="55%">Nama Gelar (Title)</th>
                                                    <th class="text-end pe-4" width="30%">Target Poin</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php 
                                                $baris = 0;
                                                while($rank = mysqli_fetch_assoc($query_ranks)): 
                                                    $badge = $badges[$baris % count($badges)];
                                                ?>
                                                <tr>
                                                    <td class="text-center">
                                                        <span class="level-badge">LVL <?= $rank['LevelAngka'] ?></span>
                                                    </td>
                                                    <td>
                                                        <span class="fs-5 me-2"><?= $badge ?></span> 
                                                        <span class="fw-semibold text-dark"><?= $rank['Gelar'] ?></span>
                                                    </td>
                                                    <td class="text-end pe-4">
                                                        <span class="poin-badge"><i class="bi bi-gem me-1"></i> <?= $rank['BatasPoin'] ?> XP</span>
                                                    </td>
                                                </tr>
                                                <?php $baris++; endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </main>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        if(window.location.search.includes('status=')) {
            window.history.replaceState(null, null, window.location.pathname);
        }
    </script>
</body>
</html>