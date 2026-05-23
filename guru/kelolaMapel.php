<?php
session_start();
require '../login/koneksi.php';

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'guru'){ header("Location: ../login/login.php"); exit; }

if(!isset($_GET['id_mapel']) || !isset($_GET['kelas'])){ echo "<script>alert('Akses tidak valid!'); window.location='guru.php';</script>"; exit; }

$id_mapel = mysqli_real_escape_string($koneksi, $_GET['id_mapel']);
$kelas    = mysqli_real_escape_string($koneksi, $_GET['kelas']);

// =========================================================================
// FITUR 1: UPDATE DESKRIPSI MAPEL
// =========================================================================
if(isset($_POST['simpan_deskripsi'])){
    $deskripsi_baru = mysqli_real_escape_string($koneksi, $_POST['deskripsi_baru']);
    mysqli_query($koneksi, "UPDATE mapel SET Deskripsi = '$deskripsi_baru' WHERE IDMapel = '$id_mapel'");
    header("Location: kelolaMapel.php?id_mapel=$id_mapel&kelas=".urlencode($kelas)."&pesan=deskripsi"); exit;
}

// =========================================================================
// FITUR 2: TAMBAH & RENAME SECTION / TOPIK
// =========================================================================
if(isset($_POST['tambah_topik'])){
    $nama_topik = mysqli_real_escape_string($koneksi, $_POST['nama_topik']);
    $q_urut = mysqli_query($koneksi, "SELECT MAX(Urutan) as max_urut FROM topik_mapel WHERE IDMapel = '$id_mapel'");
    $urut = (mysqli_fetch_assoc($q_urut)['max_urut'] ?? 0) + 1;
    mysqli_query($koneksi, "INSERT INTO topik_mapel (IDMapel, NamaTopik, Urutan) VALUES ('$id_mapel', '$nama_topik', $urut)");
    header("Location: kelolaMapel.php?id_mapel=$id_mapel&kelas=".urlencode($kelas)."&pesan=topik_tambah"); exit;
}
if(isset($_POST['edit_topik'])){
    $id_topik_edit = mysqli_real_escape_string($koneksi, $_POST['id_topik']);
    $nama_topik_baru = mysqli_real_escape_string($koneksi, $_POST['nama_topik_baru']);
    mysqli_query($koneksi, "UPDATE topik_mapel SET NamaTopik = '$nama_topik_baru' WHERE IDTopik = '$id_topik_edit'");
    header("Location: kelolaMapel.php?id_mapel=$id_mapel&kelas=".urlencode($kelas)."&pesan=topik_edit"); exit;
}

// =========================================================================
// AUTO-GENERATE SECTION JIKA MASIH KOSONG
// =========================================================================
$q_cek_topik = mysqli_query($koneksi, "SELECT * FROM topik_mapel WHERE IDMapel = '$id_mapel'");
if(mysqli_num_rows($q_cek_topik) == 0){
    mysqli_query($koneksi, "INSERT INTO topik_mapel (IDMapel, NamaTopik, Urutan) VALUES ('$id_mapel', 'Umum / Pengumuman', 1)");
    mysqli_query($koneksi, "INSERT INTO topik_mapel (IDMapel, NamaTopik, Urutan) VALUES ('$id_mapel', 'Bab 1: Pendahuluan', 2)");
    header("Refresh:0"); exit;
}

