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

// 4. Ambil Data Tugas yang Sudah Dikumpulkan Siswa Ini
$q_kumpul = mysqli_query($koneksi, "SELECT IDTugas FROM pengumpulan_tugas WHERE IDSiswa = '$id_siswa'");
$tugas_selesai = [];
while($tk = mysqli_fetch_assoc($q_kumpul)) { $tugas_selesai[] = $tk['IDTugas']; }
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
            --primary: #4f46e5;
            --primary-dark: #3730a3;
            --primary-light: #e0e7ff;
            --secondary: #0ea5e9;
            --gradient-primary: linear-gradient(135deg, #4f46e5, #0ea5e9);
            --bg-light: #f8fafc;
            --text-dark: #1e293b;
        }
        
        body { background-color: var(--bg-light); color: var(--text-dark); font-family: 'Segoe UI', system-ui, sans-serif; overflow-x: hidden; }
        
        /* NAVBAR */
        .navbar-custom { background: var(--gradient-primary) !important; box-shadow: 0 2px 10px rgba(0,0,0,0.1); padding: 10px 0; z-index: 1030;}
        
        /* LAYOUT UTAMA */
        #wrapper { display: flex; width: 100%; align-items: stretch; min-height: calc(100vh - 66px); }
        
        /* SIDEBAR KIRI (Daftar Isi Bab) */
        #sidebar-course {
            min-width: 300px; max-width: 300px;
            background: #fff; border-right: 1px solid #e2e8f0;
            position: sticky; top: 66px; height: calc(100vh - 66px);
            overflow-y: auto; padding: 20px 15px;
        }
        .course-index-title { font-size: 0.8rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 15px; padding-left: 10px; }
        .index-item {
            display: block; padding: 12px 15px; color: #475569; text-decoration: none;
            border-radius: 10px; font-weight: 600; font-size: 0.9rem; transition: 0.2s; margin-bottom: 5px;
        }
        .index-item:hover { background: var(--primary-light); color: var(--primary); }
        .index-item.active { background: var(--primary); color: #fff; box-shadow: 0 4px 10px rgba(79, 70, 229, 0.2); }

        /* KONTEN TENGAH */
        #main-content { width: 100%; padding: 40px; }
        .page-title { font-weight: 800; font-size: 2.2rem; color: var(--text-dark); margin-bottom: 5px; text-transform: uppercase;}
        .page-subtitle { color: #64748b; font-size: 1rem; margin-bottom: 40px; }

        /* ACCORDION / SECTION KONTEN */
        .section-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; margin-bottom: 25px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); overflow: hidden;}
        .section-header { padding: 20px 25px; cursor: pointer; display: flex; align-items: center; justify-content: space-between; transition: 0.2s; }
        .section-header:hover { background: #f8fafc; }
        .section-title { font-weight: 700; color: var(--text-dark); font-size: 1.25rem; margin: 0; display: flex; align-items: center;}
        .section-title i { color: #cbd5e1; margin-right: 15px; font-size: 1.4rem; transition: 0.3s;}
        .section-header[aria-expanded="true"] .section-title i { transform: rotate(90deg); color: var(--primary); }
        
        .section-body { padding: 0 25px 25px 25px; }

        /* ITEM KONTEN (Materi & Tugas) */
        .content-item {
            display: flex; align-items: center; justify-content: space-between;
            padding: 15px 20px; border: 1px solid #e2e8f0; border-radius: 12px;
            margin-top: 15px; transition: 0.2s; background: #fff;
        }
        .content-item:hover { border-color: #cbd5e1; box-shadow: 0 4px 12px rgba(0,0,0,0.03); transform: translateY(-2px); }
        
        .content-icon {
            width: 45px; height: 45px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.4rem; margin-right: 20px; flex-shrink: 0;
        }
        .icon-materi { background: #e0e7ff; color: var(--primary); }
        .icon-tugas { background: #dcfce7; color: #10b981; }

        .content-info { flex-grow: 1; }
        .content-title { font-weight: 700; color: var(--text-dark); margin-bottom: 3px; font-size: 1.05rem; }
        .content-meta { font-size: 0.8rem; color: #64748b; font-weight: 500; }

        /* TOMBOL TANDAI SELESAI */
        .btn-selesai {
            border: 2px solid #cbd5e1; color: #64748b; background: transparent;
            border-radius: 8px; font-weight: 700; font-size: 0.85rem;
            padding: 8px 16px; transition: 0.3s; white-space: nowrap;
        }
        .btn-selesai:hover { border-color: #10b981; color: #10b981; background: #f0fdf4; }
        .btn-selesai.done { background: #10b981; border-color: #10b981; color: #fff; box-shadow: 0 4px 10px rgba(16, 185, 129, 0.3); }

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
                    <i class="bi bi-arrow-left me-1"></i> Kembali ke Dashboard
                </a>
            </div>
        </div>
    </nav>

    <div id="wrapper">
        
        <nav id="sidebar-course">
            <div class="course-index-title">DAFTAR ISI KELAS</div>
            
            <?php foreach($daftar_topik as $index => $tp): ?>
                <a href="#section-<?= $tp['IDTopik'] ?>" class="index-item <?= $index == 0 ? 'active' : '' ?>" onclick="setActiveSidebar(this)">
                    <?= htmlspecialchars($tp['NamaTopik']) ?>
                </a>
            <?php endforeach; ?>
            
            <?php if(empty($daftar_topik)): ?>
                <div class="text-muted small italic ps-2">Belum ada bab dibuat oleh guru.</div>
            <?php endif; ?>
        </nav>

        <main id="main-content">
            
            <h1 class="page-title"><?= htmlspecialchars($mapel['NamaMapel']) ?></h1>
            <div class="page-subtitle"><i class="bi bi-person-video3 me-2"></i>Guru Pengampu: <strong class="text-primary"><?= htmlspecialchars($mapel['NamaGuru'] ?? 'Belum Ditentukan') ?></strong></div>

            <?php foreach($daftar_topik as $index => $tp): 
                $id_topik = $tp['IDTopik'];
                $is_first = ($index == 0); // Buka otomatis bab pertama
            ?>
            
            <div class="section-card" id="section-<?= $id_topik ?>">
                <div class="section-header" data-bs-toggle="collapse" data-bs-target="#collapse<?= $id_topik ?>" aria-expanded="<?= $is_first ? 'true' : 'false' ?>">
                    <h3 class="section-title"><i class="bi bi-chevron-right"></i> <?= htmlspecialchars($tp['NamaTopik']) ?></h3>
                    <span class="text-primary small fw-bold" style="font-size: 0.85rem;">Buka / Tutup</span>
                </div>
                
                <div id="collapse<?= $id_topik ?>" class="collapse <?= $is_first ? 'show' : '' ?>">
                    <div class="section-body">
                        
                        <?php 
                        $ada_konten = false;

                        // 1. TAMPILKAN MATERI
                        $q_materi = mysqli_query($koneksi, "SELECT * FROM materi WHERE IDMapel='$id_mapel' AND IDTopik='$id_topik'");
                        while($mt = mysqli_fetch_assoc($q_materi)): $ada_konten = true; 
                        ?>
                            <div class="content-item">
                                <div class="d-flex align-items-center flex-grow-1" style="cursor: pointer;" onclick="window.open('../dokumen_materi/<?= htmlspecialchars($mt['Filepath']) ?>', '_blank')">
                                    <div class="content-icon icon-materi"><i class="bi bi-file-earmark-text-fill"></i></div>
                                    <div class="content-info">
                                        <div class="content-title"><?= htmlspecialchars($mt['Judul']) ?></div>
                                        <div class="content-meta">Materi Pembelajaran • PDF/Dokumen</div>
                                    </div>
                                </div>
                                <button class="btn-selesai" onclick="toggleSelesai(this, 'materi_<?= $mt['IDMateri'] ?>')">
                                    <i class="bi bi-circle me-1"></i> Tandai Selesai
                                </button>
                            </div>
                        <?php endwhile; ?>

                        // 2. TAMPILKAN TUGAS
                        <?php 
                        $q_tugas = mysqli_query($koneksi, "SELECT * FROM tugas WHERE IDMapel='$id_mapel' AND IDTopik='$id_topik'");
                        while($tg = mysqli_fetch_assoc($q_tugas)): 
                            $ada_konten = true; 
                            $sudah_kumpul = in_array($tg['IDTugas'], $tugas_selesai);
                        ?>
                            <div class="content-item">
                                <div class="d-flex align-items-center flex-grow-1" style="cursor: pointer;" onclick="window.location.href='tugas.php?id_tugas=<?= $tg['IDTugas'] ?>'">
                                    <div class="content-icon icon-tugas"><i class="bi bi-journal-check"></i></div>
                                    <div class="content-info">
                                        <div class="content-title"><?= htmlspecialchars($tg['Judul']) ?></div>
                                        <div class="content-meta text-danger"><i class="bi bi-clock-history me-1"></i> Tenggat: <?= date('d M Y, H:i', strtotime($tg['Deadline'])) ?></div>
                                    </div>
                                </div>
                                
                                <?php if($sudah_kumpul): ?>
                                    <button class="btn-selesai done" disabled style="cursor: default;">
                                        <i class="bi bi-check-circle-fill me-1"></i> Telah Dikumpulkan
                                    </button>
                                <?php else: ?>
                                    <button class="btn-selesai" onclick="window.location.href='tugas.php?id_tugas=<?= $tg['IDTugas'] ?>'">
                                        <i class="bi bi-pencil-square me-1"></i> Kerjakan Tugas
                                    </button>
                                <?php endif; ?>
                            </div>
                        <?php endwhile; ?>

                        <?php if(!$ada_konten): ?>
                            <div class="text-center py-4">
                                <div class="text-muted small p-3 bg-light rounded border border-dashed">Belum ada materi atau tugas di bab ini.</div>
                            </div>
                        <?php endif; ?>

                    </div>
                </div>
            </div>
            
            <?php endforeach; ?>

        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Fitur mengubah warna menu sidebar saat di klik
        function setActiveSidebar(element) {
            document.querySelectorAll('.index-item').forEach(el => el.classList.remove('active'));
            element.classList.add('active');
        }

        // Fitur Efek Animasi "Tandai Selesai" untuk Materi
        function toggleSelesai(btn, idItem) {
            // Cek apakah sudah hijau
            let isDone = btn.classList.contains('done');
            
            if(!isDone) {
                // Berubah jadi Hijau Selesai
                btn.classList.add('done');
                btn.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> Selesai';
                
                // Simpan state di LocalStorage browser (sebagai simulasi sebelum masuk database)
                localStorage.setItem(idItem, 'selesai');

                // Notifikasi Pemanis
                Swal.fire({
                    title: 'Hebat!',
                    text: 'Anda telah menyelesaikan materi ini. Progress belajar Anda meningkat!',
                    icon: 'success',
                    toast: true,
                    position: 'bottom-end',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true
                });
            } else {
                // Batal Selesai
                btn.classList.remove('done');
                btn.innerHTML = '<i class="bi bi-circle me-1"></i> Tandai Selesai';
                localStorage.removeItem(idItem);
            }
        }

        // Jalankan saat halaman dimuat: Cek memori browser untuk materi yang sudah ditandai selesai
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.btn-selesai').forEach(btn => {
                let idItem = btn.getAttribute('onclick');
                if(idItem && idItem.includes('toggleSelesai')) {
                    // Ekstrak ID dari string onclick
                    let match = idItem.match(/'([^']+)'/);
                    if(match && localStorage.getItem(match[1]) === 'selesai') {
                        btn.classList.add('done');
                        btn.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> Selesai';
                    }
                }
            });
        });
    </script>
</body>
</html>