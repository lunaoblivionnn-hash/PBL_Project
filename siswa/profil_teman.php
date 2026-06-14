<?php
session_start();
require '../login/koneksi.php';
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'siswa'){ header("Location: ../login/login.php"); exit; }

$id_user_login = $_SESSION['IDUser'] ?? '';
$q_me = mysqli_query($koneksi, "SELECT IDSiswa, NamaSiswa, Kelas FROM siswa WHERE IDUser='$id_user_login'");
$me = mysqli_fetch_assoc($q_me);

// Tangkap ID Teman dari URL
$id_teman = mysqli_real_escape_string($koneksi, $_GET['id'] ?? '');

// Jika siswa mengklik profilnya sendiri, lempar ke total_exp.php
if($id_teman == $me['IDSiswa']) {
    header("Location: total_exp.php"); exit;
}

if(empty($id_teman)){ header("Location: peringkat_kelas.php"); exit; }

// Ambil Data Teman
$query_teman = mysqli_query($koneksi, "
    SELECT s.IDSiswa, s.NamaSiswa, s.Kelas, s.Bio, s.FotoProfil, IFNULL(g.TotalPoint, 0) as TotalPoint
    FROM siswa s
    LEFT JOIN gamifikasi g ON s.IDSiswa = g.IDSiswa
    WHERE s.IDSiswa = '$id_teman'
");

if(mysqli_num_rows($query_teman) == 0){ header("Location: peringkat_kelas.php"); exit; }
$teman = mysqli_fetch_assoc($query_teman);

// Ambil Gelar Teman
$q_ml = mysqli_query($koneksi, "SELECT * FROM master_level ORDER BY BatasPoin ASC");
$gelar_teman = 'Beginner Accountant'; $level_teman = 1;
while($lvl = mysqli_fetch_assoc($q_ml)) {
    if($teman['TotalPoint'] >= $lvl['BatasPoin']) {
        $gelar_teman = $lvl['Gelar'];
        $level_teman = $lvl['LevelAngka'];
    }
}

$ava_teman = !empty($teman['FotoProfil']) ? "../uploads/profil/".htmlspecialchars($teman['FotoProfil']) : "https://ui-avatars.com/api/?name=".urlencode($teman['NamaSiswa'])."&background=e0e7ff&color=1e1b4b&size=200";
$bio_teman = !empty($teman['Bio']) ? htmlspecialchars($teman['Bio']) : "Siswa yang rajin dan bersemangat.";
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil <?= htmlspecialchars($teman['NamaSiswa']) ?> - LMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        :root { 
            --primary: #1e1b4b;          /* Midnight Blue Pekat */
            --primary-dark: #100f28;     
            --primary-light: #e0e7ff;    
            --secondary: #3b82f6;        
            --gradient-primary: linear-gradient(135deg, #1e1b4b, #312e81);
            --gradient-card: linear-gradient(135deg, #312e81, #1e1b4b);
            --text-dark: #1e293b; 
            --text-muted: #64748b; 
        }
        body { background-color: #f8fafc; font-family: 'Segoe UI', system-ui, sans-serif; display: flex; flex-direction: column; min-height: 100vh;}
        
        .navbar-custom { background: var(--gradient-primary) !important; box-shadow: 0 4px 20px rgba(30, 27, 75, 0.3); padding: 12px 0; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar { background-color: #fff; box-shadow: 4px 0 20px rgba(0,0,0,0.03); padding: 25px 15px; z-index: 100; min-height: calc(100vh - 70px); }
        .sidebar .nav-link { color: var(--text-muted); font-weight: 600; padding: 12px 20px; border-radius: 12px; margin-bottom: 8px; transition: all 0.3s ease; }
        .sidebar .nav-link:hover { background-color: #f8fafc; color: var(--secondary); transform: translateX(5px); }
        .sidebar .nav-link.active { background-color: var(--primary-light); color: var(--primary); }

        .breadcrumb-modern { font-size: 0.9rem; font-weight: 600; color: var(--text-muted); margin-bottom: 20px; }
        .breadcrumb-modern a { color: var(--secondary); text-decoration: none; transition: 0.2s;}
        
        /* Kartu ID Teman bergaya Holographic/Gaming */
        .friend-card { background: var(--gradient-card); border-radius: 24px; padding: 40px; position: relative; overflow: hidden; box-shadow: 0 20px 40px rgba(15,23,42,0.1); color: white; border: 1px solid rgba(255,255,255,0.05);}
        .friend-card::before { content: ''; position: absolute; top: 0; left: -50%; width: 100%; height: 100%; background: linear-gradient(to right, transparent, rgba(255,255,255,0.08), transparent); transform: skewX(-20deg); animation: shine 5s infinite; }
        @keyframes shine { 0% { left: -50%; } 20% { left: 150%; } 100% { left: 150%; } }
        
        .f-avatar-wrapper { position: relative; display: inline-block; }
        .f-avatar { width: 160px; height: 160px; border-radius: 50%; object-fit: cover; border: 4px solid rgba(255,255,255,0.2); box-shadow: 0 10px 25px rgba(0,0,0,0.2); }
        .f-level-badge { position: absolute; bottom: 5px; right: 5px; background: #fbbf24; color: #000; font-weight: 900; font-size: 1.3rem; width: 45px; height: 45px; display: flex; align-items: center; justify-content: center; border-radius: 50%; border: 4px solid #1e1b4b; }
        
        .f-gelar { font-size: 2.2rem; font-weight: 900; background: linear-gradient(to right, #fbbf24, #f59e0b); -webkit-background-clip: text; -webkit-text-fill-color: transparent; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 5px; }
        .f-bio { font-size: 1.05rem; opacity: 0.8; font-style: italic; border-left: 3px solid #fbbf24; padding-left: 15px; margin-top: 20px;}
        
        /* Box Interaksi */
        .action-box { background: #fff; border-radius: 20px; padding: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.02); border: 1px solid #e2e8f0; height: 100%; display: flex; flex-direction: column; justify-content: center;}
        .btn-social { border-radius: 12px; font-weight: 700; padding: 15px 20px; font-size: 1.1rem; transition: 0.3s; display: flex; align-items: center; justify-content: center; gap: 10px;}
        .btn-social:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(0,0,0,0.05); }
        
        .btn-add { background: #f0fdf4; color: #16a34a; border: 2px solid #86efac; }
        .btn-add:hover { background: #16a34a; color: white; border-color: #16a34a; }
        .btn-chat { background: #eff6ff; color: var(--secondary); border: 2px solid #60a5fa; }
        .btn-chat:hover { background: var(--secondary); color: white; border-color: var(--secondary); }
    </style>
</head>
<body>

    <?php 
    // Mengembalikan identitas navbar agar menampilkan akun siswa yang login, bukan si teman
    $nama_lengkap = $me['NamaSiswa']; 
    $kelas_siswa = $me['Kelas']; 
    ?>

    <?php include 'komponen_navbar.php'; ?>

    <div class="container-fluid px-0 flex-grow-1">
        <div class="row g-0">
            
            <!-- Memanggil Sidebar Dinamis Terpusat -->
            <?php include 'komponen_sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-4 py-4 pb-5">
                <div class="breadcrumb-modern">
                    <i class="bi bi-house-door-fill me-1"></i> 
                    <a href="siswa.php">Dashboard</a> <i class="bi bi-chevron-right mx-2 text-muted" style="font-size: 0.7rem;"></i> 
                    <a href="peringkat_kelas.php">Papan Peringkat Kelas</a> <i class="bi bi-chevron-right mx-2 text-muted" style="font-size: 0.7rem;"></i>
                    Profil Teman
                </div>

                <div class="row g-4 align-items-stretch">
                    
                    <div class="col-xl-8">
                        <div class="friend-card h-100">
                            <div class="row align-items-center position-relative z-1">
                                <div class="col-sm-auto text-center text-sm-start mb-4 mb-sm-0">
                                    <div class="f-avatar-wrapper">
                                        <img src="<?= $ava_teman ?>" class="f-avatar" alt="Profil Teman">
                                        <div class="f-level-badge"><?= $level_teman ?></div>
                                    </div>
                                </div>
                                <div class="col-sm text-center text-sm-start ps-sm-4">
                                    <h4 class="text-white-50 mb-1 fw-bold"><?= htmlspecialchars($teman['NamaSiswa']) ?></h4>
                                    <h1 class="f-gelar"><?= $gelar_teman ?></h1>
                                    
                                    <div class="d-flex align-items-center justify-content-center justify-content-sm-start gap-3 mt-3">
                                        <span class="badge rounded-pill px-3 py-2 fs-6 border border-white border-opacity-25" style="background: rgba(255, 255, 255, 0.15); color: #ffffff;">
                                            <i class="bi bi-buildings me-1 text-warning"></i> Kelas <?= htmlspecialchars($teman['Kelas']) ?>
                                        </span>
                                        <span class="badge bg-warning text-dark px-3 py-2 fs-6 shadow-sm fw-bold">
                                            <i class="bi bi-lightning-charge-fill"></i> <?= number_format($teman['TotalPoint'], 0, ',', '.') ?> XP
                                        </span>
                                    </div>
                                    
                                    <p class="f-bio">"<?= nl2br($bio_teman) ?>"</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-4">
                        <div class="action-box text-center">
                            <h5 class="fw-bold text-dark mb-1">Berjejaring Bersama</h5>
                            <p class="text-muted small mb-4">Ajak <?= htmlspecialchars(explode(' ', trim($teman['NamaSiswa']))[0]) ?> belajar bareng atau tanyakan PR kepadanya.</p>
                            
                            <div class="d-grid gap-3">
                                <button class="btn btn-add btn-social" onclick="kirimTeman()">
                                    <i class="bi bi-person-plus-fill"></i> Tambahkan Teman
                                </button>
                                <button class="btn btn-chat btn-social" onclick="mulaiChat()">
                                    <i class="bi bi-chat-dots-fill"></i> Mulai Percakapan
                                </button>
                            </div>
                        </div>
                    </div>

                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function kirimTeman() {
            Swal.fire({
                title: 'Permintaan Terkirim! 🤝',
                text: 'Permintaan pertemanan telah dikirim ke <?= htmlspecialchars($teman['NamaSiswa']) ?>. Menunggu konfirmasi.',
                icon: 'success',
                confirmButtonColor: '#16a34a'
            });
        }

        function mulaiChat() {
            Swal.fire({
                title: 'Fitur Segera Hadir 💬',
                text: 'Modul Pesan/Chat Realtime antar siswa saat ini sedang dalam tahap pengembangan oleh Admin.',
                icon: 'info',
                confirmButtonColor: '#1e1b4b'
            });
        }
    </script>
</body>
</html>