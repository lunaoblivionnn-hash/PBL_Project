<?php
session_start();
require '../login/koneksi.php';

// Pastikan yang masuk adalah akun dengan role siswa
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'siswa'){
    header("Location: ../login/login.php");
    exit;
}

$id_user = $_SESSION['IDUser'] ?? '';

// Ambil data siswa untuk diletakkan pada bagian Navbar Profile
$query_siswa = mysqli_query($koneksi, "SELECT * FROM siswa WHERE IDUser='$id_user'");
$siswa = mysqli_fetch_assoc($query_siswa);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jadwal Pelajaran - LMS SMKN 1 Wongsorejo</title>
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

        /* Sidebar Samping - Menempel Penuh Kebawah Sempurna */
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
        
        /* Hero Banner */
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

        /* Card Konten */
        .jadwal-card { 
            border: none !important; 
            border-radius: 16px; 
            background-color: #fff !important; 
            box-shadow: 0 5px 15px rgba(0,0,0,0.04); 
            overflow: hidden; 
        }

        .day-badge {
            background: var(--primary-gradient);
            color: white;
            font-weight: 600;
            padding: 0.4rem 1rem;
            border-radius: 8px;
            display: inline-block;
            margin-bottom: 1rem;
            box-shadow: 0 4px 10px rgba(220, 53, 69, 0.2);
        }

        .table th {
            border-top: none;
            background-color: #f8f9fa !important;
            font-weight: 600;
            color: #212529;
        }
        
        .table td {
            color: #495057;
        }

        .jam-badge {
            background-color: rgba(33, 37, 41, 0.06);
            color: #212529;
            font-weight: 600;
            padding: 0.25rem 0.6rem;
            border-radius: 6px;
            font-size: 0.8rem;
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
                            <a class="nav-link" href="tugas.php">
                                <i class="bi bi-book me-2"></i>Mata Pelajaran
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="kalender.php">
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
                
                <div class="card hero-profile-card p-4 mb-4">
                    <div class="position-relative z-1 py-2">
                        <span class="badge bg-danger mb-2 px-3 py-2 rounded-pill small fw-bold" style="background-color: #dc3545 !important;">AGENDA AKADEMIK</span>
                        <h2 class="fw-bold text-white mb-1">Jadwal Pelajaran Mingguan</h2>
                        <p class="text-white-50 mb-0"><i class="bi bi-info-circle me-2"></i>Perhatikan alokasi jam masuk mata pelajaran kelas kamu agar tidak terlambat mengikuti materi pembelajaran.</p>
                    </div>
                </div>

                <div class="row g-4">
                    
                    <div class="col-xl-6">
                        <div class="card jadwal-card p-4">
                            <div class="day-badge"><i class="bi bi-calendar-check me-2"></i>Senin</div>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th style="width: 30%">Waktu</th>
                                            <th>Mata Pelajaran</th>
                                            <th>Ruangan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><span class="jam-badge">07:00 - 08:30</span></td>
                                            <td class="fw-semibold text-dark">Upacara & Pembinaan</td>
                                            <td>Lapangan</td>
                                        </tr>
                                        <tr>
                                            <td><span class="jam-badge">08:30 - 11:45</span></td>
                                            <td class="fw-semibold text-dark">Praktikum Akuntansi Dasar</td>
                                            <td>Lab Komputer 1</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-6">
                        <div class="card jadwal-card p-4">
                            <div class="day-badge"><i class="bi bi-calendar-check me-2"></i>Selasa</div>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th style="width: 30%">Waktu</th>
                                            <th>Mata Pelajaran</th>
                                            <th>Ruangan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><span class="jam-badge">07:00 - 09:30</span></td>
                                            <td class="fw-semibold text-dark">Matematika Wajib</td>
                                            <td>Kelas XI-AK 2</td>
                                        </tr>
                                        <tr>
                                            <td><span class="jam-badge">09:45 - 12:00</span></td>
                                            <td class="fw-semibold text-dark">Bahasa Inggris Bisnis</td>
                                            <td>Kelas XI-AK 2</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-6">
                        <div class="card jadwal-card p-4">
                            <div class="day-badge"><i class="bi bi-calendar-check me-2"></i>Rabu</div>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th style="width: 30%">Waktu</th>
                                            <th>Mata Pelajaran</th>
                                            <th>Ruangan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><span class="jam-badge">07:00 - 10:00</span></td>
                                            <td class="fw-semibold text-dark">Administrasi Pajak</td>
                                            <td>Lab Komputer 2</td>
                                        </tr>
                                        <tr>
                                            <td><span class="jam-badge">10:15 - 12:30</span></td>
                                            <td class="fw-semibold text-dark">Pendidikan Pancasila</td>
                                            <td>Kelas XI-AK 2</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-6">
                        <div class="card jadwal-card p-4">
                            <div class="day-badge"><i class="bi bi-calendar-check me-2"></i>Kamis</div>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th style="width: 30%">Waktu</th>
                                            <th>Mata Pelajaran</th>
                                            <th>Ruangan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><span class="jam-badge">07:00 - 09:30</span></td>
                                            <td class="fw-semibold text-dark">Bahasa Indonesia</td>
                                            <td>Kelas XI-AK 2</td>
                                        </tr>
                                        <tr>
                                            <td><span class="jam-badge">09:45 - 12:00</span></td>
                                            <td class="fw-semibold text-dark">Spreedsheet (Pengolah Data)</td>
                                            <td>Lab Komputer 1</td>
                                        </tr>
                                    </tbody>
                                </table>
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