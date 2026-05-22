<?php
session_start();
require '../login/koneksi.php';

if($_SESSION['role'] != 'admin'){
    header("Location: ../login/login.php");
    exit;
}

// AMBIL DATA DARI TABEL DATABASE ASLI LOKAL
$query_rules = mysqli_query($koneksi, "SELECT * FROM master_aturan_poin ORDER BY IDAturan ASC");
$query_ranks = mysqli_query($koneksi, "SELECT * FROM master_level ORDER BY BatasPoin ASC");

$badges = ['🥉', '🥈', '🥈', '🥇', '🥇', '🏅', '🏅', '💎', '💎', '🌟', '🌟', '🌟', '👑'];

// Kamus deskripsi UI
$desc_map = [
    'Baca Materi' => 'Otomatis didapat saat siswa membuka/mendownload materi.',
    'Nilai Tugas' => 'Sesuai input nilai murni dari Guru di sistem.',
    'Bonus Kilat' => 'Mengumpulkan tugas dalam < 24 jam sejak diposting.',
    'Bonus Cepat' => 'Mengumpulkan tugas dalam < 48 jam sejak diposting.',
    'Bonus Disiplin' => 'Mengumpulkan tugas setelah lewat 48 jam (sebelum deadline).',
    'Bonus Sempurna' => 'Tambahan apresiasi jika siswa mendapatkan Nilai Sempurna (100).'
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Parameter Gamifikasi - Admin LMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    
    <style>
        [data-bs-theme="dark"] body { background-color: #121212 !important; color: #e0e0e0 !important; }
        [data-bs-theme="dark"] .bg-light, [data-bs-theme="dark"] .bg-white, [data-bs-theme="dark"] .card, [data-bs-theme="dark"] .card-header, [data-bs-theme="dark"] .card-body { background-color: #1e1e1e !important; border-color: #333 !important; color: #e0e0e0 !important; }
        [data-bs-theme="dark"] .form-control, [data-bs-theme="dark"] .input-group-text { background-color: #2b2b2b !important; border-color: #444 !important; color: #e0e0e0 !important; }
        [data-bs-theme="dark"] .form-control:focus { background-color: #333 !important; border-color: #dc3545 !important; color: #fff !important; }
        [data-bs-theme="dark"] .form-text { color: #adb5bd !important; }
        [data-bs-theme="dark"] .btn-light { background-color: #343a40 !important; border-color: #444 !important; color: #fff !important; }
        
        .scrollable-panel { max-height: 580px; overflow-y: auto; padding-right: 5px; }
        .scrollable-panel::-webkit-scrollbar { width: 6px; }
        .scrollable-panel::-webkit-scrollbar-thumb { background-color: #dc3545; border-radius: 10px; }
    </style>
    <script> document.documentElement.setAttribute('data-bs-theme', localStorage.getItem('theme') || 'light'); </script>
</head>
<body class="bg-light">

    <nav class="navbar navbar-dark bg-danger shadow-sm">
        <div class="container-fluid px-4">
            <a class="navbar-brand fw-bold" href="aturanGamifikasi.php">
                <i class="bi bi-arrow-left-circle me-2"></i> Kembali ke Aturan Gamifikasi
            </a>
        </div>
    </nav>

    <div class="container-fluid py-4 px-md-5">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-white border-bottom p-4">
                <h4 class="fw-bold mb-0 text-danger"><i class="bi bi-sliders me-2"></i> Konfigurasi Parameter Gamifikasi</h4>
            </div>
            <div class="card-body p-4 p-md-5">
                
                <form action="proses_edit_gamifikasi.php" method="POST">
                    
                    <div class="row g-5">
                        
                        <div class="col-xl-5 col-lg-6">
                            <h5 class="fw-bold text-dark border-bottom pb-2 mb-4"><i class="bi bi-stars text-warning me-2"></i> Pengaturan Nilai Poin Tugas</h5>
                            <div class="row g-4">
                                <?php while($rule = mysqli_fetch_assoc($query_rules)): 
                                    $deskripsi = isset($desc_map[$rule['JenisAktivitas']]) ? $desc_map[$rule['JenisAktivitas']] : '';
                                ?>
                                <div class="col-12">
                                    <label class="form-label fw-bold text-secondary mb-1"><?= $rule['JenisAktivitas'] ?></label>
                                    <div class="input-group">
                                        <input type="number" name="rules[<?= $rule['IDAturan'] ?>]" class="form-control form-control-lg fw-bold" value="<?= $rule['BesaranPoin'] ?>" required>
                                        <span class="input-group-text fw-bold">XP</span>
                                    </div>
                                    <div class="form-text text-muted"><?= $deskripsi ?></div>
                                </div>
                                <?php endwhile; ?>
                            </div>
                        </div>

                        <div class="col-xl-7 col-lg-6">
                            <h5 class="fw-bold text-dark border-bottom pb-2 mb-4"><i class="bi bi-trophy-fill text-warning me-2"></i> Pengaturan Target Poin Naik Gelar</h5>
                            
                            <div class="alert alert-warning border-0 mb-3 py-2 small">
                                <i class="bi bi-info-circle-fill me-1"></i> Atur jumlah <strong>poin minimum</strong> yang harus dicapai siswa untuk bisa membuka dan mendapatkan gelar tersebut.
                            </div>

                            <div class="scrollable-panel">
                                <table class="table table-borderless align-middle">
                                    <thead>
                                        <tr class="text-secondary small fw-bold border-bottom">
                                            <th width="12%">Level</th>
                                            <th width="58%">Nama Gelar / Title</th>
                                            <th width="30%">Target Poin</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $baris = 0;
                                        while($rank = mysqli_fetch_assoc($query_ranks)): 
                                            $badge = $badges[$baris % count($badges)];
                                        ?>
                                        <tr>
                                            <td><span class="badge bg-dark px-2 py-2">LVL <?= $rank['LevelAngka'] ?></span></td>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="fs-5"><?= $badge ?></span>
                                                    <input type="text" name="ranks[<?= $rank['IDLevel'] ?>][gelar]" class="form-control form-control-sm border-0 bg-light fw-semibold" value="<?= htmlspecialchars($rank['Gelar']) ?>" required>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="input-group input-group-sm">
                                                    <input type="number" name="ranks[<?= $rank['IDLevel'] ?>][poin]" class="form-control text-center fw-bold text-danger" value="<?= $rank['BatasPoin'] ?>" required>
                                                    <span class="input-group-text">XP</span>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php $baris++; endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>

                    <div class="mt-5 pt-4 border-top d-flex justify-content-end gap-2">
                        <a href="aturanGamifikasi.php" class="btn btn-light px-4">Batal</a>
                        <button type="submit" class="btn btn-danger px-5 fw-bold shadow">
                            <i class="bi bi-save2 me-2"></i> Simpan ke Database
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>