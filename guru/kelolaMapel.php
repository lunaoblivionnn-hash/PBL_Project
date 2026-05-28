<?php
session_start();
require '../login/koneksi.php';

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'guru'){ header("Location: ../login/login.php"); exit; }
if(!isset($_GET['id_mapel']) || !isset($_GET['kelas'])){ echo "<script>alert('Akses tidak valid!'); window.location='guru.php';</script>"; exit; }

$id_mapel = mysqli_real_escape_string($koneksi, $_GET['id_mapel']);
$kelas    = mysqli_real_escape_string($koneksi, $_GET['kelas']);

// LOGIKA UPDATE, TAMBAH TOPIK, DLL
if(isset($_POST['simpan_deskripsi'])){
    $deskripsi_baru = mysqli_real_escape_string($koneksi, $_POST['deskripsi_baru']);
    mysqli_query($koneksi, "UPDATE mapel SET Deskripsi = '$deskripsi_baru' WHERE IDMapel = '$id_mapel'");
    header("Location: kelolaMapel.php?id_mapel=$id_mapel&kelas=".urlencode($kelas)."&pesan=deskripsi"); exit;
}
if(isset($_POST['tambah_topik'])){
    $nama_topik = mysqli_real_escape_string($koneksi, $_POST['nama_topik']);
    $q_urut = mysqli_query($koneksi, "SELECT MAX(Urutan) as max_urut FROM topik_mapel WHERE IDMapel = '$id_mapel' AND Kelas = '$kelas'");
    $urut = (mysqli_fetch_assoc($q_urut)['max_urut'] ?? 0) + 1;
    
    mysqli_query($koneksi, "INSERT INTO topik_mapel (IDMapel, Kelas, NamaTopik, Urutan) VALUES ('$id_mapel', '$kelas', '$nama_topik', $urut)");
    header("Location: kelolaMapel.php?id_mapel=$id_mapel&kelas=".urlencode($kelas)."&pesan=topik_tambah"); exit;
}
if(isset($_POST['edit_topik'])){
    $id_topik_edit = mysqli_real_escape_string($koneksi, $_POST['id_topik']);
    $nama_topik_baru = mysqli_real_escape_string($koneksi, $_POST['nama_topik_baru']);
    mysqli_query($koneksi, "UPDATE topik_mapel SET NamaTopik = '$nama_topik_baru' WHERE IDTopik = '$id_topik_edit'");
    header("Location: kelolaMapel.php?id_mapel=$id_mapel&kelas=".urlencode($kelas)."&pesan=topik_edit"); exit;
}

$q_cek_topik = mysqli_query($koneksi, "SELECT * FROM topik_mapel WHERE IDMapel = '$id_mapel' AND Kelas = '$kelas'");
if(mysqli_num_rows($q_cek_topik) == 0){
    mysqli_query($koneksi, "INSERT INTO topik_mapel (IDMapel, Kelas, NamaTopik, Urutan) VALUES ('$id_mapel', '$kelas', 'Umum / Pengumuman', 1)");
    mysqli_query($koneksi, "INSERT INTO topik_mapel (IDMapel, Kelas, NamaTopik, Urutan) VALUES ('$id_mapel', '$kelas', 'Bab 1: Pendahuluan', 2)");
    header("Refresh:0"); exit;
}

$query_mapel = mysqli_query($koneksi, "SELECT * FROM mapel WHERE IDMapel = '$id_mapel'");
$mapel = mysqli_fetch_assoc($query_mapel);
$nama_mapel = $mapel['NamaMapel'] ?? 'Mapel Tidak Ditemukan';
$deskripsi_mapel = !empty($mapel['Deskripsi']) ? $mapel['Deskripsi'] : 'Belum ada panduan untuk mata pelajaran ini.';

