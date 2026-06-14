<?php
session_start();
require '../login/koneksi.php';
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'siswa'){ header("Location: ../login/login.php"); exit; }

$id_user = $_SESSION['IDUser'] ?? '';
$query_siswa = mysqli_query($koneksi, "SELECT * FROM siswa WHERE IDUser='$id_user'");
$siswa = mysqli_fetch_assoc($query_siswa);
$id_siswa = $siswa['IDSiswa'] ?? '';
$kelas_siswa = $siswa['Kelas'] ?? '';
$nama_lengkap = $siswa['Nama'] ?? $siswa['NamaSiswa'] ?? 'Siswa';
$bio_siswa = $siswa['Bio'] ?? 'Halo! Saya sedang bersemangat belajar Akuntansi.';
$foto_profil = $siswa['FotoProfil'] ?? '';

// --- PROSES UPDATE PROFIL (FOTO & BIO) ---
$notif_profil = ''; 
if(isset($_POST['update_profil'])){
    $bio_baru = mysqli_real_escape_string($koneksi, trim($_POST['bio']));
    $sql_update = "UPDATE siswa SET Bio = '$bio_baru' ";
    $foto_sukses = true;
    
    if(isset($_FILES['foto']) && $_FILES['foto']['error'] == 0){
        $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
        if(in_array($ext, ['jpg','jpeg','png'])){
            $dir = "../uploads/profil/";
            if(!is_dir($dir)) mkdir($dir, 0777, true);
            $new_foto = "PROFIL_" . $id_siswa . "_" . time() . "." . $ext;
            if(move_uploaded_file($_FILES['foto']['tmp_name'], $dir . $new_foto)){
                $sql_update .= ", FotoProfil = '$new_foto' ";
                $foto_profil = $new_foto;
            }
        } else {
            $foto_sukses = false;
            $notif_profil = 'format_salah';
        }
    }
    
    if($foto_sukses) {
        $sql_update .= " WHERE IDSiswa = '$id_siswa'";
        if(mysqli_query($koneksi, $sql_update)){
            $bio_siswa = $bio_baru; 
            $notif_profil = 'sukses';
        } else {
            $notif_profil = 'gagal';
        }
    }
}

// --- AMBIL DATA GAMIFIKASI & HITUNG PROGRESS LEVEL ---
$query_gami = mysqli_query($koneksi, "SELECT TotalPoint FROM gamifikasi WHERE IDSiswa = '$id_siswa'");
$poin_siswa = (mysqli_num_rows($query_gami) > 0) ? mysqli_fetch_assoc($query_gami)['TotalPoint'] : 0;

$q_level_sekarang = mysqli_query($koneksi, "SELECT * FROM master_level WHERE BatasPoin <= $poin_siswa ORDER BY BatasPoin DESC LIMIT 1");
$lvl_now = mysqli_fetch_assoc($q_level_sekarang);
$gelar_siswa = $lvl_now['Gelar'] ?? 'Beginner Accountant';
$angka_level = $lvl_now['LevelAngka'] ?? 1;

$q_level_selanjutnya = mysqli_query($koneksi, "SELECT * FROM master_level WHERE BatasPoin > $poin_siswa ORDER BY BatasPoin ASC LIMIT 1");
$lvl_next = mysqli_fetch_assoc($q_level_selanjutnya);

// Hitung persentase bar
$poin_target = $lvl_next['BatasPoin'] ?? $poin_siswa;
$poin_awal = $lvl_now['BatasPoin'] ?? 0;
$progress_persen = 100;
if($lvl_next){
    $rentang_level = $poin_target - $poin_awal;
    $poin_didapat = $poin_siswa - $poin_awal;
    $progress_persen = ($rentang_level > 0) ? round(($poin_didapat / $rentang_level) * 100) : 100;
}

