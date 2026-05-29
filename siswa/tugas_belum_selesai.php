<?php
session_start();
require '../login/koneksi.php';
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'siswa'){ header("Location: ../login/login.php"); exit; }

$id_user = $_SESSION['IDUser'] ?? '';
$query_siswa = mysqli_query($koneksi, "SELECT * FROM siswa WHERE IDUser='$id_user'");
$siswa = mysqli_fetch_assoc($query_siswa);
$id_siswa = $siswa['IDSiswa'] ?? '';
$kelas_siswa = $siswa['Kelas'] ?? '';
$nama_lengkap = $siswa['Nama'] ?? $siswa['NamaSiswa'] ?? 'Siswa';

// HANYA TARIK TUGAS DARI BAB/TOPIK YANG MASIH ADA DI KELAS INI
$query_tugas = mysqli_query($koneksi, "
    SELECT t.*, m.NamaMapel, pt.IDPengumpulan 
    FROM tugas t 
    JOIN mapel m ON t.IDMapel = m.IDMapel 
    JOIN topik_mapel tm ON t.IDTopik = tm.IDTopik
    LEFT JOIN pengumpulan_tugas pt ON t.IDTugas = pt.IDTugas AND pt.IDSiswa = '$id_siswa'
    WHERE tm.Kelas = '$kelas_siswa' AND pt.IDPengumpulan IS NULL
    ORDER BY t.Deadline ASC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tugas Belum Selesai - LMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        :root { --primary: #4f46e5; --primary-light: #e0e7ff; --text-dark: #1e293b; --text-muted: #64748b; }
        body { background-color: #f8fafc; color: var(--text-dark); font-family: 'Segoe UI', system-ui, sans-serif; display: flex; flex-direction: column; min-height: 100vh;}
        .navbar-custom { background: linear-gradient(135deg, #4f46e5, #0ea5e9) !important; box-shadow: 0 4px 15px rgba(79, 70, 229, 0.2); padding: 10px 0; }
        .sidebar { background-color: #fff; box-shadow: 2px 0 20px rgba(0,0,0,0.03); padding: 25px 15px; z-index: 100; min-height: calc(100vh - 70px); }
        .sidebar .nav-link { color: var(--text-muted); font-weight: 600; padding: 12px 20px; border-radius: 12px; margin-bottom: 8px; transition: all 0.3s ease; }
        .sidebar .nav-link:hover { background-color: #f1f5f9; color: var(--primary); transform: translateX(5px); }
        .breadcrumb-modern { font-size: 0.9rem; font-weight: 600; color: var(--text-muted); margin-bottom: 15px; }
        .breadcrumb-modern a { color: var(--primary); text-decoration: none; transition: 0.2s; }
        .breadcrumb-modern a:hover { text-decoration: underline; }
        
        .task-list-item { background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 20px 25px; display: flex; align-items: center; gap: 20px; transition: 0.3s; margin-bottom: 15px; border-left: 5px solid #ef4444; }
        .task-list-item:hover { transform: translateX(5px); box-shadow: 0 10px 20px rgba(0,0,0,0.03); border-color: #fca5a5; }
        .task-icon { width: 50px; height: 50px; border-radius: 12px; background: #fef2f2; color: #ef4444; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; flex-shrink: 0; }
        .task-info { flex-grow: 1; }
        .btn-go { background: #fef2f2; color: #ef4444; border: 1px solid #fca5a5; font-weight: 700; padding: 8px 20px; border-radius: 8px; transition: 0.3s; white-space: nowrap;}
        .btn-go:hover { background: #ef4444; color: #fff; }
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
                    <h6 class="mb-0 fw-bold small text-nowrap" style="font-size: 1.1rem"><?= $nama_lengkap ?></h6>
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
                    </ul>
                </div>
            </nav>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-4 py-4 pb-5">
                
                <div class="breadcrumb-modern">
                    <i class="bi bi-house-door-fill me-1"></i> <a href="siswa.php">Dashboard</a> <i class="bi bi-chevron-right mx-2 text-muted" style="font-size: 0.7rem;"></i> Tugas Belum Selesai
                </div>

                <div class="d-flex align-items-center mb-4">
                    <div class="bg-danger bg-opacity-10 text-danger rounded-3 p-3 me-3"><i class="bi bi-journal-x fs-3"></i></div>
                    <div>
                        <h2 class="fw-bold mb-0 text-dark">Tugas Belum Selesai</h2>
                        <p class="text-muted mb-0">Segera kerjakan sebelum batas waktu berakhir!</p>
                    </div>
                </div>
                
                <div class="mt-4">
                    <?php if(mysqli_num_rows($query_tugas) == 0): ?>
                        <div class="alert alert-success fw-bold py-4 border-0 shadow-sm d-flex align-items-center gap-3">
                            <i class="bi bi-emoji-sunglasses fs-1 text-success"></i> 
                            <div>Luar biasa! Tidak ada tugas yang tertunda. Kamu bisa bersantai sejenak.</div>
                        </div>
                    <?php else: while($tgs = mysqli_fetch_assoc($query_tugas)): ?>
                        <div class="task-list-item">
                            <div class="task-icon"><i class="bi bi-file-earmark-text-fill"></i></div>
                            <div class="task-info">
                                <h5 class="fw-bold text-dark mb-1"><?= htmlspecialchars($tgs['Judul']) ?></h5>
                                <div class="d-flex gap-3 align-items-center text-muted small">
                                    <span><i class="bi bi-journal-bookmark-fill text-primary me-1"></i> <?= htmlspecialchars($tgs['NamaMapel']) ?></span>
                                    <span class="text-danger fw-bold"><i class="bi bi-clock-history me-1"></i> Tenggat: <?= date('d M Y, H:i', strtotime($tgs['Deadline'])) ?></span>
                                </div>
                            </div>
                            <a href="mapel.php?id_mapel=<?= urlencode($tgs['IDMapel']) ?>&open_tugas=<?= $tgs['IDTugas'] ?>" class="btn-go text-decoration-none">
                                Kerjakan <i class="bi bi-arrow-right-short ms-1"></i>
                            </a>
                        </div>
                    <?php endwhile; endif; ?>
                </div>

            </main>
        </div>
    </div>

</body>
</html>