<?php
session_start();
require '../login/koneksi.php';
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'siswa'){ header("Location: ../login/login.php"); exit; }

$id_user = $_SESSION['IDUser'] ?? '';
$query_siswa = mysqli_query($koneksi, "SELECT * FROM siswa WHERE IDUser='$id_user'");
$siswa = mysqli_fetch_assoc($query_siswa);
$kelas_siswa = $siswa['Kelas'] ?? '';
$nama_lengkap = $siswa['Nama'] ?? $siswa['NamaSiswa'] ?? 'Siswa';

// AMBIL MASTER LEVEL UNTUK MENGHITUNG GELAR SECARA DINAMIS
$q_ml = mysqli_query($koneksi, "SELECT * FROM master_level ORDER BY BatasPoin ASC");
$master_levels = [];
while($ml = mysqli_fetch_assoc($q_ml)) { $master_levels[] = $ml; }

// AMBIL SEMUA SISWA DI KELAS INI BESERTA XP-NYA
$q_rank = mysqli_query($koneksi, "
    SELECT s.IDSiswa, s.NamaSiswa, s.Bio, s.FotoProfil, IFNULL(g.TotalPoint, 0) as TotalPoint
    FROM siswa s
    LEFT JOIN gamifikasi g ON s.IDSiswa = g.IDSiswa
    WHERE s.Kelas = '$kelas_siswa'
    ORDER BY TotalPoint DESC, NamaSiswa ASC
");

// Fungsi hitung Gelar berdasarkan XP
function getLevelInfo($xp, $levels) {
    $hasil = ['Gelar' => 'Beginner Accountant', 'Angka' => 1];
    foreach($levels as $lvl) {
        if($xp >= $lvl['BatasPoin']) { $hasil = ['Gelar' => $lvl['Gelar'], 'Angka' => $lvl['LevelAngka']]; }
    }
    return $hasil;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaderboard Kelas - LMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        :root { --primary: #4f46e5; --text-dark: #1e293b; --text-muted: #64748b; }
        body { background-color: #f8fafc; font-family: 'Segoe UI', system-ui, sans-serif; display: flex; flex-direction: column; min-height: 100vh;}
        .navbar-custom { background: linear-gradient(135deg, #4f46e5, #0ea5e9) !important; box-shadow: 0 4px 15px rgba(79, 70, 229, 0.2); padding: 10px 0; }
        .sidebar { background-color: #fff; box-shadow: 2px 0 20px rgba(0,0,0,0.03); padding: 25px 15px; z-index: 100; min-height: calc(100vh - 70px); }
        .sidebar .nav-link { color: var(--text-muted); font-weight: 600; padding: 12px 20px; border-radius: 12px; margin-bottom: 8px; transition: 0.3s; }
        .sidebar .nav-link:hover { background-color: #f1f5f9; color: var(--primary); }
        .breadcrumb-modern { font-size: 0.9rem; font-weight: 600; color: var(--text-muted); margin-bottom: 20px; }
        .breadcrumb-modern a { color: var(--primary); text-decoration: none; }
        
        /* Modifikasi Rank Card untuk Stretched-Link */
        .rank-card { background: #fff; border-radius: 16px; padding: 20px 25px; display: flex; align-items: center; gap: 20px; border: 1px solid #e2e8f0; margin-bottom: 15px; transition: 0.2s; position: relative; cursor: pointer;}
        .rank-card:hover { transform: translateY(-4px); box-shadow: 0 10px 25px rgba(79,70,229,0.1); border-color: var(--primary); }
        
        .rank-number { width: 50px; font-size: 1.5rem; font-weight: 900; color: #cbd5e1; text-align: center; }
        .rank-1 .rank-number { color: #fbbf24; font-size: 2rem; text-shadow: 0 2px 5px rgba(251,191,36,0.3);}
        .rank-2 .rank-number { color: #94a3b8; font-size: 1.8rem; }
        .rank-3 .rank-number { color: #b45309; font-size: 1.6rem; }
        
        .rank-avatar { width: 60px; height: 60px; border-radius: 50%; object-fit: cover; border: 2px solid #e2e8f0; }
        .rank-info { flex-grow: 1; overflow: hidden; }
        .rank-score { text-align: right; min-width: 120px; }
        
        .search-box { position: relative; max-width: 400px; }
        .search-box i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--text-muted); }
        .search-box input { padding-left: 45px; border-radius: 50px; background: #fff; border: 1px solid #cbd5e1; box-shadow: 0 4px 10px rgba(0,0,0,0.02); }
        .search-box input:focus { border-color: var(--primary); box-shadow: 0 0 0 0.2rem rgba(79, 70, 229, 0.15); }
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
                    <h6 class="mb-0 fw-bold small"><?= $nama_lengkap ?></h6>
                    <span class="badge bg-white bg-opacity-25 rounded-pill mt-1"><?= htmlspecialchars($kelas_siswa) ?></span>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-0 flex-grow-1">
        <div class="row g-0">
            <nav class="col-md-3 col-lg-2 d-none d-md-block sidebar">
                <div class="text-muted small fw-bold mb-3 px-3">MENU AKADEMIK</div>
                <ul class="nav flex-column">
                    <li class="nav-item"><a class="nav-link" href="siswa.php"><i class="bi bi-grid-1x2-fill me-3 fs-5"></i> Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="kalender.php"><i class="bi bi-calendar2-week-fill me-3 fs-5"></i> Jadwal & Agenda</a></li>
                </ul>
            </nav>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-4 py-4 pb-5">
                <div class="breadcrumb-modern">
                    <i class="bi bi-house-door-fill me-1"></i> <a href="siswa.php">Dashboard</a> <i class="bi bi-chevron-right mx-2 text-muted" style="font-size: 0.7rem;"></i> Papan Peringkat Kelas
                </div>

                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-4 gap-3">
                    <div>
                        <h2 class="fw-bold mb-1 text-dark d-flex align-items-center gap-2">
                            <i class="bi bi-trophy-fill text-warning"></i> Papan Peringkat
                        </h2>
                        <div class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 border border-primary-subtle fw-bold" style="font-size: 0.9rem;">
                            <i class="bi bi-buildings-fill me-1"></i> Kelas <?= htmlspecialchars($kelas_siswa) ?>
                        </div>
                    </div>
                    <div class="search-box w-100">
                        <i class="bi bi-search"></i>
                        <input type="text" id="cariSiswa" class="form-control py-2" placeholder="Cari nama teman kelasmu..." onkeyup="filterSiswa()">
                    </div>
                </div>

                <div id="listPeringkat">
                    <?php 
                    $no = 1;
                    while($row = mysqli_fetch_assoc($q_rank)): 
                        $lvl_info = getLevelInfo($row['TotalPoint'], $master_levels);
                        $class_rank = ($no <= 3) ? 'rank-'.$no : '';
                        $ava = !empty($row['FotoProfil']) ? "../uploads/profil/".htmlspecialchars($row['FotoProfil']) : "https://ui-avatars.com/api/?name=".urlencode($row['NamaSiswa'])."&background=random";
                        $bio = !empty($row['Bio']) ? htmlspecialchars($row['Bio']) : "Siswa yang rajin dan bersemangat.";
                        $is_me = ($row['IDSiswa'] == $siswa['IDSiswa']) ? 'bg-primary bg-opacity-10 border-primary' : '';
                    ?>
                        <div class="rank-card <?= $class_rank ?> <?= $is_me ?> user-row">
                            <div class="rank-number">
                                <?php if($no == 1) echo '<i class="bi bi-award-fill"></i>'; else echo '#'.$no; ?>
                            </div>
                            <img src="<?= $ava ?>" class="rank-avatar shadow-sm" alt="Foto">
                            <div class="rank-info">
                                <h5 class="fw-bold mb-0 user-name">
                                    <a href="profil_teman.php?id=<?= $row['IDSiswa'] ?>" class="text-dark text-decoration-none stretched-link">
                                        <?= htmlspecialchars($row['NamaSiswa']) ?>
                                    </a>
                                </h5>
                                <div class="d-flex align-items-center gap-2 mt-1">
                                    <span class="fw-bold text-primary fs-6"><?= $lvl_info['Gelar'] ?></span>
                                    <span class="badge bg-dark text-warning small"><i class="bi bi-star-fill me-1"></i> Lv. <?= $lvl_info['Angka'] ?></span>
                                </div>
                                <p class="text-muted small mb-0 mt-1 text-truncate">"<?= $bio ?>"</p>
                            </div>
                            <div class="rank-score">
                                <h3 class="fw-bold text-dark mb-0"><?= number_format($row['TotalPoint'], 0, ',', '.') ?></h3>
                                <span class="text-muted small fw-semibold">XP</span>
                            </div>
                        </div>
                    <?php $no++; endwhile; ?>
                    
                    <div id="noData" class="d-none text-center py-5">
                        <i class="bi bi-search text-muted fs-1 mb-2"></i>
                        <h6 class="text-secondary fw-bold">Siswa tidak ditemukan.</h6>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <script>
        function filterSiswa() {
            let input = document.getElementById('cariSiswa').value.toLowerCase();
            let rows = document.getElementsByClassName('user-row');
            let found = false;

            for (let i = 0; i < rows.length; i++) {
                let name = rows[i].querySelector('.user-name').innerText.toLowerCase();
                if (name.includes(input)) {
                    rows[i].classList.remove('d-none');
                    found = true;
                } else {
                    rows[i].classList.add('d-none');
                }
            }
            
            document.getElementById('noData').classList.toggle('d-none', found);
        }
    </script>
</body>
</html>