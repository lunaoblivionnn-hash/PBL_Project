<?php
// Buka kunci session
session_start();

// Cek apakah yang masuk benar-benar admin
if($_SESSION['role'] != 'admin'){
    header("Location: ../login/login.php");
    exit;
}

include '../login/koneksi.php';

// CEK STATUS PEMELIHARAAN (MAINTENANCE) DARI DATABASE
$cek_maint = mysqli_query($koneksi, "SELECT Nilai FROM pengaturan WHERE Kunci = 'maintenance'");
$maint = mysqli_fetch_assoc($cek_maint);
$is_maintenance = (isset($maint['Nilai']) && $maint['Nilai'] == '1') ? true : false;

// 1. Hitung Total Siswa
$query_siswa = mysqli_query($koneksi, "SELECT COUNT(IDSiswa) AS total_siswa FROM SISWA");
$data_siswa = mysqli_fetch_assoc($query_siswa);
$angka_siswa = $data_siswa['total_siswa'];

// 2. Hitung Total Guru
$query_guru = mysqli_query($koneksi, "SELECT COUNT(IDGuru) AS total_guru FROM GURU");
$data_guru = mysqli_fetch_assoc($query_guru);
$angka_guru = $data_guru['total_guru'];

// 3. Hitung Total Mata Pelajaran
$query_mapel = mysqli_query($koneksi, "SELECT COUNT(DISTINCT NamaMapel) AS total_mapel FROM MAPEL");
$data_mapel = mysqli_fetch_assoc($query_mapel);
$angka_mapel = $data_mapel['total_mapel'];

// 4. Hitung Total Tugas
$query_tugas = mysqli_query($koneksi, "SELECT COUNT(IDTugas) AS total_tugas FROM TUGAS");
$data_tugas = mysqli_fetch_assoc($query_tugas);
$angka_tugas = $data_tugas['total_tugas'];

// ================= PAGINATION SISWA BARU =================
$batas = 10; 
$halaman = isset($_GET['halaman']) ? (int)$_GET['halaman'] : 1;
$halaman_awal = ($halaman > 1) ? ($halaman * $batas) - $batas : 0;

$query_total = mysqli_query($koneksi, "SELECT COUNT(IDSiswa) AS total FROM SISWA");
$data_total = mysqli_fetch_assoc($query_total);
$jumlah_data = $data_total['total'];
$total_halaman = ceil($jumlah_data / $batas);

