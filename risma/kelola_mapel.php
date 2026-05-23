<?php
session_start();
require '../login/koneksi.php';

if(!isset($_SESSION['role'])||$_SESSION['role']!='guru'){ header("Location: ../login/login.php"); exit; }

$id_user = $_SESSION['IDUser'] ?? '';
$query_guru = mysqli_query($koneksi,"SELECT * FROM guru WHERE IDUser='$id_user'");
if($query_guru && mysqli_num_rows($query_guru)>0){ $guru=mysqli_fetch_assoc($query_guru); $id_guru=$guru['IDGuru']; $nama_guru=$guru['NamaGuru']; }
else{ $id_guru=''; $nama_guru='Guru'; }

$id_mapel = mysqli_real_escape_string($koneksi, $_GET['id_mapel'] ?? '');
$tab_aktif = $_GET['tab'] ?? 'materi';

if(!$id_mapel){ header("Location: guru.php"); exit; }

// Verifikasi mapel milik guru ini
$res_mapel = mysqli_query($koneksi,"SELECT * FROM mapel WHERE IDMapel='$id_mapel' AND IDGuru='$id_guru'");
if(!$res_mapel || mysqli_num_rows($res_mapel)==0){ header("Location: guru.php"); exit; }
$mapel = mysqli_fetch_assoc($res_mapel);

// ---- DATA MATERI ----
$res_materi = mysqli_query($koneksi,"SELECT * FROM materi WHERE IDMapel='$id_mapel' ORDER BY IDMateri DESC");
$daftar_materi=[]; while($r=mysqli_fetch_assoc($res_materi)) $daftar_materi[]=$r;

// ---- DATA TUGAS ----
$res_tugas = mysqli_query($koneksi,"SELECT t.*, (SELECT COUNT(*) FROM pengumpulan_tugas WHERE IDTugas=t.IDTugas) AS jml_kumpul, (SELECT COUNT(*) FROM pengumpulan_tugas WHERE IDTugas=t.IDTugas AND Status='belum_dinilai') AS belum_dinilai FROM tugas t WHERE t.IDMapel='$id_mapel' ORDER BY t.IDTugas DESC");
$daftar_tugas=[]; while($r=mysqli_fetch_assoc($res_tugas)) $daftar_tugas[]=$r;

// ---- DATA QUIZ ----
$res_quiz = mysqli_query($koneksi,"SELECT q.*, (SELECT COUNT(*) FROM soal_quiz WHERE IDQuiz=q.IDQuiz) AS jml_soal, (SELECT COUNT(DISTINCT IDSiswa) FROM jawaban_quiz WHERE IDQuiz=q.IDQuiz) AS jml_dikerjakan FROM quiz q WHERE q.IDMapel='$id_mapel' ORDER BY q.IDQuiz DESC");
$daftar_quiz=[]; while($r=mysqli_fetch_assoc($res_quiz)) $daftar_quiz[]=$r;

// ---- STATISTIK ----
$total_bd = 0; foreach($daftar_tugas as $t) $total_bd += $t['belum_dinilai'];

