<?php
session_start();
require '../login/koneksi.php';

// Pastikan yang masuk adalah siswa
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'siswa'){
    header("Location: ../login/login.php"); exit;
}

$id_user = $_SESSION['IDUser'] ?? '';
$id_mapel = mysqli_real_escape_string($koneksi, $_GET['id_mapel'] ?? '');

if(empty($id_mapel)){ header("Location: siswa.php"); exit; }

// 1. Ambil Data Siswa
$query_siswa = mysqli_query($koneksi, "SELECT * FROM siswa WHERE IDUser='$id_user'");
$siswa = mysqli_fetch_assoc($query_siswa);
$id_siswa = $siswa['IDSiswa'] ?? '';
$nama_lengkap = $siswa['Nama'] ?? $siswa['NamaSiswa'] ?? 'Siswa';

// 2. Ambil Data Mapel
$query_mapel = mysqli_query($koneksi, "
    SELECT m.*, g.NamaGuru 
    FROM mapel m 
    LEFT JOIN guru g ON m.IDGuru = g.IDGuru 
    WHERE m.IDMapel='$id_mapel'
");
if(mysqli_num_rows($query_mapel) == 0){ header("Location: siswa.php"); exit; }
$mapel = mysqli_fetch_assoc($query_mapel);

// 3. Ambil Daftar Topik/Bab
$q_topik = mysqli_query($koneksi, "SELECT * FROM topik_mapel WHERE IDMapel = '$id_mapel' ORDER BY Urutan ASC");
$daftar_topik = [];
while($t = mysqli_fetch_assoc($q_topik)) { $daftar_topik[] = $t; }

// 4. PRE-LOAD SEMUA TUGAS & STATUS PENGUMPULAN
$q_semua_tugas = mysqli_query($koneksi, "SELECT * FROM tugas WHERE IDMapel='$id_mapel'");
$semua_tugas = [];
$tugas_selesai = [];
while($rt = mysqli_fetch_assoc($q_semua_tugas)){
    $id_t = $rt['IDTugas'];
    $q_kumpul = mysqli_query($koneksi, "SELECT * FROM pengumpulan_tugas WHERE IDTugas='$id_t' AND IDSiswa='$id_siswa'");
    $kumpul = mysqli_fetch_assoc($q_kumpul);
    
    $rt['pengumpulan'] = $kumpul; 
    $semua_tugas[$id_t] = $rt;
    
    if(!empty($kumpul)) { $tugas_selesai[] = $id_t; }
}

function hitungWaktuTersisa($deadline_str, $tgl_kumpul_str = null) {
    $deadline = strtotime($deadline_str);
    $now = time();
    if($tgl_kumpul_str) {
        $kumpul = strtotime($tgl_kumpul_str);
        $diff = $deadline - $kumpul;
        if($diff >= 0) {
            $hari = floor($diff / (60 * 60 * 24));
            $jam = floor(($diff - ($hari * 60 * 60 * 24)) / (60 * 60));
            return "Tugas diajukan " . ($hari > 0 ? "$hari hari, " : "") . "$jam jam lebih awal";
        } else {
            $diff = abs($diff);
            $hari = floor($diff / (60 * 60 * 24));
            $jam = floor(($diff - ($hari * 60 * 60 * 24)) / (60 * 60));
            return "Terlambat " . ($hari > 0 ? "$hari hari, " : "") . "$jam jam";
        }
    } else {
        $diff = $deadline - $now;
        if($diff < 0) return "<span class='text-danger fw-bold'>Batas waktu telah terlewat!</span>";
        $hari = floor($diff / (60 * 60 * 24));
        $jam = floor(($diff - ($hari * 60 * 60 * 24)) / (60 * 60));
        return "Sisa $hari hari, $jam jam lagi";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($mapel['NamaMapel']) ?> - Ruang Kelas LMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    
    <style>
        :root {
            --primary: #4f46e5; --primary-dark: #3730a3; --primary-light: #e0e7ff;
            --secondary: #0ea5e9; --bg-light: #f4f6f9; --text-dark: #1e293b;
            --gradient-primary: linear-gradient(135deg, #4f46e5, #0ea5e9);
        }
        body { background-color: var(--bg-light); color: var(--text-dark); font-family: 'Segoe UI', system-ui, sans-serif; overflow-x: hidden; }
        .navbar-custom { background: var(--gradient-primary) !important; box-shadow: 0 2px 10px rgba(0,0,0,0.1); padding: 10px 0; z-index: 1030;}
        #wrapper { display: flex; width: 100%; align-items: stretch; min-height: calc(100vh - 66px); }
        #sidebar-course { min-width: 280px; max-width: 280px; background: #fff; border-right: 1px solid #e2e8f0; position: sticky; top: 66px; height: calc(100vh - 66px); overflow-y: auto; padding: 20px 15px; }
        .course-index-title { font-size: 0.8rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 15px; padding-left: 10px; }
        .index-item { display: block; padding: 10px 15px; color: #475569; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 0.9rem; transition: 0.2s; margin-bottom: 5px; cursor: pointer; }
        .index-item:hover { background: var(--primary-light); color: var(--primary); }
        .index-item.active { background: var(--primary); color: #fff; box-shadow: 0 4px 10px rgba(79, 70, 229, 0.2); }
        
        #main-content { width: 100%; padding: 40px; }
        .page-title { font-weight: 800; font-size: 2rem; color: var(--text-dark); margin-bottom: 5px; text-transform: uppercase;}
        
        /* ACCORDION KELAS */
        .section-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 2px 6px rgba(0,0,0,0.02); overflow: hidden;}
        .section-header { padding: 18px 25px; cursor: pointer; display: flex; align-items: center; justify-content: space-between; transition: 0.2s; background: #fff; }
        .section-header:hover { background: #f8fafc; }
        .section-title { font-weight: 700; color: var(--text-dark); font-size: 1.15rem; margin: 0; display: flex; align-items: center;}
        
        .sidebar-accordion .accordion-button { padding: 10px 15px; color: #475569; border-radius: 8px; font-weight: 600; font-size: 0.9rem; margin-bottom: 2px; }
        .sidebar-accordion .accordion-button:not(.collapsed) { background: #e0e7ff; color: #4f46e5; box-shadow: none; }
        .sidebar-accordion .accordion-button:focus { box-shadow: none; }
        .sidebar-accordion .accordion-button::after { transform: scale(0.8); }
        .sidebar-subitem { display: block; padding: 6px 15px 6px 35px; font-size: 0.85rem; color: #64748b; text-decoration: none; transition: 0.2s; border-radius: 8px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;}
        .sidebar-subitem:hover { color: #4f46e5; background: #f8fafc; }
        
        .section-title-wrapper { display: flex; align-items: center; flex-grow: 1; cursor: pointer; }
        .toggle-icon { font-size: 1.2rem; color: #94a3b8; transition: transform 0.3s ease; }
        .section-title-wrapper[aria-expanded="true"] .toggle-icon { transform: rotate(90deg); color: var(--primary); }
        .section-title-wrapper[aria-expanded="true"] { border-bottom: 1px solid #e2e8f0; }
        .section-body { padding: 10px 25px 25px 25px; }

        /* ITEM KONTEN */
        .content-item { display: flex; align-items: center; justify-content: space-between; padding: 15px 20px; border: 1px solid #e2e8f0; border-radius: 10px; margin-top: 15px; transition: 0.2s; background: #fff; }
        .content-item:hover { border-color: var(--primary); box-shadow: 0 4px 12px rgba(79,70,229,0.08); transform: translateX(5px); }
        .content-icon { width: 45px; height: 45px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; margin-right: 20px; flex-shrink: 0; }
        .icon-materi { background: #e0e7ff; color: var(--primary); }
        .icon-tugas { background: #dcfce7; color: #10b981; }
        .content-info { flex-grow: 1; }
        .content-title { font-weight: 700; color: var(--text-dark); margin-bottom: 3px; font-size: 1.05rem; }
        
        .btn-selesai { border: 2px solid #cbd5e1; color: #64748b; background: transparent; border-radius: 6px; font-weight: 700; font-size: 0.8rem; padding: 6px 14px; transition: 0.3s; white-space: nowrap; }
        .btn-selesai:hover { border-color: #10b981; color: #10b981; background: #f0fdf4; }
        .btn-selesai.done { background: #10b981; border-color: #10b981; color: #fff; box-shadow: 0 4px 10px rgba(16, 185, 129, 0.3); }

        /* TAMPILAN DETAIL TUGAS */
        .tugas-detail-box { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); padding: 30px; margin-bottom: 30px; }
        .tugas-table { border-collapse: separate; border-spacing: 0; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; width: 100%; margin-bottom: 0; }
        .tugas-table th { background-color: #f8fafc; color: #475569; font-weight: 600; width: 30%; padding: 15px 20px; border-bottom: 1px solid #e2e8f0; border-right: 1px solid #e2e8f0; }
        .tugas-table td { padding: 15px 20px; border-bottom: 1px solid #e2e8f0; color: #334155; }
        .tugas-table tr:last-child th, .tugas-table tr:last-child td { border-bottom: none; }
        .status-hijau { background-color: #d1e7dd !important; color: #0f5132 !important; font-weight: 600;}
        
        .breadcrumb-custom { font-size: 0.9rem; font-weight: 600; color: #64748b; margin-bottom: 20px; }
        .breadcrumb-custom a { color: var(--primary); text-decoration: none; }
        .breadcrumb-custom a:hover { text-decoration: underline; }

        @media (max-width: 992px) { #sidebar-course { display: none; } #main-content { padding: 20px; } }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom sticky-top">
        <div class="container-fluid px-4">
            <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="siswa.php">
                <i class="bi bi-mortarboard-fill fs-4"></i> LMS Wongsorejo
            </a>
            <div class="d-flex align-items-center gap-3">
                <a href="siswa.php" class="btn btn-light btn-sm rounded-pill fw-bold px-3 text-primary shadow-sm">
                    <i class="bi bi-arrow-left me-1"></i> Dashboard
                </a>
            </div>
        </div>
    </nav>

    <div id="wrapper">
        <nav id="sidebar-course">
            <div class="p-2 mb-2 border-bottom text-center">
                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary w-100 py-2"><i class="bi bi-book me-1"></i> <?= htmlspecialchars($mapel['NamaMapel']) ?></span>
            </div>
            <div class="course-index-title mt-3">DAFTAR ISI KELAS</div>
            <a href="#" class="index-item active mb-3" id="nav-beranda" onclick="showCourseList()">
                <i class="bi bi-house-door me-2"></i> Beranda Kelas
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
                            $q_sm = mysqli_query($koneksi, "SELECT IDMateri, Judul FROM materi WHERE IDMapel='$id_mapel' AND IDTopik='$id_tp'");
                            while($sm = mysqli_fetch_assoc($q_sm)): ?>
                                <a href="javascript:void(0)" class="sidebar-subitem" onclick="bukaSectionUtama('<?= $id_tp ?>', '#itemMateri<?= $sm['IDMateri'] ?>')"><i class="bi bi-file-earmark-text text-primary me-2"></i><?= htmlspecialchars($sm['Judul']) ?></a>
                            <?php endwhile; ?>
                            
                            <?php 
                            $q_st = mysqli_query($koneksi, "SELECT IDTugas, Judul FROM tugas WHERE IDMapel='$id_mapel' AND IDTopik='$id_tp'");
                            while($st = mysqli_fetch_assoc($q_st)): ?>
                                <a href="javascript:void(0)" class="sidebar-subitem" onclick="openTaskDetail('<?= $st['IDTugas'] ?>')"><i class="bi bi-journal-check text-success me-2"></i><?= htmlspecialchars($st['Judul']) ?></a>
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
            
            <!-- SECTION 1: KOTAK BERANDA KELAS (Daftar Akordion Bab) -->
            <div id="course-list-view">
                <div class="d-flex justify-content-between align-items-end mb-4">
                    <div>
                        <h1 class="page-title"><?= htmlspecialchars($mapel['NamaMapel']) ?></h1>
                        <div class="text-muted"><i class="bi bi-person-video3 me-2"></i>Pengampu: <strong class="text-primary"><?= htmlspecialchars($mapel['NamaGuru'] ?? 'Belum Ditentukan') ?></strong></div>
                    </div>
                    <button class="btn btn-sm btn-outline-primary fw-bold px-3 rounded-pill" onclick="toggleAllChapters()" id="btnToggleAll">
                        <i class="bi bi-arrows-expand me-1"></i> <span id="txtToggleAll">Buka Semua Bab</span>
                    </button>
                </div>

                <?php foreach($daftar_topik as $index => $tp): 
                    $id_topik = $tp['IDTopik'];
                    $is_first = ($index == 0); 
                ?>
                <div class="section-card course-chapter" id="section-<?= $id_topik ?>">
                    <div class="section-header section-title-wrapper" data-bs-toggle="collapse" data-bs-target="#collapseTopik<?= $id_topik ?>" aria-expanded="<?= $is_first ? 'true' : 'false' ?>">
                        <h3 class="section-title"><i class="bi bi-bookmark-fill text-primary opacity-50 me-2"></i> <?= htmlspecialchars($tp['NamaTopik']) ?></h3>
                        <i class="bi bi-chevron-right toggle-icon"></i>
                    </div>
                    
                    <div id="collapseTopik<?= $id_topik ?>" class="collapse <?= $is_first ? 'show' : '' ?>">
                        <div class="section-body border-top-0">
                            
                            <?php $ada_konten = false;
                            
                            $q_materi = mysqli_query($koneksi, "SELECT * FROM materi WHERE IDMapel='$id_mapel' AND IDTopik='$id_topik'");
                            while($mt = mysqli_fetch_assoc($q_materi)): $ada_konten = true; 
                            ?>
                                <div class="content-item" id="itemMateri<?= $mt['IDMateri'] ?>">
                                    <a href="../dokumen_materi/<?= htmlspecialchars($mt['Filepath']) ?>" download class="d-flex align-items-center flex-grow-1 text-decoration-none">
                                        <div class="content-icon icon-materi"><i class="bi bi-file-earmark-arrow-down-fill"></i></div>
                                        <div class="content-info">
                                            <div class="content-title"><?= htmlspecialchars($mt['Judul']) ?></div>
                                            <!-- NAMA FILE ASLI MURNI DARI DATABASE -->
                                            <div class="small text-muted"><i class="bi bi-cloud-download me-1"></i> Unduh: <?= htmlspecialchars($mt['Filepath']) ?></div>
                                        </div>
                                    </a>
                                    <button class="btn-selesai" onclick="toggleSelesai(this, 'materi_<?= $mt['IDMateri'] ?>')">
                                        <i class="bi bi-circle me-1"></i> Tandai Selesai
                                    </button>
                                </div>
                            <?php endwhile; ?>

                            <?php 
                            $q_tugas_topik = mysqli_query($koneksi, "SELECT IDTugas FROM tugas WHERE IDMapel='$id_mapel' AND IDTopik='$id_topik'");
                            while($tgt = mysqli_fetch_assoc($q_tugas_topik)): 
                                $tg = $semua_tugas[$tgt['IDTugas']];
                                $ada_konten = true; 
                                $sudah_kumpul = in_array($tg['IDTugas'], $tugas_selesai);
                            ?>
                                <div class="content-item" id="itemTugas<?= $tg['IDTugas'] ?>">
                                    <div class="d-flex align-items-center flex-grow-1" style="cursor: pointer;" onclick="openTaskDetail('<?= $tg['IDTugas'] ?>')">
                                        <div class="content-icon icon-tugas"><i class="bi bi-journal-check"></i></div>
                                        <div class="content-info">
                                            <div class="content-title"><?= htmlspecialchars($tg['Judul']) ?></div>
                                            <div class="small text-danger fw-semibold"><i class="bi bi-clock-history me-1"></i> Tenggat: <?= date('d M Y, H:i', strtotime($tg['Deadline'])) ?></div>
                                        </div>
                                    </div>
                                    
                                    <?php if($sudah_kumpul): ?>
                                        <button class="btn-selesai done" disabled><i class="bi bi-check-circle-fill me-1"></i> Dikumpulkan</button>
                                    <?php else: ?>
                                        <button class="btn-selesai text-primary border-primary" onclick="openTaskDetail('<?= $tg['IDTugas'] ?>')">
                                            <i class="bi bi-pencil-square me-1"></i> Kerjakan
                                        </button>
                                    <?php endif; ?>
                                </div>
                            <?php endwhile; ?>

                            <?php if(!$ada_konten): ?>
                                <div class="text-center py-4"><div class="text-muted small p-3 bg-light rounded border border-dashed">Belum ada materi atau tugas di bab ini.</div></div>
                            <?php endif; ?>

                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- SECTION 2: LACI DETAIL TUGAS (Tampil saat tugas di-klik) -->
            <?php foreach($semua_tugas as $id_tugas => $tugas): 
                $kumpul = $tugas['pengumpulan'];
                $is_submitted = !empty($kumpul);
                
                // Cari Nama Topik untuk Breadcrumb
                $nama_topik_tgs = '';
                foreach($daftar_topik as $t){ if($t['IDTopik'] == $tugas['IDTopik']){ $nama_topik_tgs = $t['NamaTopik']; break; } }
            ?>
            <div id="task-detail-<?= $id_tugas ?>" class="d-none task-view-container">
                
                <div class="breadcrumb-custom">
                    <i class="bi bi-folder2-open me-1"></i> <a href="#" onclick="showCourseList()">Beranda Kelas</a> <i class="bi bi-chevron-right mx-1 text-muted" style="font-size:0.7rem;"></i> <?= htmlspecialchars($nama_topik_tgs) ?>
                </div>

                <div class="tugas-detail-box">
                    <div class="d-flex justify-content-between align-items-start mb-4 border-bottom pb-3">
                        <h3 class="fw-bold text-dark mb-0 d-flex align-items-center gap-3">
                            <i class="bi bi-journal-text text-danger fs-2"></i> <?= htmlspecialchars($tugas['Judul']) ?>
                        </h3>
                        <div class="d-flex gap-2">
                            <button class="btn-selesai <?= $is_submitted ? 'done' : '' ?>" disabled style="opacity: 1;">
                                <i class="bi <?= $is_submitted ? 'bi-check-circle-fill' : 'bi-circle' ?> me-1"></i> Selesai
                            </button>
                        </div>
                    </div>

                    <div class="bg-light p-4 rounded-3 border mb-4">
                        <h6 class="fw-bold text-dark mb-3"><i class="bi bi-info-square me-2 text-primary"></i>Instruksi Pengerjaan / Soal:</h6>
                        <div class="text-dark mb-3" style="line-height: 1.7;">
                            <?= nl2br(htmlspecialchars($tugas['Deskripsi'] ?? 'Silakan kerjakan tugas sesuai instruksi guru.')) ?>
                        </div>
                        
                        <?php if($is_submitted): ?>
                        <div class="pt-3 border-top d-flex gap-2">
                            <button class="btn btn-sm btn-outline-primary fw-bold px-3" onclick="alert('Fitur edit pengajuan dalam pengembangan!')"><i class="bi bi-pencil-square me-1"></i> Edit Pengajuan</button>
                            <button class="btn btn-sm btn-outline-danger fw-bold px-3" onclick="alert('Fitur hapus pengajuan dalam pengembangan!')"><i class="bi bi-trash me-1"></i> Hapus Pengajuan</button>
                        </div>
                        <?php endif; ?>
                    </div>

                    <h5 class="fw-bold text-dark mb-3">Status pengajuan tugas</h5>
                    <table class="tugas-table">
                        <tr>
                            <th>Status pengajuan</th>
                            <td class="<?= $is_submitted ? 'status-hijau' : '' ?>">
                                <?= $is_submitted ? 'Terkirim dan siap dinilai' : 'Belum ada pengajuan' ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Status penilaian</th>
                            <td>
                                <?php 
                                if($is_submitted && isset($kumpul['Nilai'])) echo "<span class='fw-bold text-success'>Dinilai (Skor: {$kumpul['Nilai']})</span>";
                                else echo "Belum dinilai";
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Waktu tersisa</th>
                            <td class="<?= (!$is_submitted && strtotime($tugas['Deadline']) < time()) ? 'text-danger fw-bold' : 'status-hijau' ?>">
                                <?= hitungWaktuTersisa($tugas['Deadline'], $kumpul['TanggalKirim'] ?? null) ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Terakhir diubah</th>
                            <td><?= $is_submitted ? date('l, d M Y, H:i', strtotime($kumpul['TanggalKirim'])) : '-' ?></td>
                        </tr>
                        <tr>
                            <th>Pengajuan berkas</th>
                            <td>
                                <?php if($is_submitted): 
                                    $file_db = $kumpul['FileJawaban'] ?? $kumpul['FileKumpul'] ?? '';
                                ?>
                                    <!-- NAMA FILE ASLI MURNI DARI DATABASE -->
                                    <a href="../uploads/tugas/<?= htmlspecialchars($file_db) ?>" target="_blank" class="text-decoration-none fw-bold text-primary">
                                        <i class="bi bi-file-earmark-arrow-down-fill me-1"></i> <?= htmlspecialchars($file_db) ?>
                                    </a>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php if($is_submitted && !empty($kumpul['KomentarGuru'])): ?>
                        <tr>
                            <th>Komentar Guru</th>
                            <td class="fst-italic text-secondary">"<?= htmlspecialchars($kumpul['KomentarGuru']) ?>"</td>
                        </tr>
                        <?php endif; ?>
                    </table>

                    <?php if(!$is_submitted): ?>
                    <div class="mt-5 pt-3">
                        <h5 class="fw-bold text-dark mb-3">Formulir Pengumpulan Tugas</h5>
                        <form action="proses_kumpul_tugas.php" method="POST" enctype="multipart/form-data" class="bg-light p-4 rounded-3 border">
                            <input type="hidden" name="id_tugas" value="<?= $id_tugas ?>">
                            <div class="mb-4">
                                <label class="form-label fw-bold text-secondary">Pilih File / Berkas Jawaban Anda <span class="text-danger">*</span></label>
                                <input type="file" class="form-control form-control-lg bg-white shadow-sm border-secondary-subtle" name="file_tugas" required>
                                <div class="form-text mt-2"><i class="bi bi-info-circle me-1"></i> Pastikan format file sesuai dengan yang diizinkan oleh guru.</div>
                            </div>
                            <button type="submit" class="btn btn-primary px-5 py-2 fw-bold shadow-sm">
                                <i class="bi bi-cloud-arrow-up-fill me-2"></i> Tambahkan Pengajuan
                            </button>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>

        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        let isSyncing = false; 

        function showCourseList() {
            document.querySelectorAll('.task-view-container').forEach(el => el.classList.add('d-none'));
            document.getElementById('course-list-view').classList.remove('d-none');
            document.querySelectorAll('.index-item').forEach(el => el.classList.remove('active'));
            document.getElementById('nav-beranda').classList.add('active');
        }

        function openTaskDetail(idTugas) {
            document.getElementById('course-list-view').classList.add('d-none');
            document.querySelectorAll('.task-view-container').forEach(el => el.classList.add('d-none'));
            document.getElementById('task-detail-' + idTugas).classList.remove('d-none');
            window.scrollTo({ top: 0, behavior: 'smooth' });
            document.querySelectorAll('.index-item').forEach(el => el.classList.remove('active'));
        }

        function bukaSectionUtama(idTopik, idTargetKonten = null) {
            document.querySelectorAll('.index-item').forEach(el => el.classList.remove('active'));
            showCourseList(); 

            const sectionTarget = document.getElementById('section-' + idTopik);
            if (sectionTarget && !idTargetKonten) {
                const y = sectionTarget.getBoundingClientRect().top + window.scrollY - 80;
                window.scrollTo({top: y, behavior: 'smooth'});
            }

            if(idTargetKonten) {
                setTimeout(() => {
                    const kontenTarget = document.querySelector(idTargetKonten);
                    if (kontenTarget) {
                        const y = kontenTarget.getBoundingClientRect().top + window.scrollY - 90;
                        window.scrollTo({top: y, behavior: 'smooth'});
                    }
                }, 350); 
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            const mainCollapses = document.querySelectorAll('.course-chapter .collapse');
            const sidebarCollapses = document.querySelectorAll('.sidebar-accordion .accordion-collapse');

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

            mainCollapses.forEach(mainLaci => {
                mainLaci.addEventListener('show.bs.collapse', function (e) {
                    if (e.target !== this || isSyncing) return; 
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

        let isAllOpen = false;
        function toggleAllChapters() {
            const sections = document.querySelectorAll('.course-chapter .collapse');
            const btnText = document.getElementById('txtToggleAll');
            const btnIcon = document.querySelector('#btnToggleAll i');
            isAllOpen = !isAllOpen;
            sections.forEach(section => {
                const bsCollapse = new bootstrap.Collapse(section, { toggle: false });
                if(isAllOpen) bsCollapse.show(); else bsCollapse.hide();
            });
            if (isAllOpen) {
                btnText.innerText = "Tutup Semua Bab";
                btnIcon.className = "bi bi-arrows-collapse me-1";
            } else {
                btnText.innerText = "Buka Semua Bab";
                btnIcon.className = "bi bi-arrows-expand me-1";
            }
        }

        // FITUR TANDAI SELESAI & ANTI-SPAM POIN
        function toggleSelesai(btn, idItem) {
            let isDone = btn.classList.contains('done');
            if(!isDone) {
                // Ubah UI seketika
                btn.classList.add('done');
                btn.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> Selesai';
                localStorage.setItem(idItem, 'selesai');
                
                // Cek memori browser: Apakah poin ini sudah diklaim sebelumnya?
                if(!localStorage.getItem(idItem + '_klaim')) {
                    fetch('proses_poin_materi.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: 'id_materi=' + encodeURIComponent(idItem)
                    })
                    .then(response => response.json()) // Baca respon sebagai JSON (bersih)
                    .then(data => {
                        if(data.status === 'sukses') {
                            localStorage.setItem(idItem + '_klaim', 'true'); // Kunci agar tidak spam
                            Swal.fire({ 
                                title: 'Materi Selesai! 📚', 
                                html: `Selamat! <b>+${data.poin} XP</b> berhasil ditambahkan.`, 
                                icon: 'success', 
                                toast: true, 
                                position: 'bottom-end', 
                                showConfirmButton: false, 
                                timer: 4000, 
                                timerProgressBar: true 
                            });
                        }
                    }).catch(error => console.log(error));
                }
            } else {
                // Boleh un-check UI, TAPI poin yang sudah masuk ke DB tetap aman (tidak nambah lagi jika diklik ulang)
                btn.classList.remove('done');
                btn.innerHTML = '<i class="bi bi-circle me-1"></i> Tandai Selesai';
                localStorage.removeItem(idItem);
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.btn-selesai').forEach(btn => {
                let idItem = btn.getAttribute('onclick');
                if(idItem && idItem.includes('toggleSelesai')) {
                    let match = idItem.match(/'([^']+)'/);
                    if(match && localStorage.getItem(match[1]) === 'selesai') {
                        btn.classList.add('done');
                        btn.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> Selesai';
                    }
                }
            });
        });
    </script>

    <?php if(isset($_GET['status']) && $_GET['status'] == 'sukses_kumpul'): 
        $poin_didapat = isset($_GET['poin']) ? htmlspecialchars($_GET['poin']) : 0;
        $nama_bonus = isset($_GET['bonus']) ? htmlspecialchars($_GET['bonus']) : 'Poin Pengumpulan';
    ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                title: 'Tugas Terkirim! 🚀',
                html: `Kerja bagus! Kamu mendapatkan <strong class="text-primary"><?= $nama_bonus ?></strong>.<br>Selamat mendapatkan <b>+<?= $poin_didapat ?> XP</b>!`,
                icon: 'success',
                confirmButtonColor: '#4f46e5',
                backdrop: `rgba(79, 70, 229, 0.1)`
            });
        });
    </script>
    <?php endif; ?>

    <script>
        // ==============================================================
        // SENSOR AUTO-OPEN TUGAS DARI HALAMAN LUAR (SMART URL)
        // ==============================================================
        document.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            const openTugasId = urlParams.get('open_tugas');
            
            if (openTugasId) {
                setTimeout(() => {
                    openTaskDetail(openTugasId);
                }, 300);
            }
        });
    </script>
</body>
</html>