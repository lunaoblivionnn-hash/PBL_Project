<?php
session_start();
require '../login/koneksi.php';

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'siswa'){
    header("Location: ../login/login.php");
    exit;
}

// 1. IDENTIFIKASI IDUSER SAMPAI SELESAI TERLEBIH DAHULU
$id_user = isset($_SESSION['IDUser']) ? $_SESSION['IDUser'] : '';

if(empty($id_user)) {
    $ses_username = isset($_SESSION['username']) ? $_SESSION['username'] : (isset($_SESSION['Username']) ? $_SESSION['Username'] : '');
    $cek_user = mysqli_query($koneksi, "SELECT IDUser FROM users WHERE Username = '$ses_username'");
    if($data_user = mysqli_fetch_assoc($cek_user)){
        $id_user = $data_user['IDUser'];
        $_SESSION['IDUser'] = $id_user; 
    }
}

// 2. BARU JALANKAN QUERY CEK WAJIB UBAH PASSWORD SETELAH IDUSER PASTI KETEMU
$query_status_sandi = mysqli_query($koneksi, "SELECT WajibUbahPassword FROM users WHERE IDUser = '$id_user'");
$status_sandi = mysqli_fetch_assoc($query_status_sandi);
$wajib_ubah = isset($status_sandi['WajibUbahPassword']) ? $status_sandi['WajibUbahPassword'] : 0;

// 3. AMBIL DATA SISWA LENGKAP
$query_siswa = mysqli_query($koneksi, "SELECT * FROM siswa WHERE IDUser = '$id_user'");
$data_siswa = mysqli_fetch_assoc($query_siswa);
$id_siswa = isset($data_siswa['IDSiswa']) ? $data_siswa['IDSiswa'] : '';