// FIX: Menggabungkan (JOIN) tabel SISWA dan USERS agar status akun asli bisa ditarik
$query_siswa_baru = mysqli_query($koneksi, "SELECT SISWA.*, users.Status FROM SISWA LEFT JOIN users ON SISWA.IDUser = users.IDUser ORDER BY SISWA.dibuat_pada DESC, SISWA.IDSiswa DESC LIMIT $halaman_awal, $batas");

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - LMS SMKN 1 Wongsorejo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../style.css">
    <style>
        .menu-title { font-size: 0.75rem; font-weight: 700; color: #adb5bd; text-transform: uppercase; letter-spacing: 1px; margin: 25px 0 10px 10px; }
    </style>
</head>
<body class="bg-light">

    <nav class="navbar navbar-expand-lg navbar-dark bg-danger sticky-top shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="#"> 
                <i class="bi bi-shield-lock-fill me-2"></i> PANEL ADMIN LMS
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="topNav">
                <ul class="navbar-nav align-items-center">
                    <li class="nav-item me-3">
                        <button class="btn btn-danger btn-sm border border-light" id="btnDarkMode" title="Ganti Tema">
                            <i class="bi bi-moon-fill text-white"></i>
                        </button>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center text-white" href="#" data-bs-toggle="dropdown">
                            <img src="https://ui-avatars.com/api/?name=Administrator&background=fff&color=dc3545" class="rounded-circle me-2 border border-2 border-white" width="30" height="30">
                            Administrator
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow">
                            <li><a class="dropdown-item" href="#"><i class="bi bi-person-circle me-2"></i>Profil Admin</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="../login/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            
        <?php include 'sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="fw-bold">Ikhtisar Sistem</h3>
                    <span class="text-muted"><i class="bi bi-calendar3 me-2"></i> <?php echo date('d M Y'); ?></span>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="card border-0 shadow-sm border-start border-primary border-4 h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="text-muted small fw-bold mb-1">TOTAL SISWA</div>
                                        <h2 class="fw-bold mb-0"><?php echo $angka_siswa; ?></h2>
                                    </div>
                                    <div class="bg-primary bg-opacity-10 p-3 rounded">
                                        <i class="bi bi-people-fill text-primary fs-3"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="card border-0 shadow-sm border-start border-success border-4 h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="text-muted small fw-bold mb-1">TOTAL GURU</div>
                                        <h2 class="fw-bold mb-0"><?php echo $angka_guru; ?></h2>
                                    </div>
                                    <div class="bg-success bg-opacity-10 p-3 rounded">
                                        <i class="bi bi-person-badge-fill text-success fs-3"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="card border-0 shadow-sm border-start border-warning border-4 h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="text-muted small fw-bold mb-1">MATA PELAJARAN</div>
                                        <h2 class="fw-bold mb-0"><?php echo $angka_mapel; ?></h2>
                                    </div>
                                    <div class="bg-warning bg-opacity-10 p-3 rounded">
                                        <i class="bi bi-book-half text-warning fs-3"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="card border-0 shadow-sm border-start border-danger border-4 h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="text-muted small fw-bold mb-1">TUGAS AKTIF</div>
                                        <h2 class="fw-bold mb-0"><?php echo $angka_tugas; ?></h2>
                                    </div>
                                    <div class="bg-danger bg-opacity-10 p-3 rounded">
                                        <i class="bi bi-file-earmark-text-fill text-danger fs-3"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-lg-8">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-white fw-bold py-3 d-flex justify-content-between align-items-center">
                                <span><i class="bi bi-person-lines-fill me-2 text-primary"></i> Pengguna Baru (Siswa)</span>
                                <a href="daftarSiswa.php" class="btn btn-sm btn-outline-primary" >Lihat Semua</a>
                            </div>
                            
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="ps-4">NISN</th>
                                                <th>Nama Lengkap</th>
                                                <th>Kelas</th>
                                                <th>Status Akun</th>
                                                <th class="pe-4 text-end">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php 
                                        if(mysqli_num_rows($query_siswa_baru) > 0) {
                                            while($row = mysqli_fetch_assoc($query_siswa_baru)) {
                                                
                                                // CEK LOGIKA STATUS AKUN
                                                $status_asli = isset($row['Status']) ? $row['Status'] : 'Aktif';
                                                
                                                if ($is_maintenance) {
                                                    // Jika server sedang perbaikan, paksa tampilan status menjadi Maintenance
                                                    $status_badge = '<span class="badge bg-warning bg-opacity-10 text-warning border border-warning"><i class="bi bi-lock-fill me-1"></i> Maintenance</span>';
                                                } else if($status_asli == 'Non-Aktif') {
                                                    $status_badge = '<span class="badge bg-danger bg-opacity-10 text-danger border border-danger">Non-Aktif</span>';
                                                } else {
                                                    $status_badge = '<span class="badge bg-success bg-opacity-10 text-success border border-success">Aktif</span>';
                                                }

                                                $id_siswa_asli = isset($row['IDSiswa']) ? $row['IDSiswa'] : '';
                                                $nisn_tampil = isset($row['NISN']) ? $row['NISN'] : '-';
                                                $nama_siswa = isset($row['NamaSiswa']) ? $row['NamaSiswa'] : 'Tanpa Nama';
                                                $kelas = isset($row['Kelas']) ? $row['Kelas'] : '-';
                                        ?>
                                        <tr>
                                            <td class="ps-4"><span class="badge bg-secondary"><?php echo $nisn_tampil; ?></span></td>
                                            <td class="fw-semibold"><?php echo $nama_siswa; ?></td>
                                            <td><?php echo $kelas; ?></td>
                                            <td><?php echo $status_badge; ?></td>
                                            <td class="pe-4 text-end">
                                                <a href="editSiswa.php?id=<?php echo $id_siswa_asli; ?>" class="btn btn-sm btn-light text-primary" title="Edit Data"><i class="bi bi-pencil-square"></i></a>
                                            </td>
                                        </tr>
                                        <?php 
                                            } 
                                        } else {
                                            echo '<tr><td colspan="5" class="text-center py-4 text-muted">Belum ada data siswa baru yang terdaftar.</td></tr>';
                                        }
                                        ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="card-footer bg-white py-3 border-top-0">
                                <nav aria-label="Page navigation">
                                    <ul class="pagination justify-content-center mb-0">
                                        <li class="page-item <?= ($halaman <= 1) ? 'disabled' : '' ?>">
                                            <a class="page-link" href="?halaman=<?= $halaman - 1 ?>">Previous</a>
                                        </li>
                                        <?php for($x = 1; $x <= $total_halaman; $x++): ?>
                                            <li class="page-item <?= ($halaman == $x) ? 'active' : '' ?>">
                                                <a class="page-link" href="?halaman=<?= $x ?>"><?= $x ?></a>
                                            </li>
                                        <?php endfor; ?>
                                        <li class="page-item <?= ($halaman >= $total_halaman) ? 'disabled' : '' ?>">
                                            <a class="page-link" href="?halaman=<?= $halaman + 1 ?>">Next</a>
                                        </li>
                                    </ul>
                                </nav>
                            </div>
                            
                        </div>
                    </div> 
                    
                    <div class="col-lg-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-white fw-bold py-3">
                                <i class="bi bi-lightning-charge-fill me-2 text-warning"></i> Aksi Cepat
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-3">
                                    <a href="tambahSiswa.php" class="btn btn-primary text-start py-3 shadow-sm text-decoration-none">
                                        <i class="bi bi-person-plus-fill me-3 fs-5"></i> Tambah Siswa Baru
                                    </a>
                                    <a href="tambahGuru.php" class="btn btn-success text-start py-3 shadow-sm text-decoration-none">
                                        <i class="bi bi-person-badge-fill me-3 fs-5"></i> Tambah Guru Baru
                                    </a>    
                                    <a href="tambahMapel.php" class="btn btn-warning text-dark text-start py-3 shadow-sm text-decoration-none">
                                         <i class="bi bi-journal-plus me-3 fs-5"></i> Buat Mata Pelajaran
                                    </a>
                                </div>
                                
                                <hr class="my-4">
                                <h6 class="fw-bold mb-3 small text-muted">SISTEM SERVER</h6>
                                <div class="d-flex align-items-center justify-content-between <?= $is_maintenance ? 'bg-danger bg-opacity-10 border border-danger' : 'bg-light border' ?> p-3 rounded">
                                    <div class="d-flex align-items-center">
                                        <i class="bi <?= $is_maintenance ? 'bi-exclamation-triangle-fill text-danger' : 'bi-hdd-network-fill text-secondary' ?> fs-3 me-3"></i>
                                        <div>
                                            <h6 class="mb-0 fw-bold <?= $is_maintenance ? 'text-danger' : 'text-dark' ?>">Database Status</h6>
                                            <?php if($is_maintenance): ?>
                                                <small class="text-danger fw-bold"><i class="bi bi-lock-fill small me-1"></i> Mode Perbaikan</small>
                                            <?php else: ?>
                                                <small class="text-success"><i class="bi bi-circle-fill small me-1"></i> Terhubung (Online)</small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div> 
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
</body>
</html>