<?php
session_start();
require '../login/koneksi.php';

if($_SESSION['role'] != 'admin'){
    header("Location: ../login/login.php");
    exit;
}

$where_clauses = [];
$search = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : '';
$filter_kelas = isset($_GET['kelas']) ? mysqli_real_escape_string($koneksi, $_GET['kelas']) : '';

if (!empty($search)) { $where_clauses[] = "(SISWA.NamaSiswa LIKE '%$search%' OR SISWA.NISN LIKE '%$search%' OR SISWA.IDSiswa LIKE '%$search%')"; }
if (!empty($filter_kelas)) { $where_clauses[] = "SISWA.Kelas = '$filter_kelas'"; }
$where_sql = count($where_clauses) > 0 ? "WHERE " . implode(' AND ', $where_clauses) : "";

// =====================================================================
// LOGIKA PAGINASI (MAX 20 DATA PER HALAMAN)
// =====================================================================
$batas = 20; 
$halaman = isset($_GET['halaman']) ? (int)$_GET['halaman'] : 1;
$halaman_awal = ($halaman > 1) ? ($halaman * $batas) - $batas : 0;

$query_total = mysqli_query($koneksi, "SELECT COUNT(IDSiswa) AS total FROM SISWA $where_sql");
$data_total = mysqli_fetch_assoc($query_total);
$jumlah_data = $data_total['total'];
$total_halaman = ceil($jumlah_data / $batas);

$query_siswa = mysqli_query($koneksi, "SELECT SISWA.*, USERS.LastAccess, USERS.Status FROM SISWA LEFT JOIN USERS ON SISWA.IDUser = USERS.IDUser $where_sql ORDER BY SISWA.NamaSiswa ASC LIMIT $halaman_awal, $batas");
$jumlah_tampil = mysqli_num_rows($query_siswa);