// Ambil Data Mapel
$query_mapel = mysqli_query($koneksi, "SELECT * FROM mapel WHERE IDMapel = '$id_mapel'");
$mapel = mysqli_fetch_assoc($query_mapel);
$nama_mapel = $mapel['NamaMapel'] ?? 'Mapel Tidak Ditemukan';
$deskripsi_mapel = !empty($mapel['Deskripsi']) ? $mapel['Deskripsi'] : 'Belum ada panduan untuk mata pelajaran ini.';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($nama_mapel) ?> - Kelola Kelas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        :root { --primary: #4f46e5; --primary-light: #eef2ff; --sidebar-width: 280px; }
        body { background-color: #f3f4f6; overflow-x: hidden; font-family: 'Segoe UI', system-ui, sans-serif; }
        
        .navbar-custom { background: #fff; border-bottom: 1px solid #e5e7eb; z-index: 1030; }
        .btn-toggle { font-size: 1.5rem; color: #4b5563; background: transparent; border: none; padding: 0 15px; }
        
        #wrapper { display: flex; width: 100%; align-items: stretch; min-height: calc(100vh - 60px); }
        #sidebar { min-width: var(--sidebar-width); max-width: var(--sidebar-width); background: #fff; border-right: 1px solid #e5e7eb; transition: all 0.3s; z-index: 1000; }
        #sidebar.collapsed { margin-left: calc(-1 * var(--sidebar-width)); }
        
        .sidebar-menu { padding: 15px 10px; list-style: none; margin: 0; }
        .sidebar-menu li a { display: flex; align-items: center; padding: 10px 15px; color: #4b5563; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 0.9rem; transition: 0.2s; margin-bottom: 5px; }
        .sidebar-menu li a:hover { background: #f3f4f6; color: var(--primary); }
        .sidebar-menu li a.active { background: var(--primary-light); color: var(--primary); }
        
        #main-content { width: 100%; padding: 30px; transition: all 0.3s; }
        .desc-box { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px; position: relative; }
        
        /* DESAIN SECTION ACCORDION */
        .section-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; margin-bottom: 16px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        .section-header { display: flex; justify-content: space-between; align-items: center; padding: 16px 20px; background: #fff; border-bottom: 1px solid #e5e7eb; }
        .section-title { font-weight: 700; color: var(--primary); margin: 0; font-size: 1.1rem; }
        .section-body { padding: 20px; background: #f8fafc; }
        
        /* DESAIN ITEM KONTEN (Materi/Tugas) */
        .resource-item { display: flex; align-items: center; padding: 12px 15px; border: 1px solid #e5e7eb; border-radius: 8px; background: #fff; margin-bottom: 10px; transition: 0.2s; cursor: pointer; }
        .resource-item:hover { border-color: var(--primary); box-shadow: 0 4px 6px rgba(79, 70, 229, 0.05); transform: translateX(4px); }
        .icon-box { width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; margin-right: 15px; }

        @media (max-width: 768px) {
            #sidebar { position: fixed; height: 100%; box-shadow: 5px 0 15px rgba(0,0,0,0.1); margin-left: calc(-1 * var(--sidebar-width)); }
            #sidebar.show-mobile { margin-left: 0; }
            #main-content { padding: 15px; }
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-custom sticky-top py-2">
        <div class="container-fluid">
            <div class="d-flex align-items-center">
                <button class="btn-toggle" id="sidebarToggle"><i class="bi bi-list"></i></button>
                <a class="navbar-brand fw-bold ms-2 text-dark fs-5" href="#">LMS <span style="color: var(--primary);">Wongsorejo</span></a>
            </div>
            <a href="guru.php" class="btn btn-outline-secondary btn-sm rounded-pill fw-bold px-3"><i class="bi bi-arrow-left me-1"></i> Dashboard</a>
        </div>
    </nav>

    <div id="wrapper">
        <nav id="sidebar">
            <div class="p-4 border-bottom">
                <h6 class="fw-bold mb-1" style="color: var(--primary);"><?= htmlspecialchars($nama_mapel) ?></h6>
                <span class="badge bg-dark bg-opacity-10 text-dark border"><i class="bi bi-building me-1"></i> Kelas <?= htmlspecialchars($kelas) ?></span>
            </div>
            <ul class="sidebar-menu">
                <li><a href="#" class="active"><i class="bi bi-journal-text me-2"></i> Ruang Kelas Utama</a></li>
                <li><a href="#"><i class="bi bi-people me-2"></i> Daftar Anggota Siswa</a></li>
                <li><a href="#"><i class="bi bi-clipboard-data me-2"></i> Rekap Penilaian</a></li>
            </ul>
        </nav>

        <main id="main-content">
            
            <div class="desc-box shadow-sm mb-4">
                <button class="btn btn-sm btn-light border position-absolute top-0 end-0 m-3 fw-bold text-secondary" data-bs-toggle="modal" data-bs-target="#modalEditDeskripsi">
                    <i class="bi bi-pencil-square me-1"></i> Edit Pengantar
                </button>
                <h2 class="fw-bold text-dark mb-1"><?= htmlspecialchars($nama_mapel) ?></h2>
                <p class="mb-0 text-muted mt-2" style="font-size: 0.9rem; max-width: 90%;"><?= nl2br(htmlspecialchars($deskripsi_mapel)) ?></p>
            </div>

            <?php 
            $q_topik_all = mysqli_query($koneksi, "SELECT * FROM topik_mapel WHERE IDMapel = '$id_mapel' ORDER BY Urutan ASC");
            while($topik = mysqli_fetch_assoc($q_topik_all)): 
                $id_topik = $topik['IDTopik'];
                $nama_topik = $topik['NamaTopik'];
            ?>
            
            <div class="section-card shadow-sm">
                <div class="section-header">
                    <h3 class="section-title"><i class="bi bi-bookmark-fill me-2" style="color: #cbd5e1;"></i><?= htmlspecialchars($nama_topik) ?></h3>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-light text-secondary border" title="Edit Nama Bagian" onclick="bukaModalEditTopik('<?= $id_topik ?>', '<?= addslashes(htmlspecialchars($nama_topik)) ?>')">
                            <i class="bi bi-pencil-fill"></i>
                        </button>
                        <button class="btn btn-sm btn-light text-secondary border" data-bs-toggle="collapse" data-bs-target="#collapse<?= $id_topik ?>">
                            <i class="bi bi-chevron-down"></i>
                        </button>
                    </div>
                </div>

                <div id="collapse<?= $id_topik ?>" class="collapse show">
                    <div class="section-body">
                        
                        <?php
                        $ada_konten = false;
                        
                        // 1. Cek Materi
                        $q_materi = mysqli_query($koneksi, "SELECT * FROM materi WHERE IDMapel='$id_mapel' AND IDTopik='$id_topik'");
                        while($mt = mysqli_fetch_assoc($q_materi)): $ada_konten = true;
                        ?>
                            <div class="resource-item">
                                <div class="icon-box bg-primary bg-opacity-10 text-primary"><i class="bi bi-file-earmark-text-fill"></i></div>
                                <div>
                                    <div class="fw-bold text-dark mb-1"><?= htmlspecialchars($mt['Judul']) ?></div>
                                    <div class="small text-muted">Materi Pembelajaran • <?= strtoupper($mt['TipeFile']) ?></div>
                                </div>
                            </div>
                        <?php endwhile; ?>

                        <?php
                        $q_tugas = mysqli_query($koneksi, "SELECT * FROM tugas WHERE IDMapel='$id_mapel' AND IDTopik='$id_topik'");
                        while($tg = mysqli_fetch_assoc($q_tugas)): $ada_konten = true;
                        ?>
                            <div class="resource-item">
                                <div class="icon-box bg-success bg-opacity-10 text-success"><i class="bi bi-journal-check"></i></div>
                                <div>
                                    <div class="fw-bold text-dark mb-1"><?= htmlspecialchars($tg['Judul']) ?></div>
                                    <div class="small text-danger fw-semibold">Batas Waktu: <?= date('d M Y H:i', strtotime($tg['Deadline'])) ?></div>
                                </div>
                            </div>
                        <?php endwhile; ?>

                        <?php if(!$ada_konten): ?>
                            <div class="text-center py-3">
                                <div class="text-muted small border border-dashed rounded p-3 bg-white">Belum ada aktivitas, materi, atau penugasan di bagian ini.</div>
                            </div>
                        <?php endif; ?>

                        <div class="mt-3 text-end">
                            <button class="btn btn-sm btn-light border border-primary text-primary fw-bold rounded-pill px-3" onclick="bukaModalAktivitas('<?= $id_topik ?>', '<?= addslashes(htmlspecialchars($nama_topik)) ?>')">
                                <i class="bi bi-plus-lg me-1"></i> Tambah Aktivitas ke Sini
                            </button>
                        </div>
                        
                    </div>
                </div>
            </div>
            
            <?php endwhile; ?>

            <div class="text-center mt-4 pt-2">
                <button class="btn btn-primary bg-opacity-10 text-primary border-primary fw-bold rounded-pill px-4 shadow-sm py-2" data-bs-toggle="modal" data-bs-target="#modalTambahTopik">
                    <i class="bi bi-plus-circle-fill me-2"></i> Tambah Section / Bagian Baru
                </button>
            </div>
            
        </main>
    </div>

    <div class="modal fade" id="modalPilihAktivitas" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow-lg">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold text-dark">Pilih Jenis Aktivitas</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted small mb-3">Menambahkan konten ke dalam: <strong id="labelTargetTopik" class="text-primary"></strong></p>
                    <div class="d-grid gap-3">
                        <a href="#" id="linkUpMateri" class="btn btn-outline-primary text-start p-3 fw-bold rounded-3">
                            <i class="bi bi-file-earmark-plus fs-4 me-3 align-middle"></i> Upload File Materi
                        </a>
                        <a href="#" id="linkUpTugas" class="btn btn-outline-success text-start p-3 fw-bold rounded-3">
                            <i class="bi bi-journal-plus fs-4 me-3 align-middle"></i> Buat Penugasan Baru
                        </a>
                        <button class="btn btn-outline-warning text-dark text-start p-3 fw-bold rounded-3" onclick="alert('Fitur Quiz dalam pengembangan!')">
                            <i class="bi bi-patch-question fs-4 me-3 align-middle"></i> Buat Quiz / Ujian
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalEditDeskripsi" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header bg-white border-bottom p-4">
                    <h5 class="modal-title fw-bold text-primary"><i class="bi bi-pencil-square me-2"></i>Edit Pengantar Mapel</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body p-4 bg-light">
                        <textarea name="deskripsi_baru" class="form-control shadow-sm border-0" rows="5" required><?= htmlspecialchars($mapel['Deskripsi'] ?? '') ?></textarea>
                    </div>
                    <div class="modal-footer border-0 p-3 bg-white">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="simpan_deskripsi" class="btn btn-primary px-4 fw-bold">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalTambahTopik" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header bg-white p-4 pb-2 border-0">
                    <h5 class="modal-title fw-bold">Tambah Bagian Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body p-4 pt-2">
                        <label class="form-label small fw-bold text-muted">Judul Bagian / Bab <span class="text-danger">*</span></label>
                        <input type="text" name="nama_topik" class="form-control" placeholder="Contoh: Bab 2 - Jurnal Umum" required>
                    </div>
                    <div class="modal-footer border-0 p-3 bg-light">
                        <button type="submit" name="tambah_topik" class="btn btn-primary w-100 fw-bold rounded-pill">Tambahkan Sekarang</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalEditTopik" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header bg-white p-4 pb-2 border-0">
                    <h5 class="modal-title fw-bold">Ubah Nama Bagian</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body p-4 pt-2">
                        <input type="hidden" name="id_topik" id="inputIdTopikEdit">
                        <label class="form-label small fw-bold text-muted">Nama Baru <span class="text-danger">*</span></label>
                        <input type="text" name="nama_topik_baru" id="inputNamaTopikEdit" class="form-control" required>
                    </div>
                    <div class="modal-footer border-0 p-3 bg-light">
                        <button type="submit" name="edit_topik" class="btn btn-primary w-100 fw-bold rounded-pill">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Toggle Sidebar
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            if (window.innerWidth <= 768) document.getElementById('sidebar').classList.toggle('show-mobile');
            else document.getElementById('sidebar').classList.toggle('collapsed');
        });

        // Script Dinamis: Melempar ID Topik ke Tombol Tambah Aktivitas
        function bukaModalAktivitas(idTopik, namaTopik) {
            document.getElementById('labelTargetTopik').innerText = namaTopik;
            
            // Ubah link form upload agar membawa parameter ID Topik
            let basePathMateri = "UpMateri.php?id_mapel=<?= urlencode($id_mapel) ?>&kelas=<?= urlencode($kelas) ?>&id_topik=" + idTopik;
            let basePathTugas = "upPenugasan.php?id_mapel=<?= urlencode($id_mapel) ?>&kelas=<?= urlencode($kelas) ?>&id_topik=" + idTopik;
            
            document.getElementById('linkUpMateri').href = basePathMateri;
            document.getElementById('linkUpTugas').href = basePathTugas;
            
            new bootstrap.Modal(document.getElementById('modalPilihAktivitas')).show();
        }

        // Script Dinamis: Melempar data lama ke Modal Edit Nama Topik
        function bukaModalEditTopik(idTopik, namaLama) {
            document.getElementById('inputIdTopikEdit').value = idTopik;
            document.getElementById('inputNamaTopikEdit').value = namaLama;
            new bootstrap.Modal(document.getElementById('modalEditTopik')).show();
        }
    </script>

    <?php if(isset($_GET['pesan'])): ?>
    <script>
        Swal.fire({ title: 'Berhasil!', icon: 'success', confirmButtonColor: '#4f46e5', timer: 2000, showConfirmButton: false });
        window.history.replaceState(null, null, window.location.pathname + "?id_mapel=<?= urlencode($id_mapel) ?>&kelas=<?= urlencode($kelas) ?>");
    </script>
    <?php endif; ?>

</body>
</html>