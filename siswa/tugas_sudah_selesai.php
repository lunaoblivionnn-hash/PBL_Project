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
    SELECT t.*, m.NamaMapel, pt.TanggalKirim, pt.Nilai 
    FROM tugas t 
    JOIN mapel m ON t.IDMapel = m.IDMapel 
    JOIN topik_mapel tm ON t.IDTopik = tm.IDTopik
    JOIN pengumpulan_tugas pt ON t.IDTugas = pt.IDTugas AND pt.IDSiswa = '$id_siswa'
    WHERE tm.Kelas = '$kelas_siswa'
    ORDER BY pt.TanggalKirim DESC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tugas Sudah Selesai - LMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        :root { 
            --primary: #1e1b4b;          
            --primary-dark: #100f28;     
            --primary-light: #e0e7ff;    
            --secondary: #3b82f6;        
            --gradient-primary: linear-gradient(135deg, #1e1b4b, #312e81);
            --gradient-card: linear-gradient(135deg, #312e81, #1e1b4b);
            --text-dark: #1e293b; 
            --text-muted: #64748b; 
        }
        body { background-color: #f8fafc; color: var(--text-dark); font-family: 'Segoe UI', system-ui, sans-serif; display: flex; flex-direction: column; min-height: 100vh;}
        
        .navbar-custom { background: var(--gradient-primary) !important; box-shadow: 0 4px 20px rgba(30, 27, 75, 0.3); padding: 12px 0; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar { background-color: #fff; box-shadow: 4px 0 20px rgba(0,0,0,0.03); padding: 25px 15px; z-index: 100; min-height: calc(100vh - 70px); }
        .sidebar .nav-link { color: var(--text-muted); font-weight: 600; padding: 12px 20px; border-radius: 12px; margin-bottom: 8px; transition: all 0.3s ease; }
        .sidebar .nav-link:hover { background-color: #f8fafc; color: var(--secondary); transform: translateX(5px); }
        .sidebar .nav-link.active { background-color: var(--primary-light); color: var(--primary); }

        .breadcrumb-modern { font-size: 0.9rem; font-weight: 600; color: var(--text-muted); margin-bottom: 15px; }
        .breadcrumb-modern a { color: var(--secondary); text-decoration: none; transition: 0.2s; }
        .breadcrumb-modern a:hover { text-decoration: underline; }
        
        .task-list-item { background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 20px 25px; display: flex; align-items: center; gap: 20px; transition: 0.3s; margin-bottom: 15px; border-left: 5px solid #10b981; }
        .task-list-item:hover { transform: translateX(5px); box-shadow: 0 10px 20px rgba(0,0,0,0.03); border-color: #86efac; }
        .task-icon { width: 50px; height: 50px; border-radius: 12px; background: #f0fdf4; color: #10b981; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; flex-shrink: 0; }
        .task-info { flex-grow: 1; }
        .btn-go { background: #f0fdf4; color: #10b981; border: 1px solid #86efac; font-weight: 700; padding: 8px 20px; border-radius: 8px; transition: 0.3s; white-space: nowrap; text-decoration: none;}
        .btn-go:hover { background: #10b981; color: #fff; }
    </style>
</head>
<body>

    <?php include 'komponen_navbar.php'; ?>

    <div class="container-fluid px-0 flex-grow-1">
        <div class="row g-0">
            <?php include 'komponen_sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-4 py-4 pb-5">
                
                <div class="breadcrumb-modern">
                    <i class="bi bi-house-door-fill me-1"></i> <a href="siswa.php">Dashboard</a> <i class="bi bi-chevron-right mx-2 text-muted" style="font-size: 0.7rem;"></i> Tugas Sudah Selesai
                </div>

                <div class="d-flex align-items-center mb-4">
                    <div class="bg-success bg-opacity-10 text-success rounded-3 p-3 me-3"><i class="bi bi-journal-check fs-3"></i></div>
                    <div>
                        <h2 class="fw-bold mb-0 text-dark">Tugas Sudah Selesai</h2>
                        <p class="text-muted mb-0">Riwayat semua penugasan yang telah kamu kumpulkan.</p>
                    </div>
                </div>
                
                <div class="mt-4">
                    <?php if(mysqli_num_rows($query_tugas) == 0): ?>
                        <div class="alert alert-secondary text-center py-4 border-0 shadow-sm d-flex justify-content-center align-items-center gap-3">
                            <i class="bi bi-inbox fs-2 text-muted"></i> 
                            <div>Belum ada tugas yang kamu kumpulkan di sistem ini.</div>
                        </div>
                    <?php else: while($tgs = mysqli_fetch_assoc($query_tugas)): ?>
                        <div class="task-list-item">
                            <div class="task-icon"><i class="bi bi-check-circle-fill"></i></div>
                            <div class="task-info">
                                <h5 class="fw-bold text-dark mb-1"><?= htmlspecialchars($tgs['Judul']) ?></h5>
                                <div class="d-flex gap-3 align-items-center text-muted small mt-2">
                                    <span><i class="bi bi-journal-bookmark-fill text-secondary me-1"></i> <?= htmlspecialchars($tgs['NamaMapel']) ?></span>
                                    <span><i class="bi bi-calendar-check me-1"></i> Dikumpul: <?= date('d M, H:i', strtotime($tgs['TanggalKirim'])) ?></span>
                                    <span class="badge bg-warning text-dark"><i class="bi bi-star-fill me-1"></i> Nilai: <?= $tgs['Nilai'] ?? 'Belum Dinilai' ?></span>
                                </div>
                            </div>
                            <a href="mapel.php?id_mapel=<?= urlencode($tgs['IDMapel']) ?>&open_tugas=<?= $tgs['IDTugas'] ?>" class="btn-go">
                                Lihat Detail <i class="bi bi-arrow-right-short ms-1"></i>
                            </a>
                        </div>
                    <?php endwhile; endif; ?>
                </div>

            </main>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>