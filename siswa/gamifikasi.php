<?php
session_start();
require '../login/koneksi.php';

// Pastikan yang masuk adalah akun dengan role siswa
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'siswa'){
    header("Location: ../login/login.php");
    exit;
}

$id_user = isset($_SESSION['IDUser']) ? $_SESSION['IDUser'] : '';

// 1. Ambil Data Siswa Lengkap
$query_siswa = mysqli_query($koneksi, "SELECT * FROM siswa WHERE IDUser = '$id_user'");
$data_siswa = mysqli_fetch_assoc($query_siswa);
$id_siswa = isset($data_siswa['IDSiswa']) ? $data_siswa['IDSiswa'] : '';

// // 2. Ambil Riwayat Poin (XP) Siswa untuk Log Aktivitas Gamifikasi// GANTI BARIS 19 JADI SEPERTI INI:
// $query_log = mysqli_query($koneksi, "SELECT * FROM log_xp WHERE IDSiswa = '$id_siswa' ORDER BY Tanggal DESC LIMIT 5");

// 3. Ambil Data Peringkat / Leaderboard Global Siswa
// $query_leaderboard = mysqli_query($koneksi, "SELECT * FROM siswa ORDER BY TotalXP DESC LIMIT 10");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Learning System Management SMKN 1 Wongsorejo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #dc3545, #9b1c26);
            --card-gradient: linear-gradient(135deg, #1e1e2f, #111119);
        }
        
        body { 
            background-color: #f4f6f9; 
            color: #333; 
            font-family: 'Segoe UI', system-ui, sans-serif;
        }
        
        /* Navbar Atas - Merah Marun Gradasi */
        .navbar-dark.bg-dark { 
            background: var(--primary-gradient) !important; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.1); 
        }
        
        /* Sidebar Samping Menu Navigasi */
        .sidebar {
            background-color: #fff !important;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            border-radius: 12px;
            padding: 15px 10px;
        }

        .sidebar .nav-link {
            color: #495057 !important;
            font-weight: 500;
            transition: all 0.2s ease;
            border-radius: 8px;
            margin-bottom: 4px;
        }

        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background: rgba(220, 53, 69, 0.1) !important;
            color: #dc3545 !important;
        }
        
        /* Card Status Utama (Total Poin dll) - Tema Gelap Gamifikasi Dashboard */
        .card.bg-primary { 
            background: var(--card-gradient) !important; 
            color: white !important; 
            border: none !important; 
            border-radius: 16px; 
            box-shadow: 0 8px 20px rgba(0,0,0,0.15); 
            position: relative;
            overflow: hidden;
        }

        .card.bg-primary::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 200px;
            height: 200px;
            background: rgba(220, 53, 69, 0.15);
            filter: blur(40px);
            border-radius: 50%;
        }

        /* Progress Bar XP Berkilau Emas */
        .progress {
            background-color: rgba(255, 255, 255, 0.1) !important;
            height: 10px;
            border-radius: 10px;
        }

        .progress-bar {
            background: linear-gradient(90deg, #ffc107, #ff8800) !important;
            box-shadow: 0 0 8px #ffc107;
        }

        /* Card Box Konten Putih (Leaderboard & Log) */
        .card { 
            border: none !important; 
            border-radius: 16px; 
            background-color: #fff !important; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.04); 
        }

        .card-title {
            color: #212529 !important;
            font-weight: 700;
        }

        /* Badge Poin (+XP) */
        .point-badge {
            background: rgba(220, 53, 69, 0.1) !important;
            color: #dc3545 !important;
            font-weight: 700;
            font-size: 0.85rem;
            padding: 0.4rem 0.8rem;
            border-radius: 50px;
        }

        /* Memaksa Teks List Kategori Tetap Berwarna Gelap Kontras */
        .list-group-item h6, .list-group-item .text-dark, .list-group-item fw-bold {
            color: #212529 !important;
        }

        .list-group-item {
            border-color: #f1f1f1 !important;
            background-color: transparent !important;
        }

        /* Navigasi Button */
        .btn-primary {
            background: var(--primary-gradient) !important;
            border: none !important;
        }

        .btn-primary:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="index.html">🎓 LMS SMKN 1 Wongsorejo</a>
            <div class="collapse navbar-collapse justify-content-end" id="topNav">
                <ul class="navbar-nav align-items-center">
                    <li class="nav-item me-3">
                        <a class="nav-link position-relative" href="#">
                            <i class="bi bi-bell-fill fs-5"></i>
                            <span class="position-absolute top-25 start-75 translate-middle p-1 bg-danger border border-light rounded-circle">
                                <span class="visually-hidden">Notifikasi Baru</span>
                            </span>
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="profileDropdown" role="button" data-bs-toggle="dropdown">
                            <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=100&auto=format&fit=crop&q=60" alt="Avatar" class="rounded-circle me-2" style="width: 32px; height: 32px; object-fit: cover;">
                            <span>Siswa</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-menu-item" href="#"><i class="bi bi-person me-2"></i>Profil</a></li>
                            <li><a class="dropdown-menu-item" href="#"><i class="bi bi-gear me-2"></i>Pengaturan</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-menu-item text-danger" href="#"><i class="bi bi-box-arrow-right me-2"></i>Keluar</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid my-4">
        <div class="row">
            
            <nav class="col-md-3 col-lg-2 d-md-block sidebar mb-4">
                <div class="position-sticky">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="siswa.php">
                                <i class="bi bi-house-door me-2"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">
                                <i class="bi bi-book me-2"></i>Mata Pelajaran
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">
                                <i class="bi bi-calendar-event me-2"></i>Jadwal
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="gamifikasi.php">
                                <i class="bi bi-trophy me-2"></i>Gamifikasi
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2 fw-bold text-dark">Pusat Gamifikasi</h1>
                </div>

                <div class="card bg-primary text-white mb-4">
                    <div class="card-body p-4">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h3 class="fw-bold mb-1">Kumpulkan Poin, Jadilah Juara!</h3>
                                <p class="mb-4 text-white-50">Selesaikan tugas tepat waktu dan raih skor kuis tertinggi untuk mendapatkan XP ekstra.</p>
                                <div class="d-flex align-items-center gap-4">
                                    <div>
                                        <small class="d-block text-white-50">TOTAL POIN ANDA</small>
                                        <span class="fs-3 fw-bold"><i class="bi bi-lightning-charge-fill text-warning"></i> 350 XP</span>
                                    </div>
                                    <div>
                                        <small class="d-block text-white-50">PERINGKAT SAAT INI</small>
                                        <span class="fs-3 fw-bold"><i class="bi bi-award-fill text-danger"></i> #4</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                                <div class="mb-2 small d-flex justify-content-between">
                                    <span class="text-white-50">Progress Level 3</span>
                                    <span class="fw-bold text-warning">70%</span>
                                </div>
                                <div class="progress">
                                    <div class="progress-bar" role="progressbar" style="width: 70%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="card p-3">
                            <div class="card-body">
                                <h5 class="card-title mb-4"><i class="bi bi-bar-chart-line-fill text-danger me-2"></i>Papan Peringkat (Top 5)</h5>
                                <div class="list-group list-group-flush">
                                    <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                                        <div class="d-flex align-items-center gap-3">
                                            <span class="fw-bold text-warning fs-5">1</span>
                                            <div class="fw-bold text-dark">Ahmad Fauzi</div>
                                        </div>
                                        <span class="fw-bold text-muted">520 XP</span>
                                    </div>
                                    <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                                        <div class="d-flex align-items-center gap-3">
                                            <span class="fw-bold text-secondary fs-5">2</span>
                                            <div class="fw-bold text-dark">Siti Aminah</div>
                                        </div>
                                        <span class="fw-bold text-muted">480 XP</span>
                                    </div>
                                    <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                                        <div class="d-flex align-items-center gap-3">
                                            <span class="fw-bold text-danger fs-5">3</span>
                                            <div class="fw-bold text-dark">Budi Santoso</div>
                                        </div>
                                        <span class="fw-bold text-muted">410 XP</span>
                                    </div>
                                    <div class="list-group-item d-flex justify-content-between align-items-center py-3 bg-light rounded">
                                        <div class="d-flex align-items-center gap-3">
                                            <span class="fw-bold text-muted fs-5">4</span>
                                            <div class="fw-bold text-danger">Risma Setiyo M (Kamu)</div>
                                        </div>
                                        <span class="fw-bold text-danger">350 XP</span>
                                    </div>
                                    <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                                        <div class="d-flex align-items-center gap-3">
                                            <span class="fw-bold text-muted fs-5">5</span>
                                            <div class="fw-bold text-dark">Dewi Lestari</div>
                                        </div>
                                        <span class="fw-bold text-muted">320 XP</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card p-3">
                            <div class="card-body">
                                <h5 class="card-title mb-4"><i class="bi bi-clock-history text-danger me-2"></i>Aktivitas Riwayat Poin</h5>
                                <div class="list-group list-group-flush">
                                    <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                                        <div>
                                            <h6 class="mb-0 small fw-bold">Bonus Kilat</h6>
                                            <small class="text-muted">Tugas Jurnal Umum (&lt; 24 jam)</small>
                                        </div>
                                        <span class="point-badge">+50 XP</span>
                                    </div>
                                    <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                                        <div>
                                            <h6 class="mb-0 small fw-bold">Nilai Tugas</h6>
                                            <small class="text-muted">Persamaan Dasar Akuntansi</small>
                                        </div>
                                        <span class="point-badge">+80 XP</span>
                                    </div>
                                    <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                                        <div>
                                            <h6 class="mb-0 small fw-bold">Baca Materi</h6>
                                            <small class="text-muted">Konsep Debit Kredit</small>
                                        </div>
                                        <span class="point-badge">+20 XP</span>
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
</body>
</html>