$avatar_url = !empty($foto_profil) ? "../uploads/profil/" . htmlspecialchars($foto_profil) : "https://ui-avatars.com/api/?name=" . urlencode($nama_lengkap) . "&background=4f46e5&color=fff&size=150";
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pusat Profil & Gamifikasi - LMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        :root { 
            --primary: #1e1b4b;          
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
        .breadcrumb-modern a { color: var(--secondary); text-decoration: none; }
        
        .profile-card { background: var(--gradient-card); border-radius: 20px; color: white; padding: 40px; position: relative; overflow: hidden; box-shadow: 0 10px 30px rgba(30,27,75,0.15); }
        .profile-card::after { content:''; position:absolute; top:-50%; right:-20%; width: 400px; height: 400px; background: radial-gradient(circle, rgba(59,130,246,0.3) 0%, transparent 70%); border-radius:50%; }
        
        .avatar-wrapper { position: relative; display: inline-block; }
        .avatar-img { width: 140px; height: 140px; border-radius: 50%; object-fit: cover; border: 4px solid rgba(255,255,255,0.2); box-shadow: 0 8px 20px rgba(0,0,0,0.3); }
        .level-badge { position: absolute; bottom: 0; right: 10px; background: #fbbf24; color: #000; font-weight: 800; font-size: 1.2rem; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; border-radius: 50%; border: 3px solid #1e1b4b; }
        
        .gelar-text { font-size: 2.2rem; font-weight: 900; background: linear-gradient(to right, #fbbf24, #f59e0b); -webkit-background-clip: text; -webkit-text-fill-color: transparent; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 5px; }
        
        .xp-bar-container { background: rgba(255,255,255,0.1); border-radius: 50px; height: 12px; overflow: hidden; margin-top: 10px; }
        .xp-bar { background: linear-gradient(90deg, #3b82f6, #60a5fa); height: 100%; border-radius: 50px; transition: width 1s ease-in-out; }
        
        .edit-box { background: #fff; border-radius: 20px; padding: 30px; box-shadow: 0 4px 20px rgba(0,0,0,0.03); border: 1px solid #e2e8f0; }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

    <?php include 'komponen_navbar.php'; ?>

    <div class="container-fluid px-0 flex-grow-1">
        <div class="row g-0">
            <?php include 'komponen_sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-4 py-4 pb-5">
                <div class="breadcrumb-modern">
                    <i class="bi bi-house-door-fill me-1"></i> <a href="siswa.php">Dashboard</a> <i class="bi bi-chevron-right mx-2 text-muted" style="font-size: 0.7rem;"></i> Total EXP & Profil
                </div>

                <div class="row g-4">
                    <div class="col-xl-7">
                        <div class="profile-card h-100">
                            <div class="row align-items-center position-relative z-1">
                                <div class="col-sm-auto text-center text-sm-start mb-4 mb-sm-0">
                                    <div class="avatar-wrapper">
                                        <img src="<?= $avatar_url ?>" class="avatar-img" alt="Profil">
                                        <div class="level-badge"><?= $angka_level ?></div>
                                    </div>
                                </div>
                                <div class="col-sm text-center text-sm-start">
                                    <h4 class="text-white-50 mb-1 fs-5"><?= htmlspecialchars($nama_lengkap) ?></h4>
                                    <h1 class="gelar-text"><?= htmlspecialchars($gelar_siswa) ?></h1>
                                    <p class="mb-0 text-light opacity-75 fst-italic">"<?= nl2br(htmlspecialchars($bio_siswa)) ?>"</p>
                                </div>
                            </div>

                            <div class="mt-5 position-relative z-1 bg-white bg-opacity-10 p-3 rounded-4 border border-white border-opacity-25">
                                <div class="d-flex justify-content-between align-items-end mb-2">
                                    <div>
                                        <div class="small text-white-50 fw-bold text-uppercase">Total Experience (XP)</div>
                                        <div class="fs-3 fw-bold text-white"><?= number_format($poin_siswa, 0, ',', '.') ?> <span class="fs-6 text-warning">XP</span></div>
                                    </div>
                                    <div class="text-end">
                                        <?php if($lvl_next): ?>
                                            <div class="small text-white-50 fw-bold">Menuju Level <?= $lvl_next['LevelAngka'] ?></div>
                                            <div class="fw-bold text-white"><?= number_format($poin_target - $poin_siswa, 0, ',', '.') ?> XP lagi</div>
                                        <?php else: ?>
                                            <div class="small text-warning fw-bold"><i class="bi bi-star-fill"></i> Level Maksimal</div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="xp-bar-container">
                                    <div class="xp-bar" style="width: <?= $progress_persen ?>%;"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-5">
                        <div class="edit-box h-100">
                            <h5 class="fw-bold mb-4 text-dark"><i class="bi bi-pencil-square text-secondary me-2"></i>Edit Profil Player</h5>
                            <form action="" method="POST" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label class="form-label small fw-bold text-secondary">Ubah Foto Profil</label>
                                    <input type="file" name="foto" class="form-control bg-light" accept=".jpg,.jpeg,.png">
                                    <small class="text-muted" style="font-size:0.75rem;">Biarkan kosong jika tidak ingin mengubah foto. (Maks 2MB, JPG/PNG)</small>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label small fw-bold text-secondary">Bio / Status Mode</label>
                                    <textarea name="bio" class="form-control bg-light" rows="4" maxlength="150" placeholder="Tulis sesuatu yang memotivasi..." required><?= htmlspecialchars($bio_siswa) ?></textarea>
                                </div>
                                <button type="submit" name="update_profil" class="btn btn-primary w-100 fw-bold rounded-3 py-2 shadow-sm" style="background-color: var(--secondary); border: none;">
                                    <i class="bi bi-save-fill me-2"></i> Simpan Perubahan Profil
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php if($notif_profil === 'sukses'): ?>
    <script>
        Swal.fire({
            title: 'Berhasil!',
            text: 'Profil dan Bio kamu sukses diperbarui.',
            icon: 'success',
            confirmButtonColor: '#3b82f6',
            timer: 2500,
            showConfirmButton: false
        }).then(() => {
            window.location = 'total_exp.php';
        });
    </script>
    <?php elseif($notif_profil === 'format_salah'): ?>
    <script>
        Swal.fire({
            title: 'Format Salah!',
            text: 'Foto profil harus berekstensi JPG atau PNG.',
            icon: 'error',
            confirmButtonColor: '#ef4444'
        });
    </script>
    <?php elseif($notif_profil === 'gagal'): ?>
    <script>
        Swal.fire({
            title: 'Oops!',
            text: 'Terjadi kesalahan sistem saat menyimpan profil.',
            icon: 'error',
            confirmButtonColor: '#ef4444'
        });
    </script>
    <?php endif; ?>
</body>
</html>