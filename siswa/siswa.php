<?php
session_start();
require '../login/koneksi.php';

// Pastikan yang masuk adalah akun dengan role siswa
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

// Deteksi Mode Maintenance Global: Tendang otomatis jika server dikunci oleh admin
$cek_maint_global = mysqli_query($koneksi, "SELECT Nilai FROM pengaturan WHERE Kunci = 'maintenance'");
$maint_global = mysqli_fetch_assoc($cek_maint_global);
if(isset($maint_global['Nilai']) && $maint_global['Nilai'] == '1') {
    session_destroy();
    header("Location: ../login/login.php");
    exit;
}

// 3. AMBIL DATA PROFIL SISWA
$query_siswa = mysqli_query($koneksi, "SELECT * FROM siswa WHERE IDUser = '$id_user'");
$siswa = mysqli_fetch_assoc($query_siswa);

if(!$siswa) {
    session_destroy();
    header("Location: ../login/login.php?status=akun_dihapus");
    exit;
}

$id_siswa     = isset($siswa['IDSiswa']) ? $siswa['IDSiswa'] : '';
$nama_siswa   = isset($siswa['NamaSiswa']) ? $siswa['NamaSiswa'] : 'Siswa Anonim';
$kelas_siswa  = isset($siswa['Kelas']) ? $siswa['Kelas'] : 'KOSONG';

// 4. AMBIL DATA GAMIFIKASI SISWA REAL-TIME
$query_gami = mysqli_query($koneksi, "SELECT g.*, l.LevelAngka, l.Gelar, l.BatasPoin FROM gamifikasi g LEFT JOIN master_level l ON g.IDLevel = l.IDLevel WHERE g.IDSiswa = '$id_siswa'");
$gami = mysqli_fetch_assoc($query_gami);

$total_point    = isset($gami['TotalPoint']) ? $gami['TotalPoint'] : 0;
$level_sekarang = isset($gami['LevelAngka']) ? $gami['LevelAngka'] : 1;
$gelar_aktif    = isset($gami['Gelar']) ? $gami['Gelar'] : 'Beginner Accountant';
$batas_curr     = isset($gami['BatasPoin']) ? $gami['BatasPoin'] : 0;

$level_next = $level_sekarang + 1;
$query_next = mysqli_query($koneksi, "SELECT BatasPoin FROM master_level WHERE LevelAngka = '$level_next'");
$next_level = mysqli_fetch_assoc($query_next);
$batas_next = $next_level ? $next_level['BatasPoin'] : 2000;

$rentang_xp = $batas_next - $batas_curr;
$xp_berjalan = $total_point - $batas_curr;
$persen_xp   = ($rentang_xp > 0) ? min(100, max(0, ($xp_berjalan / $rentang_xp) * 100)) : 100;

