<?php
session_start();
require '../login/koneksi.php';

if($_SESSION['role'] != 'admin'){
    header("Location: ../login/login.php");
    exit;
}

$where_clauses = [];
$search = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : '';
$filter_mapel = isset($_GET['mapel']) ? mysqli_real_escape_string($koneksi, $_GET['mapel']) : '';

if (!empty($search)) {
    $where_clauses[] = "(guru.NamaGuru LIKE '%$search%' OR guru.NIP_NUPTK LIKE '%$search%' OR guru.IDGuru LIKE '%$search%')";
}
if (!empty($filter_mapel)) {
    $where_clauses[] = "guru.MataPelajaran LIKE '%\"$filter_mapel\"%'";
}

$where_sql = count($where_clauses) > 0 ? "WHERE " . implode(' AND ', $where_clauses) : "";

// =====================================================================
// LOGIKA PAGINASI (MAX 20 DATA PER HALAMAN)
// =====================================================================
$batas = 20; 
$halaman = isset($_GET['halaman']) ? (int)$_GET['halaman'] : 1;
$halaman_awal = ($halaman > 1) ? ($halaman * $batas) - $batas : 0;

$query_total = mysqli_query($koneksi, "SELECT COUNT(IDGuru) AS total FROM guru $where_sql");
$data_total = mysqli_fetch_assoc($query_total);
$jumlah_data = $data_total['total'];
$total_halaman = ceil($jumlah_data / $batas);

$query_guru = mysqli_query($koneksi, "SELECT guru.*, users.LastAccess, users.Status FROM guru LEFT JOIN users ON guru.IDUser = users.IDUser $where_sql ORDER BY guru.NamaGuru ASC LIMIT $halaman_awal, $batas");
$jumlah_tampil = mysqli_num_rows($query_guru);

