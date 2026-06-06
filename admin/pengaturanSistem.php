<?php
session_start();
require '../login/koneksi.php';

if($_SESSION['role'] != 'admin'){
    header("Location: ../login/login.php");
    exit;
}

// 1. CEK & BUAT TABEL OTOMATIS JIKA BELUM ADA
mysqli_query($koneksi, "CREATE TABLE IF NOT EXISTS pengaturan (Kunci VARCHAR(50) PRIMARY KEY, Nilai VARCHAR(50) NOT NULL)");

// 2. AMBIL STATUS MAINTENANCE DARI DATABASE
$query_maint = mysqli_query($koneksi, "SELECT Nilai FROM pengaturan WHERE Kunci = 'maintenance'");
if($query_maint && mysqli_num_rows($query_maint) > 0) {
    $maint_data = mysqli_fetch_assoc($query_maint);
    $maintenance_mode = ($maint_data['Nilai'] == '1') ? true : false;
} else {
    mysqli_query($koneksi, "INSERT IGNORE INTO pengaturan (Kunci, Nilai) VALUES ('maintenance', '0')");
    $maintenance_mode = false;
}

// 3. PROSES SIMPAN DATA KETIKA TOMBOL DITEKAN
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Tangkap status sakelar (jika dicentang bernilai 1, jika tidak bernilai 0)
    $maint_val = isset($_POST['maintenance']) ? '1' : '0';
    
    // Simpan ke database
    mysqli_query($koneksi, "UPDATE pengaturan SET Nilai = '$maint_val' WHERE Kunci = 'maintenance'");
    
    // Refresh halaman dengan status sukses
    header("Location: pengaturanSistem.php?status=sukses");
    exit;
}