// Amankan parameter pencarian saat pindah halaman
$url_query = "";
if(!empty($search)) $url_query .= "&search=".urlencode($search);
if(!empty($filter_kelas)) $url_query .= "&kelas=".urlencode($filter_kelas);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Siswa - Admin LMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../style.css">
    <style>
        body { background-color: #f8f9fa; }
        .header-red { background: #dc3545; color: white; padding: 2rem 0 3.8rem 0; margin: 0; position: relative; }
        .header-red h2 { margin: 0; font-weight: 700; font-size: 1.7rem; letter-spacing: -0.5px; }
        .main-content { margin-top: -2.8rem; position: relative; z-index: 10; }
        .filter-card { border: none; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); background: white; }
        .table-card { border: none; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); background: white; overflow: hidden; }
        .table thead th { background-color: #f8f9fa; color: #6c757d; font-size: 0.75rem; text-transform: uppercase; font-weight: 700; padding: 12px 15px; border-bottom: 1px solid #eee; }
    </style>
</head>
<body class="bg-light">

    <nav class="navbar navbar-expand-lg navbar-dark bg-danger sticky-top shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="#"> 
                <i class="bi bi-shield-lock-fill me-2"></i> PANEL ADMIN LMS
            </a>
            <div class="collapse navbar-collapse justify-content-end" id="topNav">
                <ul class="navbar-nav align-items-center">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center text-white" href="#" data-bs-toggle="dropdown">
                            <img src="https://ui-avatars.com/api/?name=Administrator&background=fff&color=dc3545" class="rounded-circle me-2 border border-2 border-white" width="30" height="30">
                            Administrator
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow">
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

            <main class="col-md-9 ms-sm-auto col-lg-10 px-0">
                
                <div class="header-red">
                    <div class="container">
                        <div class="d-flex justify-content-between align-items-center">
                            <h2>Daftar Pengguna (Siswa)</h2>
                            <a href="tambahSiswa.php" class="btn btn-light btn-sm fw-bold px-3 text-danger shadow-sm border-0">
                                <i class="bi bi-person-plus-fill me-2"></i> Tambah Siswa
                            </a>
                        </div>
                    </div>
                </div>

                <div class="container main-content pb-5">
                    
                    <div class="card filter-card mb-4">
                        <div class="card-body p-4">
                            <form action="" method="GET" class="row g-3 align-items-end">
                                <div class="col-md-5">
                                    <label class="form-label small fw-bold text-muted">Cari Nama / NISN</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-0"><i class="bi bi-search"></i></span>
                                        <input type="text" name="search" class="form-control bg-light border-0" placeholder="Ketik nama atau NISN..." value="<?= htmlspecialchars($search) ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-muted">Filter Kelas</label>
                                    <select name="kelas" class="form-select bg-light border-0">
                                        <option value="">Semua Kelas</option>
                                        <?php 
                                        $kelas_list = ['X AKL 1', 'X AKL 2', 'XI AKL 1', 'XI AKL 2', 'XII AKL 1', 'XII AKL 2'];
                                        foreach($kelas_list as $k) {
                                            $selected = ($filter_kelas == $k) ? 'selected' : '';
                                            echo "<option value='$k' $selected>$k</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <button type="submit" class="btn btn-dark w-100 fw-bold py-2 shadow-sm">Terapkan</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div id="bulkActionArea" class="mb-3 d-none">
                        <div class="alert alert-danger d-flex justify-content-between align-items-center py-2 px-3 border-0 shadow-sm">
                            <span class="fw-bold small"><i class="bi bi-check2-circle me-2"></i> <span id="selectedCountText">0</span> siswa dipilih</span>
                            <button type="button" class="btn btn-danger btn-sm fw-bold px-3" onclick="hapusMassal()">Hapus Massal</button>
                        </div>
                    </div>

                    <div class="card table-card">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-center" style="width: 45px;"><input class="form-check-input" type="checkbox" id="checkAll"></th>
                                        <th>NAMA SISWA</th><th>NISN</th><th>KELAS</th><th>EMAIL</th><th>LAST AKSES</th><th class="text-end pe-4">AKSI</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if($jumlah_tampil > 0): while($row = mysqli_fetch_assoc($query_siswa)): ?>
                                        <tr>
                                            <td class="text-center"><input class="form-check-input checkItem" type="checkbox" name="ids[]" value="<?= $row['IDSiswa'] ?>"></td>
                                            <td>
                                                <div class="fw-bold text-dark"><?= $row['NamaSiswa'] ?></div>
                                                <div class="text-muted" style="font-size: 0.7rem;">ID: <?= $row['IDSiswa'] ?></div>
                                            </td>
                                            <td class="text-muted fw-semibold small"><?= $row['NISN'] ?></td>
                                            <td><span class="badge bg-light text-dark border fw-normal px-3"><?= $row['Kelas'] ?></span></td>
                                            <td class="text-muted small"><?= $row['Email'] ?: '-' ?></td>
                                            <td class="small text-muted"><?= $row['LastAccess'] ? date('d M, H:i', strtotime($row['LastAccess'])) : '<span class="text-warning">Belum Login</span>' ?></td>
                                            <td class="text-end pe-4">
                                                <div class="dropdown">
                                                    <button class="btn btn-light btn-sm border-0 bg-transparent" type="button" data-bs-toggle="dropdown"><i class="bi bi-three-dots-vertical"></i></button>
                                                    <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                                                        <li><a class="dropdown-item py-2" href="editSiswa.php?id=<?= $row['IDSiswa'] ?>"><i class="bi bi-pencil-square me-2 text-primary"></i> Edit Data</a></li>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li><a class="dropdown-item py-2 text-danger" href="#" onclick="konfirmasiHapus('<?= $row['IDSiswa'] ?>', '<?= $row['NamaSiswa'] ?>')"><i class="bi bi-trash3 me-2"></i> Hapus Permanen</a></li>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; else: ?>
                                        <tr><td colspan="7" class="text-center py-5 text-muted">Data siswa tidak ditemukan.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <?php if($total_halaman > 1): ?>
                        <div class="card-footer bg-white py-3 border-top-0 border-top">
                            <nav aria-label="Page navigation">
                                <ul class="pagination justify-content-center mb-0">
                                    <li class="page-item <?= ($halaman <= 1) ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?halaman=<?= $halaman - 1 ?><?= $url_query ?>">Previous</a>
                                    </li>

                                    <?php for($x = 1; $x <= $total_halaman; $x++): ?>
                                        <li class="page-item <?= ($halaman == $x) ? 'active' : '' ?>">
                                            <a class="page-link" href="?halaman=<?= $x ?><?= $url_query ?>"><?= $x ?></a>
                                        </li>
                                    <?php endfor; ?>

                                    <li class="page-item <?= ($halaman >= $total_halaman) ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?halaman=<?= $halaman + 1 ?><?= $url_query ?>">Next</a>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                        <?php endif; ?>

                    </div>
                </div>

            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    const checkAll = document.getElementById('checkAll');
    const checkItems = document.querySelectorAll('.checkItem');
    const bulkArea = document.getElementById('bulkActionArea');
    const countText = document.getElementById('selectedCountText');

    checkAll.addEventListener('change', function() { checkItems.forEach(item => item.checked = this.checked); updateCounter(); });
    checkItems.forEach(item => { item.addEventListener('change', updateCounter); });

    function updateCounter() {
        const checkedCount = document.querySelectorAll('.checkItem:checked').length;
        countText.innerText = checkedCount;
        if (checkedCount > 0) { bulkArea.classList.remove('d-none'); } 
        else { bulkArea.classList.add('d-none'); checkAll.checked = false; }
    }

    function konfirmasiHapus(id, nama) {
        Swal.fire({
            title: 'Hapus ' + nama + '?', text: "Data akan dihapus permanen!", icon: 'warning', showCancelButton: true, confirmButtonColor: '#dc3545', confirmButtonText: 'Ya, Hapus'
        }).then((result) => { if (result.isConfirmed) { window.location.href = 'hapusSiswa.php?id=' + id; } })
    }

    function hapusMassal() {
        const selectedIds = Array.from(document.querySelectorAll('.checkItem:checked')).map(cb => cb.value);
        Swal.fire({
            title: 'Hapus ' + selectedIds.length + ' Siswa?', text: "Tindakan ini tidak bisa dibatalkan!", icon: 'error', showCancelButton: true, confirmButtonColor: '#dc3545', confirmButtonText: 'Ya, Hapus Semua'
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.createElement('form'); form.method = 'POST'; form.action = 'hapusSiswaMassal.php';
                selectedIds.forEach(id => {
                    const input = document.createElement('input'); input.type = 'hidden'; input.name = 'ids[]'; input.value = id; form.appendChild(input);
                });
                document.body.appendChild(form); form.submit();
            }
        });
    }
    </script>
    
    <?php if(isset($_GET['status'])): ?>
        <script>
            let status = '<?= $_GET['status'] ?>'; let pesan = '';
            if(status === 'sukses_tambah') pesan = 'Data baru berhasil ditambahkan!';
            else if(status === 'sukses_edit') pesan = 'Perubahan data berhasil disimpan!';
            else if(status === 'sukses_hapus') pesan = 'Data berhasil dihapus secara permanen.';
            else if(status === 'sukses_upload') pesan = 'Upload data massal berhasil diproses!';
            else if(status === 'sukses_hapus_massal') pesan = 'Data massal berhasil dihapus!';

            if(pesan !== '') {
                Swal.fire({ title: 'Berhasil!', text: pesan, icon: 'success', confirmButtonColor: '#198754', timer: 3000, showConfirmButton: false });
                window.history.replaceState(null, null, window.location.pathname);
            }
        </script>
    <?php endif; ?>
</body>
</html>