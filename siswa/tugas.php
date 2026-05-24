<?php
session_start();
require '../login/koneksi.php';

// Pastikan yang masuk adalah siswa
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'siswa'){
    header("Location: ../login/login.php"); exit;
}

$id_user = $_SESSION['IDUser'] ?? '';

// 1. Ambil data siswa
$query_siswa = mysqli_query($koneksi, "SELECT * FROM siswa WHERE IDUser='$id_user'");
$siswa = mysqli_fetch_assoc($query_siswa);
$id_siswa = $siswa['IDSiswa'] ?? '';
$nama_lengkap = $siswa['Nama'] ?? $siswa['NamaSiswa'] ?? 'Siswa';
$kelas_siswa = $siswa['Kelas'] ?? '';

// 2. Ambil Semua Daftar Tugas untuk Kelas Ini (Beserta Status Pengumpulannya)
// Menggabungkan tugas dari semua mapel yang sesuai dengan kelas siswa
$query_tugas = mysqli_query($koneksi, "
    SELECT t.*, m.NamaMapel, m.IDMapel, pt.IDPengumpulan, pt.Nilai, pt.TanggalKirim
    FROM tugas t 
    JOIN mapel m ON t.IDMapel = m.IDMapel 
    LEFT JOIN pengumpulan_tugas pt ON t.IDTugas = pt.IDTugas AND pt.IDSiswa = '$id_siswa'
    WHERE m.Kelas LIKE '%\"$kelas_siswa\"%'
    ORDER BY t.Deadline ASC
");

$daftar_tugas = [];
$tugas_pending = 0;
$tugas_selesai = 0;

if($query_tugas) {
    while($row = mysqli_fetch_assoc($query_tugas)) {
        $daftar_tugas[] = $row;
        if(empty($row['IDPengumpulan'])) {
            $tugas_pending++;
        } else {
            $tugas_selesai++;
        }
    }
}

// Fungsi Bantuan Sisa Waktu
function sisaWaktu($deadline_str) {
    $deadline = strtotime($deadline_str);
    $now = time();
    $diff = $deadline - $now;
    if($diff < 0) return "<span class='text-danger fw-bold'><i class='bi bi-exclamation-circle me-1'></i>Terlewat</span>";
    
    $hari = floor($diff / (60 * 60 * 24));
    $jam = floor(($diff - ($hari * 60 * 60 * 24)) / (60 * 60));
    
    if($hari > 0) return "<span class='text-warning fw-bold'><i class='bi bi-clock-history me-1'></i>$hari hari lagi</span>";
    return "<span class='text-danger fw-bold'><i class='bi bi-clock-history me-1'></i>$jam jam lagi</span>";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Tugas - LMS Wongsorejo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    
    <style>
        :root {
            --primary: #4f46e5; --primary-dark: #3730a3; --primary-light: #e0e7ff;
            --secondary: #0ea5e9; --bg-light: #f8fafc; --text-dark: #1e293b;
            --gradient-primary: linear-gradient(135deg, #4f46e5, #0ea5e9);
        }
        body { background-color: var(--bg-light); color: var(--text-dark); font-family: 'Segoe UI', system-ui, sans-serif; overflow-x: hidden; }
        
        /* NAVBAR & SIDEBAR (Gaya Seragam) */
        .navbar-custom { background: var(--gradient-primary) !important; box-shadow: 0 4px 15px rgba(79, 70, 229, 0.2); padding: 10px 0; z-index: 1030;}
        #wrapper { display: flex; width: 100%; align-items: stretch; min-height: calc(100vh - 66px); }
        .sidebar { min-width: 280px; max-width: 280px; background: #fff; border-right: 1px solid #e2e8f0; padding: 25px 15px; position: sticky; top: 66px; height: calc(100vh - 66px); }
        .sidebar .nav-link { color: #64748b; font-weight: 600; padding: 12px 20px; border-radius: 12px; margin-bottom: 8px; transition: 0.2s; }
        .sidebar .nav-link:hover { background: #f1f5f9; color: var(--primary); transform: translateX(5px); }
        .sidebar .nav-link.active { background: var(--primary-light); color: var(--primary); }

        /* KONTEN UTAMA */
        #main-content { width: 100%; padding: 40px; }
        .page-title { font-weight: 800; font-size: 2.2rem; color: var(--text-dark); margin-bottom: 5px; text-transform: uppercase;}
        .page-subtitle { color: #64748b; font-size: 1rem; margin-bottom: 40px; }

        /* KOTAK REKAPITULASI TUGAS */
        .summary-box { background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 25px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); display: flex; gap: 30px; align-items: center; margin-bottom: 30px;}
        .summary-item { text-align: center; flex: 1; }
        .summary-item:not(:last-child) { border-right: 1px solid #e2e8f0; }
        .summary-num { font-size: 2.5rem; font-weight: 800; line-height: 1; margin-bottom: 5px; }

        /* TABEL TUGAS MODERN */
        .task-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.02); overflow: hidden;}
        .table-custom { margin-bottom: 0; }
        .table-custom thead th { background: #f8fafc; color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 1px; padding: 15px 20px; border-bottom: 1px solid #e2e8f0; }
        .table-custom tbody td { padding: 18px 20px; vertical-align: middle; border-bottom: 1px solid #f1f5f9; color: #334155; }
        .table-custom tbody tr:last-child td { border-bottom: none; }
        .table-custom tbody tr:hover { background-color: #f8fafc; }

        /* STATUS BADGES */
        .badge-status { padding: 8px 15px; border-radius: 50px; font-weight: 700; font-size: 0.75rem; letter-spacing: 0.5px; }
        .status-belum { background-color: #fef2f2; color: #ef4444; border: 1px solid #fca5a5; }
        .status-selesai { background-color: #f0fdf4; color: #10b981; border: 1px solid #86efac; }
        
        .btn-kerjakan { background: var(--primary-light); color: var(--primary); font-weight: 700; border-radius: 8px; padding: 8px 20px; transition: 0.3s; }
        .btn-kerjakan:hover { background: var(--primary); color: #fff; box-shadow: 0 4px 10px rgba(79, 70, 229, 0.2); }

        @media (max-width: 992px) { .sidebar { display: none; } #main-content { padding: 20px; } .summary-box { flex-direction: column; gap: 15px; } .summary-item:not(:last-child) { border-right: none; border-bottom: 1px solid #e2e8f0; padding-bottom: 15px; width: 100%;} }
    </style>
</head>
<body>

    <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom sticky-top">
        <div class="container-fluid px-4">
            <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="siswa.php">
                <i class="bi bi-mortarboard-fill fs-4"></i> LMS Wongsorejo
            </a>
            
            <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#mobileMenu">
                <i class="bi bi-list fs-1"></i>
            </button>

            <div class="collapse navbar-collapse justify-content-end" id="mobileMenu">
                <ul class="navbar-nav d-lg-none mb-3 mt-2 border-top pt-3">
                    <li class="nav-item"><a class="nav-link fw-bold text-white" href="siswa.php"><i class="bi bi-house-door me-2"></i>Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link active text-white" href="tugas.php"><i class="bi bi-book me-2"></i>Mata Pelajaran & Tugas</a></li>
                    <li class="nav-item"><a class="nav-link text-white" href="kalender.php"><i class="bi bi-calendar-event me-2"></i>Jadwal Pelajaran</a></li>
                    <li class="nav-item mt-2"><a class="nav-link text-danger fw-bold bg-white rounded text-center" href="../login/logout.php">Keluar Akun</a></li>
                </ul>

                <div class="d-none d-lg-flex align-items-center gap-3">
                    <div class="text-end text-white">
                        <h6 class="mb-0 fw-bold small text-nowrap" style="font-size: 1.1rem"><?= htmlspecialchars($nama_lengkap) ?></h6>
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

    <div id="wrapper">
        <!-- SIDEBAR -->
        <nav class="sidebar d-none d-lg-block">
            <div class="text-muted small fw-bold mb-3 px-3 text-uppercase" style="letter-spacing: 1px;">Menu Utama</div>
            <ul class="nav flex-column">
                <li class="nav-item"><a class="nav-link" href="siswa.php"><i class="bi bi-grid-1x2-fill me-3 fs-5 align-middle"></i> Dashboard</a></li>
                <li class="nav-item"><a class="nav-link active" href="tugas.php"><i class="bi bi-journal-bookmark-fill me-3 fs-5 align-middle"></i> Ruang Kelas & Tugas</a></li>
                <li class="nav-item"><a class="nav-link" href="kalender.php"><i class="bi bi-calendar2-week-fill me-3 fs-5 align-middle"></i> Jadwal & Agenda</a></li>
                <li class="nav-item mt-4 mb-2"><div class="text-muted small fw-bold px-3 text-uppercase" style="letter-spacing: 1px;">Prestasi</div></li>
                <li class="nav-item"><a class="nav-link text-warning" href="gamifikasi.php"><i class="bi bi-trophy-fill me-3 fs-5 align-middle"></i> Gamifikasi</a></li>
            </ul>
        </nav>

        <!-- KONTEN UTAMA -->
        <main id="main-content">
            <h1 class="page-title">Daftar Seluruh Tugas</h1>
            <div class="page-subtitle">Pantau dan kelola semua penugasanmu dari berbagai mata pelajaran di satu tempat.</div>

            <!-- KOTAK REKAPITULASI -->
            <div class="summary-box">
                <div class="summary-item">
                    <div class="summary-num text-primary"><?= count($daftar_tugas) ?></div>
                    <div class="text-muted fw-bold small text-uppercase letter-spacing-1">Total Tugas</div>
                </div>
                <div class="summary-item">
                    <div class="summary-num text-danger"><?= $tugas_pending ?></div>
                    <div class="text-muted fw-bold small text-uppercase letter-spacing-1">Belum Selesai</div>
                </div>
                <div class="summary-item">
                    <div class="summary-num text-success"><?= $tugas_selesai ?></div>
                    <div class="text-muted fw-bold small text-uppercase letter-spacing-1">Telah Diselesaikan</div>
                </div>
            </div>

            <!-- TABEL DAFTAR TUGAS -->
            <div class="task-card">
                <div class="table-responsive">
                    <table class="table table-custom">
                        <thead>
                            <tr>
                                <th>Judul Tugas</th>
                                <th>Mata Pelajaran</th>
                                <th>Batas Waktu</th>
                                <th>Status</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($daftar_tugas)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        <i class="bi bi-emoji-smile fs-1 d-block mb-3 text-secondary opacity-50"></i>
                                        <h5 class="fw-bold text-dark">Hore! Belum Ada Tugas.</h5>
                                        <p>Saat ini tidak ada tugas yang diberikan dari semua mata pelajaranmu.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach($daftar_tugas as $tgs): 
                                    $is_done = !empty($tgs['IDPengumpulan']);
                                ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-dark fs-6"><?= htmlspecialchars($tgs['Judul']) ?></div>
                                            <div class="small text-muted text-truncate" style="max-width: 250px;"><?= htmlspecialchars($tgs['Deskripsi']) ?></div>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-primary border px-2 py-1"><i class="bi bi-journal-text me-1"></i><?= htmlspecialchars($tgs['NamaMapel']) ?></span>
                                        </td>
                                        <td>
                                            <div class="text-dark fw-semibold"><?= date('d M Y, H:i', strtotime($tgs['Deadline'])) ?></div>
                                            <div class="small mt-1"><?= $is_done ? '-' : sisaWaktu($tgs['Deadline']) ?></div>
                                        </td>
                                        <td>
                                            <?php if($is_done): ?>
                                                <span class="badge-status status-selesai"><i class="bi bi-check-circle-fill me-1"></i> Diserahkan<?= isset($tgs['Nilai']) ? " ({$tgs['Nilai']})" : "" ?></span>
                                            <?php else: ?>
                                                <span class="badge-status status-belum"><i class="bi bi-x-circle-fill me-1"></i> Belum Selesai</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <!-- Tombol diarahkan langsung ke halaman mapel spesifik (Single Page Mode) -->
                                            <a href="mapel.php?id_mapel=<?= urlencode($tgs['IDMapel']) ?>" class="btn btn-kerjakan text-decoration-none">
                                                <?= $is_done ? 'Lihat Detail' : 'Kerjakan Sekarang' ?> <i class="bi bi-arrow-right-short ms-1 fs-5 align-middle"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
