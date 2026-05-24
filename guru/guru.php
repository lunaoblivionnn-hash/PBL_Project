<?php
session_start();
require '../login/koneksi.php';

// Pastikan yang masuk adalah akun dengan role guru
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'guru'){
    header("Location: ../login/login.php");
    exit;
}

// 1. IDENTIFIKASI IDUSER
$id_user = isset($_SESSION['IDUser']) ? $_SESSION['IDUser'] : '';
if(empty($id_user)) {
    $ses_username = isset($_SESSION['username']) ? $_SESSION['username'] : '';
    $cek_user = mysqli_query($koneksi, "SELECT IDUser FROM users WHERE Username = '".mysqli_real_escape_string($koneksi,$ses_username)."'");
    if($data_user = mysqli_fetch_assoc($cek_user)){ 
        $id_user = $data_user['IDUser']; 
        $_SESSION['IDUser'] = $id_user; 
    }
}

// 2. CEK WAJIB UBAH PASSWORD
$query_status_sandi = mysqli_query($koneksi, "SELECT WajibUbahPassword FROM users WHERE IDUser = '$id_user'");
$status_sandi = mysqli_fetch_assoc($query_status_sandi);
$wajib_ubah = isset($status_sandi['WajibUbahPassword']) ? $status_sandi['WajibUbahPassword'] : 0;

// 3. DETEKSI MODE MAINTENANCE GLOBAL
$cek_maint = mysqli_query($koneksi, "SELECT Nilai FROM pengaturan WHERE Kunci = 'maintenance'");
$maint = mysqli_fetch_assoc($cek_maint);
if(isset($maint['Nilai']) && $maint['Nilai'] == '1') { 
    session_destroy(); 
    header("Location: ../login/login.php"); 
    exit; 
}

// 4. AMBIL DATA PROFIL GURU
$query_guru = mysqli_query($koneksi, "SELECT * FROM guru WHERE IDUser = '$id_user'");
if ($query_guru && mysqli_num_rows($query_guru) > 0) {
    $guru       = mysqli_fetch_assoc($query_guru);
    $id_guru    = $guru['IDGuru'];
    $nama_guru  = $guru['NamaGuru'];
    $nip_guru   = !empty($guru['NIP_NUPTK']) ? $guru['NIP_NUPTK'] : '-';
} else { 
    $id_guru=''; $nama_guru='Guru Pengampu'; $nip_guru='-';
}

// 5. AMBIL DATA MAPEL LANGSUNG DARI TABEL MAPEL (SINGLE SOURCE OF TRUTH)
$q_mapel_guru = mysqli_query($koneksi, "SELECT * FROM mapel WHERE IDGuru = '$id_guru' ORDER BY NamaMapel ASC");
$daftar_semua_mapel = [];
$mapel_unik = [];
$kelas_unik = [];

$id_mapel_pertama = '';
$is_first = true;

while($row = mysqli_fetch_assoc($q_mapel_guru)) {
    $mapel_unik[$row['NamaMapel']] = true;
    if($is_first) { $id_mapel_pertama = $row['IDMapel']; $is_first = false; }
    
    // Kelas disimpan dalam format JSON di tabel mapel (contoh: ["X AKL 1", "X AKL 2"])
    $kelas_arr = json_decode($row['Kelas'], true) ?: [];
    foreach($kelas_arr as $kls) {
        $kelas_unik[$kls] = true;
        $daftar_semua_mapel[] = [
            'kelas' => $kls,
            'mapel' => $row['NamaMapel'],
            'id_mapel' => $row['IDMapel'],
            'tahun' => $row['TahunAjaran'],
            'gambar' => $row['Gambar']
        ];
    }
}

$total_mapel = count($mapel_unik);
$total_kelas_diajar = count($kelas_unik);
$total_belum_dinilai = 0; // Bisa dihubungkan ke sub-query pengumpulan tugas nanti

