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

// ++ ARRAY UNTUK MODAL MULTI-KELAS JAVASCRIPT ++
$data_mapel_js = []; 

$id_mapel_pertama = '';
$is_first = true;

while($row = mysqli_fetch_assoc($q_mapel_guru)) {
    $id_mapel_row = $row['IDMapel'];
    $mapel_unik[$row['NamaMapel']] = true;
    if($is_first) { $id_mapel_pertama = $row['IDMapel']; $is_first = false; }
    
    $kelas_arr = json_decode($row['Kelas'], true) ?: [];
    
    // Tarik daftar nama Topik/Bab unik khusus untuk mata pelajaran ini
    $q_tpk = mysqli_query($koneksi, "SELECT DISTINCT NamaTopik FROM topik_mapel WHERE IDMapel = '$id_mapel_row'");
    $topik_arr = [];
    while($tp = mysqli_fetch_assoc($q_tpk)) { $topik_arr[] = $tp['NamaTopik']; }
    
    // Masukkan ke array JS
    $data_mapel_js[$id_mapel_row] = [
        'nama' => $row['NamaMapel'],
        'kelas' => $kelas_arr,
        'topik' => $topik_arr
    ];

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
$total_belum_dinilai = 0; 

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
        
        /* Hero Card */
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
        <span class="text-muted ms-2" style="font-size:.78rem;">Sebarkan materi dan tugas ke banyak kelas sekaligus</span>
    </div>
    <div class="row g-3 mb-5">
        <div class="col-6 col-sm-3">
            <a href="javascript:void(0)" onclick="bukaModalMulti('materi', '<?= $id_mapel_pertama ?>', '')" class="qa-card">
                <div class="qa-icon" style="background:#eff6ff;"><i class="bi bi-file-earmark-plus text-primary"></i></div>
                <div style="font-size:.82rem;font-weight:600;">Upload Materi</div>
            </a>
        </div>
        <div class="col-6 col-sm-3">
            <a href="javascript:void(0)" onclick="bukaModalMulti('tugas', '<?= $id_mapel_pertama ?>', '')" class="qa-card">
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
            <span class="text-muted" style="font-size:.85rem;">Pilih kelas untuk mengelola materi, tugas, dan nilai siswa</span>
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
        foreach($daftar_semua_mapel as $item):
            $kelas_nama = $item['kelas'];
            $nama_mapel_item = $item['mapel'];
            $id_mapel_real = $item['id_mapel'];
            $tahun_ajaran = $item['tahun'];
            
            // Generator SVG 
            $nama_mapel_svg = htmlspecialchars($nama_mapel_item);
            $gradients = [['#4f46e5', '#06b6d4'], ['#f12711', '#f5af19'], ['#834d9b', '#d04ed6'], ['#11998e', '#38ef7d'], ['#fc4a1a', '#f7b733']];
            $idx = strlen($nama_mapel_item) % 5;
            $c1 = $gradients[$idx][0]; $c2 = $gradients[$idx][1];
            $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="600" height="300"><defs><linearGradient id="g'.$idx.'" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" stop-color="'.$c1.'"/><stop offset="100%" stop-color="'.$c2.'"/></linearGradient></defs><rect width="100%" height="100%" fill="url(#g'.$idx.')"/><text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" fill="#ffffff" font-family="Arial, sans-serif" font-size="38" font-weight="bold">'.$nama_mapel_svg.'</text></svg>';
            $cover_img = 'data:image/svg+xml;base64,' . base64_encode($svg);

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
                    else { Swal.showValidationMessage('Gagal dari Server: ' + data.pesan); return false; }
                } catch (e) {
                    Swal.showValidationMessage('Penyebab Error: ' + text.substring(0, 100)); return false;
                }
            })
            .catch(error => { Swal.showValidationMessage('Fetch Gagal: ' + error.message); return false; });
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

<!-- ============================================================== -->
<!-- MODAL MULTI KELAS & SCRIPT -->
<!-- ============================================================== -->