// =================================================================================
// 1. SIAPKAN DATA DAFTAR BAB UNTUK DITAMPILKAN DI SIDEBAR KIRI & KONTEN UTAMA
// =================================================================================
$q_topik_all = mysqli_query($koneksi, "SELECT * FROM topik_mapel WHERE IDMapel = '$id_mapel' AND Kelas = '$kelas' ORDER BY Urutan ASC");
$daftar_topik = [];
while($t = mysqli_fetch_assoc($q_topik_all)) { $daftar_topik[] = $t; }
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
        body { background-color: #f8fafc; overflow-x: hidden; font-family: 'Segoe UI', system-ui, sans-serif; }
        .navbar-custom { background: #fff; border-bottom: 1px solid #e5e7eb; z-index: 1030; }
        .btn-toggle { font-size: 1.5rem; color: #4b5563; background: transparent; border: none; padding: 0 15px; }
        
        /* LAYOUT BERSAMA (WRAPPER & SIDEBAR BARU) */
        #wrapper { display: flex; width: 100%; align-items: stretch; min-height: calc(100vh - 60px); }
        #sidebar-course { min-width: 280px; max-width: 280px; background: #fff; border-right: 1px solid #e2e8f0; position: sticky; top: 60px; height: calc(100vh - 60px); overflow-y: auto; padding: 20px 15px; z-index: 100; transition: all 0.3s;}
        #sidebar-course.collapsed { margin-left: -280px; }
        .course-index-title { font-size: 0.8rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 15px; padding-left: 10px; }
        .index-item { display: block; padding: 10px 15px; color: #475569; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 0.9rem; transition: 0.2s; margin-bottom: 5px; cursor: pointer; }
        .index-item:hover { background: #e0e7ff; color: #4f46e5; }
        .index-item.active { background: #4f46e5; color: #fff; box-shadow: 0 4px 10px rgba(79, 70, 229, 0.2); }
        
        #main-content { width: 100%; padding: 30px 40px; transition: all 0.3s; }
        .desc-box { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px; position: relative; }
        
        /* SECTION CARD BARU (Gaya Akordion) */
        .section-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 2px 6px rgba(0,0,0,0.02); overflow: hidden; }
        .section-header { padding: 15px 20px; display: flex; align-items: center; justify-content: space-between; background: #fff; transition: 0.2s; }
        .section-header:hover { background: #f8fafc; }
        
        .section-title-wrapper { display: flex; align-items: center; flex-grow: 1; cursor: pointer; }
        .toggle-icon { font-size: 1.2rem; color: #94a3b8; transition: transform 0.3s ease; margin-right: 15px; }
        .section-title-wrapper[aria-expanded="true"] .toggle-icon { transform: rotate(90deg); color: #4f46e5; }
        .section-title-wrapper[aria-expanded="true"] { border-bottom: 1px solid transparent; }
        .section-title { font-weight: 700; color: #1e293b; font-size: 1.15rem; margin: 0; }
        
        .section-body { padding: 15px 25px 25px 25px; border-top: 1px solid #e2e8f0; }
        
        /* Tombol Aksi Kanan (Titik Tiga) */
        .btn-action-section { color: #64748b; background: transparent; border: none; padding: 8px 12px; border-radius: 8px; transition: 0.2s; }
        .btn-action-section:hover { background: #e2e8f0; color: #1e293b; }
        
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

        @media (max-width: 768px) { #sidebar-course { display: none; } #main-content { padding: 20px; } }

        /* CSS TAMBAHAN UNTUK SIDEBAR ACCORDION */
        .sidebar-accordion .accordion-button { padding: 10px 15px; color: #475569; border-radius: 8px; font-weight: 600; font-size: 0.9rem; margin-bottom: 2px; }
        .sidebar-accordion .accordion-button:not(.collapsed) { background: #e0e7ff; color: #4f46e5; box-shadow: none; }
        .sidebar-accordion .accordion-button:focus { box-shadow: none; }
        .sidebar-accordion .accordion-button::after { transform: scale(0.8); }
        .sidebar-subitem { display: block; padding: 6px 15px 6px 35px; font-size: 0.85rem; color: #64748b; text-decoration: none; transition: 0.2s; border-radius: 8px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;}
        .sidebar-subitem:hover { color: #4f46e5; background: #f8fafc; }
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
    <nav id="sidebar-course">
            <div class="p-2 mb-2 border-bottom text-center">
                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary w-100 py-2"><i class="bi bi-building me-1"></i> Kelas <?= htmlspecialchars($kelas) ?></span>
            </div>
            <div class="course-index-title mt-3">DAFTAR ISI KELAS</div>
            <a href="#header-mapel" class="index-item active mb-3" onclick="setActiveSidebar(this)">
                <i class="bi bi-info-square me-2"></i> Pengaturan Umum
            </a>
            
            <div class="accordion accordion-flush sidebar-accordion" id="accordionSidebar">
            <?php foreach($daftar_topik as $tp): 
                $id_tp = $tp['IDTopik'];
            ?>
                <div class="accordion-item bg-transparent border-0">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed bg-transparent" type="button" data-bs-toggle="collapse" data-bs-target="#sideCollapse<?= $id_tp ?>" aria-expanded="false" onclick="bukaSectionUtama('<?= $id_tp ?>')">
                            <span class="text-truncate" style="max-width: 190px;"><?= htmlspecialchars($tp['NamaTopik']) ?></span>
                        </button>
                    </h2>
                    <div id="sideCollapse<?= $id_tp ?>" class="accordion-collapse collapse" data-bs-parent="#accordionSidebar">
                        <div class="accordion-body p-0 pb-2">
                            <?php 
                            // Tarik daftar materi untuk sidebar
                            $q_sm = mysqli_query($koneksi, "SELECT IDMateri, Judul FROM materi WHERE IDMapel='$id_mapel' AND IDTopik='$id_tp'");
                            while($sm = mysqli_fetch_assoc($q_sm)): ?>
                                <a href="#detailMateri<?= $sm['IDMateri'] ?>" class="sidebar-subitem" onclick="bukaSectionUtama('<?= $id_tp ?>', '#detailMateri<?= $sm['IDMateri'] ?>')"><i class="bi bi-file-earmark-text text-primary me-2"></i><?= htmlspecialchars($sm['Judul']) ?></a>
                            <?php endwhile; ?>
                            
                            <?php 
                            // Tarik daftar tugas untuk sidebar
                            $q_st = mysqli_query($koneksi, "SELECT IDTugas, Judul FROM tugas WHERE IDMapel='$id_mapel' AND IDTopik='$id_tp'");
                            while($st = mysqli_fetch_assoc($q_st)): ?>
                                <a href="#detailTugas<?= $st['IDTugas'] ?>" class="sidebar-subitem" onclick="bukaSectionUtama('<?= $id_tp ?>', '#detailTugas<?= $st['IDTugas'] ?>')"><i class="bi bi-journal-check text-success me-2"></i><?= htmlspecialchars($st['Judul']) ?></a>
                            <?php endwhile; ?>
                            
                            <?php if(mysqli_num_rows($q_sm) == 0 && mysqli_num_rows($q_st) == 0): ?>
                                <div class="sidebar-subitem fst-italic text-muted" style="pointer-events: none;">Belum ada konten</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>
        </nav>

        <main id="main-content">
            
            <div id="header-mapel" class="desc-box shadow-sm mb-4">
                <button class="btn btn-sm btn-light border position-absolute top-0 end-0 m-3 fw-bold text-secondary" data-bs-toggle="modal" data-bs-target="#modalEditDeskripsi">
                    <i class="bi bi-pencil-square me-1"></i> Edit Pengantar
                </button>
                <h2 class="fw-bold text-dark mb-1"><?= htmlspecialchars($nama_mapel) ?></h2>
                <p class="mb-0 text-muted mt-2" style="font-size: 0.9rem; max-width: 90%;"><?= nl2br(htmlspecialchars($deskripsi_mapel)) ?></p>
            </div>

            <div class="d-flex justify-content-end mb-3">
                <button class="btn btn-sm btn-white border text-secondary fw-bold shadow-sm rounded-pill px-3 bg-white" id="btnToggleAll" onclick="toggleAllSections()">
                    <i class="bi bi-arrows-expand me-1"></i> <span id="textToggleAll">Buka Semua Bab</span>
                </button>
            </div>

            <?php 
            // =================================================================================
            // 2. RENDER CARD SECTION GAYA BARU DARI ARRAY DAFTAR TOPIK
            // =================================================================================
            foreach($daftar_topik as $index => $topik): 
                $id_topik = $topik['IDTopik'];
                $nama_topik = $topik['NamaTopik'];
                $is_first = ($index == 0);
            ?>
            
            <div class="section-card course-chapter" id="section-<?= $id_topik ?>">
                
                <div class="section-header">
                    <div class="section-title-wrapper" data-bs-toggle="collapse" data-bs-target="#collapseTopik<?= $id_topik ?>" aria-expanded="<?= $is_first ? 'true' : 'false' ?>">
                        <i class="bi bi-chevron-right toggle-icon"></i>
                        <h3 class="section-title"><?= htmlspecialchars($nama_topik) ?></h3>
                    </div>
                    
                    <div class="dropdown">
                        <button class="btn-action-section" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-three-dots-vertical fs-5"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3">
                            <li>
                                <a class="dropdown-item fw-semibold text-primary py-2" href="#" data-bs-toggle="modal" data-bs-target="#modalEditTopik" onclick="document.getElementById('inputIdTopikEdit').value='<?= $id_topik ?>'; document.getElementById('inputNamaTopikEdit').value='<?= addslashes(htmlspecialchars($nama_topik)) ?>';">
                                    <i class="bi bi-pencil-square me-2"></i> Edit Judul Bab
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item fw-semibold text-danger py-2" href="hapusTopik.php?id=<?= $id_topik ?>&id_mapel=<?= $id_mapel ?>&kelas=<?= urlencode($kelas) ?>" onclick="return confirm('Yakin ingin menghapus bab ini beserta seluruh materi dan tugas di dalamnya?')">
                                    <i class="bi bi-trash-fill me-2"></i> Hapus Bab
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>

                <div id="collapseTopik<?= $id_topik ?>" class="collapse <?= $is_first ? 'show' : '' ?>">
                    <div class="section-body">
                        
                        <?php
                        $ada_konten = false;
                        
                        // ==========================================
                        // RENDER KONTEN MATERI 
                        // ==========================================
                        $q_materi = mysqli_query($koneksi, "SELECT * FROM materi WHERE IDMapel='$id_mapel' AND IDTopik='$id_topik'");
                        while($mt = mysqli_fetch_assoc($q_materi)): $ada_konten = true;
                        ?>
                            <div class="resource-item" data-bs-toggle="collapse" data-bs-target="#detailMateri<?= $mt['IDMateri'] ?>">
                                <div class="icon-box bg-primary bg-opacity-10 text-primary"><i class="bi bi-file-earmark-text-fill"></i></div>
                                <div class="flex-grow-1">
                                    <div class="fw-bold text-dark mb-1"><?= htmlspecialchars($mt['Judul']) ?></div>
                                    <?php 
                                    // Membersihkan nama file materi dari angka random
                                    $file_asli_materi = explode('_', $mt['Filepath']);
                                    array_shift($file_asli_materi); // Buang bagian pertama (MATERI)
                                    array_shift($file_asli_materi); // Buang bagian kedua (Timestamp)
                                    array_shift($file_asli_materi); // Buang bagian ketiga (Angka Random)
                                    $nama_bersih_materi = !empty($file_asli_materi) ? implode('_', $file_asli_materi) : basename($mt['Filepath']);
                                    // Jika nama masih kosong karena format beda, pakai nama mentah
                                    if($nama_bersih_materi == "") $nama_bersih_materi = $mt['Filepath'];
                                    ?>
                                    <div class="small text-muted"><i class="bi bi-paperclip me-1"></i> <?= htmlspecialchars($nama_bersih_materi) ?></div>
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
                        // RENDER KONTEN TUGAS 
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
            <?php endforeach; ?>

            <div class="text-center mt-4 pt-2 mb-5">
                <button class="btn btn-outline-primary border-2 fw-bold rounded-pill px-4 shadow-sm py-2" data-bs-toggle="modal" data-bs-target="#modalTambahTopik">
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
                </form>
            </div>
        </div>
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

    <div class="modal fade" id="modalEditDeskripsi" tabindex="-1"><div class="modal-dialog modal-dialog-centered modal-lg"><div class="modal-content border-0 shadow-lg rounded-4"><form method="POST"><div class="modal-body p-4 bg-light"><textarea name="deskripsi_baru" class="form-control shadow-sm border-0" rows="5" required><?= htmlspecialchars($mapel['Deskripsi'] ?? '') ?></textarea></div><div class="modal-footer border-0 p-3"><button type="submit" name="simpan_deskripsi" class="btn btn-primary px-4 fw-bold">Simpan</button></div></form></div></div></div>
    <div class="modal fade" id="modalTambahTopik" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0 shadow-lg rounded-4"><form method="POST"><div class="modal-body p-4 pt-4"><label class="form-label small fw-bold">Judul Bagian / Bab <span class="text-danger">*</span></label><input type="text" name="nama_topik" class="form-control" required></div><div class="modal-footer border-0 p-3"><button type="submit" name="tambah_topik" class="btn btn-primary w-100 fw-bold rounded-pill">Tambahkan</button></div></form></div></div></div>
    <div class="modal fade" id="modalEditTopik" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0 shadow-lg rounded-4"><form method="POST"><div class="modal-body p-4 pt-4"><input type="hidden" name="id_topik" id="inputIdTopikEdit"><label class="form-label small fw-bold">Nama Baru <span class="text-danger">*</span></label><input type="text" name="nama_topik_baru" id="inputNamaTopikEdit" class="form-control" required></div><div class="modal-footer border-0 p-3"><button type="submit" name="edit_topik" class="btn btn-primary w-100 fw-bold rounded-pill">Simpan Perubahan</button></div></form></div></div></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.getElementById('sidebarToggle').addEventListener('click', function() { document.getElementById('sidebar-course').classList.toggle('collapsed'); });
        
        function bukaModalAktivitas(idTopik, namaTopik) { document.getElementById('labelTargetTopik').innerText = namaTopik; document.getElementById('inputTopikMateri').value = idTopik; document.getElementById('inputTopikTugas').value = idTopik; new bootstrap.Modal(document.getElementById('modalPilihAktivitas')).show(); }
        function pindahModal(idModalTujuan) { bootstrap.Modal.getInstance(document.getElementById('modalPilihAktivitas')).hide(); new bootstrap.Modal(document.getElementById(idModalTujuan)).show(); }
        
        function showFileMateri(input) { const f = input.files[0]; if (!f) return; document.getElementById('namaFileMateri').textContent = f.name; document.getElementById('previewMateriBox').classList.remove('d-none'); document.getElementById('previewMateriBox').classList.add('d-flex'); document.getElementById('zoneMateri').classList.add('d-none'); }
        function clearFileMateri() { document.getElementById('fileMateri').value = ''; document.getElementById('previewMateriBox').classList.add('d-none'); document.getElementById('previewMateriBox').classList.remove('d-flex'); document.getElementById('zoneMateri').classList.remove('d-none'); }

        // Fungsi Highlight Sidebar Aktif
        function setActiveSidebar(element) {
            document.querySelectorAll('.index-item').forEach(el => el.classList.remove('active'));
            element.classList.add('active');
        }

        // =========================================================================
        // MESIN SINKRONISASI 2 ARAH (SIDEBAR <--> CARD UTAMA)
        // =========================================================================
        
        // Fungsi ini hanya bertugas meluncurkan (scroll) layar ke posisi yang pas
        function bukaSectionUtama(idTopik, idTargetKonten = null) {
            document.querySelectorAll('.index-item').forEach(el => el.classList.remove('active'));

            const sectionTarget = document.getElementById('section-' + idTopik);
            if (sectionTarget && !idTargetKonten) {
                // Scroll mulus ke Card Utama
                const y = sectionTarget.getBoundingClientRect().top + window.scrollY - 80;
                window.scrollTo({top: y, behavior: 'smooth'});
            }

            // Jika mengeklik sub-konten (materi/tugas) di sidebar
            if(idTargetKonten) {
                setTimeout(() => {
                    const kontenTarget = document.querySelector(idTargetKonten);
                    if(kontenTarget && !kontenTarget.classList.contains('show')) {
                        new bootstrap.Collapse(kontenTarget, { toggle: false }).show();
                    }
                    if (kontenTarget) {
                        const y = kontenTarget.previousElementSibling.getBoundingClientRect().top + window.scrollY - 90;
                        window.scrollTo({top: y, behavior: 'smooth'});
                    }
                }, 350); 
            }
        }

        // Listener Otomatis untuk membuka/menutup Card secara bersilangan
        let isSyncing = false; // Gembok anti macet (infinite loop)

        document.addEventListener('DOMContentLoaded', function () {
            const mainCollapses = document.querySelectorAll('.course-chapter .collapse');
            const sidebarCollapses = document.querySelectorAll('.sidebar-accordion .accordion-collapse');

            // 1. JIKA SIDEBAR DIKLIK -> CARD UTAMA IKUT BUKA/TUTUP
            sidebarCollapses.forEach(sideLaci => {
                sideLaci.addEventListener('show.bs.collapse', function (e) {
                    if (e.target !== this || isSyncing) return;
                    isSyncing = true;
                    
                    const idTopik = this.id.replace('sideCollapse', '');
                    const mainLaci = document.getElementById('collapseTopik' + idTopik);
                    const titleWrapper = document.querySelector('#section-' + idTopik + ' .section-title-wrapper');

                    if (mainLaci && !mainLaci.classList.contains('show')) {
                        new bootstrap.Collapse(mainLaci, { toggle: false }).show();
                        if(titleWrapper) titleWrapper.setAttribute('aria-expanded', 'true');
                    }
                    setTimeout(() => { isSyncing = false; }, 10);
                });

                sideLaci.addEventListener('hide.bs.collapse', function (e) {
                    if (e.target !== this || isSyncing) return;
                    isSyncing = true;
                    
                    const idTopik = this.id.replace('sideCollapse', '');
                    const mainLaci = document.getElementById('collapseTopik' + idTopik);
                    const titleWrapper = document.querySelector('#section-' + idTopik + ' .section-title-wrapper');

                    if (mainLaci && mainLaci.classList.contains('show')) {
                        new bootstrap.Collapse(mainLaci, { toggle: false }).hide();
                        if(titleWrapper) titleWrapper.setAttribute('aria-expanded', 'false');
                    }
                    setTimeout(() => { isSyncing = false; }, 10);
                });
            });

            // 2. JIKA CARD UTAMA DIKLIK -> SIDEBAR IKUT BUKA/TUTUP
            mainCollapses.forEach(mainLaci => {
                mainLaci.addEventListener('show.bs.collapse', function (e) {
                    if (e.target !== this || isSyncing) return; // Abaikan jika yang diklik sub-materi
                    isSyncing = true;
                    
                    const idTopik = this.id.replace('collapseTopik', '');
                    const sidebarLaci = document.getElementById('sideCollapse' + idTopik);

                    if (sidebarLaci && !sidebarLaci.classList.contains('show')) {
                        new bootstrap.Collapse(sidebarLaci, { toggle: false }).show();
                    }
                    setTimeout(() => { isSyncing = false; }, 10);
                });

                mainLaci.addEventListener('hide.bs.collapse', function (e) {
                    if (e.target !== this || isSyncing) return;
                    isSyncing = true;
                    
                    const idTopik = this.id.replace('collapseTopik', '');
                    const sidebarLaci = document.getElementById('sideCollapse' + idTopik);

                    if (sidebarLaci && sidebarLaci.classList.contains('show')) {
                        new bootstrap.Collapse(sidebarLaci, { toggle: false }).hide();
                    }
                    setTimeout(() => { isSyncing = false; }, 10);
                });
            });
        });
        // =========================================================================

        // Fitur Buka/Tutup Semua Section
        let isAllOpen = false;
        function toggleAllSections() {
            const sections = document.querySelectorAll('.course-chapter');
            const btnText = document.getElementById('textToggleAll');
            const btnIcon = document.querySelector('#btnToggleAll i');

            isAllOpen = !isAllOpen; 

            sections.forEach(section => {
                const targetId = section.querySelector('.section-title-wrapper').getAttribute('data-bs-target');
                const collapseDiv = document.querySelector(targetId);
                const bsCollapse = new bootstrap.Collapse(collapseDiv, { toggle: false });
                
                if (isAllOpen) { bsCollapse.show(); } else { bsCollapse.hide(); }
            });

            if (isAllOpen) {
                btnText.innerText = "Tutup Semua Bab";
                btnIcon.className = "bi bi-arrows-collapse me-1";
            } else {
                btnText.innerText = "Buka Semua Bab";
                btnIcon.className = "bi bi-arrows-expand me-1";
            }
        }

        // Fungsi untuk Auto-Populate Form Edit Tugas
        function bukaModalEditTugas(btnElement) {
            const id = btnElement.getAttribute('data-id');
            const judul = btnElement.getAttribute('data-judul');
            const deskripsi = btnElement.getAttribute('data-deskripsi');
            const deadline = btnElement.getAttribute('data-deadline');
            const poin = btnElement.getAttribute('data-poin');
            const tipeFileStr = btnElement.getAttribute('data-tipe');

            document.getElementById('editTugasId').value = id;
            document.getElementById('editTugasJudul').value = judul;
            document.getElementById('editTugasDeskripsi').value = deskripsi;
            document.getElementById('editTugasDeadline').value = deadline;
            document.getElementById('editTugasPoin').value = poin;

            const allowedFiles = tipeFileStr ? tipeFileStr.split(', ') : [];
            const checkboxes = document.querySelectorAll('.edit-tf-check');
            checkboxes.forEach(cb => { cb.checked = allowedFiles.includes(cb.value); });

            new bootstrap.Modal(document.getElementById('modalEditTugas')).show();
        }

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