// Simulasi data pengaturan lainnya
$app_name = "LMS SMKN 1 Wongsorejo";
$kepsek_nama = "Drs. H. Budi Santoso, M.Pd";
$kepsek_nip = "19650817 199002 1 003";
$tahun_aktif = "2025/2026";
$semester_aktif = "Genap";
$default_password = "password123";
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Sistem - Admin LMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; }
        .header-red { background: #dc3545; color: white; padding: 2rem 0 3.8rem 0; margin: 0; position: relative; }
        .header-red h2 { margin: 0; font-weight: 700; font-size: 1.7rem; letter-spacing: -0.5px; }
        .main-content { margin-top: -2.8rem; position: relative; z-index: 10; }
        .nav-pills .nav-link { color: #495057; font-weight: 600; border-radius: 8px; padding: 12px 20px; margin-bottom: 10px; transition: all 0.3s; }
        .nav-pills .nav-link.active { background-color: #dc3545; color: white; box-shadow: 0 4px 10px rgba(220,53,69,0.2); }
        .nav-pills .nav-link:hover:not(.active) { background-color: #f1f3f5; }
        .settings-card { border: none; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
    </style>
</head>
<body class="bg-light">

    <nav class="navbar navbar-expand-lg navbar-dark bg-danger sticky-top shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="admin.php"> 
                <i class="bi bi-shield-lock-fill me-2"></i> PANEL ADMIN LMS
            </a>
            <div class="collapse navbar-collapse justify-content-end" id="topNav">
                <ul class="navbar-nav align-items-center">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center text-white" href="#" data-bs-toggle="dropdown">
                            <img src="https://ui-avatars.com/api/?name=Administrator&background=fff&color=dc3545" class="rounded-circle me-2 border border-2 border-white" width="30" height="30">
                            Administrator
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow">
                            <li><a class="dropdown-item text-danger" href="../login/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <?php include 'sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-0">
                <div class="header-red">
                    <div class="container">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h2>Pengaturan Sistem</h2>
                                <p class="mb-0 text-white-50 small">Konfigurasi utama aplikasi, tahun ajaran, dan keamanan dasar.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="container main-content pb-5">
                    <div class="card settings-card">
                        <div class="card-body p-4 p-md-5">
                            <div class="row">
                                <div class="col-md-3 border-end pe-md-4 mb-4 mb-md-0">
                                    <div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                                        <button class="nav-link text-start" data-bs-toggle="pill" data-bs-target="#tab-identitas" type="button">
                                            <i class="bi bi-building me-2"></i> Identitas Sekolah
                                        </button>
                                        <button class="nav-link text-start" data-bs-toggle="pill" data-bs-target="#tab-akademik" type="button">
                                            <i class="bi bi-calendar3 me-2"></i> Tahun Akademik
                                        </button>
                                        <button class="nav-link active text-start" data-bs-toggle="pill" data-bs-target="#tab-sistem" type="button">
                                            <i class="bi bi-shield-check me-2"></i> Sistem & Keamanan
                                        </button>
                                    </div>
                                </div>

                                <div class="col-md-9 ps-md-4">
                                    <form action="" method="POST" enctype="multipart/form-data">
                                        <div class="tab-content" id="v-pills-tabContent">
                                            
                                            <div class="tab-pane fade" id="tab-identitas" role="tabpanel">
                                                <h5 class="fw-bold mb-4 text-dark border-bottom pb-2">Profil & Identitas Aplikasi</h5>
                                                <div class="row g-4">
                                                    <div class="col-12">
                                                        <label class="form-label fw-bold small text-muted">Nama Aplikasi / Sekolah</label>
                                                        <input type="text" name="app_name" class="form-control bg-light" value="<?= $app_name ?>">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label fw-bold small text-muted">Nama Kepala Sekolah</label>
                                                        <input type="text" name="kepsek_nama" class="form-control bg-light" value="<?= $kepsek_nama ?>">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label fw-bold small text-muted">NIP Kepala Sekolah</label>
                                                        <input type="text" name="kepsek_nip" class="form-control bg-light" value="<?= $kepsek_nip ?>">
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="tab-pane fade" id="tab-akademik" role="tabpanel">
                                                <h5 class="fw-bold mb-4 text-dark border-bottom pb-2">Konfigurasi Tahun Akademik</h5>
                                                <div class="row g-4">
                                                    <div class="col-md-6">
                                                        <label class="form-label fw-bold small text-muted">Tahun Ajaran Aktif</label>
                                                        <select name="tahun_aktif" class="form-select bg-light">
                                                            <option value="2025/2026" selected>2025/2026</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label fw-bold small text-muted">Semester Aktif</label>
                                                        <select name="semester_aktif" class="form-select bg-light">
                                                            <option value="Genap" selected>Semester Genap</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="tab-pane fade show active" id="tab-sistem" role="tabpanel">
                                                <h5 class="fw-bold mb-4 text-dark border-bottom pb-2">Kontrol Sistem & Keamanan</h5>
                                                <div class="row g-4">
                                                    <div class="col-12 mt-4 pt-3">
                                                        <div class="d-flex justify-content-between align-items-center p-3 <?= $maintenance_mode ? 'bg-danger bg-opacity-10 border border-danger rounded' : 'bg-light border rounded' ?>">
                                                            <div>
                                                                <h6 class="fw-bold mb-1 <?= $maintenance_mode ? 'text-danger' : 'text-dark' ?>">
                                                                    <i class="bi bi-exclamation-triangle-fill me-2"></i> Mode Pemeliharaan (Maintenance)
                                                                </h6>
                                                                <span class="text-muted small">Jika diaktifkan, siswa dan guru tidak dapat login ke dalam sistem.</span>
                                                            </div>
                                                            <div class="form-check form-switch fs-4">
                                                                <input class="form-check-input" type="checkbox" role="switch" name="maintenance" value="1" <?= $maintenance_mode ? 'checked' : '' ?> style="cursor: pointer;">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                        </div>

                                        <div class="mt-5 pt-3 border-top d-flex justify-content-end">
                                            <button type="submit" class="btn btn-primary fw-bold px-5 shadow-sm">
                                                <i class="bi bi-save2 me-2"></i> Simpan Perubahan
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <?php if(isset($_GET['status']) && $_GET['status'] == 'sukses'): ?>
    <script>
        Swal.fire({
            title: 'Tersimpan!',
            text: 'Pengaturan sistem berhasil diperbarui.',
            icon: 'success',
            timer: 2500,
            showConfirmButton: false
        });
        window.history.replaceState(null, null, window.location.pathname);
    </script>
    <?php endif; ?>
</body>
</html>