$badges = ['🥉', '🥈', '🥈', '🥇', '🥇', '🏅', '🏅', '💎', '💎', '🌟', '🌟', '🌟', '👑'];
$emoji_badge = $badges[($level_sekarang - 1) % count($badges)];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard LMS - Siswa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #dc3545, #9b1c26);
            --card-gradient: linear-gradient(135deg, #1e1e2f, #111119);
        }
        body { background-color: #f4f6f9; color: #333; transition: all 0.3s ease; }
        .navbar-custom { background: var(--primary-gradient); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .hero-profile-card { background: var(--card-gradient); color: white; border: none; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.15); overflow: hidden; position: relative; }
        .hero-profile-card::before { content: ''; position: absolute; top: -50%; right: -20%; width: 300px; height: 300px; background: rgba(220, 53, 69, 0.15); filter: blur(50px); border-radius: 50%; }
        .progress-xp { height: 12px; border-radius: 10px; background-color: rgba(255,255,255,0.1); overflow: visible; }
        .progress-bar-glow { background: linear-gradient(90deg, #ffc107, #ff8800); box-shadow: 0 0 12px #ffc107; border-radius: 10px; position: relative; transition: width 0.5s ease; }
        .mapel-card { border: none; border-radius: 16px; background-color: #fff; box-shadow: 0 5px 15px rgba(0,0,0,0.04); transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1); overflow: hidden; }
        .mapel-card:hover { transform: translateY(-8px); box-shadow: 0 15px 30px rgba(220,53,69,0.15); }
        .mapel-img-container { height: 140px; background-color: #e9ecef; position: relative; overflow: hidden; }
        .mapel-img { width: 100%; height: 100%; object-fit: cover; }
        .empty-state-box { background: #fff; border-radius: 20px; padding: 4rem 2rem; box-shadow: 0 10px 30px rgba(0,0,0,0.03); }
        [data-bs-theme="dark"] body { background-color: #121212; color: #e0e0e0; }
        [data-bs-theme="dark"] .mapel-card { background-color: #1e1e1e; box-shadow: 0 5px 15px rgba(0,0,0,0.3); }
        [data-bs-theme="dark"] .empty-state-box { background-color: #1e1e1e; }
        [data-bs-theme="dark"] .card-light-box { background-color: #2b2b2b !important; color: #fff !important; }
        [data-bs-theme="dark"] footer { background-color: #1a1a1a !important; border-top: 1px solid #2d2d2d; }
    </style>
    <script>
        document.documentElement.setAttribute('data-bs-theme', localStorage.getItem('theme') || 'light');
    </script>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom sticky-top">
        <div class="container py-1">
            <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="siswa.php">
                <i class="bi bi-mortarboard-fill fs-3"></i> LMS SMKN 1 WONGSOREJO
            </a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#topNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="topNav">
                <ul class="navbar-nav align-items-center gap-2 mt-2 mt-lg-0">
                    <li class="nav-item"><a class="nav-link active fw-semibold px-3" href="siswa.php"><i class="bi bi-house-door me-1"></i>Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link px-3" href="#"><i class="bi bi-collection-play me-1"></i>Tugas Saya</a></li>
                    <li class="nav-item"><a class="nav-link px-3" href="#"><i class="bi bi-trophy me-1"></i>Leaderboard</a></li>
                    <li class="nav-item ms-lg-2">
                        <button class="btn btn-link text-white p-2" id="themeToggle" onclick="switchTheme()">
                            <i class="bi bi-sun-fill fs-5" id="themeIcon"></i>
                        </button>
                    </li>
                    <li class="nav-item dropdown ms-lg-2">
                        <a class="nav-link dropdown-toggle bg-white bg-opacity-10 rounded-pill px-3 text-white d-flex align-items-center gap-2" href="#" data-bs-toggle="dropdown">
                            <img src="https://ui-avatars.com/api/?name=<?= urlencode($nama_siswa) ?>&background=fff&color=dc3545" class="rounded-circle" width="26" height="26">
                            <?= htmlspecialchars(explode(' ', trim($nama_siswa))[0]); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2">
                            <li><a class="dropdown-item" href="#"><i class="bi bi-person-badge me-2"></i>Profil Saya</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="../login/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Keluar Aplikasi</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row mb-5">
            <div class="col-12">
                <div class="card hero-profile-card p-4 p-md-5">
                    <div class="row align-items-center g-4">
                        <div class="col-auto">
                            <div class="position-relative">
                                <img src="https://ui-avatars.com/api/?name=<?= urlencode($nama_siswa) ?>&size=128&background=dc3545&color=fff" class="rounded-4 border border-4 border-opacity-20 border-white shadow shadow-lg" width="100" height="100">
                                <span class="position-absolute bottom-0 end-0 bg-warning text-dark fw-bold rounded-circle px-2 py-1 small shadow border border-2 border-dark" style="margin-bottom: -5px; margin-right: -5px;">
                                    LV <?= $level_sekarang ?>
                                </span>
                            </div>
                        </div>
                        <div class="col-md flex-grow-1">
                            <span class="badge bg-danger px-3 py-2 rounded-pill fw-bold mb-2 small"><i class="bi bi-shield-fill me-1"></i> Jurusan Akuntansi (AKL)</span>
                            <h2 class="fw-bold mb-1 m-0"><?= $nama_siswa ?></h2>
                            <p class="text-white-50 m-0 d-flex align-items-center gap-2 small">
                                <span class="fs-5"><?= $emoji_badge ?></span> Gelar Aktif: <strong class="text-warning"><?= $gelar_aktif ?></strong>
                            </p>
                        </div>
                        <div class="col-xl-4 col-lg-5 col-md-12">
                            <div class="card-light-box p-3 rounded-4 style-box" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08);">
                                <div class="d-flex justify-content-between text-white-50 small mb-2">
                                    <span>Progress Pengalaman (XP)</span>
                                    <span class="fw-bold text-white"><i class="bi bi-gem text-warning me-1"></i><?= $total_point ?> / <?= $batas_next ?> XP</span>
                                </div>
                                <div class="progress progress-xp">
                                    <div class="progress-bar progress-bar-glow" style="width: <?= $persen_xp ?>%"></div>
                                </div>
                                <div class="text-end text-warning small mt-2" style="font-size: 0.75rem;">
                                    <?= ($batas_next - $total_point) ?> XP lagi menuju Level <?= $level_next ?>!
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <?php if(empty($kelas_siswa) || $kelas_siswa == 'KOSONG'): ?>
                    <div class="empty-state-box text-center">
                        <div class="text-warning mb-4" style="font-size: 4.5rem;">
                            <i class="bi bi-exclamation-octagon-fill"></i>
                        </div>
                        <h4 class="fw-bold text-dark mb-2">Kamu Belum Terdaftar di Kelas</h4>
                        <p class="text-muted mx-auto mb-4 small" style="max-width: 520px; line-height: 1.6;">
                            Waduh, akun kamu saat ini belum dikelompokkan ke ruang kelas manapun oleh Administrator. Silakan hubungi bagian Admin Kurikulum sekolah untuk mendaftarkan akunmu agar seluruh materi dan tugas akuntansi bisa diakses di sini.
                        </p>
                        <button class="btn btn-danger fw-bold rounded-pill px-4 shadow-sm btn-sm" onclick="location.reload();">
                            <i class="bi bi-arrow-clockwise me-1"></i> Refresh Halaman
                        </button>
                    </div>
                <?php else: ?>
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h4 class="fw-bold m-0 text-dark"><i class="bi bi-grid-3x3-gap-fill text-danger me-2"></i> Ruang Kelas: <?= $kelas_siswa ?></h4>
                            <p class="text-muted small mb-0 m-0">Berikut adalah daftar kurikulum mata pelajaran akuntansi aktif kamu.</p>
                        </div>
                        <span class="badge bg-secondary bg-opacity-10 text-secondary border px-3 py-2 rounded-pill fw-semibold fs-6">
                            <i class="bi bi-door-open-fill me-1"></i> Kelas Aktif
                        </span>
                    </div>

                    <div class="row g-4">
                        <?php 
                        $query_mapel = mysqli_query($koneksi, "SELECT m.*, g.NamaGuru FROM mapel m LEFT JOIN guru g ON m.IDGuru = g.IDGuru WHERE m.Kelas LIKE '%\"$kelas_siswa\"%' ORDER BY m.NamaMapel ASC");
                        if(mysqli_num_rows($query_mapel) == 0):
                        ?>
                            <div class="col-12">
                                <div class="alert alert-info border-0 shadow-sm p-4 rounded-3">
                                    <i class="bi bi-info-circle-fill fs-4 me-2 align-middle"></i>
                                    Kelas <strong><?= $kelas_siswa ?></strong> sudah aktif, namun Admin belum menginput mata pelajaran untuk kelas ini.
                                </div>
                            </div>
                        <?php 
                        else:
                            while($mapel = mysqli_fetch_assoc($query_mapel)):
                                $cover_img = !empty($mapel['Gambar']) ? '../assets/img/cover/' . $mapel['Gambar'] : 'https://images.unsplash.com/photo-1554224155-8d04cb21cd6c?auto=format&fit=crop&q=80&w=600';
                                $nama_pengampu = !empty($mapel['NamaGuru']) ? $mapel['NamaGuru'] : 'Belum Ditentukan';
                        ?>
                            <div class="col-xl-4 col-md-6">
                                <div class="card mapel-card h-100 shadow-sm">
                                    <div class="mapel-img-container">
                                        <img src="<?= $cover_img ?>" class="mapel-img" alt="Cover Mapel">
                                        <span class="position-absolute top-3 start-3 badge bg-dark bg-opacity-70 backdrop-blur rounded-pill px-3 py-2 small">
                                            <i class="bi bi-bookmark-fill text-warning me-1"></i> AKL
                                        </span>
                                    </div>
                                    <div class="card-body p-4 d-flex flex-column">
                                        <h5 class="fw-bold text-dark mb-1 text-truncate" title="<?= $mapel['NamaMapel'] ?>">
                                            <?= $mapel['NamaMapel'] ?>
                                        </h5>
                                        <p class="text-muted small mb-1"><i class="bi bi-person-badge-fill me-1"></i> Guru: <?= htmlspecialchars($nama_pengampu) ?></p>
                                        <p class="text-muted small mb-3">Kode Kelas: <?= $mapel['Kelas'] ?> • Kurikulum Merdeka</p>
                                        <p class="text-secondary small mb-4 flex-grow-1" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; font-size: 0.85rem; line-height: 1.5; min-height: 2.6rem;">
                                            <?= !empty($mapel['Deskripsi']) ? htmlspecialchars($mapel['Deskripsi']) : 'Tidak ada deskripsi singkat mengenai mata pelajaran ini.' ?>
                                        </p>
                                        <div class="border-top pt-3 d-flex justify-content-between align-items-center">
                                            <span class="small text-muted fw-semibold"><i class="bi bi-file-earmark-text me-1"></i> Materi & Tugas</span>
                                            <a href="#" class="btn btn-danger btn-sm rounded-pill px-4 fw-bold shadow-sm">
                                                Masuk Kelas <i class="bi bi-arrow-right ms-1"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php 
                            endwhile;
                        endif; 
                        ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <footer class="bg-white border-top py-4 mt-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6 text-center text-md-start mb-2 mb-md-0">
                    <p class="text-muted small mb-0">&copy; 2026 <strong>LMS SMKN 1 Wongsorejo</strong>. All Rights Reserved.</p>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <p class="text-muted small mb-0">Developed with ❤️ by <strong>V</strong> & Kelompok.</p>
                </div>
            </div>
        </div>
    </footer>

    <script>
        const themeToggle = document.getElementById('themeToggle');
        const themeIcon = document.getElementById('themeIcon');
        function updateIcon(theme) {
            if (theme === 'dark') { themeIcon.className = 'bi bi-moon-stars-fill text-info fs-5'; } 
            else { themeIcon.className = 'bi bi-sun-fill text-warning fs-5'; }
        }
        updateIcon(localStorage.getItem('theme') || 'light');
        function switchTheme() {
            const currentTheme = document.documentElement.getAttribute('data-bs-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-bs-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateIcon(newTheme);
        }
    </script>

    <?php if(isset($wajib_ubah) && $wajib_ubah == 1): ?>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        Swal.fire({
            title: 'Keamanan Akun!',
            text: 'Ini adalah login pertama Anda. Demi keamanan, silakan tentukan password baru untuk akun Anda sekarang.',
            icon: 'warning',
            input: 'text',
            inputAttributes: {
                placeholder: 'Ketik password baru Anda di sini...',
                required: 'required',
                autocapitalize: 'off'
            },
            showCancelButton: false,
            confirmButtonText: '<i class="bi bi-shield-check me-1"></i> Simpan & Buka Akses',
            confirmButtonColor: '#dc3545',
            allowOutsideClick: false,
            allowEscapeKey: false,
            preConfirm: (password) => {
                if (!password || password.trim() === "") {
                    Swal.showValidationMessage('Password baru tidak boleh dikosongkan!');
                    return false;
                }
                
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
                            return false; 
                        }
                    } catch (e) {
                        Swal.showValidationMessage('Penyebab Error: ' + text.substring(0, 100)); 
                        return false;
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
</body>
</html>