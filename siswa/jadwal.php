<?php
session_start();
require '../login/koneksi.php';

// Pastikan yang masuk adalah siswa
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'siswa'){
    header("Location: ../login/login.php"); exit;
}

$id_user = $_SESSION['IDUser'] ?? '';

// Ambil Data Siswa
$query_siswa = mysqli_query($koneksi, "SELECT * FROM siswa WHERE IDUser='$id_user'");
$siswa = mysqli_fetch_assoc($query_siswa);
$id_siswa = $siswa['IDSiswa'] ?? '';
$kelas_siswa = $siswa['Kelas'] ?? '';
$nama_lengkap = $siswa['Nama'] ?? $siswa['NamaSiswa'] ?? 'Siswa';

// --- PROSES TAMBAH JADWAL ---
if(isset($_POST['tambah_jadwal'])){
    $hari = mysqli_real_escape_string($koneksi, $_POST['hari']);
    $jam_mulai = mysqli_real_escape_string($koneksi, $_POST['jam_mulai']);
    $jam_selesai = mysqli_real_escape_string($koneksi, $_POST['jam_selesai']);
    $kegiatan = mysqli_real_escape_string($koneksi, $_POST['kegiatan']);
    $warna = mysqli_real_escape_string($koneksi, $_POST['warna']);

    $sql = "INSERT INTO jadwal_siswa (IDSiswa, Hari, JamMulai, JamSelesai, Kegiatan, WarnaLabel) 
            VALUES ('$id_siswa', '$hari', '$jam_mulai', '$jam_selesai', '$kegiatan', '$warna')";
    
    if(mysqli_query($koneksi, $sql)){
        header("Location: jadwal.php?status=sukses_tambah"); exit;
    } else {
        header("Location: jadwal.php?status=gagal"); exit;
    }
}

// --- PROSES HAPUS JADWAL ---
if(isset($_GET['hapus'])){
    $id_hapus = (int)$_GET['hapus'];
    mysqli_query($koneksi, "DELETE FROM jadwal_siswa WHERE IDJadwal='$id_hapus' AND IDSiswa='$id_siswa'");
    header("Location: jadwal.php?status=sukses_hapus"); exit;
}

// --- AMBIL DATA JADWAL SISWA ---
$hari_kerja = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
$jadwal_mingguan = [];
foreach($hari_kerja as $h) { $jadwal_mingguan[$h] = []; }

