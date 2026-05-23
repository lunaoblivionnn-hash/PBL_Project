<?php
session_start();
require '../login/koneksi.php';

if($_SESSION['role'] != 'admin'){
    header("Location: ../login/login.php");
    exit;
}

// Cek apakah ada parameter kelas di URL
if (!isset($_GET['kelas']) || empty($_GET['kelas'])) {
    header("Location: mataPelajaran.php");
    exit;
}

$kelas = mysqli_real_escape_string($koneksi, $_GET['kelas']);

// Ambil data mata pelajaran untuk kelas ini, gabungkan dengan tabel guru untuk dapat nama gurunya
$query_mapel = mysqli_query($koneksi, "
    SELECT m.*, g.NamaGuru 
    FROM mapel m 
    LEFT JOIN guru g ON m.IDGuru = g.IDGuru 
    WHERE m.Kelas LIKE '%\"$kelas\"%' 
    ORDER BY m.TahunAjaran DESC, m.NamaMapel ASC
");
$jumlah_mapel = mysqli_num_rows($query_mapel);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Kelas <?= htmlspecialchars($kelas) ?> - Admin LMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; }
        
        .header-red { background: #dc3545; color: white; padding: 2rem 0 3.8rem 0; margin: 0; position: relative; }
        .header-red h2 { margin: 0; font-weight: 700; font-size: 1.7rem; letter-spacing: -0.5px; }
        .main-content { margin-top: -2.8rem; position: relative; z-index: 10; }
        
        /* Desain Kartu Mapel */
        .mapel-card {
            border: none;
            border-radius: 15px;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        .mapel-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        }
        .mapel-img-container {
            height: 160px;
            width: 100%;
            background-color: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }
        .mapel-img-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        /* Ikon default jika tidak ada gambar */
        .mapel-img-container .default-icon {
            font-size: 4rem;
            color: #adb5bd;
        }
        .tahun-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
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
                            <div>
                                <a href="mataPelajaran.php" class="text-white text-decoration-none small opacity-75 mb-1 d-block">
                                    <i class="bi bi-arrow-left me-1"></i> Kembali ke Menu Mapel
                                </a>
                                <h2>Kurikulum Kelas <?= htmlspecialchars($kelas) ?></h2>
                            </div>
                            <a href="tambahMapel.php" class="btn btn-light fw-bold px-4 text-danger shadow-sm border-0 rounded-pill">
                                <i class="bi bi-plus-lg me-2"></i> Tambah Mapel
                            </a>
                        </div>
                    </div>
                </div>

                <div class="container main-content pb-5">
                    
                    <?php if($jumlah_mapel > 0): ?>
                        <div class="row g-4">
                            <?php while($row = mysqli_fetch_assoc($query_mapel)): ?>
                            <div class="col-12 col-md-6 col-xl-4">
                                <div class="card mapel-card h-100">
                                    
                                <div class="mapel-img-container">
                                    <div class="tahun-badge"><?= $row['TahunAjaran'] ?></div>
                                    <?php 
                                    // Generator Gambar Gradien Otomatis (SVG)
                                    $nama_mapel_svg = htmlspecialchars($row['NamaMapel']);
                                    $gradients = [
                                        ['#4f46e5', '#06b6d4'], // Indigo ke Cyan
                                        ['#f12711', '#f5af19'], // Merah ke Kuning
                                        ['#834d9b', '#d04ed6'], // Ungu ke Pink
                                        ['#11998e', '#38ef7d'], // Hijau Alam
                                        ['#fc4a1a', '#f7b733']  // Oranye Matahari
                                    ];
                                    $idx = strlen($row['NamaMapel']) % 5; 
                                    $c1 = $gradients[$idx][0]; $c2 = $gradients[$idx][1];
                                    
                                    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="600" height="300"><defs><linearGradient id="g'.$idx.'" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" stop-color="'.$c1.'"/><stop offset="100%" stop-color="'.$c2.'"/></linearGradient></defs><rect width="100%" height="100%" fill="url(#g'.$idx.')"/><text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" fill="#ffffff" font-family="Arial, sans-serif" font-size="38" font-weight="bold">'.$nama_mapel_svg.'</text></svg>';
                                    
                                    $cover_img = 'data:image/svg+xml;base64,' . base64_encode($svg);

                                    // Timpa dengan gambar asli jika admin sudah menguploadnya
                                    if(!empty($row['Gambar']) && file_exists("../image/mapel/" . $row['Gambar'])) {
                                        $cover_img = "../image/mapel/" . $row['Gambar'];
                                    }
                                    ?>
                                    <img src="<?= $cover_img ?>" alt="Cover Mapel">
                                </div>

                                    <div class="card-body p-4 d-flex flex-column">
                                        <h5 class="fw-bold mb-1 text-dark"><?= $row['NamaMapel'] ?></h5>
                                        <div class="text-muted small mb-3">ID: <?= $row['IDMapel'] ?></div>
                                        
                                        <div class="mb-3 flex-grow-1">
                                            <p class="card-text text-secondary small" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                                <?= !empty($row['Deskripsi']) ? $row['Deskripsi'] : '<i>Belum ada deskripsi untuk mata pelajaran ini.</i>' ?>
                                            </p>
                                        </div>

                                        <div class="d-flex align-items-center p-2 bg-light rounded mb-3 border">
                                            <div class="bg-white rounded-circle d-flex align-items-center justify-content-center border me-2" style="width: 35px; height: 35px;">
                                                <i class="bi bi-person-video3 text-primary"></i>
                                            </div>
                                            <div>
                                                <div class="small text-muted" style="font-size: 0.7rem;">Guru Pengampu</div>
                                                <div class="fw-semibold text-dark" style="font-size: 0.85rem;">
                                                    <?= $row['NamaGuru'] ? $row['NamaGuru'] : '<span class="text-danger fst-italic">Belum Ditentukan</span>' ?>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="d-flex gap-2 mt-auto">
                                            <a href="editMapel.php?id=<?= $row['IDMapel'] ?>&kelas=<?= urlencode($kelas) ?>" class="btn btn-outline-primary btn-sm w-50 fw-bold"><i class="bi bi-pencil-square me-1"></i> Edit</a>
                                            <button class="btn btn-outline-danger btn-sm w-50 fw-bold" onclick="konfirmasiHapusMapel('<?= $row['IDMapel'] ?>', '<?= addslashes($row['NamaMapel']) ?>')"><i class="bi bi-trash3 me-1"></i> Hapus</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        
                        <div class="card border-0 shadow-sm rounded-4 text-center py-5">
                            <div class="card-body py-5">
                                <i class="bi bi-folder-x text-muted" style="font-size: 4rem;"></i>
                                <h4 class="fw-bold mt-3 text-secondary">Belum Ada Mata Pelajaran</h4>
                                <p class="text-muted mb-4">Kelas <?= htmlspecialchars($kelas) ?> saat ini belum memiliki kurikulum atau mata pelajaran yang terdaftar.</p>
                                <a href="tambahMapel.php" class="btn btn-danger px-4 fw-bold rounded-pill">
                                    <i class="bi bi-plus-lg me-2"></i> Tambahkan Mata Pelajaran Pertama
                                </a>
                            </div>
                        </div>

                    <?php endif; ?>

                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function konfirmasiHapusMapel(id, nama) {
            Swal.fire({
                title: 'Hapus ' + nama + '?',
                text: "Mata pelajaran ini akan terhapus dari kelas ini!",
                icon: 'warning', showCancelButton: true, confirmButtonColor: '#dc3545', confirmButtonText: 'Ya, Hapus'
            }).then((result) => {
                if (result.isConfirmed) {
                    // SEKARANG SUDAH AKTIF:
                    window.location.href = 'hapusMapel.php?id=' + id + '&kelas=<?= urlencode($kelas) ?>';
                }
            })
        }
    </script>
    
    <?php if(isset($_GET['status'])): ?>
        <script>
            let status = '<?= $_GET['status'] ?>';
            let pesan = '';

            if(status === 'sukses_edit') pesan = 'Perubahan mata pelajaran berhasil disimpan!';
            else if(status === 'sukses_hapus') pesan = 'Mata pelajaran berhasil dihapus dari kelas ini!';

            if(pesan !== '') {
                Swal.fire({ title: 'Berhasil!', text: pesan, icon: 'success', confirmButtonColor: '#198754', timer: 3000, showConfirmButton: false });
                window.history.replaceState(null, null, window.location.pathname);
            }
        </script>
    <?php endif; ?>
</body>
</html>