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

// 2. Ambil detail mapel
$query_mapel = mysqli_query($koneksi, "SELECT m.*, g.NamaGuru FROM mapel m LEFT JOIN guru g ON m.IDGuru = g.IDGuru WHERE m.IDMapel='$id_mapel'");
if(mysqli_num_rows($query_mapel) == 0){
    header("Location: siswa.php"); exit;
}
$mapel = mysqli_fetch_assoc($query_mapel);

// 3. Ambil daftar materi
$query_materi = mysqli_query($koneksi, "SELECT * FROM materi WHERE IDMapel='$id_mapel'");

// 4. Ambil daftar tugas
$query_tugas = mysqli_query($koneksi, "SELECT * FROM tugas WHERE IDMapel='$id_mapel'");

// 5. Ambil daftar kuis
// $query_quiz = mysqli_query($koneksi, "SELECT * FROM quiz WHERE IDMapel='$id_mapel'");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Kelas - LMS SMKN 1 Wongsorejo</title>
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
            transition: all 0.3s ease; 
            font-family: 'Segoe UI', system-ui, sans-serif;
        }
        
        /* Navbar Utama Tema Merah Gradasi */
        .navbar-custom { 
            background: var(--primary-gradient); 
            box-shadow: 0 4px 12px rgba(0,0,0,0.1); 
        }
        
        /* Hero Header Menggunakan Card Gradient Gelap Dashboard */
        .hero-profile-card { 
            background: var(--card-gradient); 
            color: white; 
            border: none; 
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

        /* Card List Box Putih Bersih Rapi */
        .mapel-card { 
            border: none; 
            border-radius: 16px; 
            background-color: #fff !important; 
            box-shadow: 0 5px 15px rgba(0,0,0,0.04); 
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1); 
            overflow: hidden; 
        }
        
        .mapel-card:hover { 
            transform: translateY(-4px); 
            box-shadow: 0 12px 24px rgba(220,53,69,0.12); 
        }

        /* Memaksa teks di dalam kartu agar tetap berwarna gelap saat light mode */
        .mapel-card h6, 
        .mapel-card .text-dark, 
        .mapel-card a.text-primary {
            color: #212529 !important;
        }

        /* List Row Item di dalam Card */
        .item-list-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid #efefef;
        }

        .item-list-row:last-child {
            border-bottom: none;
        }

        .empty-state-box { 
            background: #fff; 
            border-radius: 20px; 
            padding: 3rem 2rem; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.03); 
        }

        /* Dark Mode Compatibility Support */
        [data-bs-theme="dark"] body { background-color: #121212; color: #e0e0e0; }
        [data-bs-theme="dark"] .mapel-card { background-color: #1e1e1e !important; box-shadow: 0 5px 15px rgba(0,0,0,0.3); }
        [data-bs-theme="dark"] .mapel-card h6, 
        [data-bs-theme="dark"] .mapel-card .text-dark { color: #e0e0e0 !important; }
        [data-bs-theme="dark"] .mapel-card a.text-primary { color: #0d6efd !important; }
        [data-bs-theme="dark"] .empty-state-box { background-color: #1e1e1e; }
        [data-bs-theme="dark"] .item-list-row { border-color: #2d2d2d; }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom sticky-top py-2">
        <div class="container px-4">
            <a class="navbar-brand fw-bold d-flex align-items-center" href="siswa.php">
                <span class="fs-5 tracking-wide">🎓 LMS SMKN 1 Wongsorejo</span>
            </a>
            
            <div class="d-flex align-items-center">
                <div class="text-end text-white">
                    <h6 class="mb-0 fw-bold small text-nowrap"><?= htmlspecialchars($siswa['NamaSiswa'] ?? 'Siswa') ?></h6>
                    <small class="text-white-50 text-uppercase d-block" style="font-size: 0.65rem; letter-spacing: 0.5px;"><?= htmlspecialchars($siswa['Kelas'] ?? '') ?></small>
                </div>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        
        <div class="mb-3">
            <a href="siswa.php" class="btn btn-sm btn-white bg-white border text-dark shadow-sm rounded-pill px-3 fw-semibold">
                <i class="bi bi-arrow-left me-1 text-danger"></i> Kembali ke Dashboard
            </a>
        </div>
        
        <div class="card hero-profile-card p-4 mb-5">
            <div class="position-relative z-1 py-2">
                <span class="badge bg-danger mb-2 px-3 py-2 rounded-pill small fw-bold" style="background-color: #dc3545 !important;">RUANG KELAS AKTIF</span>
                <h2 class="fw-bold text-white mb-1"><?= htmlspecialchars($mapel['NamaMapel']) ?></h2>
                <p class="text-white-50 mb-0"><i class="bi bi-person-badge me-2"></i>Guru Pengampu: <strong><?= htmlspecialchars($mapel['NamaGuru'] ?? 'Belum Ditentukan') ?></strong></p>
            </div>
        </div>

        <div class="row g-4">
            
            <div class="col-lg-6">
                <h5 class="fw-bold mb-3 d-flex align-items-center text-dark">
                    <i class="bi bi-file-earmark-text-fill text-danger me-2"></i> Materi Pembelajaran
                </h5>
                
                <div class="card mapel-card p-2">
                    <?php if(mysqli_num_rows($query_materi) == 0): ?>
                        <p class="text-muted small text-center my-4">Belum ada file materi dibagikan.</p>
                    <?php else: ?>
                        <?php while($materi = mysqli_fetch_assoc($query_materi)): ?>
                            <div class="item-list-row">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="p-2 bg-danger bg-opacity-10 text-danger rounded fs-4">
                                        <i class="bi bi-file-earmark-pdf"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0 text-dark small fw-bold"><?= htmlspecialchars($materi['JudulMateri']) ?></h6>
                                        <small class="text-muted" style="font-size:0.75rem;">Format: PDF Document</small>
                                    </div>
                                </div>
                                <a href="../uploads/materi/<?= htmlspecialchars($materi['FileMateri']) ?>" target="_blank" class="btn btn-sm btn-outline-danger rounded-pill px-3" style="font-size:0.75rem;">
                                    <i class="bi bi-download"></i> Unduh
                                </a>
                            </div>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-lg-6">
                
                <h5 class="fw-bold mb-3 d-flex align-items-center text-dark">
                    <i class="bi bi-journal-check text-danger me-2"></i> Tugas Pembelajaran
                </h5>
                <div class="card mapel-card p-2 mb-4">
                    <?php if(mysqli_num_rows($query_tugas) == 0): ?>
                        <p class="text-muted small text-center my-4">Tidak ada tugas terdaftar.</p>
                    <?php else: ?>
                        <?php while($tugas = mysqli_fetch_assoc($query_tugas)): ?>
                            <div class="item-list-row">
                                <div>
                                    <h6 class="mb-0 text-dark small fw-bold"><?= htmlspecialchars($tugas['JudulTugas']) ?></h6>
                                    <small class="text-danger" style="font-size:0.75rem;"><i class="bi bi-clock"></i> Batas: <?= date('d M, H:i', strtotime($tugas['Deadline'])) ?></small>
                                </div>
                                <a href="kerjakan_tugas.php?id_tugas=<?= $tugas['IDTugas'] ?>" class="btn btn-sm btn-danger text-white rounded-pill fw-semibold px-3" style="background: linear-gradient(135deg, #dc3545, #9b1c26); border: none; font-size:0.75rem;">
                                    Buka Tugas
                                </a>
                            </div>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </div>

                <h5 class="fw-bold mb-3 d-flex align-items-center text-dark">
                    <i class="bi bi-trophy-fill text-danger me-2"></i> Kuis & Evaluasi
                </h5>
                <div class="card mapel-card p-2">
                    <?php if(mysqli_num_rows($query_quiz) == 0): ?>
                        <p class="text-muted small text-center my-4">Tidak ada kuis aktif saat ini.</p>
                    <?php else: ?>
                        <?php while($quiz = mysqli_fetch_assoc($query_quiz)): ?>
                            <div class="item-list-row">
                                <div>
                                    <h6 class="mb-0 text-dark small fw-bold"><?= htmlspecialchars($quiz['JudulQuiz']) ?></h6>
                                    <small class="text-muted" style="font-size:0.75rem;"><i class="bi bi-hourglass-split"></i> Durasi: <?= $quiz['Durasi'] ?> Menit</small>
                                </div>
                                <a href="kerjakan_quiz.php?id_quiz=<?= $quiz['IDQuiz'] ?>" class="btn btn-sm btn-warning text-dark rounded-pill fw-bold px-3" style="font-size:0.75rem;">
                                    Mulai Kuis
                                </a>
                            </div>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>