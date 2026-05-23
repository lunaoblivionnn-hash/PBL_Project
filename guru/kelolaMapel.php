<?php
session_start();
require '../login/koneksi.php';

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'guru'){ header("Location: ../login/login.php"); exit; }
if(!isset($_GET['id_mapel']) || !isset($_GET['kelas'])){ echo "<script>alert('Akses tidak valid!'); window.location='guru.php';</script>"; exit; }

$id_mapel = mysqli_real_escape_string($koneksi, $_GET['id_mapel']);
$kelas    = mysqli_real_escape_string($koneksi, $_GET['kelas']);

// LOGIKA UPDATE, TAMBAH TOPIK, DLL TETAP SAMA
if(isset($_POST['simpan_deskripsi'])){
    $deskripsi_baru = mysqli_real_escape_string($koneksi, $_POST['deskripsi_baru']);
    mysqli_query($koneksi, "UPDATE mapel SET Deskripsi = '$deskripsi_baru' WHERE IDMapel = '$id_mapel'");
    header("Location: kelolaMapel.php?id_mapel=$id_mapel&kelas=".urlencode($kelas)."&pesan=deskripsi"); exit;
}
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

$q_cek_topik = mysqli_query($koneksi, "SELECT * FROM topik_mapel WHERE IDMapel = '$id_mapel'");
if(mysqli_num_rows($q_cek_topik) == 0){
    mysqli_query($koneksi, "INSERT INTO topik_mapel (IDMapel, NamaTopik, Urutan) VALUES ('$id_mapel', 'Umum / Pengumuman', 1)");
    mysqli_query($koneksi, "INSERT INTO topik_mapel (IDMapel, NamaTopik, Urutan) VALUES ('$id_mapel', 'Bab 1: Pendahuluan', 2)");
    header("Refresh:0"); exit;
}

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
        .sidebar-menu li a:hover, .sidebar-menu li a.active { background: var(--primary-light); color: var(--primary); }
        #main-content { width: 100%; padding: 30px; transition: all 0.3s; }
        .desc-box { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px; position: relative; }
        .section-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; margin-bottom: 16px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        .section-header { display: flex; justify-content: space-between; align-items: center; padding: 16px 20px; background: #fff; border-bottom: 1px solid #e5e7eb; border-radius: 12px 12px 0 0; }
        .section-title { font-weight: 700; color: var(--primary); margin: 0; font-size: 1.1rem; }
        .section-body { padding: 20px; background: #f8fafc; border-radius: 0 0 12px 12px; }
        
        /* DESAIN KONTEN YANG BISA DIKLIK (INTERAKTIF) */
        .resource-item { display: flex; align-items: center; padding: 14px 18px; border: 1px solid #e5e7eb; border-radius: 10px; background: #fff; margin-bottom: 8px; transition: 0.2s; cursor: pointer; position: relative; z-index: 2; }
        .resource-item:hover { border-color: var(--primary); box-shadow: 0 4px 10px rgba(79, 70, 229, 0.08); }
        .icon-box { width: 42px; height: 42px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; margin-right: 15px; }
        
        /* DESAIN AREA LACI TERSEMBUNYI (DETAIL) */
        .detail-collapse-body { margin-top: -12px; margin-bottom: 15px; padding: 25px 20px 20px 20px; background: #fff; border: 1px solid #e5e7eb; border-top: none; border-radius: 0 0 10px 10px; box-shadow: inset 0 3px 5px rgba(0,0,0,0.02); }
        .detail-materi { border-left: 4px solid var(--primary) !important; }
        .detail-tugas { border-left: 4px solid #198754 !important; }
        
        .upload-zone { border: 2.5px dashed #c7d2fe; border-radius: 14px; padding: 30px 20px; text-align: center; cursor: pointer; transition: .3s; background: #f8fafc; }
        .upload-zone:hover { border-color: var(--primary); background: #eff6ff; }
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
                        <button class="btn btn-sm btn-light text-secondary border" data-bs-toggle="collapse" data-bs-target="#collapseTopik<?= $id_topik ?>">
                            <i class="bi bi-chevron-down"></i>
                        </button>
                    </div>
                </div>

                <div id="collapseTopik<?= $id_topik ?>" class="collapse show">
                    <div class="section-body">
                        
                        <?php
                        $ada_konten = false;
                        
                        // ==========================================
                        // RENDER KONTEN MATERI (BISA DI-EXPAND)
                        // ==========================================
                        $q_materi = mysqli_query($koneksi, "SELECT * FROM materi WHERE IDMapel='$id_mapel' AND IDTopik='$id_topik'");
                        while($mt = mysqli_fetch_assoc($q_materi)): $ada_konten = true;
                        ?>
                            <div class="resource-item" data-bs-toggle="collapse" data-bs-target="#detailMateri<?= $mt['IDMateri'] ?>">
                                <div class="icon-box bg-primary bg-opacity-10 text-primary"><i class="bi bi-file-earmark-text-fill"></i></div>
                                <div class="flex-grow-1">
                                    <div class="fw-bold text-dark mb-1"><?= htmlspecialchars($mt['Judul']) ?></div>
                                    <div class="small text-muted"><i class="bi bi-paperclip me-1"></i> <?= htmlspecialchars($mt['Filepath']) ?></div>
                                </div>
                                <i class="bi bi-chevron-down text-muted"></i>
                            </div>
                            
                            <div class="collapse" id="detailMateri<?= $mt['IDMateri'] ?>">
                                <div class="detail-collapse-body detail-materi">
                                    <h6 class="fw-bold text-dark mb-2">Deskripsi Materi:</h6>
                                    <p class="text-secondary small mb-4" style="line-height: 1.6;">
                                        <?= !empty($mt['Deskripsi']) ? nl2br(htmlspecialchars($mt['Deskripsi'])) : '<i>Tidak ada deskripsi tambahan yang diberikan oleh guru.</i>' ?>
                                    </p>
                                    <div class="d-flex justify-content-between align-items-center bg-light p-2 px-3 border rounded">
                                        <div class="small text-muted fw-semibold"><i class="bi bi-clock-history me-1"></i> Diunggah pada: <span class="text-dark"><?= date('d M Y, H:i', strtotime($mt['TanggalUpload'] ?? 'now')) ?> WIB</span></div>
                                        <div class="d-flex gap-2">
                                            <button class="btn btn-sm btn-outline-primary fw-bold px-3 bg-white" onclick="alert('Nantinya ini akan membuka modal form Edit Materi!')">
                                                <i class="bi bi-pencil-square me-1"></i> Edit
                                            </button>
                                            <a href="../dokumen_materi/<?= htmlspecialchars($mt['Filepath']) ?>" class="btn btn-sm btn-primary fw-bold px-3" target="_blank" download>
                                                <i class="bi bi-cloud-arrow-down-fill me-1"></i> Download / Buka File
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>

                        <?php
                        // ==========================================
                        // RENDER KONTEN TUGAS (BISA DI-EXPAND)
                        // ==========================================
                        $q_tugas = mysqli_query($koneksi, "SELECT * FROM tugas WHERE IDMapel='$id_mapel' AND IDTopik='$id_topik'");
                        while($tg = mysqli_fetch_assoc($q_tugas)): $ada_konten = true;
                            $file_izinkan = !empty($tg['TipeFileDiizinkan']) ? $tg['TipeFileDiizinkan'] : 'Semua Jenis File';
                        ?>
                            <div class="resource-item" data-bs-toggle="collapse" data-bs-target="#detailTugas<?= $tg['IDTugas'] ?>">
                                <div class="icon-box bg-success bg-opacity-10 text-success"><i class="bi bi-journal-check"></i></div>
                                <div class="flex-grow-1">
                                    <div class="fw-bold text-dark mb-1"><?= htmlspecialchars($tg['Judul']) ?></div>
                                    <div class="small text-danger fw-semibold"><i class="bi bi-clock-history me-1"></i> Batas Waktu: <?= date('d M Y, H:i', strtotime($tg['Deadline'])) ?></div>
                                </div>
                                <i class="bi bi-chevron-down text-muted"></i>
                            </div>

                            <div class="collapse" id="detailTugas<?= $tg['IDTugas'] ?>">
                                <div class="detail-collapse-body detail-tugas">
                                    <h6 class="fw-bold text-dark mb-2">Instruksi Pengerjaan / Soal:</h6>
                                    <div class="p-3 bg-light border rounded mb-3 text-dark small" style="line-height: 1.6;">
                                        <?= !empty($tg['Deskripsi']) ? nl2br(htmlspecialchars($tg['Deskripsi'])) : '<i>Kerjakan tugas sesuai dengan arahan judul di atas.</i>' ?>
                                    </div>
                                    
                                    <div class="row g-2 mb-4">
                                        <div class="col-md-6">
                                            <div class="border rounded p-2 small"><i class="bi bi-calendar-plus text-secondary me-2"></i>Dibuat: <strong class="text-dark"><?= isset($tg['TanggalDibuat']) ? date('d M Y, H:i', strtotime($tg['TanggalDibuat'])) : '-' ?></strong></div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="border rounded p-2 small"><i class="bi bi-exclamation-circle text-danger me-2"></i>Tenggat: <strong class="text-danger"><?= date('d M Y, H:i', strtotime($tg['Deadline'])) ?></strong></div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="border rounded p-2 small"><i class="bi bi-file-earmark-check text-success me-2"></i>File Diizinkan: <strong class="text-dark"><?= htmlspecialchars($file_izinkan) ?></strong></div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="border rounded p-2 small"><i class="bi bi-star-fill text-warning me-2"></i>Poin Maksimal: <strong class="text-dark"><?= $tg['PoinMaksimal'] ?> Poin</strong></div>
                                        </div>
                                    </div>
        
                                    <div class="text-end mb-3">
                                        <button class="btn btn-sm btn-outline-success fw-bold px-4" 
                                                data-id="<?= $tg['IDTugas'] ?>"
                                                data-judul="<?= htmlspecialchars($tg['Judul']) ?>"
                                                data-deskripsi="<?= htmlspecialchars($tg['Deskripsi']) ?>"
                                                data-deadline="<?= date('Y-m-d\TH:i', strtotime($tg['Deadline'])) ?>"
                                                data-poin="<?= $tg['PoinMaksimal'] ?>"
                                                data-tipe="<?= htmlspecialchars($tg['TipeFileDiizinkan'] ?? '') ?>"
                                                onclick="bukaModalEditTugas(this)">
                                            <i class="bi bi-pencil-square me-1"></i> Edit Tugas
                                        </button>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center p-3 bg-success bg-opacity-10 border border-success border-opacity-25 rounded-3">
                                        <div>
                                            <div class="fw-bold text-success mb-1"><i class="bi bi-people-fill me-2"></i>Status Pengumpulan Kelas</div>
                                            <div class="small text-muted" style="font-size: 0.8rem;">Lihat dan beri nilai siswa yang sudah mengumpulkan.</div>
                                        </div>
                                        <a href="rekapTugas.php?id_tugas=<?= $tg['IDTugas'] ?>&kelas=<?= urlencode($kelas) ?>" class="btn btn-success fw-bold px-4 rounded-pill shadow-sm">
                                            Lihat Pekerjaan Siswa <i class="bi bi-arrow-right-short ms-1 fs-5 align-middle"></i>
                                        </a>
                                    </div>
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
                        <button onclick="pindahModal('modalUploadMateri')" class="btn btn-outline-primary text-start p-3 fw-bold rounded-3">
                            <i class="bi bi-file-earmark-plus fs-4 me-3 align-middle"></i> Upload File Materi
                        </button>
                        <button onclick="pindahModal('modalBuatTugas')" class="btn btn-outline-success text-start p-3 fw-bold rounded-3">
                            <i class="bi bi-journal-plus fs-4 me-3 align-middle"></i> Buat Penugasan Baru
                        </button>
                        <button class="btn btn-outline-warning text-dark text-start p-3 fw-bold rounded-3" onclick="alert('Sabar ya, fitur Quiz sedang dalam perakitan!')">
                            <i class="bi bi-patch-question fs-4 me-3 align-middle"></i> Buat Quiz / Ujian
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalUploadMateri" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content rounded-4 border-0 shadow-lg">
                <div class="modal-header bg-primary text-white border-0 p-4">
                    <h5 class="modal-title fw-bold"><i class="bi bi-file-earmark-plus me-2"></i>Upload Materi Baru</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="Proses_Up_Materi.php" method="POST" enctype="multipart/form-data" id="frmMateri">
                    <div class="modal-body p-4 bg-light">
                        <input type="hidden" name="id_mapel" value="<?= htmlspecialchars($id_mapel) ?>">
                        <input type="hidden" name="kelas" value="<?= htmlspecialchars($kelas) ?>">
                        <input type="hidden" name="id_topik" id="inputTopikMateri">
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold text-secondary small">Judul Materi <span class="text-danger">*</span></label>
                            <input type="text" name="judul" class="form-control" placeholder="Contoh: Pengenalan Akuntansi Dasar" required maxlength="100">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold text-secondary small">Deskripsi Tambahan</label>
                            <textarea name="deskripsi" class="form-control" rows="3" maxlength="300" placeholder="Tulis keterangan atau instruksi membaca..."></textarea>
                        </div>
                        <div class="mb-2">
                            <label class="form-label fw-bold text-secondary small">Pilih File Dokumen <span class="text-danger">*</span></label>
                            <div class="upload-zone" id="zoneMateri" onclick="document.getElementById('fileMateri').click()">
                                <i class="bi bi-cloud-arrow-up" style="font-size:2.5rem;color:#a5b4fc;"></i>
                                <div class="fw-semibold text-muted mt-2 small">Klik area ini untuk memilih file</div>
                                <input type="file" name="materi_file" id="fileMateri" class="d-none" required accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.jpg,.jpeg,.png,.mp4" onchange="showFileMateri(this)">
                            </div>
                            <div class="d-none align-items-center bg-white border rounded p-3 mt-2 shadow-sm" id="previewMateriBox">
                                <i class="bi bi-file-earmark-check-fill text-success fs-3 me-3"></i>
                                <div class="flex-grow-1"><div id="namaFileMateri" class="fw-bold text-dark small"></div></div>
                                <button type="button" class="btn btn-sm text-danger" onclick="clearFileMateri()"><i class="bi bi-x-lg"></i></button>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 p-3 bg-white">
                        <button type="submit" class="btn btn-primary px-4 fw-bold shadow-sm" id="btnSubmitMateri">Mulai Upload</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalBuatTugas" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content rounded-4 border-0 shadow-lg">
                <div class="modal-header bg-success text-white border-0 p-4">
                    <h5 class="modal-title fw-bold"><i class="bi bi-journal-plus me-2"></i>Buat Penugasan Baru</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="Proses_Up_Tugas.php" method="POST" id="frmTugas">
                    <div class="modal-body p-4 bg-light">
                        <input type="hidden" name="id_mapel" value="<?= htmlspecialchars($id_mapel) ?>">
                        <input type="hidden" name="kelas" value="<?= htmlspecialchars($kelas) ?>">
                        <input type="hidden" name="id_topik" id="inputTopikTugas">
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold text-secondary small">Judul Tugas <span class="text-danger">*</span></label>
                            <input type="text" name="judul" class="form-control" placeholder="Contoh: Analisis Studi Kasus Bab 1" required maxlength="100">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold text-secondary small">Instruksi Pengerjaan / Soal</label>
                            <textarea name="deskripsi" class="form-control" rows="4" maxlength="1000" placeholder="Jelaskan secara rinci apa yang harus dikerjakan siswa..."></textarea>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-7">
                                <label class="form-label fw-bold text-secondary small">Batas Waktu Pengumpulan <span class="text-danger">*</span></label>
                                <input type="datetime-local" name="deadline" class="form-control fw-bold" required value="<?= date('Y-m-d\T23:59', strtotime('+1 day')) ?>" min="<?= date('Y-m-d\TH:i') ?>">
                            </div>
                            <div class="col-md-5">
                                <label class="form-label fw-bold text-secondary small">Poin Maksimal</label>
                                <div class="input-group">
                                    <input type="number" name="poin_maksimal" class="form-control fw-bold text-success" value="100" min="10" max="1000" step="10">
                                    <span class="input-group-text bg-white">Poin</span>
                                </div>
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="form-label fw-bold text-secondary small">Jenis File yang Boleh Dikumpulkan Siswa <span class="text-danger">*</span></label>
                            <div class="p-3 bg-white border rounded">
                            <div class="d-flex flex-wrap gap-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="tipe_file[]" value="PDF" id="tf1" checked>
                                        <label class="form-check-label fw-semibold small" for="tf1">Dokumen PDF</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="tipe_file[]" value="Word" id="tf_word">
                                        <label class="form-check-label fw-semibold small" for="tf_word">Word Document</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="tipe_file[]" value="Excel/Spreadsheet" id="tf_excel">
                                        <label class="form-check-label fw-semibold small" for="tf_excel">Excel / Spreadsheet</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="tipe_file[]" value="PowerPoint" id="tf_ppt">
                                        <label class="form-check-label fw-semibold small" for="tf_ppt">PowerPoint (PPT)</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="tipe_file[]" value="Gambar/Foto" id="tf3">
                                        <label class="form-check-label fw-semibold small" for="tf3">Gambar (JPG/PNG)</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="tipe_file[]" value="Video/Audio" id="tf4">
                                        <label class="form-check-label fw-semibold small" for="tf4">Video / Audio</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="tipe_file[]" value="Google Doc (Link)" id="tf5">
                                        <label class="form-check-label fw-semibold small" for="tf5">Google Doc (Link)</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                    
                    <div class="modal-footer border-0 p-3 bg-white">
                        <button type="submit" class="btn btn-success px-5 fw-bold shadow-sm" id="btnSubmitTugas">
                            <i class="bi bi-send-fill me-2"></i>Publikasikan Tugas
                        </button>
                    </div>

                    <div class="modal fade" id="modalEditTugas" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content rounded-4 border-0 shadow-lg">
                <div class="modal-header bg-success text-white border-0 p-4">
                    <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>Edit Penugasan</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="Proses_Edit_Tugas.php" method="POST" id="frmEditTugas">
                    <div class="modal-body p-4 bg-light">
                        <input type="hidden" name="id_mapel" value="<?= htmlspecialchars($id_mapel) ?>">
                        <input type="hidden" name="kelas" value="<?= htmlspecialchars($kelas) ?>">
                        <input type="hidden" name="id_tugas" id="editTugasId">
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold text-secondary small">Judul Tugas <span class="text-danger">*</span></label>
                            <input type="text" name="judul" id="editTugasJudul" class="form-control" required maxlength="100">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold text-secondary small">Instruksi Pengerjaan / Soal</label>
                            <textarea name="deskripsi" id="editTugasDeskripsi" class="form-control" rows="4" maxlength="1000"></textarea>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-7">
                                <label class="form-label fw-bold text-secondary small">Batas Waktu Pengumpulan <span class="text-danger">*</span></label>
                                <input type="datetime-local" name="deadline" id="editTugasDeadline" class="form-control fw-bold" required>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label fw-bold text-secondary small">Poin Maksimal</label>
                                <div class="input-group">
                                    <input type="number" name="poin_maksimal" id="editTugasPoin" class="form-control fw-bold text-success" min="10" max="1000" step="10">
                                    <span class="input-group-text bg-white">Poin</span>
                                </div>
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="form-label fw-bold text-secondary small">Jenis File yang Boleh Dikumpulkan Siswa <span class="text-danger">*</span></label>
                            <div class="p-3 bg-white border rounded">
                                <div class="d-flex flex-wrap gap-4">
                                    <div class="form-check">
                                        <input class="form-check-input edit-tf-check" type="checkbox" name="tipe_file[]" value="PDF" id="etf1">
                                        <label class="form-check-label fw-semibold small" for="etf1">Dokumen PDF</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input edit-tf-check" type="checkbox" name="tipe_file[]" value="Word" id="etf_word">
                                        <label class="form-check-label fw-semibold small" for="etf_word">Word Document</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input edit-tf-check" type="checkbox" name="tipe_file[]" value="Excel/Spreadsheet" id="etf_excel">
                                        <label class="form-check-label fw-semibold small" for="etf_excel">Excel / Spreadsheet</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input edit-tf-check" type="checkbox" name="tipe_file[]" value="PowerPoint" id="etf_ppt">
                                        <label class="form-check-label fw-semibold small" for="etf_ppt">PowerPoint (PPT)</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input edit-tf-check" type="checkbox" name="tipe_file[]" value="Gambar/Foto" id="etf3">
                                        <label class="form-check-label fw-semibold small" for="etf3">Gambar (JPG/PNG)</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input edit-tf-check" type="checkbox" name="tipe_file[]" value="Video/Audio" id="etf4">
                                        <label class="form-check-label fw-semibold small" for="etf4">Video / Audio</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input edit-tf-check" type="checkbox" name="tipe_file[]" value="Google Doc (Link)" id="etf5">
                                        <label class="form-check-label fw-semibold small" for="etf5">Google Doc (Link)</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                    <div class="modal-footer border-0 p-3 bg-white">
                        <button type="submit" class="btn btn-success px-5 fw-bold shadow-sm" id="btnSubmitEditTugas">
                            <i class="bi bi-save-fill me-2"></i>Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalEditDeskripsi" tabindex="-1"><div class="modal-dialog modal-dialog-centered modal-lg"><div class="modal-content border-0 shadow-lg rounded-4"><form method="POST"><div class="modal-body p-4 bg-light"><textarea name="deskripsi_baru" class="form-control shadow-sm border-0" rows="5" required><?= htmlspecialchars($mapel['Deskripsi'] ?? '') ?></textarea></div><div class="modal-footer border-0 p-3"><button type="submit" name="simpan_deskripsi" class="btn btn-primary px-4 fw-bold">Simpan</button></div></form></div></div></div>
    <div class="modal fade" id="modalTambahTopik" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0 shadow-lg rounded-4"><form method="POST"><div class="modal-body p-4 pt-4"><label class="form-label small fw-bold">Judul Bagian / Bab <span class="text-danger">*</span></label><input type="text" name="nama_topik" class="form-control" required></div><div class="modal-footer border-0 p-3"><button type="submit" name="tambah_topik" class="btn btn-primary w-100 fw-bold rounded-pill">Tambahkan</button></div></form></div></div></div>
    <div class="modal fade" id="modalEditTopik" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0 shadow-lg rounded-4"><form method="POST"><div class="modal-body p-4 pt-4"><input type="hidden" name="id_topik" id="inputIdTopikEdit"><label class="form-label small fw-bold">Nama Baru <span class="text-danger">*</span></label><input type="text" name="nama_topik_baru" id="inputNamaTopikEdit" class="form-control" required></div><div class="modal-footer border-0 p-3"><button type="submit" name="edit_topik" class="btn btn-primary w-100 fw-bold rounded-pill">Simpan Perubahan</button></div></form></div></div></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.getElementById('sidebarToggle').addEventListener('click', function() { if (window.innerWidth <= 768) document.getElementById('sidebar').classList.toggle('show-mobile'); else document.getElementById('sidebar').classList.toggle('collapsed'); });
        function bukaModalAktivitas(idTopik, namaTopik) { document.getElementById('labelTargetTopik').innerText = namaTopik; document.getElementById('inputTopikMateri').value = idTopik; document.getElementById('inputTopikTugas').value = idTopik; new bootstrap.Modal(document.getElementById('modalPilihAktivitas')).show(); }
        function pindahModal(idModalTujuan) { bootstrap.Modal.getInstance(document.getElementById('modalPilihAktivitas')).hide(); new bootstrap.Modal(document.getElementById(idModalTujuan)).show(); }
        function bukaModalEditTopik(idTopik, namaLama) { document.getElementById('inputIdTopikEdit').value = idTopik; document.getElementById('inputNamaTopikEdit').value = namaLama; new bootstrap.Modal(document.getElementById('modalEditTopik')).show(); }
        
        function showFileMateri(input) { const f = input.files[0]; if (!f) return; document.getElementById('namaFileMateri').textContent = f.name; document.getElementById('previewMateriBox').classList.remove('d-none'); document.getElementById('previewMateriBox').classList.add('d-flex'); document.getElementById('zoneMateri').classList.add('d-none'); }
        function clearFileMateri() { document.getElementById('fileMateri').value = ''; document.getElementById('previewMateriBox').classList.add('d-none'); document.getElementById('previewMateriBox').classList.remove('d-flex'); document.getElementById('zoneMateri').classList.remove('d-none'); }

        // Fungsi untuk Auto-Populate Form Edit Tugas
        function bukaModalEditTugas(btnElement) {
            // Sedot data dari tombol yang diklik
            const id = btnElement.getAttribute('data-id');
            const judul = btnElement.getAttribute('data-judul');
            const deskripsi = btnElement.getAttribute('data-deskripsi');
            const deadline = btnElement.getAttribute('data-deadline');
            const poin = btnElement.getAttribute('data-poin');
            const tipeFileStr = btnElement.getAttribute('data-tipe');

            // Tempelkan data ke form modal
            document.getElementById('editTugasId').value = id;
            document.getElementById('editTugasJudul').value = judul;
            document.getElementById('editTugasDeskripsi').value = deskripsi;
            document.getElementById('editTugasDeadline').value = deadline;
            document.getElementById('editTugasPoin').value = poin;

            // Logika cerdas untuk mencentang kembali checkbox jenis file
            const allowedFiles = tipeFileStr ? tipeFileStr.split(', ') : [];
            const checkboxes = document.querySelectorAll('.edit-tf-check');
            checkboxes.forEach(cb => {
                cb.checked = allowedFiles.includes(cb.value);
            });

            // Tampilkan Modalnya
            new bootstrap.Modal(document.getElementById('modalEditTugas')).show();
        }

        // Efek loading tombol simpan edit tugas
        document.getElementById('frmEditTugas').addEventListener('submit', function() {
            const b = document.getElementById('btnSubmitEditTugas');
            b.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...';
            b.disabled = true;
        });
    </script>
    <?php if(isset($_GET['pesan']) || isset($_GET['status'])): ?>
    <script>Swal.fire({ title: 'Berhasil!', icon: 'success', confirmButtonColor: '#4f46e5', timer: 2000, showConfirmButton: false }); window.history.replaceState(null, null, window.location.pathname + "?id_mapel=<?= urlencode($id_mapel) ?>&kelas=<?= urlencode($kelas) ?>");</script>
    <?php endif; ?>
</body>
</html>