// Mengambil ID Mapel pertama dari database sebagai shortcut menu aksi cepat
$id_mapel_pertama = '';
if(!empty($mapel_unik)) {
    $nama_mapel_first = mysqli_real_escape_string($koneksi, array_key_first($mapel_unik));
    $q_first = mysqli_query($koneksi, "SELECT IDMapel FROM mapel WHERE NamaMapel = '$nama_mapel_first' LIMIT 1");
    if($r_first = mysqli_fetch_assoc($q_first)) {
        $id_mapel_pertama = $r_first['IDMapel'];
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Guru – LMS SMKN 1 Wongsorejo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-dk: #3730a3;
            --grad: linear-gradient(135deg, #4f46e5, #3730a3);
        }
        * { font-family: 'Segoe UI', system-ui, sans-serif; }
        body { background: #f0f2f8; transition: background .3s; }
        
        /* Navbar */
        .navbar-custom { background: var(--grad); box-shadow: 0 4px 12px rgba(0,0,0,.12); }
        
        /* Hero Card (Desain Modern Risma) */
        .hero-card { background: linear-gradient(135deg, #1e1e2f, #111119); color: #fff; border: none; border-radius: 22px; box-shadow: 0 12px 35px rgba(0,0,0,.18); position: relative; overflow: hidden; }
        .hero-card::before { content: ''; position: absolute; top: -60px; right: -30px; width: 320px; height: 320px; background: rgba(79,70,229,.18); filter: blur(55px); border-radius: 50%; pointer-events: none; }
        .hero-card::after { content: ''; position: absolute; bottom: -40px; left: -20px; width: 200px; height: 200px; background: rgba(6,182,212,.12); filter: blur(45px); border-radius: 50%; pointer-events: none; }
        
        /* Kotak Statistik */
        .stat-box { background: rgba(255,255,255,.06); border: 1px solid rgba(255,255,255,.09); border-radius: 14px; padding: 14px 20px; text-align: center; }
        .stat-box .val { font-size: 1.9rem; font-weight: 800; }
        
        /* Quick Actions Card */
        .qa-card { background: #fff; border-radius: 16px; padding: 18px 12px; text-align: center; box-shadow: 0 3px 10px rgba(0,0,0,.05); transition: .25s; text-decoration: none; color: #333; display: block; border: 2px solid transparent; }
        .qa-card:hover { border-color: var(--primary); transform: translateY(-4px); color: var(--primary); }
        .qa-icon { width: 50px; height: 50px; border-radius: 13px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; margin: 0 auto 8px; }
        
        /* Mapel Card */
        .mapel-card { border: none; border-radius: 18px; background: #fff; box-shadow: 0 4px 14px rgba(0,0,0,.05); transition: all .3s cubic-bezier(.25,.8,.25,1); overflow: hidden; }
        .mapel-card:hover { transform: translateY(-7px); box-shadow: 0 16px 32px rgba(79,70,229,.14); }
        .mapel-img-wrap { height: 140px; background: #e9ecef; position: relative; overflow: hidden; }
        .mapel-img { width: 100%; height: 100%; object-fit: cover; transition: transform .4s; }
        .mapel-card:hover .mapel-img { transform: scale(1.07); }
        .kelas-badge { position: absolute; top: 10px; left: 10px; background: rgba(0,0,0,.65); backdrop-filter: blur(6px); color: #fff; border-radius: 20px; padding: 4px 12px; font-size: .74rem; font-weight: 600; max-width: 80%; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .alert-badge { position: absolute; top: 10px; right: 10px; background: #ef4444; color: #fff; border-radius: 20px; padding: 4px 10px; font-size: .7rem; font-weight: 700; box-shadow: 0 4px 10px rgba(239, 68, 68, 0.4); }
        .btn-kelola { background: var(--grad); color: #fff; border: none; border-radius: 20px; padding: 7px 18px; font-size: .82rem; font-weight: 600; transition: .25s; text-decoration: none; }
        .btn-kelola:hover { opacity: .85; transform: scale(1.04); color: #fff; }
        
        /* Empty State */
        .empty-box { background: #fff; border-radius: 20px; padding: 4rem 2rem; box-shadow: 0 8px 25px rgba(0,0,0,.04); }
        
        /* Dark Mode Configuration */
        [data-bs-theme="dark"] body { background: #0f0f1a; color: #e0e0e0; }
        [data-bs-theme="dark"] .qa-card, [data-bs-theme="dark"] .mapel-card, [data-bs-theme="dark"] .empty-box { background: #1a1a2e; }
        [data-bs-theme="dark"] .qa-card { color: #e0e0e0; }
        [data-bs-theme="dark"] footer { background: #12121e !important; border-top: 1px solid #2d2d4e; }
        [data-bs-theme="dark"] .text-dark { color: #e0e0e0 !important; }
        
        @media(max-width:576px) { .stat-box .val { font-size: 1.4rem; } .hero-card { border-radius: 16px; } }
    </style>
    <script>document.documentElement.setAttribute('data-bs-theme', localStorage.getItem('theme') || 'light');</script>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark navbar-custom sticky-top">
    <div class="container py-1">
        <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="guru.php">
            <i class="bi bi-person-workspace fs-4"></i> PANEL GURU LMS
        </a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#topNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse justify-content-end" id="topNav">
            <ul class="navbar-nav align-items-center gap-1 mt-2 mt-lg-0">
                <li class="nav-item"><a class="nav-link active fw-semibold px-3" href="guru.php"><i class="bi bi-speedometer2 me-1"></i>Dashboard</a></li>
                <li class="nav-item">
                    <button class="btn btn-link text-white p-2" id="themeToggle" onclick="switchTheme()" style="text-decoration:none;">
                        <i class="bi bi-sun-fill fs-5" id="themeIcon"></i>
                    </button>
                </li>
                <li class="nav-item dropdown ms-1">
                    <a class="nav-link dropdown-toggle bg-white bg-opacity-10 rounded-pill px-3 text-white d-flex align-items-center gap-2" href="#" data-bs-toggle="dropdown">
                        <img src="https://ui-avatars.com/api/?name=<?= urlencode($nama_guru) ?>&background=fff&color=4f46e5" class="rounded-circle" width="26" height="26">
                        <?= htmlspecialchars(explode(' ', trim($nama_guru))[0]) ?>
                        <?php if($total_belum_dinilai > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:.62rem;">
                                <?= $total_belum_dinilai ?>
                            </span>
                        <?php endif; ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2">
                        <li class="px-3 py-2">
                            <div class="fw-bold" style="font-size:.88rem;"><?= htmlspecialchars($nama_guru) ?></div>
                            <div class="text-muted" style="font-size:.74rem;">NIP: <?= htmlspecialchars($nip_guru) ?></div>
                        </li>
                        <li><hr class="dropdown-divider my-1"></li>
                        <li><a class="dropdown-item text-danger" href="../login/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Keluar</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container py-4 py-md-5">

    <div class="card hero-card p-4 p-md-5 mb-4">
        <div class="row align-items-center g-4">
            <div class="col-auto">
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($nama_guru) ?>&size=128&background=4f46e5&color=fff"
                     class="rounded-4 border border-4 border-white border-opacity-20 shadow" width="90" height="90">
            </div>
            <div class="col">
                <span class="badge rounded-pill fw-semibold mb-2 small px-3 py-2" style="background:linear-gradient(135deg,#06b6d4,#0891b2);">
                    <i class="bi bi-patch-check-fill me-1"></i> Tenaga Pendidik Aktif
                </span>
                <h3 class="fw-bold mb-1"><?= htmlspecialchars($nama_guru) ?></h3>
                <p class="text-white-50 mb-0 small">NIP / NUPTK: <strong><?= htmlspecialchars($nip_guru) ?></strong></p>
            </div>
            <div class="col-xl-5 col-lg-6 col-12">
                <div class="row g-2">
                    <div class="col-4"><div class="stat-box"><div class="text-white-50 small mb-1">Mapel</div><div class="val text-warning"><?= $total_mapel ?></div></div></div>
                    <div class="col-4"><div class="stat-box"><div class="text-white-50 small mb-1">Kelas</div><div class="val text-info"><?= $total_kelas_diajar ?></div></div></div>
                    <div class="col-4"><div class="stat-box"><div class="text-white-50 small mb-1">Dinilai</div><div class="val text-danger"><?= $total_belum_dinilai ?></div></div></div>
                </div>
            </div>
        </div>
    </div>

    <?php if($total_mapel > 0): ?>
    <div class="mb-2 ps-1">
        <span class="fw-bold" style="font-size:1rem;"><i class="bi bi-lightning-charge-fill text-warning me-2"></i>Aksi Cepat</span>
        <span class="text-muted ms-2" style="font-size:.78rem;">Pilih mapel di bawah untuk aksi lebih spesifik</span>
    </div>
    <div class="row g-3 mb-5">
        <div class="col-6 col-sm-3">
            <a href="kelola_mapel.php?id_mapel=<?= $id_mapel_pertama ?>&tab=materi" class="qa-card">
                <div class="qa-icon" style="background:#eff6ff;"><i class="bi bi-file-earmark-plus text-primary"></i></div>
                <div style="font-size:.82rem;font-weight:600;">Upload Materi</div>
            </a>
        </div>
        <div class="col-6 col-sm-3">
            <a href="kelola_mapel.php?id_mapel=<?= $id_mapel_pertama ?>&tab=tugas" class="qa-card">
                <div class="qa-icon" style="background:#f0fdf4;"><i class="bi bi-journal-plus" style="color:#059669;"></i></div>
                <div style="font-size:.82rem;font-weight:600;">Buat Tugas</div>
            </a>
        </div>
        <div class="col-6 col-sm-3">
            <a href="kelola_mapel.php?id_mapel=<?= $id_mapel_pertama ?>&tab=quiz" class="qa-card">
                <div class="qa-icon" style="background:#fefce8;"><i class="bi bi-patch-question-fill" style="color:#d97706;"></i></div>
                <div style="font-size:.82rem;font-weight:600;">Buat Quiz</div>
            </a>
        </div>
        <div class="col-6 col-sm-3">
            <a href="kelola_mapel.php?id_mapel=<?= $id_mapel_pertama ?>&tab=penilaian" class="qa-card">
                <div class="qa-icon" style="background:#fdf4ff;"><i class="bi bi-pencil-square" style="color:#9333ea;"></i></div>
                <div style="font-size:.82rem;font-weight:600;">Beri Nilai</div>
                <?php if($total_belum_dinilai > 0): ?><div style="font-size:.7rem;color:#ef4444;font-weight:700;"><?= $total_belum_dinilai ?> menunggu</div><?php endif; ?>
            </a>
        </div>
    </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <span class="fw-bold fs-5 text-dark"><i class="bi bi-collection-fill me-2" style="color:var(--primary);"></i>Ruang Kelas & Mapel Diampu</span><br>
            <span class="text-muted" style="font-size:.85rem;">Klik "Kelola Kelas" untuk unggah materi, buat tugas, quiz & penilaian</span>
        </div>
    </div>

    <?php if($total_mapel == 0): ?>
    <div class="empty-box text-center">
        <div style="font-size:4.5rem;color:#6366f1;" class="mb-3"><i class="bi bi-folder-symlink-fill"></i></div>
        <h4 class="fw-bold text-dark">Belum Mengampu Mata Pelajaran</h4>
        <p class="text-muted mx-auto mb-4 small" style="max-width:520px; line-height:1.6;">
            Halo Bapak/Ibu Guru. Akun Anda saat ini belum dipetakan ke mata pelajaran dan ruang kelas manapun oleh Administrator Kurikulum. Silakan hubungi bagian tata usaha/admin pusat.
        </p>
        <button class="btn btn-primary rounded-pill px-4 shadow-sm" style="background:var(--primary);border:none;" onclick="location.reload();">
            <i class="bi bi-arrow-clockwise me-1"></i> Cek Sinkronisasi Data
        </button>
    </div>

    <?php else: ?>
    <div class="row g-4">
        <?php
        // Tampilkan Card Mapel berjejer langsung dari array database mapel yang sudah disiapkan di atas
        foreach($daftar_semua_mapel as $item):
            $kelas_nama = $item['kelas'];
            $nama_mapel_item = $item['mapel'];
            $id_mapel_real = $item['id_mapel'];
            $tahun_ajaran = $item['tahun'];
            
            // Generator Gambar Gradien Otomatis (SVG)
            $nama_mapel_svg = htmlspecialchars($nama_mapel_item);
            $gradients = [['#4f46e5', '#06b6d4'], ['#f12711', '#f5af19'], ['#834d9b', '#d04ed6'], ['#11998e', '#38ef7d'], ['#fc4a1a', '#f7b733']];
            $idx = strlen($nama_mapel_item) % 5;
            $c1 = $gradients[$idx][0]; $c2 = $gradients[$idx][1];
            $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="600" height="300"><defs><linearGradient id="g'.$idx.'" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" stop-color="'.$c1.'"/><stop offset="100%" stop-color="'.$c2.'"/></linearGradient></defs><rect width="100%" height="100%" fill="url(#g'.$idx.')"/><text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" fill="#ffffff" font-family="Arial, sans-serif" font-size="38" font-weight="bold">'.$nama_mapel_svg.'</text></svg>';
            $cover_img = 'data:image/svg+xml;base64,' . base64_encode($svg);

            // Timpa dengan gambar asli jika admin sudah menguploadnya
            if(!empty($item['gambar']) && file_exists('../image/mapel/'.$item['gambar'])) {
                $cover_img = '../image/mapel/'.$item['gambar'];
            }
        ?>
        
        <div class="col-md-6 col-lg-4 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden mapel-card" style="transition: transform 0.3s;">
                <div style="height: 140px; position: relative;">
                    <img src="<?= $cover_img ?>" alt="cover" class="w-100 h-100" style="object-fit:cover;">
                    
                    <span class="position-absolute top-0 end-0 m-2 badge bg-dark bg-opacity-75 rounded-pill shadow-sm">
                        <i class="bi bi-tag-fill text-warning me-1"></i> <?= $id_mapel_real ?>
                    </span>
                    
                    <span class="position-absolute bottom-0 start-0 m-2 badge bg-white text-primary rounded-pill shadow-sm fw-bold px-3 py-2" style="font-size: 0.8rem;">
                        <i class="bi bi-buildings-fill me-1"></i> <?= htmlspecialchars($kelas_nama) ?>
                    </span>
                </div>
                <div class="card-body p-4 d-flex flex-column bg-white">
                    <h5 class="fw-bold mb-1 text-dark text-truncate" title="<?= htmlspecialchars($nama_mapel_item) ?>">
                        <?= htmlspecialchars($nama_mapel_item) ?>
                    </h5>
                    <p class="text-muted small mb-4">
                        <?php if(!empty($tahun_ajaran)): ?>
                            <i class="bi bi-calendar-event me-1 text-primary"></i> <?= htmlspecialchars($tahun_ajaran) ?>
                        <?php endif; ?>
                    </p>
                    <div class="mt-auto">
                        <a href="kelolaMapel.php?id_mapel=<?= $id_mapel_real ?>&kelas=<?= urlencode($kelas_nama) ?>" class="btn btn-primary btn-sm w-100 rounded-pill fw-bold shadow-sm py-2" style="background: var(--grad); border: none;">
                            Kelola Kelas <i class="bi bi-arrow-right-circle ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
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
            
            // Mulai mengirim data ke proses backend
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