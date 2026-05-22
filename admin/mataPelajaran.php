<?php
session_start();
require '../login/koneksi.php';

if($_SESSION['role'] != 'admin'){
    header("Location: ../login/login.php");
    exit;
}

$kelas_list = ['X AKL 1', 'X AKL 2', 'XI AKL 1', 'XI AKL 2', 'XII AKL 1', 'XII AKL 2'];

// Ambil seluruh daftar siswa untuk keperluan modal pilihan centang anggota kelas
$query_semua_siswa = mysqli_query($koneksi, "SELECT * FROM siswa ORDER BY NamaSiswa ASC");
$siswa_list = [];
while($row = mysqli_fetch_assoc($query_semua_siswa)) {
    $siswa_list[] = $row;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Mata Pelajaran - Admin LMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; }
        
        .header-red { background: #dc3545; color: white; padding: 2rem 0 5rem 0; margin: 0; position: relative; }
        .header-red h2 { margin: 0; font-weight: 700; font-size: 1.7rem; letter-spacing: -0.5px; }
        .main-content { margin-top: -2.8rem; position: relative; z-index: 10; }

        .class-card {
            border: none;
            border-radius: 15px;
            transition: all 0.3s ease;
            cursor: pointer;
            overflow: hidden;
        }
        .class-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.1) !important;
        }

        /* CONTAINER SCROLL UNTUK KEDUA MODAL */
        .table-scroll-container {
            max-height: 320px; 
            overflow-y: auto; 
            overflow-x: hidden;
            border: 1px solid #e9ecef;
            border-radius: 10px;
        }

        /* CONFIGURASI SEARAH DENGAN SIDEBAR DARK MODE */
        [data-bs-theme="dark"] body { background-color: #121212 !important; }
        [data-bs-theme="dark"] .class-card { background-color: #1e1e1e !important; border-color: #333 !important; }
        [data-bs-theme="dark"] .class-card h4 { color: #e0e0e0 !important; }
        [data-bs-theme="dark"] .text-dark { color: #e0e0e0 !important; }
        [data-bs-theme="dark"] .modal-content { background-color: #1e1e1e !important; color: #e0e0e0 !important; }
        [data-bs-theme="dark"] .modal-header { border-bottom: 1px solid #333 !important; }
        [data-bs-theme="dark"] .modal-footer { background-color: #1a1a1a !important; border-top: 1px solid #333 !important; }
        [data-bs-theme="dark"] .table-light { --bs-table-bg: #2b2b2b; --bs-table-color: #adb5bd; }
        [data-bs-theme="dark"] .siswa-row:hover td { background-color: #252525 !important; }
        [data-bs-theme="dark"] .table-scroll-container { border-color: #333 !important; }
        [data-bs-theme="dark"] .form-control, [data-bs-theme="dark"] .form-select, [data-bs-theme="dark"] .input-group-text { background-color: #2b2b2b !important; border-color: #444 !important; color: #e0e0e0 !important; }
        [data-bs-theme="dark"] .btn-light { background-color: #343a40 !important; border-color: #444 !important; color: #fff !important; }
    </style>
</head>
<body class="bg-light">

    <nav class="navbar navbar-expand-lg navbar-dark bg-danger sticky-top shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="admin.php">
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
                            <div>
                                <h2>Kelola Mata Pelajaran per Kelas</h2>
                                <p class="mb-0 text-white-50 small">Pilih rumpun kelas untuk mengonfigurasi kurikulum mata pelajaran atau mengatur daftar siswa.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="container main-content pb-5">
                    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                        <?php foreach($kelas_list as $kelas): 
                            // Hitung jumlah mapel di kelas ini secara real-time
                            $q_count = mysqli_query($koneksi, "SELECT COUNT(IDMapel) as total FROM mapel WHERE Kelas LIKE '%\"$kelas\"%'");
                            $d_count = mysqli_fetch_assoc($q_count);
                            $count = $d_count['total'];
                        ?>
                        <div class="col">
                            <div class="card class-card h-100 bg-white shadow-sm border position-relative" onclick="window.location='detailKelas.php?kelas=<?= urlencode($kelas) ?>'">
                                <div class="card-body p-4">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div class="bg-danger bg-opacity-10 text-danger rounded-3 p-3">
                                            <i class="bi bi-building fs-3"></i>
                                        </div>
                                        
                                        <div class="d-flex gap-1">
                                            <button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-2 shadow-sm" 
                                                    onclick="event.stopPropagation(); openUploadModal('<?= $kelas ?>')">
                                                <i class="bi bi-person-plus-fill"></i> + Siswa
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-2 shadow-sm" 
                                                    onclick="event.stopPropagation(); openLihatModal('<?= $kelas ?>')">
                                                <i class="bi bi-eye-fill"></i> Lihat
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <h4 class="fw-bold text-dark mb-1"><?= $kelas ?></h4>
                                    <p class="text-muted small mb-3">Jurusan Akuntansi & Keuangan Lembaga</p>
                                    
                                    <div class="border-top pt-3 d-flex justify-content-between align-items-center">
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary fw-normal">
                                            <i class="bi bi-book me-1"></i> <?= $count ?> Mata Pelajaran
                                        </span>
                                        <i class="bi bi-arrow-right text-muted"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <div class="modal fade" id="modalUploadSiswa" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content shadow-lg border-0 rounded-4">
                <div class="modal-header bg-danger text-white p-4">
                    <h5 class="modal-title fw-bold" id="modalTitleKelas">
                        <i class="bi bi-people-fill me-2"></i> Atur Anggota Kelas
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <form action="proses_tambah_siswa_kelas.php" method="POST">
                    <div class="modal-body p-4">
                        
                        <input type="hidden" name="kelas_target" id="inputKelasTarget">

                        <div class="alert alert-info border-0 small mb-4">
                            <i class="bi bi-info-circle-fill me-2"></i> 
                            Siswa yang dicentang di bawah ini akan dimasukkan ke kelas target setelah menekan tombol simpan. Gunakan fitur filter untuk mempercepat pemilihan.
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-7">
                                <label class="form-label small fw-bold text-muted">Cari Nama Siswa</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                                    <input type="text" id="searchSiswa" class="form-control bg-light border-start-0" placeholder="Ketik nama siswa..." onkeyup="filterSiswaDanKelas()">
                                </div>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label small fw-bold text-muted">Filter Kelas Asal</label>
                                <select id="filterKelasSiswa" class="form-select bg-light" onchange="filterSiswaDanKelas()">
                                    <option value="SEMUA">Tampilkan Semua Siswa</option>
                                    <option value="KOSONG">Belum Memiliki Kelas</option>
                                    <?php foreach($kelas_list as $kls_opsi): ?>
                                        <option value="<?= $kls_opsi ?>"><?= $kls_opsi ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="table-scroll-container">
                            <table class="table table-hover align-middle mb-0" id="tableSiswaModal">
                                <thead class="table-light sticky-top" style="z-index: 5;">
                                    <tr class="small fw-bold text-secondary">
                                        <th width="12%" class="text-center">
                                            <input class="form-check-input" type="checkbox" id="selectAllSiswa" onclick="toggleSelectAll(this)" title="Pilih semua siswa yang tampil">
                                        </th>
                                        <th width="58%">Nama Lengkap Siswa</th>
                                        <th width="30%">Kelas Saat Ini</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(empty($siswa_list)): ?>
                                        <tr>
                                            <td colspan="3" class="text-center text-muted py-4">Tidak ada data siswa yang tersedia di database.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach($siswa_list as $sw): 
                                            $kelas_sekarang = !empty($sw['Kelas']) ? $sw['Kelas'] : 'KOSONG';
                                        ?>
                                        <tr class="siswa-row" data-nama="<?= htmlspecialchars(strtolower($sw['NamaSiswa'])) ?>" data-kelas="<?= $kelas_sekarang ?>">
                                            <td class="text-center">
                                                <input class="form-check-input chk-siswa" type="checkbox" name="selected_siswa[]" value="<?= $sw['IDSiswa'] ?>">
                                            </td>
                                            <td>
                                                <div class="fw-semibold text-dark client-nama"><?= $sw['NamaSiswa'] ?></div>
                                            </td>
                                            <td>
                                                <?php if($kelas_sekarang !== 'KOSONG'): ?>
                                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border px-2 py-1"><?= $kelas_sekarang ?></span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning bg-opacity-10 text-warning border border-warning-subtle px-2 py-1">Belum Ada Kelas</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                    </div>
                    <div class="modal-footer p-3 bg-light border-top d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-light px-4 fw-semibold border" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger px-5 fw-bold shadow">
                            <i class="bi bi-check-all me-1"></i> Simpan Anggota Kelas
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalLihatSiswa" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg border-0 rounded-4">
                <div class="modal-header bg-secondary text-white p-4">
                    <h5 class="modal-title fw-bold" id="modalLihatTitle">
                        <i class="bi bi-eye-fill me-2"></i> Anggota Kelas
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="table-scroll-container">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light sticky-top" style="z-index: 5;">
                                <tr class="small fw-bold text-secondary">
                                    <th width="80%">Nama Lengkap Siswa</th>
                                    <th width="20%" class="text-center">Keluarkan</th>
                                </tr>
                            </thead>
                            <tbody id="listSiswaKelas">
                                </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer p-3 bg-light border-top">
                    <button type="button" class="btn btn-secondary px-4 fw-semibold btn-sm border" data-bs-dismiss="modal">Tutup Jendela</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        function openUploadModal(namaKelas) {
            document.getElementById('modalTitleKelas').innerHTML = '<i class="bi bi-people-fill me-2"></i> Atur Anggota Kelas : ' + namaKelas;
            document.getElementById('inputKelasTarget').value = namaKelas;
            
            document.getElementById('searchSiswa').value = '';
            document.getElementById('filterKelasSiswa').value = 'SEMUA';
            document.getElementById('selectAllSiswa').checked = false;
            
            let checkboxes = document.querySelectorAll('.chk-siswa');
            checkboxes.forEach(chk => chk.checked = false);
            
            let rows = document.querySelectorAll('.siswa-row');
            rows.forEach(row => row.style.display = '');

            var myModal = new bootstrap.Modal(document.getElementById('modalUploadSiswa'));
            myModal.show();
        }

        // PERBAIKAN TOTAL SINTAKS ENGINE FILTER LIVE
        function filterSiswaDanKelas() {
            let keyword = document.getElementById('searchSiswa').value.toLowerCase().trim();
            let filterKelas = document.getElementById('filterKelasSiswa').value;
            let rows = document.querySelectorAll('.siswa-row');

            document.getElementById('selectAllSiswa').checked = false;

            rows.forEach(row => {
                let namaSiswa = row.getAttribute('data-nama');
                let kelasSiswa = row.getAttribute('data-kelas');

                let matchKeyword = namaSiswa.includes(keyword);
                let matchKelas = false;

                if (filterKelas === 'SEMUA') {
                    matchKelas = true;
                } else if (filterKelas === 'KOSONG' && kelasSiswa === 'KOSONG') {
                    matchKelas = true;
                } else if (kelasSiswa === filterKelas) {
                    matchKelas = true;
                }

                if (matchKeyword && matchKelas) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                    let chk = row.querySelector('.chk-siswa');
                    if (chk) chk.checked = false;
                }
            });
        }

        function toggleSelectAll(masterCheckbox) {
            let rows = document.querySelectorAll('.siswa-row');
            rows.forEach(row => {
                if (row.style.display !== 'none') {
                    let singleCheckbox = row.querySelector('.chk-siswa');
                    if (singleCheckbox) {
                        singleCheckbox.checked = masterCheckbox.checked;
                    }
                }
            });
        }

        function openLihatModal(namaKelas) {
            document.getElementById('modalLihatTitle').innerHTML = '<i class="bi bi-eye-fill me-2"></i> Anggota Kelas: ' + namaKelas;
            let tbody = document.getElementById('listSiswaKelas');
            
            tbody.innerHTML = '<tr><td colspan="2" class="text-center py-4"><div class="spinner-border spinner-border-sm text-danger me-2"></div><span class="text-muted small">Mengambil data...</span></td></tr>';
            
            var myModal = new bootstrap.Modal(document.getElementById('modalLihatSiswa'));
            myModal.show();

            fetch('get_siswa_kelas.php?kelas=' + encodeURIComponent(namaKelas))
                .then(response => response.text())
                .then(html => {
                    tbody.innerHTML = html;
                });
        }

        function konfirmasiKeluarkan(idSiswa, namaSiswa, namaKelas) {
            Swal.fire({
                title: 'Keluarkan Siswa?',
                text: "Akun " + namaSiswa + " akan dikeluarkan dari keanggotaan kelas " + namaKelas + ".",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Keluarkan!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'proses_keluarkan_siswa.php?id=' + idSiswa + '&kelas=' + encodeURIComponent(namaKelas);
                }
            });
        }
    </script>
    
    <?php if(isset($_GET['status'])): ?>
        <script>
            let status = '<?= $_GET['status'] ?>';
            
            if(status === 'sukses_tambah') {
                Swal.fire({ 
                    title: 'Berhasil!', 
                    text: 'Mata pelajaran baru berhasil ditambahkan.', 
                    icon: 'success', 
                    timer: 3000, 
                    showConfirmButton: false 
                });
            } else if(status === 'sukses_tambah_siswa') {
                let jml = parseInt('<?= isset($_GET['jumlah']) ? $_GET['jumlah'] : 0 ?>');
                let kls = '<?= isset($_GET['kelas']) ? $_GET['kelas'] : "" ?>';
                
                // Jika ada siswa baru yang berhasil masuk
                if (jml > 0) {
                    Swal.fire({
                        title: 'Berhasil Diperbarui!',
                        text: 'Sebanyak ' + jml + ' siswa telah sukses dikonfigurasi menjadi anggota kelas ' + kls + '.',
                        icon: 'success',
                        confirmButtonColor: '#dc3545'
                    });
                } else {
                    // Jika Admin mencentang siswa yang SUDAH ADA di kelas tersebut
                    Swal.fire({
                        title: 'Tidak Ada Perubahan',
                        text: 'Siswa yang kamu centang sudah menjadi anggota di kelas ' + kls + ', sehingga tidak ada data baru yang ditambahkan.',
                        icon: 'info',
                        confirmButtonColor: '#6c757d'
                    });
                }
            } else if(status === 'sukses_keluar') {
                Swal.fire({ 
                    title: 'Berhasil Dikeluarkan!', 
                    text: 'Siswa yang bersangkutan telah resmi dilepas dari keanggotaan kelas.', 
                    icon: 'success', 
                    confirmButtonColor: '#6c757d' 
                });
            }

            // Bersihkan URL agar notif tidak muncul ganda saat halaman di-refresh
            window.history.replaceState(null, null, window.location.pathname);
        </script>
    <?php endif; ?>
</body>
</html>