// 4. AMBIL DAFTAR MATA PELAJARAN YANG DIAJAR OLEH GURU COCOK KELAS (DUMMY/REAL)
$query_mapel = mysqli_query($koneksi, "SELECT m.*, g.NamaGuru FROM mapel m LEFT JOIN guru g ON m.IDGuru = g.IDGuru");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Siswa - LMS SMKN 1 Wongsorejo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #dc3545, #9b1c26);
            --card-gradient: linear-gradient(135deg, #1e1e2f, #111119);
        }
        
        /* Memaksa landasan utama menggunakan tinggi penuh layar */
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            background-color: #f4f6f9; 
            color: #333; 
            font-family: 'Segoe UI', system-ui, sans-serif;
        }
        
        /* Navbar Utama Tema Merah Gradasi (Sama Besar & Warnanya) */
        .navbar-custom { 
            background: var(--primary-gradient) !important; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.1); 
        }

        /* Sidebar Samping - Konsisten Panjang Kebawah 1 Layar Penuh */
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
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .mapel-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
        }

        .mapel-card h4, .mapel-card h5, .mapel-card h6, .mapel-card p {
            color: #212529 !important;
        }
        
        .text-muted-custom {
            color: #6c757d !important;
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
                    <h6 class="mb-0 fw-bold small text-nowrap" style="font-size: 1.25rem"><?= htmlspecialchars($data_siswa['Nama'] ?? 'Siswa') ?></h6>
                    <small class="text-white-50 text-uppercase d-block" style="font-size: 0.65rem; letter-spacing: 0.5px;"><?= htmlspecialchars($data_siswa['Kelas'] ?? '') ?></small>
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
                            <a class="nav-link active" href="siswa.php">
                                <i class="bi bi-house-door me-2"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="tugas.php">
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
                
                <div class="card hero-profile-card p-4 mb-4">
                    <div class="position-relative z-1 py-2">
                        <span class="badge bg-danger mb-2 px-3 py-2 rounded-pill small fw-bold" style="background-color: #dc3545 !important;">DASHBOARD AKADEMIK</span>
                        <h2 class="fw-bold text-white mb-1">Selamat Datang Kembali, <?= htmlspecialchars($data_siswa['Nama'] ?? 'Siswa') ?>!</h2>
                        <p class="text-white-50 mb-0"><i class="bi bi-mortarboard-fill me-2"></i>Tetap semangat belajar, cek berkas materi dan tugas harianmu di bawah ini.</p>
                    </div>
                </div>

                <h4 class="fw-bold text-dark mb-3"><i class="bi bi-journals text-danger me-2"></i>Daftar Mata Pelajaran</h4>

                <div class="row g-4">
                    <?php if(mysqli_num_rows($query_mapel) == 0): ?>
                        <div class="col-12">
                            <div class="card mapel-card p-5 text-center">
                                <p class="text-muted-custom mb-0">Belum ada kelas mata pelajaran yang tersedia untuk saat ini.</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php while($row = mysqli_fetch_assoc($query_mapel)): ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="card mapel-card h-100 d-flex flex-column justify-content-between p-4">
                                    <div>
                                        <div class="d-flex align-items-center justify-content-between mb-3">
                                            <span class="badge bg-danger bg-opacity-10 text-danger rounded px-2.5 py-1.5 small fw-bold" style="color: #dc3545 !important; background-color: rgba(220,53,69,0.1) !important;">
                                                📖 KELAS MAPEL
                                            </span>
                                            <i class="bi bi-arrow-up-right-circle text-muted fs-5"></i>
                                        </div>
                                        <h5 class="fw-bold text-dark mb-1"><?= htmlspecialchars($row['NamaMapel']) ?></h5>
                                        <p class="text-muted-custom small mb-4"><i class="bi bi-person-badge me-1"></i>Guru: <?= htmlspecialchars($row['NamaGuru'] ?? 'Belum Ditentukan') ?></p>
                                    </div>
                                    <a href="mapel.php?id_mapel=<?= $row['IDMapel'] ?>" class="btn btn-sm btn-danger text-white rounded-pill w-100 fw-semibold py-2" style="background: linear-gradient(135deg, #dc3545, #9b1c26); border: none;">
                                        Masuk Kelas
                                    </a>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </div>

            </main>
        </div>
    </div>

    <?php if($wajib_ubah == 1): ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
            title: 'Keamanan Akun!',
            text: 'Anda menggunakan kata sandi bawaan sistem. Diwajibkan untuk memperbarui kata sandi Anda terlebih dahulu demi keamanan data.',
            icon: 'warning',
            allowOutsideClick: false,
            allowEscapeKey: false,
            confirmButtonText: 'Ubah Sandi Sekarang',
            confirmButtonColor: '#dc3545',
            input: 'password',
            inputPlaceholder: 'Ketikkan kata sandi baru Anda...',
            inputAttributes: { autocapitalize: 'off', autocorrect: 'off' },
            inputValidator: (value) => {
                if (!value) { return 'Kata sandi baru tidak boleh kosong!'; }
                if (value.length < 5) { return 'Kata sandi minimal harus terdiri dari 5 karakter!'; }
            },
            showLoaderOnConfirm: true,
            preConfirm: (password) => {
                return fetch('proses_ubah_sandi_paksa.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'password_baru=' + encodeURIComponent(password)
                })
                .then(async response => {
                    const text = await response.text();
                    try {
                        const data = JSON.parse(text);
                        if (data.status === 'sukses') { return data; } 
                        else { 
                            Swal.showValidationMessage('Gagal dari Server: ' + data.pesan); 
                            return false; \r\n                        }
                    } catch (e) {
                        Swal.showValidationMessage('Penyebab Error: ' + text.substring(0, 100)); \r\n                        return false;
                    }
                })
                .catch(error => { 
                    Swal.showValidationMessage('Fetch Gagal: ' + error.message); 
                    return false;
                });
            }
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({ title: 'Akses Dibuka!', text: 'Sandi berhasil diperbarui.', icon: 'success', timer: 1500, showConfirmButton: false })
                .then(() => { location.reload(); });
            }
        });
    });
    </script>
    <?php endif; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>