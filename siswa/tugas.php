<?php
session_start();
require '../login/koneksi.php';

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'siswa'){
    header("Location: ../login/login.php"); exit;
}

$id_user = $_SESSION['IDUser'] ?? '';
$id_tugas = mysqli_real_escape_string($koneksi, $_GET['id_tugas'] ?? '');

// Ambil data siswa
$query_siswa = mysqli_query($koneksi, "SELECT * FROM siswa WHERE IDUser='$id_user'");
$siswa = mysqli_fetch_assoc($query_siswa);
$id_siswa = $siswa['IDSiswa'] ?? '';

// Jika parameter id_tugas kosong, carikan daftar semua tugas siswa
$mode_detail = !empty($id_tugas);

if($mode_detail) {
    // Ambil detail satu tugas spesifik
    $query_tugas = mysqli_query($koneksi, "
        SELECT t.*, m.NamaMapel 
        FROM tugas t 
        JOIN mapel m ON t.IDMapel = m.IDMapel 
        WHERE t.IDTugas = '$id_tugas'
    ");
    if(mysqli_num_rows($query_tugas) == 0) { header("Location: siswa.php"); exit; }
    $tugas = mysqli_fetch_assoc($query_tugas);

    // Cek status kumpul siswa saat ini
    $query_kumpul = mysqli_query($koneksi, "SELECT * FROM pengumpulan_tugas WHERE IDTugas='$id_tugas' AND IDSiswa='$id_siswa'");
    $kumpul = mysqli_fetch_assoc($query_kumpul);
} else {
    // Ambil semua daftar tugas yang harus dikerjakan siswa
    $query_semua_tugas = mysqli_query($koneksi, "
        SELECT t.*, m.NamaMapel, pt.Status, pt.Nilai 
        FROM tugas t 
        JOIN mapel m ON t.IDMapel = m.IDMapel
        LEFT JOIN pengumpulan_tugas pt ON t.IDTugas = pt.IDTugas AND pt.IDSiswa = '$id_siswa'
        ORDER BY t.Deadline ASC
    ");
}

// PROSES SUBMIT FILE JAWABAN
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_tugas'])) {
    $id_t_post = mysqli_real_escape_string($koneksi, $_POST['id_tugas']);
    $dl_check = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT Deadline FROM tugas WHERE IDTugas='$id_t_post'"));
    
    if(isset($_FILES['file_jawaban']) && $_FILES['file_jawaban']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['file_jawaban'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['pdf', 'doc', 'docx', 'zip', 'rar', 'jpg', 'png'];
        
        if(!in_array($ext, $allowed)) {
            echo "<script>alert('Format file tidak diizinkan! Gunakan PDF, Word, Image atau RAR/ZIP.'); history.back();</script>";
            exit;
        }

        $dir = "../uploads/tugas/";
        if(!is_dir($dir)) mkdir($dir, 0755, true);
        
        $filename = "JAWAB_" . $id_t_post . "_" . $id_siswa . "_" . time() . "." . $ext;
        
        if(move_uploaded_file($file['tmp_name'], $dir . $filename)) {
            $status = (strtotime($dl_check['Deadline']) < time()) ? 'terlambat' : 'sudah_dinilai'; 
            // Jika guru belum menilai, default label status di database menyesuaikan logic system web Anda (e.g. 'belum_dinilai')
            $status = 'belum_dinilai'; 

            // Cek apakah update atau insert baru
            $cek_eksistensi = mysqli_query($koneksi, "SELECT IDPengumpulan FROM pengumpulan_tugas WHERE IDTugas='$id_t_post' AND IDSiswa='$id_siswa'");
            if(mysqli_num_rows($cek_eksistensi) > 0) {
                $sql_save = "UPDATE pengumpulan_tugas SET FileJawaban='$filename', TanggalKirim=NOW(), Status='$status' WHERE IDTugas='$id_t_post' AND IDSiswa='$id_siswa'";
            } else {
                // Generate ID pengumpulan jika dibutuhkan string primer manual
                $res_id = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT IDPengumpulan FROM pengumpulan_tugas ORDER BY IDPengumpulan DESC LIMIT 1"));
                $num = $res_id ? ((int)$res_id['IDPengumpulan'] + 1) : 1;
                
                $sql_save = "INSERT INTO pengumpulan_tugas (IDPengumpulan, IDTugas, IDSiswa, FileJawaban, TanggalKirim, Status, Nilai) VALUES ($num, '$id_t_post', '$id_siswa', '$filename', NOW(), '$status', NULL)";
            }

            if(mysqli_query($koneksi, $sql_save)) {
                echo "<script>alert('Tugas Berhasil Dikumpulkan!'); window.location='tugas.php?id_tugas=$id_t_post';</script>";
            } else {
                echo "<script>alert('Gagal menyimpan ke database.'); history.back();</script>";
            }
        }
    } else {
        echo "<script>alert('Silakan pilih file jawaban valid terlebih dahulu.'); history.back();</script>";
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kotak Tugas - LMS Siswa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        :root { --bg-dark: #111119; --card-dark: #1a1a26; --accent-blue: #4f46e5; }
        body { background-color: var(--bg-dark); color: #e2e8f0; font-family: 'Segoe UI', system-ui, sans-serif; }
        .glass-sidebar { background: rgba(26, 26, 38, 0.6); backdrop-filter: blur(12px); border-right: 1px solid rgba(255,255,255,0.08); min-height: 100vh; position: fixed; }
        .glass-card { background: rgba(26, 26, 38, 0.6); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 16px; }
        .nav-link-custom { color: #94a3b8; border-radius: 10px; padding: 11px 16px; display: flex; align-items: center; gap: 12px; font-size: 0.9rem; transition: 0.2s; text-decoration: none; }
        .nav-link-custom:hover, .nav-link-custom.active { background: var(--accent-blue); color: #fff; box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3); }
        .badge-status { font-size: 0.75rem; padding: 5px 12px; border-radius: 20px; font-weight: 600; display: inline-block; }
        .status-belum { background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); }
        .status-sudah { background: rgba(34, 197, 94, 0.15); color: #4ade80; border: 1px solid rgba(34, 197, 94, 0.3); }
    </style>
</head>
<body>

    <div class="container-fluid">
        <div class="row">
            <div class="col-md-3 col-lg-2 d-none d-md-block glass-sidebar p-3">
                <div class="d-flex align-items-center gap-2 mb-4 px-2 py-3">
                    <span class="fs-4">🎓</span>
                    <h6 class="mb-0 fw-bold text-white tracking-wide">LMS SMKN 1</h6>
                </div>
                <div class="d-flex flex-column gap-1">
                    <a href="siswa.php" class="nav-link-custom"><i class="bi bi-grid-1x2-fill"></i> Dashboard</a>
                    <a href="siswa.php" class="nav-link-custom"><i class="bi bi-book"></i> Kelas Saya</a>
                    <a href="tugas.php" class="nav-link-custom active"><i class="bi bi-journal-bookmark"></i> Cek Tugas</a>
                    <a href="gamifikasi.php" class="nav-link-custom"><i class="bi bi-trophy"></i> Papan Skor</a>
                    <hr class="text-white-50">
                    <a href="../login/logout.php" class="nav-link-custom text-danger"><i class="bi bi-box-arrow-right"></i> Keluar</a>
                </div>
            </div>

            <div class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                
                <?php if($mode_detail): ?>
                    <div class="mb-3">
                        <a href="tugas.php" class="text-decoration-none text-muted small"><i class="bi bi-chevron-left"></i> Kembali ke Daftar Semua Tugas</a>
                    </div>

                    <div class="row g-4">
                        <div class="col-lg-7">
                            <div class="glass-card p-4 h-100">
                                <span class="badge bg-secondary mb-2" style="font-size:0.75rem;"><?= htmlspecialchars($tugas['NamaMapel']) ?></span>
                                <h4 class="fw-bold text-white mb-1"><?= htmlspecialchars($tugas['Judul']) ?></h4>
                                <p class="text-danger small"><i class="bi bi-calendar-event me-1"></i> Batas Pengumpulan: <?= date('d F Y, H:i', strtotime($tugas['Deadline'])) ?></p>
                                <hr class="text-white-50">
                                <h6 class="fw-bold text-white small mb-2">Instruksi Tugas:</h6>
                                <p class="text-muted small" style="line-height:1.6; white-space: pre-wrap;"><?= htmlspecialchars($tugas['Deskripsi'] ?? 'Tidak ada deskripsi instruksi tambahan.') ?></p>
                            </div>
                        </div>

                        <div class="col-lg-5">
                            <div class="glass-card p-4">
                                <h5 class="fw-bold text-white mb-3"><i class="bi bi-upload me-2 text-primary"></i>Pengumpulan Jawaban</h5>
                                
                                <?php if($kumpul): ?>
                                    <div class="p-3 rounded mb-3" style="background: rgba(255,255,255,0.02); border: 1px dashed rgba(255,255,255,0.1);">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <small class="text-muted">Status Pengiriman:</small>
                                            <span class="badge-status status-sudah">Sudah Dikumpulkan</span>
                                        </div>
                                        <small class="text-white d-block text-truncate mb-2"><i class="bi bi-file-earmark-check me-1"></i> <?= htmlspecialchars($kumpul['FileJawaban']) ?></small>
                                        <small class="text-muted d-block" style="font-size:0.7rem;"><i class="bi bi-check-all"></i> Dikirim pada: <?= date('d/m/Y H:i', strtotime($kumpul['TanggalKirim'])) ?></small>
                                    </div>

                                    <div class="p-3 rounded mb-4" style="background: rgba(79, 70, 229, 0.08); border: 1px solid rgba(79, 70, 229, 0.2);">
                                        <small class="text-white-50 d-block mb-1">Nilai Perolehan:</small>
                                        <h2 class="fw-bold text-warning mb-1"><?= $kumpul['Nilai'] !== null ? $kumpul['Nilai'] : '<span class="text-muted fs-5 fw-normal">Belum Dinilai</span>' ?> <span class="fs-6 text-muted fw-normal">/ <?= $tugas['PoinMaksimal'] ?></span></h2>
                                        <?php if(!empty($kumpul['KomentarGuru'])): ?>
                                            <small class="text-muted d-block mt-2"><strong>Catatan Guru:</strong> "<?= htmlspecialchars($kumpul['KomentarGuru']) ?>"</small>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-warning border-0 bg-warning bg-opacity-10 text-warning small mb-4">
                                        Anda belum mengirimkan lembar kerja untuk tugas ini.
                                    </div>
                                <?php endif; ?>

                                <form action="tugas.php" method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="id_tugas" value="<?= $tugas['IDTugas'] ?>">
                                    <div class="mb-3">
                                        <label class="form-label text-white-50 small fw-semibold">Pilih File Jawaban (PDF, DOCX, ZIP, Max 10MB)</label>
                                        <input type="file" name="file_jawaban" class="form-control bg-dark text-white border-secondary small" required>
                                    </div>
                                    <button type="submit" name="submit_tugas" class="btn btn-primary w-100 fw-bold py-2" style="background: var(--accent-blue); border:none; border-radius:10px;">
                                        <i class="bi bi-cloud-arrow-up-fill me-1"></i> <?= $kumpul ? 'Perbarui Jawaban' : 'Kirim Jawaban' ?>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                <?php else: ?>
                    <h4 class="fw-bold text-white mb-1"><i class="bi bi-journal-bookmark text-primary me-2"></i>Semua Tugas Aktif</h4>
                    <p class="text-muted small mb-4">Berikut adalah daftar seluruh penugasan Anda lintas mata pelajaran.</p>

                    <div class="glass-card p-3">
                        <div class="table-responsive">
                            <table class="table table-dark table-hover mb-0 align-middle" style="--bs-table-bg:transparent;">
                                <thead class="text-muted small" style="border-bottom: 1px solid rgba(255,255,255,0.08)">
                                    <tr>
                                        <th>Mata Pelajaran</th>
                                        <th>Judul Tugas</th>
                                        <th>Batas Waktu</th>
                                        <th>Status Kerja</th>
                                        <th class="text-end">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="small">
                                    <?php if(mysqli_num_rows($query_semua_tugas) == 0): ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-4">Hebat! Tidak ada tugas tersisa yang perlu dikerjakan.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php while($row = mysqli_fetch_assoc($query_semua_tugas)): 
                                            $is_done = ($row['Status'] != null);
                                        ?>
                                            <tr style="border-bottom: 1px solid rgba(255,255,255,0.04)">
                                                <td class="fw-bold text-white"><?= htmlspecialchars($row['NamaMapel']) ?></td>
                                                <td><?= htmlspecialchars($row['Judul']) ?></td>
                                                <td class="text-danger"><?= date('d/m H:i', strtotime($row['Deadline'])) ?></td>
                                                <td>
                                                    <?php if($is_done): ?>
                                                        <span class="badge-status status-sudah">Selesai (Score: <?= $row['Nilai'] ?? '-' ?>)</span>
                                                    <?php else: ?>
                                                        <span class="badge-status status-belum">Belum Selesai</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-end">
                                                    <a href="tugas.php?id_tugas=<?= $row['IDTugas'] ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                                        Detail
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>