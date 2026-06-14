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
$kelas_siswa = $siswa['Kelas'] ?? '';
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

// 3. Ambil Daftar Topik/Bab (SUDAH DIKUNCI BERDASARKAN KELAS)
$q_topik = mysqli_query($koneksi, "SELECT * FROM topik_mapel WHERE IDMapel = '$id_mapel' AND Kelas = '$kelas_siswa' ORDER BY Urutan ASC");
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
            --primary: #1e1b4b;          
            --primary-dark: #100f28;     
            --primary-light: #e0e7ff;    
            --secondary: #3b82f6;        
            --gradient-primary: linear-gradient(135deg, #1e1b4b, #312e81);
            --text-dark: #1e293b; 
            --text-muted: #64748b;
        }
        body { background-color: #f8fafc; color: var(--text-dark); font-family: 'Segoe UI', system-ui, sans-serif; overflow-x: hidden; }
        .navbar-custom { background: var(--gradient-primary) !important; box-shadow: 0 4px 20px rgba(30, 27, 75, 0.3); padding: 12px 0; border-bottom: 1px solid rgba(255,255,255,0.1); z-index: 1030; }
        
        /* PEMBAGIAN GRID YANG RAPI TANPA HACK */
        #wrapper { display: flex; width: 100%; align-items: stretch; min-height: calc(100vh - 66px); } 
        
        #sidebar-course { width: 260px; min-width: 260px; background: #fff; border-right: 1px solid #e2e8f0; position: sticky; top: 66px; height: calc(100vh - 66px); overflow-y: auto; padding: 25px 15px; }
        .course-index-title { font-size: 0.8rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 15px; padding-left: 10px; }
        .index-item { display: block; padding: 10px 15px; color: #475569; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 0.9rem; transition: 0.2s; margin-bottom: 5px; cursor: pointer; }
        .index-item:hover { background: var(--primary-light); color: var(--primary); }
        .index-item.active { background: var(--primary); color: #fff; box-shadow: 0 4px 10px rgba(79, 70, 229, 0.2); }
        
        /* Ruang Kanan untuk Teks Breadcrumb + Konten */
        #right-column { flex-grow: 1; display: flex; flex-direction: column; min-width: 0; }
        #main-content { padding: 40px; flex-grow: 1; }
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

        /* NAV BAWAH (PREVIOUS & JUMP TO) */
        .bottom-nav-box { background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 20px 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.02); display: flex; justify-content: space-between; align-items: center; margin-top: 20px; }
        .prev-activity { display: flex; align-items: center; text-decoration: none; color: #64748b; transition: 0.2s; padding-right: 25px; border-right: 1px solid #e2e8f0; }
        .prev-activity:hover { color: var(--primary); }
        .prev-activity i { font-size: 2.2rem; font-weight: 300; margin-right: 15px; }
        .prev-info { display: flex; flex-direction: column; }
        .prev-label { font-size: 0.8rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 2px; }
        .prev-title { font-weight: 700; color: var(--secondary); font-size: 1.05rem; }
        .jump-select { max-width: 300px; border-color: #cbd5e1; border-radius: 8px; font-weight: 600; color: #475569; }
    </style>
</head>
<body>

<?php include 'komponen_navbar.php'; ?>

<div class="container-fluid px-0">
    <div id="wrapper">
        
        <!-- SIDEBAR KIRI -->
        <nav id="sidebar-course" class="d-none d-lg-block">
            <div class="mb-2 px-1">
                <h4 class="fw-bold text-dark text-uppercase mb-1" style="font-size: 1.35rem; letter-spacing: -0.5px; line-height: 1.2; color: #1e1b4b !important;">
                    <?= htmlspecialchars($mapel['NamaMapel']) ?>
                </h4>
                <div class="fw-bold text-secondary" style="font-size: 1.05rem;">
                    Kelas <?= htmlspecialchars($kelas_siswa) ?>
                </div>
            </div>
            
            <div class="course-index-title mt-4">DAFTAR ISI KELAS</div>
            
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
                    <div id="sideCollapse<?= $id_tp ?>" class="accordion-collapse collapse">
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

        <!-- RUANG KANAN (BREADCRUMB + KONTEN) -->
        <div id="right-column">
            
            <!-- BREADCRUMB (BERBAUR DENGAN BACKGROUND BAWAAN) -->
            <div class="px-4 border-bottom d-flex align-items-center sticky-top" style="height: 55px; top: 66px; z-index: 1020; border-color: #e2e8f0 !important; background-color: #f8fafc;">
                <div class="d-flex align-items-center gap-2 text-muted fw-semibold" style="font-size: 0.95rem;">
                    <i class="bi bi-house-door-fill text-secondary"></i> 
                    <a href="siswa.php" class="text-decoration-none transition" style="color: #3b82f6;">Dashboard</a> 
                    <i class="bi bi-chevron-right opacity-50" style="font-size: 0.75rem;"></i> 
                    <span class="text-secondary fw-normal"><?= htmlspecialchars($mapel['NamaMapel']) ?></span>
                </div>
            </div>

            <!-- KONTEN TENGAH -->
            <main id="main-content">
                
                <!-- TAMPILAN KELAS MATERI & TUGAS -->
                <div id="course-list-view">
                    
                    <!-- HEADER UTAMA MAPEL -->
                    <div class="mb-4">
                        <!-- Card Pengantar -->
                        <div class="card border-0 shadow-sm rounded-4 mb-3" style="background: #ffffff; border: 1px solid #f1f5f9 !important;">
                            <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
                                <h5 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2" style="font-size: 1.1rem; color: #1e1b4b !important;">
                                    <i class="bi bi-info-circle-fill text-primary"></i> Pengantar Mata Pelajaran
                                </h5>
                            </div>
                            <div class="card-body px-4 pb-4 pt-2">
                                <p class="text-muted mb-0 fs-6" style="line-height: 1.7; color: #475569 !important;">
                                    <?= !empty($mapel['Deskripsi']) ? nl2br(htmlspecialchars($mapel['Deskripsi'])) : '<i>Belum ada deskripsi pengantar untuk mata pelajaran ini.</i>' ?>
                                </p>
                            </div>
                        </div>

                        <!-- Informasi Guru Pengampu -->
                        <div class="text-muted small fw-semibold d-flex align-items-center gap-2 ps-2 mb-3">
                            <i class="bi bi-person-video3 text-primary fs-6"></i>
                            <span>Pengampu: <strong class="text-primary fs-6 fw-bold"><?= htmlspecialchars($mapel['NamaGuru'] ?? 'Belum Ditentukan') ?></strong></span>
                        </div>

                        <!-- Garis Pemisah & Tombol Buka -->
                        <hr class="text-secondary opacity-25 my-3">
                        <div class="d-flex justify-content-end mt-3 mb-2">
                            <button class="btn btn-sm fw-bold px-4 py-2 rounded-pill transition shadow-sm" style="border: 1px solid #cbd5e1; color: #475569; background: #fff;" onclick="toggleAllChapters()" id="btnToggleAll">
                                <i class="bi bi-arrows-expand me-1"></i> <span id="txtToggleAll">Buka Semua Bab</span>
                            </button>
                        </div>
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
                                                <div class="small text-muted"><i class="bi bi-cloud-download me-1"></i> Unduh: <?= htmlspecialchars($mt['Filepath']) ?></div>
                                            </div>
                                        </a>
                                        <button class="btn-selesai" onclick="toggleSelesai(this, 'materi_<?= $mt['IDMateri'] ?>')">
                                            <i class="bi bi-circle me-1"></i> Tandai Selesai
                                        </button>
                                    </div>
                                <?php endwhile; ?>

                                <?php 
                                // RENDER KUIS
                                $q_kuis = mysqli_query($koneksi, "SELECT * FROM kuis WHERE IDMapel='$id_mapel' AND IDTopik='$id_topik'");
                                while($kq = mysqli_fetch_assoc($q_kuis)): 
                                    $ada_konten = true; 
                                    $id_kuis_render = $kq['IDKuis'];
                                    
                                    $q_cek_nilai = mysqli_query($koneksi, "SELECT NilaiAkhir, WaktuSelesai FROM kuis_nilai WHERE IDKuis='$id_kuis_render' AND IDSiswa='$id_siswa'");
                                    $data_nilai = mysqli_fetch_assoc($q_cek_nilai);
                                    
                                    $sudah_dikerjakan = (!empty($data_nilai['WaktuSelesai'])) ? true : false;
                                    $nilai_siswa = $data_nilai['NilaiAkhir'] ?? 0;
                                    $tampilkan_nilai = ($kq['Status'] == 'Draft') ? true : false; 
                                ?>
                                    <div class="content-item flex-column align-items-stretch" id="itemKuis<?= $kq['IDKuis'] ?>">
                                        <div class="d-flex align-items-center w-100" data-bs-toggle="collapse" data-bs-target="#detailKuis<?= $kq['IDKuis'] ?>" style="cursor: pointer;">
                                            <div class="content-icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-patch-question-fill"></i></div>
                                            <div class="content-info flex-grow-1">
                                                <div class="content-title"><?= htmlspecialchars($kq['Judul']) ?></div>
                                                <div class="small text-muted mt-1">
                                                    <i class="bi bi-card-checklist me-1"></i> Evaluasi / Ujian 
                                                    <?php if($sudah_dikerjakan): ?>
                                                        <?php if($tampilkan_nilai): ?>
                                                            <span class="ms-2 badge bg-success">Skor: <?= $nilai_siswa ?>/100</span>
                                                        <?php else: ?>
                                                            <span class="ms-2 badge bg-secondary">Menunggu Penilaian Guru</span>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="ms-auto text-secondary"><i class="bi bi-chevron-down fw-bold"></i></div>
                                        </div>
                                        
                                        <div class="collapse w-100 mt-3" id="detailKuis<?= $kq['IDKuis'] ?>">
                                            <div class="p-3 bg-light rounded border border-warning border-opacity-25">
                                                <div class="row g-3 mb-3">
                                                    <div class="col-sm-6">
                                                        <div class="small text-muted mb-1">Status Kuis</div>
                                                        <div class="fw-bold <?= $sudah_dikerjakan ? 'text-success' : 'text-primary' ?>"><?= $sudah_dikerjakan ? '<i class="bi bi-check-circle-fill me-1"></i> Sudah Dikerjakan' : '<i class="bi bi-record-circle-fill me-1"></i> Belum Dikerjakan' ?></div>
                                                    </div>
                                                    <div class="col-sm-6">
                                                        <div class="small text-muted mb-1">Deadline</div>
                                                        <div class="fw-bold text-dark"><i class="bi bi-calendar-event me-1"></i> <?= $kq['Deadline'] ? date('d M Y, H:i', strtotime($kq['Deadline'])) : 'Tanpa Batas Waktu' ?></div>
                                                    </div>
                                                    <div class="col-sm-6">
                                                        <div class="small text-muted mb-1">Durasi Pengerjaan</div>
                                                        <div class="fw-bold text-dark"><i class="bi bi-hourglass-split me-1"></i> <?= $kq['DurasiMenit'] ?> Menit</div>
                                                    </div>
                                                    <div class="col-sm-6">
                                                        <div class="small text-muted mb-1">Poin Maksimal</div>
                                                        <div class="fw-bold text-dark"><i class="bi bi-star-fill text-warning me-1"></i> 100 Poin</div>
                                                    </div>
                                                </div>
                                                
                                                <div class="d-flex justify-content-end border-top pt-3">
                                                    <?php if($sudah_dikerjakan): ?>
                                                        <button class="btn btn-secondary fw-bold px-4" disabled><i class="bi bi-check-circle-fill me-1"></i> Selesai Dikumpulkan</button>
                                                    <?php else: ?>
                                                        <button class="btn btn-warning fw-bold text-dark px-4 shadow-sm" onclick="konfirmasiKuis('<?= $kq['IDKuis'] ?>', '<?= addslashes(htmlspecialchars($kq['Judul'])) ?>')"><i class="bi bi-play-circle-fill me-1"></i> Mulai Ujian</button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
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

                <!-- SECTION LACI DETAIL TUGAS -->
                <?php foreach($semua_tugas as $id_tugas => $tugas): 
                    $kumpul = $tugas['pengumpulan'];
                    $is_submitted = !empty($kumpul);
                    
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
                            <div class="mb-4 pb-3 border-bottom border-secondary-subtle">
                                <div class="d-flex align-items-center mb-1">
                                    <span class="fw-bold text-dark me-2" style="width: 100px;">Dibuka:</span>
                                    <span class="text-secondary"><?= isset($tugas['TanggalDibuat']) ? str_replace(
                                        ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'], 
                                        ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'], 
                                        date('l, d M Y, H:i', strtotime($tugas['TanggalDibuat']))
                                    ) : '-' ?></span>
                                </div>
                                <div class="d-flex align-items-center">
                                    <span class="fw-bold text-dark me-2" style="width: 100px;">Jatuh tempo:</span>
                                    <span class="text-secondary"><?= str_replace(
                                        ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'], 
                                        ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'], 
                                        date('l, d M Y, H:i', strtotime($tugas['Deadline']))
                                    ) ?></span>
                                </div>
                            </div>

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

                        <?php if(!$is_submitted): 
                            $tipe_diizinkan = $tugas['TipeFileDiizinkan'] ?? '';
                            $arr_accept = [];
                            if(strpos($tipe_diizinkan, 'PDF') !== false) $arr_accept[] = '.pdf';
                            if(strpos($tipe_diizinkan, 'Word') !== false) array_push($arr_accept, '.doc', '.docx');
                            if(strpos($tipe_diizinkan, 'Excel') !== false) array_push($arr_accept, '.xls', '.xlsx');
                            if(strpos($tipe_diizinkan, 'PowerPoint') !== false) array_push($arr_accept, '.ppt', '.pptx');
                            if(strpos($tipe_diizinkan, 'Gambar') !== false) array_push($arr_accept, '.jpg', '.jpeg', '.png');
                            if(strpos($tipe_diizinkan, 'Video') !== false) array_push($arr_accept, '.mp4', '.mp3', '.avi', '.wav');
                            $accept_html = !empty($arr_accept) ? implode(',', $arr_accept) : '*/*';
                        ?>
                        <div class="mt-5 pt-3">
                            <h5 class="fw-bold text-dark mb-3">Formulir Pengumpulan Tugas</h5>
                            <form action="proses_kumpul_tugas.php" method="POST" enctype="multipart/form-data" class="bg-light p-4 rounded-3 border">
                                <input type="hidden" name="id_tugas" value="<?= $id_tugas ?>">
                                <div class="mb-4">
                                    <div class="d-flex justify-content-between align-items-end mb-2">
                                        <label class="form-label fw-bold text-secondary mb-0">Pilih File / Berkas Jawaban Anda <span class="text-danger">*</span></label>
                                        <span class="badge bg-info text-dark border shadow-sm"><i class="bi bi-info-circle me-1"></i> Format: <?= htmlspecialchars($tipe_diizinkan ?: 'Semua File') ?></span>
                                    </div>
                                    <input type="file" class="form-control form-control-lg bg-white shadow-sm border-secondary-subtle" name="file_tugas" required accept="<?= $accept_html ?>" onchange="validasiEkstensiTugas(this, '<?= htmlspecialchars(addslashes($tipe_diizinkan)) ?>')">
                                    <div class="form-text mt-2 text-danger fw-semibold"><i class="bi bi-exclamation-triangle me-1"></i> File yang tidak sesuai dengan format di atas akan otomatis ditolak oleh sistem.</div>
                                </div>
                                <button type="submit" class="btn btn-primary px-5 py-2 fw-bold shadow-sm">
                                    <i class="bi bi-cloud-arrow-up-fill me-2"></i> Tambahkan Pengajuan
                                </button>
                            </form>
                        </div>
                        <?php endif; ?>
                    
                    <div class="bottom-nav-box mt-5">
                            <?php 
                            $prev_tugas_id = '';
                            $prev_tugas_judul = '';
                            $keys = array_keys($semua_tugas);
                            $current_index = array_search($id_tugas, $keys);
                            
                            if ($current_index > 0) {
                                $prev_tugas_id = $keys[$current_index - 1];
                                $prev_tugas_judul = $semua_tugas[$prev_tugas_id]['Judul'];
                            }
                            ?>
                            
                            <div>
                                <?php if(!empty($prev_tugas_id)): ?>
                                <a href="javascript:void(0)" onclick="openTaskDetail('<?= $prev_tugas_id ?>')" class="prev-activity">
                                    <i class="bi bi-chevron-left"></i>
                                    <div class="prev-info">
                                        <span class="prev-label">Aktivitas Sebelumnya</span>
                                        <span class="prev-title"><?= htmlspecialchars(strtoupper($prev_tugas_judul)) ?></span>
                                    </div>
                                </a>
                                <?php else: ?>
                                <span class="text-muted small fst-italic">Ini adalah aktivitas pertama.</span>
                                <?php endif; ?>
                            </div>

                            <div>
                                <select class="form-select jump-select" onchange="if(this.value) openTaskDetail(this.value)">
                                    <option value="" selected disabled>Lompat ke...</option>
                                    <?php foreach($semua_tugas as $jump_id => $jump_data): ?>
                                        <option value="<?= $jump_id ?>"><?= htmlspecialchars($jump_data['Judul']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                    </div>
                </div>
                <?php endforeach; ?>

            </main>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    let isSyncing = false; 

    function showCourseList() {
        document.querySelectorAll('.task-view-container').forEach(el => el.classList.add('d-none'));
        document.getElementById('course-list-view').classList.remove('d-none');
        document.querySelectorAll('.index-item').forEach(el => el.classList.remove('active'));
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
        const mainSections = document.querySelectorAll('.course-chapter .collapse');
        const sidebarSections = document.querySelectorAll('.sidebar-accordion .accordion-collapse');
        
        const btnText = document.getElementById('txtToggleAll');
        const btnIcon = document.querySelector('#btnToggleAll i');
        
        isAllOpen = !isAllOpen;
        
        mainSections.forEach(section => {
            const bsCollapse = bootstrap.Collapse.getOrCreateInstance(section);
            if(isAllOpen) bsCollapse.show(); else bsCollapse.hide();
        });
        
        sidebarSections.forEach(sideSection => {
            const bsCollapseSide = bootstrap.Collapse.getOrCreateInstance(sideSection);
            if(isAllOpen) bsCollapseSide.show(); else bsCollapseSide.hide();
        });
        
        if (isAllOpen) {
            btnText.innerText = "Tutup Semua Bab";
            btnIcon.className = "bi bi-arrows-collapse me-1";
        } else {
            btnText.innerText = "Buka Semua Bab";
            btnIcon.className = "bi bi-arrows-expand me-1";
        }
    }

    function toggleSelesai(btn, idItem) {
        let isDone = btn.classList.contains('done');
        if(!isDone) {
            let teksAwal = btn.innerHTML;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...';
            
            fetch('cek_gamifikasi.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'id_materi=' + encodeURIComponent(idItem)
            })
            .then(response => response.json()) 
            .then(data => {
                btn.classList.add('done');
                btn.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> Selesai';
                localStorage.setItem(idItem, 'selesai'); 

                if(data.status === 'sukses') {
                    Swal.fire({ 
                        title: 'Materi Selesai! 📚', 
                        html: data.notif || `Selamat! <b>+${data.poin} XP</b> berhasil ditambahkan.`, 
                        icon: 'success', 
                        toast: true, position: 'bottom-end', 
                        showConfirmButton: false, timer: 4000, timerProgressBar: true 
                    });
                } else if (data.status === 'sudah_klaim') {
                    Swal.fire({ 
                        title: 'Sudah Tuntas!', 
                        text: 'Materi ini sudah kamu selesaikan dan XP-nya sudah masuk.', 
                        icon: 'info', 
                        toast: true, position: 'bottom-end', 
                        showConfirmButton: false, timer: 3500
                    });
                } else {
                    Swal.fire('Ups!', data.pesan || 'Terjadi kesalahan sistem.', 'warning');
                    btn.innerHTML = teksAwal; btn.classList.remove('done');
                }
            }).catch(error => {
                console.error('Error Network:', error);
                Swal.fire('Koneksi Gagal', 'Sistem gagal menghubungi server poin.', 'error');
                btn.innerHTML = teksAwal; btn.classList.remove('done');
            });
        } else {
            btn.classList.remove('done');
            btn.innerHTML = '<i class="bi bi-circle me-1"></i> Tandai Selesai';
            localStorage.removeItem(idItem); 
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.btn-selesai').forEach(btn => {
            let match = btn.getAttribute('onclick').match(/'(.*?)'/);
            if(match && match[1]) {
                let idItem = match[1];
                if(localStorage.getItem(idItem) === 'selesai') {
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
    document.addEventListener('DOMContentLoaded', () => {
        const urlParams = new URLSearchParams(window.location.search);
        const openTugasId = urlParams.get('open_tugas');
        
        if (openTugasId) {
            setTimeout(() => {
                openTaskDetail(openTugasId);
            }, 300);
        }
    });

    function konfirmasiKuis(idKuis, judulKuis) {
        Swal.fire({
            title: 'Mulai Ujian Sekarang?',
            html: `Kamu akan mengerjakan: <br><b class="text-primary fs-5">${judulKuis}</b><br><br><span class="text-danger small"><i class="bi bi-exclamation-triangle-fill"></i> Waktu akan langsung berjalan dan tidak bisa dijeda. Sudah siap?</span>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#4f46e5',
            cancelButtonColor: '#cbd5e1',
            confirmButtonText: 'Ya, Mulai!',
            cancelButtonText: 'Nanti Saja',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `kerjakan_quiz.php?id_kuis=${idKuis}`;
            }
        });
    }

    function validasiEkstensiTugas(inputEle, allowedTypesStr) {
        if(!allowedTypesStr || allowedTypesStr.trim() === '') return true; 
        
        const file = inputEle.files[0];
        if(!file) return true;
        
        const ext = file.name.split('.').pop().toLowerCase();
        let isValid = false;
        
        if(allowedTypesStr.includes('PDF') && ext === 'pdf') isValid = true;
        if(allowedTypesStr.includes('Word') && ['doc', 'docx'].includes(ext)) isValid = true;
        if(allowedTypesStr.includes('Excel') && ['xls', 'xlsx'].includes(ext)) isValid = true;
        if(allowedTypesStr.includes('PowerPoint') && ['ppt', 'pptx'].includes(ext)) isValid = true;
        if(allowedTypesStr.includes('Gambar') && ['jpg', 'jpeg', 'png'].includes(ext)) isValid = true;
        if(allowedTypesStr.includes('Video') && ['mp4', 'mp3', 'avi', 'wav'].includes(ext)) isValid = true;
        if(allowedTypesStr.includes('Google Doc')) isValid = true; 
        
        if(!isValid) {
            Swal.fire({
                title: 'Format File Ditolak!',
                html: `File yang kamu masukkan berformat <b>.${ext.toUpperCase()}</b><br>Padahal guru hanya mengizinkan:<br><div class='p-2 bg-light border text-danger fw-bold mt-2 rounded'>${allowedTypesStr}</div>`,
                icon: 'error',
                confirmButtonColor: '#4f46e5'
            });
            inputEle.value = ''; 
        }
    }
</script>
</body>
</html>