// Amankan parameter pencarian saat pindah halaman
$url_query = "";
if(!empty($search)) $url_query .= "&search=".urlencode($search);
if(!empty($filter_mapel)) $url_query .= "&mapel=".urlencode($filter_mapel);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Guru - Admin LMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../style.css">
    <style>
        body { background-color: #f8f9fa; }
        .header-red { background: #dc3545; color: white; padding: 2rem 0 3.8rem 0; margin: 0; position: relative; }
        .header-red h2 { margin: 0; font-weight: 700; font-size: 1.7rem; letter-spacing: -0.5px; }
        .main-content { margin-top: -2.8rem; position: relative; z-index: 10; }
        .filter-card { border: none; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); background: white; }
        
        /* PERBAIKAN: Menghapus overflow: hidden agar dropdown tidak memunculkan scrollbar */
        .table-card { border: none; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); background: white; }
        
        .table thead th { background-color: #f8f9fa; color: #6c757d; font-size: 0.75rem; text-transform: uppercase; font-weight: 700; padding: 12px 15px; border-bottom: 1px solid #eee; }
        .mapel-badge { font-size: 0.75rem; white-space: normal; text-align: left; display: inline-block; margin-bottom: 4px; }
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
                            <h2>Daftar Pengguna (Guru)</h2>
                            <a href="tambahGuru.php" class="btn btn-light btn-sm fw-bold px-3 text-danger shadow-sm border-0">
                                <i class="bi bi-person-plus-fill me-2"></i> Tambah Guru
                            </a>
                        </div>
                    </div>
                </div>

                <div class="container main-content pb-5">
                    
                    <div class="card filter-card mb-4">
                        <div class="card-body p-4">
                            <form action="" method="GET" class="row g-3 align-items-end">
                                <div class="col-md-5">
                                    <label class="form-label small fw-bold text-muted">Cari Nama / NIP</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-0"><i class="bi bi-search"></i></span>
                                        <input type="text" name="search" class="form-control bg-light border-0" placeholder="Ketik nama atau NIP guru..." value="<?= htmlspecialchars($search) ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-muted">Filter Mata Pelajaran</label>
                                    <select name="mapel" class="form-select bg-light border-0">
                                        <option value="">Semua Mata Pelajaran</option>
                                        <?php 
                                        $mapel_list = ['Akuntansi Dasar', 'Ekonomi Bisnis', 'Matematika', 'Bahasa Indonesia', 'Bahasa Inggris', 'Pendidikan Agama'];
                                        foreach($mapel_list as $m) {
                                            $selected = ($filter_mapel == $m) ? 'selected' : '';
                                            echo "<option value='$m' $selected>$m</option>";
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
                            <span class="fw-bold small"><i class="bi bi-check2-circle me-2"></i> <span id="selectedCountText">0</span> guru dipilih</span>
                            <button type="button" class="btn btn-danger btn-sm fw-bold px-3" onclick="hapusMassal()">Hapus Massal</button>
                        </div>
                    </div>

                    <div class="card table-card">
                        <div class="table-responsive" style="overflow: visible;">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-center" style="width: 45px;"><input class="form-check-input" type="checkbox" id="checkAll"></th>
                                        <th>NAMA GURU</th><th>NIP / KODE</th><th style="min-width: 250px;">KELAS & MATA PELAJARAN</th><th>EMAIL</th><th>LAST AKSES</th><th class="text-end pe-4">AKSI</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if($jumlah_tampil > 0): while($row = mysqli_fetch_assoc($query_guru)): ?>
                                        <tr>
                                            <td class="text-center"><input class="form-check-input checkItem" type="checkbox" name="ids[]" value="<?= $row['IDGuru'] ?>"></td>
                                            <td>
                                                <div class="fw-bold text-dark"><?= $row['NamaGuru'] ?></div>
                                                <div class="text-muted" style="font-size: 0.7rem;">ID: <?= $row['IDGuru'] ?></div>
                                            </td>
                                            <td class="text-muted fw-semibold small"><?= !empty($row['NIP_NUPTK']) ? $row['NIP_NUPTK'] : '-' ?></td>
                                            
                                            <td>
                                                <?php 
                                                $akses = json_decode($row['MataPelajaran'], true);
                                                if(!empty($akses) && is_array($akses)) {
                                                    foreach($akses as $kelas => $mapels) {
                                                        $daftar_mapel = implode(", ", $mapels);
                                                        echo "<div class='badge bg-light text-dark border mapel-badge'>";
                                                        echo "<span class='text-danger fw-bold'>$kelas:</span> $daftar_mapel";
                                                        echo "</div><br>";
                                                    }
                                                } else {
                                                    echo "<span class='text-muted small fst-italic'>Belum ada jadwal</span>";
                                                }
                                                ?>
                                            </td>

                                            <td class="text-muted small"><?= !empty($row['Email']) ? $row['Email'] : '-' ?></td>
                                            <td class="small text-muted"><?= $row['LastAccess'] ? date('d M, H:i', strtotime($row['LastAccess'])) : '<span class="text-warning">Belum Login</span>' ?></td>
                                            <td class="text-end pe-4">
                                                <div class="dropdown">
                                                    <button class="btn btn-link text-secondary p-0" type="button" data-bs-toggle="dropdown" aria-expanded="false" data-bs-boundary="window">
                                                        <i class="bi bi-three-dots-vertical fs-5"></i>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                                                        <li><a class="dropdown-item py-2" href="editGuru.php?id=<?= $row['IDGuru'] ?>"><i class="bi bi-pencil-square me-2 text-primary"></i> Edit Data</a></li>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li><a class="dropdown-item py-2 text-danger" href="#" onclick="konfirmasiHapus('<?= $row['IDGuru'] ?>', '<?= addslashes($row['NamaGuru']) ?>')"><i class="bi bi-trash3 me-2"></i> Hapus Permanen</a></li>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; else: ?>
                                        <tr><td colspan="7" class="text-center py-5 text-muted">Data guru tidak ditemukan.</td></tr>
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

    if(checkAll) {
        checkAll.addEventListener('change', function() { checkItems.forEach(item => item.checked = this.checked); updateCounter(); });
    }
    
    checkItems.forEach(item => { item.addEventListener('change', updateCounter); });

    function updateCounter() {
        const checkedCount = document.querySelectorAll('.checkItem:checked').length;
        countText.innerText = checkedCount;
        if (checkedCount > 0) { bulkArea.classList.remove('d-none'); } 
        else { bulkArea.classList.add('d-none'); if(checkAll) checkAll.checked = false; }
    }

    // Fungsi Konfirmasi Hapus yang memicu SweetAlert
    function konfirmasiHapus(id, nama) {
        Swal.fire({
            title: 'Hapus ' + nama + '?', 
            text: "Data guru akan dihapus permanen!", 
            icon: 'warning', 
            showCancelButton: true, 
            confirmButtonColor: '#dc3545', 
            confirmButtonText: 'Ya, Hapus'
        }).then((result) => { 
            if (result.isConfirmed) { 
                window.location.href = 'hapusGuru.php?id=' + id; 
            } 
        })
    }

    function hapusMassal() {
        const selectedIds = Array.from(document.querySelectorAll('.checkItem:checked')).map(cb => cb.value);
        Swal.fire({
            title: 'Hapus ' + selectedIds.length + ' Guru?', text: "Tindakan ini tidak bisa dibatalkan!", icon: 'error', showCancelButton: true, confirmButtonColor: '#dc3545', confirmButtonText: 'Ya, Hapus Semua'
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.createElement('form'); form.method = 'POST'; form.action = 'hapusGuruMassal.php';
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
            let status = '<?= $_GET['status'] ?>';
            let params = new URLSearchParams(window.location.search);
            
            if(status === 'sukses_tambah') {
                Swal.fire({ title: 'Berhasil!', text: 'Data guru baru berhasil ditambahkan.', icon: 'success', timer: 3000, showConfirmButton: false });
            } else if(status === 'sukses_edit') {
                Swal.fire({ title: 'Berhasil!', text: 'Perubahan data berhasil disimpan.', icon: 'success', timer: 3000, showConfirmButton: false });
            } else if(status === 'sukses_hapus') {
                Swal.fire({ title: 'Terhapus!', text: 'Data berhasil dihapus secara permanen.', icon: 'success', timer: 3000, showConfirmButton: false });
            } else if(status === 'gagal_hapus') {
                Swal.fire({ title: 'Gagal!', text: 'Terjadi kesalahan saat menghapus data.', icon: 'error', timer: 3000, showConfirmButton: false });
            } else if(status === 'info_upload') {
                let ok = params.get('ok'); let fail = params.get('fail');
                if (fail > 0) {
                    Swal.fire({ title: 'Upload Selesai!', html: `<b class="text-success">${ok} Data Berhasil</b> disimpan.<br><b class="text-danger">${fail} Data Gagal</b> karena typo/format kelas tidak sesuai.`, icon: 'warning', confirmButtonColor: '#0f6cb6' });
                } else {
                    Swal.fire({ title: 'Upload Sukses!', text: `${ok} Data guru berhasil diupload tanpa ada kesalahan.`, icon: 'success', timer: 4000, showConfirmButton: false });
                }
            }
            window.history.replaceState(null, null, window.location.pathname);
        </script>
    <?php endif; ?>

</body>
</html>