function fileIcon($ext){
    $m=['pdf'=>'bi-file-earmark-pdf text-danger','doc'=>'bi-file-earmark-word text-primary','docx'=>'bi-file-earmark-word text-primary','ppt'=>'bi-file-earmark-ppt text-warning','pptx'=>'bi-file-earmark-ppt text-warning','xls'=>'bi-file-earmark-excel text-success','xlsx'=>'bi-file-earmark-excel text-success','jpg'=>'bi-file-earmark-image text-info','jpeg'=>'bi-file-earmark-image text-info','png'=>'bi-file-earmark-image text-info','mp4'=>'bi-file-earmark-play text-danger'];
    return $m[strtolower($ext)]??'bi-file-earmark text-secondary';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kelola – <?= htmlspecialchars($mapel['NamaMapel']) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
<style>
:root{--primary:#4f46e5;--grad:linear-gradient(135deg,#4f46e5,#3730a3);}
*{font-family:'Segoe UI',system-ui,sans-serif;}
body{background:#f0f2f8;}
/* Navbar */
.navbar-custom{background:var(--grad);box-shadow:0 4px 12px rgba(0,0,0,.12);}
/* Hero mapel */
.mapel-hero{background:linear-gradient(135deg,#1e1e2f,#111119);color:#fff;border-radius:20px;padding:28px 32px;margin-bottom:24px;position:relative;overflow:hidden;box-shadow:0 10px 30px rgba(0,0,0,.15);}
.mapel-hero::before{content:'';position:absolute;top:-50px;right:-30px;width:280px;height:280px;background:rgba(79,70,229,.2);filter:blur(50px);border-radius:50%;pointer-events:none;}
/* Tabs */
.tab-nav{display:flex;gap:4px;background:#e8eaf6;border-radius:14px;padding:5px;margin-bottom:24px;overflow-x:auto;}
.tab-btn{border:none;background:transparent;border-radius:10px;padding:9px 18px;font-size:.84rem;font-weight:600;color:#64748b;cursor:pointer;transition:.2s;white-space:nowrap;position:relative;}
.tab-btn.active{background:#fff;color:var(--primary);box-shadow:0 2px 8px rgba(0,0,0,.08);}
.tab-btn .tab-badge{background:#ef4444;color:#fff;font-size:.6rem;font-weight:700;padding:2px 6px;border-radius:10px;margin-left:4px;}
/* Content panels */
.tab-panel{display:none;}.tab-panel.active{display:block;}
/* Stats mini */
.stat-mini{background:#fff;border-radius:12px;padding:14px;text-align:center;box-shadow:0 2px 8px rgba(0,0,0,.05);}
.stat-mini .sn{font-size:1.6rem;font-weight:800;}
/* Item cards */
.item-card{background:#fff;border-radius:14px;border:1.5px solid #e8edf5;padding:14px 16px;margin-bottom:10px;display:flex;align-items:center;gap:14px;transition:.2s;}
.item-card:hover{border-color:var(--primary);box-shadow:0 4px 12px rgba(79,70,229,.08);}
.item-ico{width:46px;height:46px;border-radius:11px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;flex-shrink:0;}
.item-info{flex:1;min-width:0;}
.item-title{font-weight:700;font-size:.88rem;color:#1e293b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.item-meta{font-size:.73rem;color:#94a3b8;margin-top:2px;}
.item-actions{display:flex;gap:6px;flex-shrink:0;}
.btn-act{padding:5px 11px;border-radius:8px;font-size:.74rem;border:1.5px solid #e2e8f0;background:#fff;color:#475569;transition:.2s;text-decoration:none;cursor:pointer;}
.btn-act:hover{background:var(--primary);color:#fff;border-color:var(--primary);}
.btn-act.red:hover{background:#ef4444;border-color:#ef4444;color:#fff;}
/* Badges */
.bdg-belum{background:#fef3c7;color:#b45309;font-size:.7rem;padding:3px 9px;border-radius:8px;font-weight:700;}
.bdg-sudah{background:#dcfce7;color:#15803d;font-size:.7rem;padding:3px 9px;border-radius:8px;font-weight:700;}
.bdg-terlambat{background:#fee2e2;color:#dc2626;font-size:.7rem;padding:3px 9px;border-radius:8px;font-weight:700;}
/* Dashed btn */
.btn-dashed{border:2px dashed #cbd5e1;color:#94a3b8;width:100%;padding:11px;border-radius:12px;background:none;transition:.3s;font-size:.83rem;cursor:pointer;}
.btn-dashed:hover{border-color:var(--primary);color:var(--primary);background:#eff6ff;}
/* Empty */
.empty-st{text-align:center;padding:36px 20px;color:#94a3b8;}
.empty-st i{font-size:2.5rem;display:block;margin-bottom:8px;}
/* Modal */
.modal-content{border-radius:20px;border:none;}
.act-opt{border:2px solid #e8edf5;border-radius:14px;padding:18px 12px;text-align:center;cursor:pointer;transition:.2s;text-decoration:none;color:#1e293b;display:block;}
.act-opt:hover{border-color:var(--primary);background:#eff6ff;color:var(--primary);transform:translateY(-3px);}
/* Penilaian table */
.tbl-nilai td,.tbl-nilai th{vertical-align:middle;font-size:.83rem;}
/* Dark */
[data-bs-theme="dark"] body{background:#0f0f1a;color:#e0e0e0;}
[data-bs-theme="dark"] .item-card,[data-bs-theme="dark"] .stat-mini{background:#1a1a2e;border-color:#2d2d4e;}
[data-bs-theme="dark"] .tab-nav{background:#1a1a2e;}
[data-bs-theme="dark"] .tab-btn.active{background:#2d2d4e;color:#818cf8;}
[data-bs-theme="dark"] .btn-act{background:#1a1a2e;color:#e0e0e0;border-color:#2d2d4e;}
@media(max-width:576px){.mapel-hero{padding:20px 18px;}.tab-btn{padding:8px 12px;font-size:.78rem;}.item-card{flex-wrap:wrap;}.item-actions{width:100%;justify-content:flex-end;}}
</style>
<script>document.documentElement.setAttribute('data-bs-theme',localStorage.getItem('theme')||'light');</script>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-dark navbar-custom sticky-top">
    <div class="container py-1">
        <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="guru.php">
            <i class="bi bi-arrow-left me-1"></i> Dashboard Guru
        </a>
        <div class="ms-auto d-flex align-items-center gap-2">
            <button class="btn btn-link text-white p-2" onclick="switchTheme()" style="text-decoration:none;"><i class="bi bi-sun-fill fs-5" id="themeIcon"></i></button>
            <span class="text-white-50 small d-none d-md-inline"><?= htmlspecialchars($nama_guru) ?></span>
            <a href="../login/logout.php" class="btn btn-sm btn-light bg-white bg-opacity-10 text-white border-0" style="border-radius:20px;">
                <i class="bi bi-box-arrow-right me-1"></i><span class="d-none d-sm-inline">Keluar</span>
            </a>
        </div>
    </div>
</nav>

<div class="container py-4" style="max-width:900px;">

    <!-- MAPEL HERO -->
    <div class="mapel-hero">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div>
                <span class="badge rounded-pill px-3 py-1 mb-2 small" style="background:rgba(255,255,255,.12);color:#c7d2fe;">
                    <i class="bi bi-building me-1"></i><?= htmlspecialchars($mapel['Kelas']??'-') ?>
                    <?php if(!empty($mapel['TahunAjaran'])): ?> &bull; <?= htmlspecialchars($mapel['TahunAjaran']) ?><?php endif; ?>
                </span>
                <h4 class="fw-bold mb-1"><?= htmlspecialchars($mapel['NamaMapel']) ?></h4>
                <p class="text-white-50 mb-0 small"><?= htmlspecialchars($mapel['Deskripsi']??'Kelola seluruh konten pembelajaran.') ?></p>
            </div>
            <div class="row g-2" style="min-width:200px;">
                <div class="col-4"><div class="text-center"><div class="fw-bold fs-4 text-warning"><?= count($daftar_materi) ?></div><div class="text-white-50" style="font-size:.72rem;">Materi</div></div></div>
                <div class="col-4"><div class="text-center"><div class="fw-bold fs-4 text-info"><?= count($daftar_tugas) ?></div><div class="text-white-50" style="font-size:.72rem;">Tugas</div></div></div>
                <div class="col-4"><div class="text-center"><div class="fw-bold fs-4 text-success"><?= count($daftar_quiz) ?></div><div class="text-white-50" style="font-size:.72rem;">Quiz</div></div></div>
            </div>
        </div>
    </div>

    <!-- TABS -->
    <div class="tab-nav" id="tabNav">
        <button class="tab-btn <?= $tab_aktif=='materi'?'active':'' ?>" onclick="setTab('materi')">
            <i class="bi bi-file-earmark-text me-1"></i>Materi
        </button>
        <button class="tab-btn <?= $tab_aktif=='tugas'?'active':'' ?>" onclick="setTab('tugas')">
            <i class="bi bi-journal-check me-1"></i>Penugasan
        </button>
        <button class="tab-btn <?= $tab_aktif=='quiz'?'active':'' ?>" onclick="setTab('quiz')">
            <i class="bi bi-patch-question me-1"></i>Quiz
        </button>
        <button class="tab-btn <?= $tab_aktif=='penilaian'?'active':'' ?>" onclick="setTab('penilaian')">
            <i class="bi bi-pencil-square me-1"></i>Penilaian
            <?php if($total_bd>0): ?><span class="tab-badge"><?= $total_bd ?></span><?php endif; ?>
        </button>
    </div>

    <!-- ============ TAB MATERI ============ -->
    <div class="tab-panel <?= $tab_aktif=='materi'?'active':'' ?>" id="panel-materi">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <span class="fw-bold"><i class="bi bi-file-earmark-text text-primary me-2"></i>Daftar Materi (<?= count($daftar_materi) ?>)</span>
            <a href="upMateri.php?id_mapel=<?= $id_mapel ?>" class="btn btn-sm btn-primary rounded-pill px-3" style="background:var(--primary);border:none;">
                <i class="bi bi-plus-lg me-1"></i>Tambah Materi
            </a>
        </div>

        <?php if(empty($daftar_materi)): ?>
        <div class="empty-st"><i class="bi bi-file-earmark-x"></i><p class="mb-0" style="font-size:.83rem;">Belum ada materi. Klik tombol di atas untuk menambahkan.</p></div>
        <?php else: ?>
        <?php foreach($daftar_materi as $m): ?>
        <div class="item-card">
            <div class="item-ico" style="background:#eff6ff;"><i class="bi <?= fileIcon($m['TipeFile']) ?>"></i></div>
            <div class="item-info">
                <div class="item-title"><?= htmlspecialchars($m['Judul']) ?></div>
                <div class="item-meta">
                    <?php if(!empty($m['Deskripsi'])): ?><?= htmlspecialchars(substr($m['Deskripsi'],0,60)).(strlen($m['Deskripsi'])>60?'...':'') ?> &bull; <?php endif; ?>
                    <span class="text-uppercase"><?= $m['TipeFile'] ?></span>
                </div>
            </div>
            <div class="item-actions">
                <?php if(!empty($m['Filepath'])): ?>
                <a href="../uploads/materi/<?= htmlspecialchars($m['Filepath']) ?>" target="_blank" class="btn-act" title="Unduh"><i class="bi bi-download"></i></a>
                <?php endif; ?>
                <a href="hapusMateri.php?id=<?= $m['IDMateri'] ?>&id_mapel=<?= $id_mapel ?>" class="btn-act red" onclick="return confirm('Yakin hapus materi ini?')" title="Hapus"><i class="bi bi-trash"></i></a>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>

        <button class="btn-dashed mt-2" onclick="setTab('tugas')">
            <i class="bi bi-plus-circle me-2"></i>Lanjut ke Tab Penugasan
        </button>
    </div>

    <!-- ============ TAB TUGAS ============ -->
    <div class="tab-panel <?= $tab_aktif=='tugas'?'active':'' ?>" id="panel-tugas">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <span class="fw-bold"><i class="bi bi-journal-check text-success me-2"></i>Daftar Penugasan (<?= count($daftar_tugas) ?>)</span>
            <a href="upPenugasan.php?id_mapel=<?= $id_mapel ?>" class="btn btn-sm rounded-pill px-3 fw-bold text-white" style="background:#059669;border:none;">
                <i class="bi bi-plus-lg me-1"></i>Buat Tugas
            </a>
        </div>

        <?php if(empty($daftar_tugas)): ?>
        <div class="empty-st"><i class="bi bi-journal-x"></i><p class="mb-0" style="font-size:.83rem;">Belum ada penugasan. Klik tombol di atas untuk membuat tugas.</p></div>
        <?php else: ?>
        <?php foreach($daftar_tugas as $t):
            $dl = new DateTime($t['Deadline']);
            $now = new DateTime();
            $lewat = $dl < $now;
        ?>
        <div class="item-card">
            <div class="item-ico" style="background:#f0fdf4;"><i class="bi bi-journal-check" style="color:#059669;font-size:1.3rem;"></i></div>
            <div class="item-info">
                <div class="item-title"><?= htmlspecialchars($t['Judul']) ?></div>
                <div class="item-meta d-flex flex-wrap gap-2 mt-1">
                    <span class="<?= $lewat?'bdg-terlambat':'bdg-sudah' ?>">
                        <i class="bi bi-clock me-1"></i><?= $lewat?'Sudah Lewat':'Deadline: '.$dl->format('d M Y H:i') ?>
                    </span>
                    <span><i class="bi bi-people me-1"></i><?= $t['jml_kumpul'] ?> dikumpulkan</span>
                    <?php if($t['belum_dinilai']>0): ?><span class="bdg-belum"><?= $t['belum_dinilai'] ?> belum dinilai</span><?php endif; ?>
                    <span><i class="bi bi-star me-1"></i><?= $t['PoinMaksimal'] ?> poin</span>
                </div>
            </div>
            <div class="item-actions">
                <a href="pengumpulan.php?id_tugas=<?= $t['IDTugas'] ?>&id_mapel=<?= $id_mapel ?>" class="btn-act" title="Lihat & Nilai">
                    <i class="bi bi-eye me-1"></i>Nilai
                </a>
                <a href="hapusTugas.php?id=<?= $t['IDTugas'] ?>&id_mapel=<?= $id_mapel ?>" class="btn-act red" onclick="return confirm('Yakin hapus tugas ini? Semua pengumpulan akan ikut terhapus!')" title="Hapus">
                    <i class="bi bi-trash"></i>
                </a>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- ============ TAB QUIZ ============ -->
    <div class="tab-panel <?= $tab_aktif=='quiz'?'active':'' ?>" id="panel-quiz">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <span class="fw-bold"><i class="bi bi-patch-question text-warning me-2"></i>Daftar Quiz (<?= count($daftar_quiz) ?>)</span>
            <a href="upQuiz.php?id_mapel=<?= $id_mapel ?>" class="btn btn-sm rounded-pill px-3 fw-bold text-white" style="background:#d97706;border:none;">
                <i class="bi bi-plus-lg me-1"></i>Buat Quiz
            </a>
        </div>

        <?php if(empty($daftar_quiz)): ?>
        <div class="empty-st"><i class="bi bi-question-diamond"></i><p class="mb-0" style="font-size:.83rem;">Belum ada quiz. Klik tombol di atas untuk membuat quiz baru.</p></div>
        <?php else: ?>
        <?php foreach($daftar_quiz as $q): ?>
        <div class="item-card">
            <div class="item-ico" style="background:#fefce8;"><i class="bi bi-patch-question-fill text-warning" style="font-size:1.3rem;"></i></div>
            <div class="item-info">
                <div class="item-title"><?= htmlspecialchars($q['JudulQuiz']) ?></div>
                <div class="item-meta d-flex flex-wrap gap-2 mt-1">
                    <span><i class="bi bi-list-ol me-1"></i><?= $q['jml_soal'] ?> soal</span>
                    <span><i class="bi bi-clock me-1"></i><?= $q['Durasi'] ?> menit</span>
                    <span><i class="bi bi-people me-1"></i><?= $q['jml_dikerjakan'] ?> siswa</span>
                    <span><i class="bi bi-star me-1"></i><?= $q['PoinPerSoal'] ?> poin/soal</span>
                </div>
            </div>
            <div class="item-actions">
                <a href="hasilQuiz.php?id_quiz=<?= $q['IDQuiz'] ?>&id_mapel=<?= $id_mapel ?>" class="btn-act" title="Lihat Hasil">
                    <i class="bi bi-bar-chart me-1"></i>Hasil
                </a>
                <a href="hapusQuiz.php?id=<?= $q['IDQuiz'] ?>&id_mapel=<?= $id_mapel ?>" class="btn-act red" onclick="return confirm('Yakin hapus quiz ini?')" title="Hapus">
                    <i class="bi bi-trash"></i>
                </a>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- ============ TAB PENILAIAN ============ -->
    <div class="tab-panel <?= $tab_aktif=='penilaian'?'active':'' ?>" id="panel-penilaian">
        <div class="mb-3">
            <span class="fw-bold"><i class="bi bi-pencil-square" style="color:#9333ea;" class="me-2"></i> Penilaian Pengumpulan Tugas</span>
        </div>

        <?php if(empty($daftar_tugas)): ?>
        <div class="empty-st"><i class="bi bi-inbox"></i><p class="mb-0" style="font-size:.83rem;">Belum ada tugas dibuat. Buat tugas terlebih dahulu.</p></div>
        <?php else: ?>

        <!-- Accordion per tugas -->
        <div class="accordion" id="accordionPenilaian">
        <?php foreach($daftar_tugas as $idx => $t):
            $dl = new DateTime($t['Deadline']);
            // Ambil pengumpulan untuk tugas ini
            $res_pk = mysqli_query($koneksi,"SELECT pt.*,s.NamaSiswa,s.Kelas,s.NISN FROM pengumpulan_tugas pt JOIN siswa s ON pt.IDSiswa=s.IDSiswa WHERE pt.IDTugas='{$t['IDTugas']}' ORDER BY CASE pt.Status WHEN 'belum_dinilai' THEN 0 WHEN 'terlambat' THEN 1 ELSE 2 END, pt.TanggalKirim ASC");
            $pengumpulan=[];while($r=mysqli_fetch_assoc($res_pk))$pengumpulan[]=$r;
        ?>
        <div class="accordion-item border-0 mb-2" style="border-radius:14px!important;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.05);">
            <h2 class="accordion-header">
                <button class="accordion-button <?= $idx>0?'collapsed':'' ?> fw-semibold" type="button"
                        data-bs-toggle="collapse" data-bs-target="#acc<?= $idx ?>" style="border-radius:14px;font-size:.88rem;">
                    <div class="d-flex align-items-center gap-2 flex-wrap w-100">
                        <span><?= htmlspecialchars($t['Judul']) ?></span>
                        <?php if($t['belum_dinilai']>0): ?><span class="bdg-belum ms-1"><?= $t['belum_dinilai'] ?> belum dinilai</span><?php endif; ?>
                        <span class="ms-auto text-muted" style="font-size:.74rem;font-weight:400;"><?= $t['jml_kumpul'] ?> pengumpulan</span>
                    </div>
                </button>
            </h2>
            <div id="acc<?= $idx ?>" class="accordion-collapse collapse <?= $idx==0?'show':'' ?>">
                <div class="accordion-body p-0">
                <?php if(empty($pengumpulan)): ?>
                    <div class="text-center py-4 text-muted" style="font-size:.83rem;"><i class="bi bi-inbox me-2"></i>Belum ada yang mengumpulkan tugas ini.</div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table tbl-nilai mb-0">
                        <thead style="background:#f8fafc;font-size:.78rem;">
                            <tr>
                                <th class="ps-3">Siswa</th>
                                <th>Waktu Kumpul</th>
                                <th>Status</th>
                                <th>Nilai</th>
                                <th>File</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach($pengumpulan as $pk):
                            $tgl = new DateTime($pk['TanggalKirim']);
                            $tepat = $tgl <= $dl;
                        ?>
                        <tr>
                            <td class="ps-3">
                                <div class="fw-semibold"><?= htmlspecialchars($pk['NamaSiswa']) ?></div>
                                <div class="text-muted" style="font-size:.72rem;"><?= htmlspecialchars($pk['Kelas']) ?></div>
                            </td>
                            <td><?= $tgl->format('d/m/Y H:i') ?></td>
                            <td>
                                <?php if($pk['Status']=='sudah_dinilai'): ?><span class="bdg-sudah">Sudah Dinilai</span>
                                <?php elseif($pk['Status']=='terlambat'): ?><span class="bdg-terlambat">Terlambat</span>
                                <?php else: ?><span class="bdg-belum">Belum Dinilai</span><?php endif; ?>
                            </td>
                            <td>
                                <?php if($pk['Nilai']!==null): ?>
                                <span class="fw-bold" style="color:var(--primary);"><?= $pk['Nilai'] ?></span>
                                <span class="text-muted" style="font-size:.72rem;">/ <?= $t['PoinMaksimal'] ?></span>
                                <?php else: ?><span class="text-muted">-</span><?php endif; ?>
                            </td>
                            <td>
                                <?php if(!empty($pk['FileJawaban'])): ?>
                                <a href="../uploads/tugas/<?= htmlspecialchars($pk['FileJawaban']) ?>" target="_blank" class="btn-act" style="display:inline-block;" title="Unduh">
                                    <i class="bi bi-download"></i>
                                </a>
                                <?php else: ?><span class="text-muted" style="font-size:.72rem;">Tidak ada</span><?php endif; ?>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm rounded-pill px-3 fw-semibold text-white" style="background:var(--primary);border:none;font-size:.75rem;"
                                    onclick="bukaNilai(<?= $pk['IDPengumpulan'] ?>,'<?= htmlspecialchars(addslashes($pk['NamaSiswa'])) ?>',<?= $pk['Nilai']!==null?$pk['Nilai']:'null' ?>,'<?= htmlspecialchars(addslashes($pk['KomentarGuru']??'')) ?>',<?= $t['PoinMaksimal'] ?>,'<?= $t['IDTugas'] ?>')">
                                    <?= $pk['Status']=='sudah_dinilai'?'<i class="bi bi-pencil me-1"></i>Edit':'<i class="bi bi-check2-circle me-1"></i>Nilai' ?>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

</div><!-- /container -->

<!-- MODAL PENILAIAN -->
<div class="modal fade" id="modalNilai" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content p-2">
            <div class="modal-header border-0">
                <h6 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2" style="color:#9333ea;"></i>Beri Nilai Tugas</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-4" style="font-size:.85rem;">Siswa: <strong id="mNamaSiswa"></strong></p>
                <form action="nilaiTugas.php" method="POST" id="formNilai">
                    <input type="hidden" name="id_pengumpulan" id="mIDPengumpulan">
                    <input type="hidden" name="id_mapel" value="<?= $id_mapel ?>">
                    <input type="hidden" name="id_tugas" id="mIDTugas">
                    <input type="hidden" name="poin_maks" id="mPoinMaks">

                    <div class="text-center mb-4">
                        <div id="mNilaiAngka" style="font-size:3.5rem;font-weight:800;color:var(--primary);line-height:1;">0</div>
                        <div id="mGradeLabel" class="fw-bold mt-1" style="font-size:.9rem;"></div>
                        <div class="text-muted" style="font-size:.74rem;">dari <span id="mPoinLabel">100</span> poin</div>
                    </div>

                    <label class="form-label" style="font-size:.78rem;font-weight:700;color:#64748b;">GESER UNTUK ATUR NILAI</label>
                    <input type="range" name="nilai" id="mSlider" class="form-range mb-3" min="0" max="100" step="5" value="0"
                           style="accent-color:var(--primary);">

                    <label class="form-label" style="font-size:.78rem;font-weight:700;color:#64748b;">KOMENTAR GURU (OPSIONAL)</label>
                    <textarea name="komentar" id="mKomentar" class="form-control mb-4" rows="3"
                              placeholder="Tulis catatan/masukan untuk siswa..." maxlength="500"
                              style="border-radius:10px;border:2px solid #e8edf5;font-size:.86rem;"></textarea>

                    <button type="submit" class="btn w-100 fw-bold text-white" style="background:var(--primary);border:none;border-radius:12px;padding:12px;">
                        <i class="bi bi-check-circle me-2"></i>Simpan Nilai
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Theme
function updateIcon(t){document.getElementById('themeIcon').className=t==='dark'?'bi bi-moon-stars-fill fs-5':'bi bi-sun-fill fs-5';}
updateIcon(localStorage.getItem('theme')||'light');
function switchTheme(){const c=document.documentElement.getAttribute('data-bs-theme'),n=c==='dark'?'light':'dark';document.documentElement.setAttribute('data-bs-theme',n);localStorage.setItem('theme',n);updateIcon(n);}

// Tab switching
function setTab(name){
    document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('active'));
    document.querySelectorAll('.tab-panel').forEach(p=>p.classList.remove('active'));
    document.querySelector('[onclick="setTab(\''+name+'\')"]').classList.add('active');
    document.getElementById('panel-'+name).classList.add('active');
    history.replaceState(null,'','kelolaMapel.php?id_mapel=<?= $id_mapel ?>&tab='+name);
}

// Modal penilaian
function bukaNilai(idPk,nama,nilai,komentar,maks,idTugas){
    document.getElementById('mIDPengumpulan').value=idPk;
    document.getElementById('mNamaSiswa').textContent=nama;
    document.getElementById('mIDTugas').value=idTugas;
    document.getElementById('mPoinMaks').value=maks;
    document.getElementById('mPoinLabel').textContent=maks;
    document.getElementById('mSlider').max=maks;
    document.getElementById('mSlider').value=nilai!==null?nilai:0;
    document.getElementById('mKomentar').value=komentar||'';
    updateNilai();
    new bootstrap.Modal(document.getElementById('modalNilai')).show();
}
function updateNilai(){
    const v=parseInt(document.getElementById('mSlider').value);
    const mx=parseInt(document.getElementById('mSlider').max);
    const p=mx>0?(v/mx)*100:0;
    document.getElementById('mNilaiAngka').textContent=v;
    let grade,color;
    if(p>=90){grade='A — Sangat Baik 🌟';color='#15803d';}
    else if(p>=80){grade='B — Baik 👍';color='#0369a1';}
    else if(p>=70){grade='C — Cukup 👌';color='#b45309';}
    else if(p>=60){grade='D — Perlu Perbaikan ⚠️';color='#c2410c';}
    else{grade='E — Tidak Lulus ❌';color='#dc2626';}
    document.getElementById('mGradeLabel').textContent=grade;
    document.getElementById('mGradeLabel').style.color=color;
    document.getElementById('mNilaiAngka').style.color=color;
}
document.getElementById('mSlider').addEventListener('input',updateNilai);
</script>
</body>
</html>