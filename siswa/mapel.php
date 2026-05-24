<?php
session_start();
require '../login/koneksi.php';

// Pastikan yang masuk adalah akun dengan role siswa
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'siswa'){
    header("Location: ../login/login.php"); exit;
}

$id_user = $_SESSION['IDUser'] ?? '';
$id_mapel = mysqli_real_escape_string($koneksi, $_GET['id_mapel'] ?? '');

if(empty($id_mapel)){
    header("Location: siswa.php"); exit;
}

// 1. Ambil data siswa
$query_siswa = mysqli_query($koneksi, "SELECT * FROM siswa WHERE IDUser='$id_user'");
$siswa = mysqli_fetch_assoc($query_siswa);
$id_siswa = $siswa['IDSiswa'] ?? '';

// 2. Ambil detail mapel (Sesuai kolom asli: IDMapel, NamaMapel, IDGuru)
$query_mapel = mysqli_query($koneksi, "
    SELECT m.*, g.NamaGuru 
    FROM mapel m 
    LEFT JOIN guru g ON m.IDGuru = g.IDGuru 
    WHERE m.IDMapel='$id_mapel'
");
if(mysqli_num_rows($query_mapel) == 0){
    header("Location: siswa.php"); exit;
}
$mapel = mysqli_fetch_assoc($query_mapel);

// 3. Ambil daftar materi
$query_materi = mysqli_query($koneksi, "SELECT * FROM materi WHERE IDMapel='$id_mapel'");

// 4. Ambil daftar tugas berkaitan dengan mapel ini
$query_tugas = mysqli_query($koneksi, "SELECT * FROM tugas WHERE IDMapel='$id_mapel' ORDER BY Deadline ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ruang Kelas: <?= htmlspecialchars($mapel['NamaMapel']) ?> - LMS SMKN 1 Wongsorejo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #dc3545, #9b1c26);
            --card-gradient: linear-gradient(135deg, #1e1e2f, #111119);
        }
        
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
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
            height: 100%;
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
        
        .hero-profile-card { 
            background: var(--card-gradient) !important; 
            color: white !important; 
            border: none !important; 
            border-radius: 20px; 
            box-shadow: 0 10px 25px rgba(0,0,0,0.15); 
            overflow: hidden; 
            position: relative; 
        }
        
        .hero-profile-card::before { 
            content: ''; 
            position: absolute; 
            top: -50%; 
            right: -20%; 
            width: 300px; 
            height: 300px; 
            background: rgba(220, 53, 69, 0.15); 
            filter: blur(50px); 
            border-radius: 50%; 
        }

        .mapel-card { 
            border: none !important; 
            border-radius: 16px; 
            background-color: #fff !important; 
            box-shadow: 0 5px 15px rgba(0,0,0,0.04); 
            overflow: hidden; 
        }

        .item-list-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.85rem 1rem;
            border-bottom: 1px solid #f1f3f5;
            transition: background-color 0.2s ease;
        }

        .item-list-row:last-child {
            border-bottom: none;
        }

        .item-list-row:hover {
            background-color: #f8f9fa;
        }

        .text-dark {
            color: #212529 !important;
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom sticky-top py-2">
        <div class="container-fluid px-4">
            <a class="navbar-brand fw-bold d-flex align-items-center" href="siswa.php">
                <span class="fs-5 tracking-wide">🎓 LMS SMKN 1 Wongsorejo</span>
            </a>
            
            <div class="d-flex align-items-center gap-3">
                <div class="text-end text-white d-none d-md-block">
                    <h6 class="mb-0 fw-bold small text-nowrap" style="font-size: 1.25rem"><?= htmlspecialchars($siswa['Nama'] ?? 'Siswa') ?></h6>
                    <small class="text-white-50 text-uppercase d-block" style="font-size: 0.65rem; letter-spacing: 0.5px;"><?= htmlspecialchars($siswa['Kelas'] ?? '') ?></small>
                </div>
                <div class="rounded-circle bg-white p-0.5 shadow-sm border border-2 border-white border-opacity-20">
                    <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=100&auto=format&fit=crop&q=60" alt="Avatar" class="rounded-circle" style="width: 35px; height: 35px; object-fit: cover;">
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-0">
        <div class="row g-0">
            
            <nav class="col-md-3 col-lg-2 d-md-block sidebar">
                <div class="position-sticky">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="siswa.php">
                                <i class="bi bi-house-door me-2"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="tugas.php">
                                <i class="bi bi-book me-2"></i>Mata Pelajaran
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="jadwal.php">
                                <i class="bi bi-calendar-event me-2"></i>Jadwal
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="gamifikasi.php">
                                <i class="bi bi-trophy me-2"></i>Gamifikasi
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">

                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
                    <h1 class="h2 fw-bold text-dark">Ruang Virtual Kelas</h1>
                    <a href="siswa.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                        <i class="bi bi-arrow-left me-1"></i> Kembali ke Beranda
                    </a>
                </div>

                <div class="card hero-profile-card p-4 mb-4">
                    <div class="position-relative z-1 py-2">
                        <span class="badge bg-danger mb-2 px-3 py-2 rounded-pill small fw-bold" style="background-color: #dc3545 !important;">MATA PELAJARAN AKTIF</span>
                        <h2 class="fw-bold text-white mb-1"><?= htmlspecialchars($mapel['NamaMapel']) ?></h2>
                        <p class="text-white-50 mb-0"><i class="bi bi-person-badge me-2"></i>Guru Pengampu: <strong><?= htmlspecialchars($mapel['NamaGuru'] ?? 'Belum Ditentukan') ?></strong></p>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-lg-8">
                        <h5 class="fw-bold mb-3 d-flex align-items-center text-dark">
                            <i class="bi bi-journal-text text-danger me-2"></i> Bahan Ajar & Modul Pembelajaran
                        </h5>
                        <div class="card mapel-card p-2 mb-4">
                            <?php if(mysqli_num_rows($query_materi) == 0): ?>
                                <p class="text-muted small text-center my-4">Materi belum diunggah oleh guru pengampu.</p>
                            <?php else: ?>
                                <?php while($materi = mysqli_fetch_assoc($query_materi)): ?>
                                    <div class="item-list-row">
                                        <div>
                                            <h6 class="mb-0 text-dark small fw-bold"><?= htmlspecialchars($materi['JudulMateri']) ?></h6>
                                            <small class="text-muted" style="font-size:0.75rem;"><i class="bi bi-file-earmark-arrow-down"></i> Modul Belajar Digital</small>
                                        </div>
                                        <a href="../uploads/materi/<?= $materi['FileMateri'] ?>" target="_blank" class="btn btn-sm btn-danger text-white rounded-pill px-3" style="background: linear-gradient(135deg, #dc3545, #9b1c26); border: none; font-size:0.75rem;">
                                            Unduh Modul
                                        </a>
                                    </div>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </div>

                        <h5 class="fw-bold mb-3 d-flex align-items-center text-dark">
                            <i class="bi bi-collection text-danger me-2"></i> Daftar Tugas Terkait
                        </h5>
                        <div class="card mapel-card p-2">
                            <?php if(mysqli_num_rows($query_tugas) == 0): ?>
                                <p class="text-muted small text-center my-4">Tidak ada tugas mandiri khusus pada mata pelajaran ini.</p>
                            <?php else: ?>
                                <?php while($tgs = mysqli_fetch_assoc($query_tugas)): ?>
                                    <div class="item-list-row">
                                        <div>
                                            <h6 class="mb-0 text-dark small fw-bold"><?= htmlspecialchars($tgs['JudulTugas']) ?></h6>
                                            <small class="text-muted" style="font-size:0.75rem;"><i class="bi bi-clock"></i> Batas: <?= date('d M Y, H:i', strtotime($tgs['Deadline'])) ?> WIB</small>
                                        </div>
                                        <a href="tugas.php?id_tugas=<?= $tgs['IDTugas'] ?>" class="btn btn-sm btn-outline-danger rounded-pill px-3" style="font-size:0.75rem;">
                                            Lihat Tugas
                                        </a>
                                    </div>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <h5 class="fw-bold mb-3 d-flex align-items-center text-dark">
                            <i class="bi bi-trophy-fill text-danger me-2"></i> Kuis & Evaluasi
                        </h5>
                        <div class="card mapel-card p-2">
                            <div class="item-list-row">
                                <div>
                                    <h6 class="mb-0 text-dark small fw-bold">Evaluasi Pemahaman Materi 1</h6>
                                    <small class="text-muted" style="font-size:0.75rem;"><i class="bi bi-hourglass-split"></i> Durasi: 15 Menit</small>
                                </div>
                                <button class="btn btn-sm btn-warning text-dark rounded-pill fw-bold px-3" style="font-size:0.75rem;" onclick="alert('Fitur pengerjaan kuis sedang dalam tahap sinkronisasi data database.')">
                                    Mulai
                                </button>
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