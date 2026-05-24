<?php
session_start();
require '../login/koneksi.php';

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'siswa'){
    header("Location: ../login/login.php"); exit;
}

$id_user = $_SESSION['IDUser'] ?? '';
$id_tugas = mysqli_real_escape_string($koneksi, $_GET['id_tugas'] ?? '');

// Ambil data siswa
$query_siswa = mysqli_query($koneksi, "SELECT * FROM siswa WHERE IDUser='$id_user'");
$siswa = mysqli_fetch_assoc($query_siswa);
$id_siswa = $siswa['IDSiswa'] ?? '';

// Jika parameter id_tugas kosong, carikan daftar semua tugas siswa
$mode_detail = !empty($id_tugas);

if($mode_detail) {
    // Ambil detail satu tugas spesifik
    $query_tugas = mysqli_query($koneksi, "
        SELECT t.*, m.NamaMapel 
        FROM tugas t 
        JOIN mapel m ON t.IDMapel = m.IDMapel 
        WHERE t.IDTugas = '$id_tugas'
    ");
    if(mysqli_num_rows($query_tugas) == 0) { header("Location: siswa.php"); exit; }
    $tugas = mysqli_fetch_assoc($query_tugas);

    // Menggunakan nama tabel pengumpulan_tugas
    $query_kumpul = mysqli_query($koneksi, "SELECT * FROM pengumpulan_tugas WHERE IDTugas='$id_tugas' AND IDSiswa='$id_siswa'");
    $kumpul = mysqli_fetch_assoc($query_kumpul);
    $sudah_kumpul = !empty($kumpul);
} else {
    // PENYESUAIAN: kt.IDKumpul diganti menjadi kt.IDPengumpulan sesuai database asli kamu
    $query_daftar_tugas = mysqli_query($koneksi, "
        SELECT t.*, m.NamaMapel, kt.IDPengumpulan, kt.Nilai, kt.Status
        FROM tugas t
        JOIN mapel m ON t.IDMapel = m.IDMapel
        LEFT JOIN pengumpulan_tugas kt ON t.IDTugas = kt.IDTugas AND kt.IDSiswa = '$id_siswa'
        ORDER BY t.Deadline ASC
    ");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mata Pelajaran & Tugas - LMS SMKN 1 Wongsorejo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #dc3545, #9b1c26);
            --card-gradient: linear-gradient(135deg, #1e1e2f, #111119);
        }
        
        /* Memaksa landasan utama menggunakan tinggi penuh layar tanpa bocor margin */
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            background-color: #f4f6f9; 
            color: #333; 
            font-family: 'Segoe UI', system-ui, sans-serif;
        }
        
        /* Navbar Utama Merah Marun Gradasi */
        .navbar-custom { 
            background: var(--primary-gradient) !important; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.1); 
        }

        /* Sidebar Samping - Panjang Kebawah Penuh 1 Layar Sempurna */
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
        
        /* Hero Banner Menggunakan Card Gradient Gelap */
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

        /* Card Box Konten Putih Rapi */
        .mapel-card { 
            border: none !important; 
            border-radius: 16px; 
            background-color: #fff !important; 
            box-shadow: 0 5px 15px rgba(0,0,0,0.04); 
            overflow: hidden; 
        }

        .mapel-card h4, .mapel-card h5, .mapel-card th, .mapel-card td {
            color: #212529 !important;
        }

        /* Status Badge Pengumpulan Tugas */
        .badge-status {
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.4rem 0.8rem;
            border-radius: 50px;
            display: inline-block;
        }
        .status-sudah {
            background-color: rgba(25, 135, 84, 0.1) !important;
            color: #198754 !important;
        }
        .status-belum {
            background-color: rgba(220, 53, 69, 0.1) !important;
            color: #dc3545 !important;
        }

        .form-control:focus {
            border-color: #dc3545;
            box-shadow: 0 0 0 0.25px rgba(220, 53, 69, 0.25);
        }

        .table th {
            border-top: none;
            background-color: #f8f9fa !important;
            font-weight: 600;
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
                            <a class="nav-link" href="kalender.php">
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

                <?php if($mode_detail): ?>
                    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
                        <h1 class="h2 fw-bold text-dark">Detail Lembar Tugas</h1>
                        <a href="tugas.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                            <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
                        </a>
                    </div>

                    <div class="row g-4">
                        <div class="col-lg-8">
                            <div class="card mapel-card p-4 mb-4">
                                <span class="badge bg-danger bg-opacity-10 text-danger rounded mb-3 px-2.5 py-1.5 small fw-bold align-self-start" style="color: #dc3545 !important; background-color: rgba(220,53,69,0.1) !important;">
                                    <?= htmlspecialchars($tugas['NamaMapel']) ?>
                                </span>
                                <h3 class="fw-bold text-dark mb-2"><?= htmlspecialchars($tugas['JudulTugas']) ?></h3>
                                <p class="text-muted small mb-4"><i class="bi bi-clock-history me-1"></i> Batas Pengumpulan: <strong class="text-danger"><?= date('d M Y, H:i', strtotime($tugas['Deadline'])) ?> WIB</strong></p>
                                
                                <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">Deskripsi / Instruksi Tugas:</h5>
                                <p class="text-secondary" style="line-height: 1.6;"><?= nl2br(htmlspecialchars($tugas['Deskripsi'])) ?></p>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="card mapel-card p-4">
                                <h5 class="fw-bold text-dark mb-3"><i class="bi bi-cloud-arrow-up text-danger me-2"></i>Status Pengumpulan</h5>
                                
                                <?php if($sudah_kumpul): ?>
                                    <div class="alert alert-success border-0 rounded-3 d-flex align-items-start gap-2 mb-3">
                                        <i class="bi bi-check-circle-fill mt-0.5"></i>
                                        <div>
                                            <span class="fw-bold d-block small">Tugas Telah Dikumpulkan!</span>
                                            <span class="small opacity-75">Selesai pada: <?= date('d M Y, H:i', strtotime($kumpul['TanggalKumpul'])) ?> WIB</span>
                                        </div>
                                    </div>
                                    <div class="p-3 bg-light rounded-3 mb-3">
                                        <small class="text-muted d-block mb-1">Catatan Berkas Anda:</small>
                                        <a href="../uploads/tugas/<?= $kumpul['FileKumpul'] ?>" target="_blank" class="text-decoration-none fw-semibold text-truncate d-block small"><i class="bi bi-file-earmark-text me-1"></i> Lihat Berkas Terkirim</a>
                                    </div>
                                    <div class="text-center py-2">
                                        <span class="text-muted small d-block mb-1">Nilai Evaluasi:</span>
                                        <h2 class="fw-bold text-success mb-0"><?= $kumpul['Nilai'] ?? 'Belum Dinilai' ?></h2>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-warning border-0 rounded-3 d-flex align-items-start gap-2 mb-4">
                                        <i class="bi bi-exclamation-circle-fill mt-0.5"></i>
                                        <span class="small fw-semibold">Anda belum mengirimkan lembar jawaban berkas tugas ini.</span>
                                    </div>
                                    
                                    <form action="proses_kumpul_tugas.php" method="POST" enctype="multipart/form-data">
                                        <input type="hidden" name="id_tugas" value="<?= $id_tugas ?>">
                                        <div class="mb-3">
                                            <label class="form-label small fw-bold text-dark">Unggah Berkas Jawaban (PDF/DOCX/ZIP):</label>
                                            <input type="file" class="form-control form-control-sm rounded-3" name="file_tugas" required>
                                        </div>
                                        <button type="submit" class="btn btn-danger w-100 rounded-pill fw-semibold py-2" style="background: linear-gradient(135deg, #dc3545, #9b1c26); border: none;">
                                            Kirim Tugas Sekarang
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                <?php else: ?>
                    <div class="card hero-profile-card p-4 mb-4">
                        <div class="position-relative z-1 py-2">
                            <span class="badge bg-danger mb-2 px-3 py-2 rounded-pill small fw-bold" style="background-color: #dc3545 !important;">DAFTAR AKUMULASI</span>
                            <h2 class="fw-bold text-white mb-1">Pusat Informasi Tugas Mandiri</h2>
                            <p class="text-white-50 mb-0"><i class="bi bi-check2-circle me-2"></i>Pantau dan kumpulkan seluruh tugas mata pelajaran siswa secara berkala sebelum batas waktu berakhir.</p>
                        </div>
                    </div>

                    <div class="card mapel-card">
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead>
                                        <tr class="text-dark">
                                            <th class="py-3 px-4" style="border-top-left-radius: 16px;">Mata Pelajaran</th>
                                            <th class="py-3">Topik Judul Tugas</th>
                                            <th class="py-3">Batas Waktu (Deadline)</th>
                                            <th class="py-3">Status Evaluasi</th>
                                            <th class="py-3 px-4 text-end" style="border-top-right-radius: 16px;">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(mysqli_num_rows($query_daftar_tugas) == 0): ?>
                                            <tr>
                                                <td colspan="5" class="text-center py-5 text-muted">Luar biasa! Tidak ada tanggungan tugas aktif untuk saat ini.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php while($row = mysqli_fetch_assoc($query_daftar_tugas)): 
                                                // PENYESUAIAN: Menggunakan IDPengumpulan hasil fetch sinkronisasi database
                                                $is_done = !empty($row['IDPengumpulan']);
                                            ?>
                                                <tr>
                                                    <td class="py-3 px-4 fw-semibold text-dark"><?= htmlspecialchars($row['NamaMapel']) ?></td>
                                                    <td class="text-dark"><?= htmlspecialchars($row['JudulTugas']) ?></td>
                                                    <td class="text-muted small"><?= date('d M Y, H:i', strtotime($row['Deadline'])) ?> WIB</td>
                                                    <td>
                                                        <?php if($is_done): ?>
                                                            <span class="badge-status status-sudah">Selesai (Score: <?= $row['Nilai'] ?? '-' ?>)</span>
                                                        <?php else: ?>
                                                            <span class="badge-status status-belum">Belum Selesai</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="text-end px-4">
                                                        <a href="tugas.php?id_tugas=<?= $row['IDTugas'] ?>" class="btn btn-sm btn-danger text-white rounded-pill px-3" style="background: linear-gradient(135deg, #dc3545, #9b1c26); border: none; font-size: 0.75rem;">
                                                            Detail
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>