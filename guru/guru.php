<?php
session_start();
require '../login/koneksi.php';

// Pastikan yang masuk adalah akun dengan role guru
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'guru'){
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

// 3. AMBIL DATA PROFIL GURU
$query_guru = mysqli_query($koneksi, "SELECT * FROM guru WHERE IDUser = '$id_user'");
if ($query_guru && mysqli_num_rows($query_guru) > 0) {
    $guru = mysqli_fetch_assoc($query_guru);
    $id_guru   = $guru['IDGuru'];
    $nama_guru = $guru['NamaGuru'];
    $nip_guru  = !empty($guru['NIP_NUPTK']) ? $guru['NIP_NUPTK'] : '-';
} else {
    $id_guru   = '';
    $nama_guru = 'Guru Pengampu';
    $nip_guru  = '-';
}

// 4. AMBIL DAFTAR MAPEL YANG DIAMPUL OLEH GURU INI
$query_mapel = mysqli_query($koneksi, "SELECT * FROM mapel WHERE IDGuru = '$id_guru' ORDER BY Kelas ASC, NamaMapel ASC");
$total_mapel = ($query_mapel) ? mysqli_num_rows($query_mapel) : 0;

// 5. HITUNG TOTAL KELAS UNIK YANG DIAJAR
$query_kelas_unik = mysqli_query($koneksi, "SELECT COUNT(DISTINCT Kelas) as total_kelas FROM mapel WHERE IDGuru = '$id_guru'");
$data_kelas_unik = ($query_kelas_unik) ? mysqli_fetch_assoc($query_kelas_unik) : ['total_kelas' => 0];
$total_kelas_diajar = isset($data_kelas_unik['total_kelas']) ? $data_kelas_unik['total_kelas'] : 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard LMS - Guru</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #4f46e5, #3730a3);
            --teacher-badge: linear-gradient(135deg, #06b6d4, #0891b2);
        }
        body { background-color: #f4f6f9; color: #333; transition: all 0.3s ease; }
        .navbar-custom { background: var(--primary-gradient); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .hero-teacher-card { background: linear-gradient(135deg, #1e1e2f, #111119); color: white; border: none; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.15); position: relative; overflow: hidden; }
        .hero-teacher-card::before { content: ''; position: absolute; top: -50%; right: -20%; width: 300px; height: 300px; background: rgba(79, 70, 229, 0.2); filter: blur(50px); border-radius: 50%; }
        .stat-box { background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 14px; padding: 12px 20px; }
        .mapel-card { border: none; border-radius: 16px; background-color: #fff; box-shadow: 0 5px 15px rgba(0,0,0,0.04); transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1); overflow: hidden; }
        .mapel-card:hover { transform: translateY(-8px); box-shadow: 0 15px 30px rgba(79, 70, 229, 0.15); }
        .mapel-img-container { height: 140px; background-color: #e9ecef; position: relative; overflow: hidden; }
        .mapel-img { width: 100%; height: 100%; object-fit: cover; }
        .empty-state-box { background: #fff; border-radius: 20px; padding: 4rem 2rem; box-shadow: 0 10px 30px rgba(0,0,0,0.03); }
        [data-bs-theme="dark"] body { background-color: #121212; color: #e0e0e0; }
        [data-bs-theme="dark"] .mapel-card { background-color: #1e1e1e; box-shadow: 0 5px 15px rgba(0,0,0,0.3); }
        [data-bs-theme="dark"] .empty-state-box { background-color: #1e1e1e; }
        [data-bs-theme="dark"] .text-dark { color: #e0e0e0 !important; }
        [data-bs-theme="dark"] footer { background-color: #1a1a1a !important; border-top: 1px solid #2d2d2d; }
    </style>
    <script>
        document.documentElement.setAttribute('data-bs-theme', localStorage.getItem('theme') || 'light');
    </script>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom sticky-top">
        <div class="container py-1">
            <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="guru.php">
                <i class="bi bi-person-workspace fs-3"></i> PANEL GURU LMS
            </a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#topNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="topNav">
                <ul class="navbar-nav align-items-center gap-2 mt-2 mt-lg-0">
                    <li class="nav-item"><a class="nav-link active fw-semibold px-3" href="guru.php"><i class="bi bi-speedometer2 me-1"></i>Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link px-3" href="#"><i class="bi bi-file-earmark-plus me-1"></i>Input Tugas</a></li>
                    <li class="nav-item"><a class="nav-link px-3" href="#"><i class="bi bi-check2-square me-1"></i>Penilaian</a></li>
                    <li class="nav-item ms-lg-2">
                        <button class="btn btn-link text-white p-2" id="themeToggle" onclick="switchTheme()">
                            <i class="bi bi-sun-fill fs-5" id="themeIcon"></i>
                        </button>
                    </li>
                    <li class="nav-item dropdown ms-lg-2">
                        <a class="nav-link dropdown-toggle bg-white bg-opacity-10 rounded-pill px-3 text-white d-flex align-items-center gap-2" href="#" data-bs-toggle="dropdown">
                            <img src="https://ui-avatars.com/api/?name=<?= urlencode($nama_guru) ?>&background=fff&color=4f46e5" class="rounded-circle" width="26" height="26">
                            <?= htmlspecialchars(explode(' ', trim($nama_guru))[0]); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2">
                            <li><a class="dropdown-item" href="#"><i class="bi bi-person-gear me-2"></i>Pengaturan Akun</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="../login/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Keluar</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row mb-5">
            <div class="col-12">
                <div class="card hero-teacher-card p-4 p-md-5">
                    <div class="row align-items-center g-4">
                        <div class="col-auto">
                            <img src="https://ui-avatars.com/api/?name=<?= urlencode($nama_guru) ?>&size=128&background=4f46e5&color=fff" class="rounded-4 border border-4 border-opacity-20 border-white shadow shadow-lg" width="100" height="100">
                        </div>
                        <div class="col-md flex-grow-1">
                            <span class="badge px-3 py-2 rounded-pill fw-bold mb-2 small" style="background: var(--teacher-badge);"><i class="bi bi-patch-check-fill me-1"></i> Tenaga Pendidik Aktif</span>
                            <h2 class="fw-bold mb-1 m-0"><?= htmlspecialchars($nama_guru) ?></h2>
                            <p class="text-white-50 m-0 small">NIP: <strong><?= htmlspecialchars($nip_guru) ?></strong></p>
                        </div>
                        <div class="col-xl-4 col-lg-5 col-md-12">
                            <div class="row g-2">
                                <div class="col-6">
                                    <div class="stat-box text-center">
                                        <div class="text-white-50 small mb-1">Total Mapel</div>
                                        <div class="fs-3 fw-bold text-warning"><?= isset($total_mapel) ? $total_mapel : 0 ?></div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="stat-box text-center">
                                        <div class="text-white-50 small mb-1">Total Kelas</div>
                                        <div class="fs-3 fw-bold text-info"><?= isset($total_kelas_diajar) ? $total_kelas_diajar : 0 ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <?php if($total_mapel == 0): ?>
                    <div class="empty-state-box text-center">
                        <div class="text-secondary mb-4" style="font-size: 4.5rem; color: #6366f1 !important;">
                            <i class="bi bi-folder-symlink-fill"></i>
                        </div>
                        <h4 class="fw-bold text-dark mb-2">Belum Mengampu Mata Pelajaran</h4>
                        <p class="text-muted mx-auto mb-4 small" style="max-width: 520px; line-height: 1.6;">
                            Halo Bapak/Ibu Guru. Akun Anda saat ini belum dipetakan ke mata pelajaran dan ruang kelas manapun oleh Administrator Kurikulum. Silakan hubungi bagian tata usaha/admin pusat untuk memberikan otorisasi akses kelas mengajar Anda.
                        </p>
                        <button class="btn btn-primary fw-bold rounded-pill px-4 shadow-sm btn-sm" style="background-color: #4f46e5; border: none;" onclick="location.reload();">
                            <i class="bi bi-arrow-clockwise me-1"></i> Cek Sinkronisasi Data
                        </button>
                    </div>
                <?php else: ?>
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h4 class="fw-bold m-0 text-dark"><i class="bi bi-collection-fill text-primary me-2"></i> Ruang Kelas & Mapel yang Diampu</h4>
                            <p class="text-muted small mb-0 m-0">Silakan pilih kelas untuk mengunggah materi, tugas, atau melakukan penilaian siswa.</p>
                        </div>
                    </div>

                    <div class="row g-4">
                        <?php 
                        mysqli_data_seek($query_mapel, 0);
                        while($mapel = mysqli_fetch_assoc($query_mapel)):
                            $cover_img = !empty($mapel['Gambar']) ? '../assets/img/cover/' . $mapel['Gambar'] : 'https://images.unsplash.com/photo-1434030216411-0b793f4b4173?auto=format&fit=crop&q=80&w=600';
                        ?>
                            <div class="col-xl-4 col-md-6">
                                <div class="card mapel-card h-100 shadow-sm">
                                    <div class="mapel-img-container">
                                        <img src="<?= $cover_img ?>" class="mapel-img" alt="Cover Kelas">
                                        <span class="position-absolute top-3 start-3 badge bg-dark bg-opacity-75 backdrop-blur rounded-pill px-3 py-2 small">
                                            <i class="bi bi-building-fill text-info me-1"></i> <?= $mapel['Kelas'] ?>
                                        </span>
                                    </div>
                                    <div class="card-body p-4 d-flex flex-column">
                                        <h5 class="fw-bold text-dark mb-1 text-truncate" title="<?= $mapel['NamaMapel'] ?>">
                                            <?= $mapel['NamaMapel'] ?>
                                        </h5>
                                        <p class="text-muted small mb-4 flex-grow-1">Akses penuh pengelolaan modul belajar kurikulum di kelas <strong><?= $mapel['Kelas'] ?></strong>.</p>
                                        <div class="border-top pt-3 d-flex justify-content-between align-items-center">
                                            <span class="small text-muted fw-semibold"><i class="bi bi-people me-1"></i> Ruang Guru</span>
                                            <a href="#" class="btn btn-primary btn-sm rounded-pill px-4 fw-bold shadow-sm" style="background-color: #4f46e5; border: none;">
                                                Kelola Kelas <i class="bi bi-gear-fill ms-1"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
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
            confirmButtonColor: '#4f46e5',
            allowOutsideClick: false,
            allowEscapeKey: false,
            preConfirm: (password) => {
                if (!password || password.trim() === "") {
                    Swal.showValidationMessage('Password baru tidak boleh dikosongkan!');
                    return false;
                }
                
                // Mulai mengirim data
                return fetch('proses_ubah_sandi_paksa.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'password_baru=' + encodeURIComponent(password)
                })
                .then(async response => {
                    // TANGKAP TEXT APAPUN DARI SERVER (Biar kita tahu error aslinya)
                    const text = await response.text();
                    try {
                        // Coba paksa ubah jadi JSON
                        const data = JSON.parse(text);
                        if (data.status === 'sukses') { return data; } 
                        else { 
                            Swal.showValidationMessage('Gagal dari Server: ' + data.pesan); 
                            return false; 
                        }
                    } catch (e) {
                        // JIKA GAGAL JADI JSON, TAMPILKAN ERROR ASLINYA DI LAYAR!
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