<div class="modal fade" id="modalUploadMateriMulti" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header bg-primary text-white border-0 p-4">
                <h5 class="modal-title fw-bold"><i class="bi bi-file-earmark-plus me-2"></i>Upload Materi Cepat</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="Proses_Up_Materi_Multi.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body p-4 bg-light">
                    <input type="hidden" name="id_mapel" id="materiIdMapel">
                    
                    <div class="alert alert-primary bg-primary bg-opacity-10 border-primary border-opacity-25 mb-4">
                        <strong><i class="bi bi-book me-2"></i>Mapel: </strong> <span id="materiLabelMapel"></span>
                    </div>

                    <div class="mb-4 bg-white p-3 border rounded shadow-sm">
                        <label class="form-label fw-bold text-dark small mb-3">Tugaskan ke Kelas Mana Saja? <span class="text-danger">*</span></label>
                        <div id="kelasCheckboxMateri" class="d-flex flex-wrap gap-4"></div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold text-secondary small">Pilih / Ketik Bab (Section) <span class="text-danger">*</span></label>
                        <input type="text" name="nama_topik" class="form-control fw-bold text-primary" list="listTopikMateri" placeholder="Pilih dari daftar atau ketik Bab baru..." required>
                        <datalist id="listTopikMateri"></datalist>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-secondary small">Judul Materi <span class="text-danger">*</span></label>
                        <input type="text" name="judul" class="form-control" placeholder="Contoh: Pengenalan Akuntansi Dasar" required maxlength="100">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-secondary small">Deskripsi (Opsional)</label>
                        <textarea name="deskripsi" class="form-control" rows="2" maxlength="300" placeholder="Tulis instruksi membaca..."></textarea>
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-bold text-secondary small">File Dokumen <span class="text-danger">*</span></label>
                        <input type="file" name="materi_file" class="form-control bg-white" required accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.jpg,.jpeg,.png,.mp4">
                    </div>
                </div>
                <div class="modal-footer border-0 p-3 bg-white">
                    <button type="submit" class="btn btn-primary px-4 fw-bold shadow-sm w-100">Sebarkan Materi ke Kelas Terpilih</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalBuatTugasMulti" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header bg-success text-white border-0 p-4">
                <h5 class="modal-title fw-bold"><i class="bi bi-journal-plus me-2"></i>Buat Penugasan Cepat</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="Proses_Up_Tugas_Multi.php" method="POST">
                <div class="modal-body p-4 bg-light">
                    <input type="hidden" name="id_mapel" id="tugasIdMapel">
                    
                    <div class="alert alert-success bg-success bg-opacity-10 border-success border-opacity-25 mb-4">
                        <strong><i class="bi bi-book me-2"></i>Mapel: </strong> <span id="tugasLabelMapel"></span>
                    </div>

                    <div class="mb-4 bg-white p-3 border rounded shadow-sm">
                        <label class="form-label fw-bold text-dark small mb-3">Tugaskan ke Kelas Mana Saja? <span class="text-danger">*</span></label>
                        <div id="kelasCheckboxTugas" class="d-flex flex-wrap gap-4"></div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold text-secondary small">Pilih / Ketik Bab (Section) <span class="text-danger">*</span></label>
                        <input type="text" name="nama_topik" class="form-control fw-bold text-success" list="listTopikTugas" placeholder="Pilih dari daftar atau ketik Bab baru..." required>
                        <datalist id="listTopikTugas"></datalist>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-secondary small">Judul Tugas <span class="text-danger">*</span></label>
                        <input type="text" name="judul" class="form-control" required maxlength="100">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-secondary small">Instruksi Pengerjaan</label>
                        <textarea name="deskripsi" class="form-control" rows="3" maxlength="1000"></textarea>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-7">
                            <label class="form-label fw-bold text-secondary small">Batas Waktu (Deadline) <span class="text-danger">*</span></label>
                            <input type="datetime-local" name="deadline" class="form-control fw-bold" required value="<?= date('Y-m-d\T23:59', strtotime('+1 day')) ?>">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-bold text-secondary small">Poin Maksimal</label>
                            <div class="input-group">
                                <input type="number" name="poin_maksimal" class="form-control fw-bold text-success" value="100" min="10" step="10">
                                <span class="input-group-text bg-white">Poin</span>
                            </div>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label fw-bold text-secondary small">Jenis File Diizinkan <span class="text-danger">*</span></label>
                        <div class="p-3 bg-white border rounded">
                            <div class="d-flex flex-wrap gap-4">
                                <div class="form-check"><input class="form-check-input" type="checkbox" name="tipe_file[]" value="PDF" id="tf_pdf" checked><label class="form-check-label small" for="tf_pdf">Dokumen PDF</label></div>
                                <div class="form-check"><input class="form-check-input" type="checkbox" name="tipe_file[]" value="Word" id="tf_word"><label class="form-check-label small" for="tf_word">Word</label></div>
                                <div class="form-check"><input class="form-check-input" type="checkbox" name="tipe_file[]" value="Excel" id="tf_excel"><label class="form-check-label small" for="tf_excel">Excel</label></div>
                                <div class="form-check"><input class="form-check-input" type="checkbox" name="tipe_file[]" value="Gambar/Foto" id="tf_gbr"><label class="form-check-label small" for="tf_gbr">Gambar (JPG/PNG)</label></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-3 bg-white">
                    <button type="submit" class="btn btn-success px-5 fw-bold shadow-sm w-100">Sebarkan Tugas ke Kelas Terpilih</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Membaca Data Mapel yang Diambil PHP
    const dbMapelData = <?= json_encode($data_mapel_js) ?>;

    function bukaModalMulti(jenis, idMapel, kelasUtama) {
        const mapelInfo = dbMapelData[idMapel];
        if (!mapelInfo) return;

        const containerKelas = document.getElementById(jenis === 'materi' ? 'kelasCheckboxMateri' : 'kelasCheckboxTugas');
        const dataListTopik = document.getElementById(jenis === 'materi' ? 'listTopikMateri' : 'listTopikTugas');
        
        // 1. Cetak Checkbox Kelas
        let htmlCb = '';
        mapelInfo.kelas.forEach(kls => {
            // Jika diklik dari Aksi Cepat (kelasUtama kosong), maka centang semua kelas secara default
            const isChecked = (kls === kelasUtama || kelasUtama === '') ? 'checked' : '';
            htmlCb += `
                <div class="form-check form-switch fs-6">
                    <input class="form-check-input border-secondary" type="checkbox" name="kelas[]" value="${kls}" id="${jenis}_${kls.replace(/\s+/g, '')}" ${isChecked} style="cursor: pointer;">
                    <label class="form-check-label fw-bold text-dark" for="${jenis}_${kls.replace(/\s+/g, '')}" style="cursor: pointer;">${kls}</label>
                </div>
            `;
        });
        containerKelas.innerHTML = htmlCb;

        // 2. Cetak Daftar Topik (Bab) untuk Auto-complete Datalist
        let htmlTopik = '';
        mapelInfo.topik.forEach(tp => { htmlTopik += `<option value="${tp}">`; });
        dataListTopik.innerHTML = htmlTopik;

        // 3. Tampilkan Modal
        if (jenis === 'materi') {
            document.getElementById('materiIdMapel').value = idMapel;
            document.getElementById('materiLabelMapel').innerText = mapelInfo.nama;
            new bootstrap.Modal(document.getElementById('modalUploadMateriMulti')).show();
        } else {
            document.getElementById('tugasIdMapel').value = idMapel;
            document.getElementById('tugasLabelMapel').innerText = mapelInfo.nama;
            new bootstrap.Modal(document.getElementById('modalBuatTugasMulti')).show();
        }
    }
</script>

<?php if(isset($_GET['status']) && $_GET['status'] == 'sukses_multi'): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({ 
            title: 'Berhasil Disebarkan! 🚀', 
            text: 'Data berhasil diunggah dan disebarkan ke semua kelas yang dicentang.', 
            icon: 'success', 
            confirmButtonColor: '#4f46e5' 
        });
        window.history.replaceState(null, null, window.location.pathname);
    });
</script>
<?php endif; ?>

</body>
</html>