$q_jadwal = mysqli_query($koneksi, "SELECT * FROM jadwal_siswa WHERE IDSiswa='$id_siswa' ORDER BY JamMulai ASC");
while($row = mysqli_fetch_assoc($q_jadwal)){
    $jadwal_mingguan[$row['Hari']][] = $row;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jadwal & Agenda - LMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        :root {
            --primary: #1e1b4b;          /* Midnight Blue Peakt */
            --primary-dark: #100f28;     
            --primary-light: #e0e7ff;    /* Biru tipis untuk hover */
            --secondary: #3b82f6;        /* Slate Blue untuk aksen */
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
        .breadcrumb-modern a { color: var(--primary); text-decoration: none; transition: 0.2s; }
        
        /* Grid Jadwal Papan Kanban */
        .board-container { display: flex; gap: 15px; overflow-x: auto; padding-bottom: 20px; min-height: 600px; }
        .board-column { background: #e2e8f0; min-width: 280px; max-width: 320px; border-radius: 16px; padding: 15px; display: flex; flex-direction: column; }
        .board-header { font-weight: 800; color: var(--text-dark); margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid #cbd5e1; display: flex; justify-content: space-between; align-items: center;}
        
        .schedule-card { background: #fff; border-radius: 12px; padding: 15px; margin-bottom: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.02); transition: 0.2s; position: relative; border-left: 5px solid #ccc; cursor: grab;}
        .schedule-card:hover { transform: translateY(-3px); box-shadow: 0 8px 15px rgba(0,0,0,0.05); }
        .schedule-card:active { cursor: grabbing; }
        
        .time-badge { font-size: 0.75rem; font-weight: 700; color: #64748b; background: #f1f5f9; padding: 4px 10px; border-radius: 6px; display: inline-block; margin-bottom: 8px;}
        .kegiatan-text { font-weight: 700; color: var(--text-dark); font-size: 1.05rem; margin-bottom: 5px; line-height: 1.4;}
        
        .btn-delete { position: absolute; top: 10px; right: 10px; color: #cbd5e1; background: transparent; border: none; font-size: 1.1rem; transition: 0.2s;}
        .btn-delete:hover { color: #ef4444; }

        /* Varian Warna Pilihan Siswa */
        .border-biru { border-left-color: #3b82f6 !important; }
        .border-merah { border-left-color: #ef4444 !important; }
        .border-kuning { border-left-color: #f59e0b !important; }
        .border-hijau { border-left-color: #10b981 !important; }
        .border-ungu { border-left-color: #8b5cf6 !important; }
        .border-pink { border-left-color: #ec4899 !important; }

        /* Selector Warna di Form Modal */
        .color-selector input[type="radio"] { display: none; }
        .color-selector label { width: 35px; height: 35px; border-radius: 50%; cursor: pointer; display: inline-block; margin-right: 10px; border: 3px solid transparent; transition: 0.2s; }
        .color-selector input[type="radio"]:checked + label { border-color: #1e293b; transform: scale(1.1); box-shadow: 0 4px 10px rgba(0,0,0,0.2); }
        .bg-biru { background-color: #3b82f6; } .bg-merah { background-color: #ef4444; } .bg-kuning { background-color: #f59e0b; }
        .bg-hijau { background-color: #10b981; } .bg-ungu { background-color: #8b5cf6; } .bg-pink { background-color: #ec4899; }
        
        /* Custom Scrollbar untuk papan */
        .board-container::-webkit-scrollbar { height: 8px; }
        .board-container::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 10px; }
        .board-container::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        .board-container::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
</head>
<body>

    <?php include 'komponen_navbar.php'; ?>

    <div class="container-fluid px-0 flex-grow-1">
        <div class="row g-0">
            <?php include 'komponen_sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-4 py-4 pb-5">
                <div class="breadcrumb-modern">
                    <i class="bi bi-house-door-fill me-1"></i> <a href="siswa.php">Dashboard</a> <i class="bi bi-chevron-right mx-2 text-muted" style="font-size: 0.7rem;"></i> Jadwal & Agenda Pribadi
                </div>

                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-4 gap-3">
                    <div>
                        <h2 class="fw-bold mb-1 text-dark d-flex align-items-center gap-2">
                            <i class="bi bi-calendar-day-fill text-primary"></i> Perencana Jadwal
                        </h2>
                        <p class="text-muted mb-0">Rancang jadwal pelajaran dan agenda kegiatan pribadimu minggu ini.</p>
                    </div>
                    <button class="btn btn-primary fw-bold px-4 py-2 rounded-pill shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambahJadwal">
                        <i class="bi bi-plus-lg me-1"></i> Tambah Agenda Baru
                    </button>
                </div>

                <!-- PAPAN KANBAN JADWAL -->
                <div class="board-container">
                    <?php 
                    $hari_indo = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
                    foreach($hari_indo as $hari): 
                        $jumlah_kegiatan = count($jadwal_mingguan[$hari]);
                    ?>
                        <div class="board-column">
                            <div class="board-header">
                                <span class="fs-5 text-uppercase letter-spacing-1"><?= $hari ?></span>
                                <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary-subtle rounded-pill"><?= $jumlah_kegiatan ?> Agenda</span>
                            </div>
                            
                            <div class="board-body">
                                <?php if($jumlah_kegiatan == 0): ?>
                                    <div class="text-center py-4 opacity-50">
                                        <i class="bi bi-cup-hot fs-1 d-block mb-2"></i>
                                        <span class="small fw-bold">Hari Kosong</span>
                                    </div>
                                <?php else: ?>
                                    <?php foreach($jadwal_mingguan[$hari] as $j): ?>
                                        <div class="schedule-card border-<?= htmlspecialchars($j['WarnaLabel']) ?>">
                                            <a href="jadwal.php?hapus=<?= $j['IDJadwal'] ?>" class="btn-delete" onclick="return confirm('Hapus agenda ini?')"><i class="bi bi-x-circle-fill"></i></a>
                                            <div class="time-badge"><i class="bi bi-clock me-1"></i> <?= date('H:i', strtotime($j['JamMulai'])) ?> - <?= date('H:i', strtotime($j['JamSelesai'])) ?></div>
                                            <div class="kegiatan-text"><?= htmlspecialchars($j['Kegiatan']) ?></div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

            </main>
        </div>
    </div>

    <!-- MODAL TAMBAH JADWAL -->
    <div class="modal fade" id="modalTambahJadwal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 shadow-lg">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold text-dark"><i class="bi bi-journal-plus text-primary me-2"></i>Tambah Agenda</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <form action="" method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-bold text-secondary small">Nama Pelajaran / Kegiatan</label>
                            <input type="text" class="form-control bg-light" name="kegiatan" placeholder="Contoh: Praktikum Akuntansi Dasar" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold text-secondary small">Pilih Hari</label>
                            <select class="form-select bg-light fw-semibold" name="hari" required>
                                <option value="Senin">Senin</option>
                                <option value="Selasa">Selasa</option>
                                <option value="Rabu">Rabu</option>
                                <option value="Kamis">Kamis</option>
                                <option value="Jumat">Jumat</option>
                                <option value="Sabtu">Sabtu</option>
                            </select>
                        </div>
                        <div class="row mb-3">
                            <div class="col-6">
                                <label class="form-label fw-bold text-secondary small">Jam Mulai</label>
                                <input type="time" class="form-control bg-light" name="jam_mulai" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-bold text-secondary small">Jam Selesai</label>
                                <input type="time" class="form-control bg-light" name="jam_selesai" required>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-bold text-secondary small d-block">Label Warna</label>
                            <div class="color-selector">
                                <input type="radio" name="warna" id="c_biru" value="biru" checked>
                                <label for="c_biru" class="bg-biru"></label>
                                
                                <input type="radio" name="warna" id="c_hijau" value="hijau">
                                <label for="c_hijau" class="bg-hijau"></label>
                                
                                <input type="radio" name="warna" id="c_kuning" value="kuning">
                                <label for="c_kuning" class="bg-kuning"></label>
                                
                                <input type="radio" name="warna" id="c_merah" value="merah">
                                <label for="c_merah" class="bg-merah"></label>

                                <input type="radio" name="warna" id="c_ungu" value="ungu">
                                <label for="c_ungu" class="bg-ungu"></label>

                                <input type="radio" name="warna" id="c_pink" value="pink">
                                <label for="c_pink" class="bg-pink"></label>
                            </div>
                        </div>
                        <button type="submit" name="tambah_jadwal" class="btn btn-primary w-100 fw-bold py-2 rounded-3 shadow-sm">Simpan Agenda</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('status')) {
            const status = urlParams.get('status');
            if(status === 'sukses_tambah') {
                Swal.fire({ title: 'Tersimpan!', text: 'Jadwal barumu berhasil ditambahkan.', icon: 'success', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
            } else if(status === 'sukses_hapus') {
                Swal.fire({ title: 'Dihapus!', text: 'Agenda tersebut berhasil dihapus.', icon: 'info', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
            }
            window.history.replaceState(null, null, window.location.pathname);
        }
    </script>
</body>
</html>