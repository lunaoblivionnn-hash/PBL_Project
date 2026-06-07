<?php
session_start();
require '../login/koneksi.php';

// Pastikan yang mengakses adalah guru
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'guru'){
    header("Location: ../login/login.php"); exit;
}

if(!isset($_GET['id_tugas']) || !isset($_GET['kelas'])){
    echo "<script>alert('Data tugas atau kelas tidak valid!'); window.location='guru.php';</script>"; exit;
}

$id_tugas = mysqli_real_escape_string($koneksi, $_GET['id_tugas']);
$kelas    = mysqli_real_escape_string($koneksi, $_GET['kelas']);

// 1. Ambil Informasi Tugas
$q_tugas = mysqli_query($koneksi, "
    SELECT t.*, m.NamaMapel 
    FROM tugas t
    JOIN mapel m ON t.IDMapel = m.IDMapel
    WHERE t.IDTugas = '$id_tugas'
");
$data_tugas = mysqli_fetch_assoc($q_tugas);

if(!$data_tugas) {
    die("Data tugas tidak ditemukan.");
}

$deadline_str = $data_tugas['Deadline'] ?? '';
$deadline_waktu = !empty($deadline_str) ? strtotime($deadline_str) : 0;
$deadline_format = ($deadline_waktu > 0) ? date('d M Y - H:i', $deadline_waktu) . " WIB" : "Tidak ada batas waktu";

// 2. Ambil Daftar Siswa di Kelas Tersebut Beserta Data Pengumpulan Tugasnya
// Menggunakan LEFT JOIN agar siswa yang BELUM mengumpulkan tetap tampil di daftar
$query_rekap = mysqli_query($koneksi, "
    SELECT 
        s.IDSiswa, s.NamaSiswa, s.NISN,
        pt.IDPengumpulan, pt.FileJawaban, pt.TanggalKirim, pt.Nilai, pt.KomentarGuru, pt.Status
    FROM siswa s
    LEFT JOIN pengumpulan_tugas pt ON s.IDSiswa = pt.IDSiswa AND pt.IDTugas = '$id_tugas'
    WHERE s.Kelas = '$kelas'
    ORDER BY s.NamaSiswa ASC
");

// 3. Proses Pemberian Nilai (Form Submit)
if(isset($_POST['simpan_nilai'])) {
    $id_kumpul = mysqli_real_escape_string($koneksi, $_POST['id_pengumpulan']);
    $nilai_input = (int)$_POST['nilai'];
    $komentar = mysqli_real_escape_string($koneksi, $_POST['komentar']);
    $id_siswa_nilai = mysqli_real_escape_string($koneksi, $_POST['id_siswa_nilai']);
    
    // Validasi maksimal nilai 100
    if($nilai_input > 100) $nilai_input = 100;
    if($nilai_input < 0) $nilai_input = 0;

    $q_update = mysqli_query($koneksi, "
        UPDATE pengumpulan_tugas 
        SET Nilai = $nilai_input, KomentarGuru = '$komentar', Status = 'sudah_dinilai' 
        WHERE IDPengumpulan = '$id_kumpul'
    ");

    if($q_update) {
        // ==============================================================
        // SISTEM SUNTIK GAMIFIKASI (AT002 & AT006)
        // ==============================================================
        // 1. Cek apakah ini pemberian nilai pertama kali? (Agar tidak poin ganda jika guru edit nilai)
        $cek_riwayat = mysqli_query($koneksi, "SELECT IDRiwayat FROM riwayat_poin WHERE IDSiswa='$id_siswa_nilai' AND IDPengumpulan='$id_kumpul' AND IDAturan IN ('AT002', 'AT006')");
        
        if(mysqli_num_rows($cek_riwayat) == 0) {
            $total_poin_tambahan = 0;
            
            // Aturan 1: Diberi Nilai Berapa Pun -> Dapat AT002 (Misal: 100XP)
            $q_at2 = mysqli_query($koneksi, "SELECT BesaranPoin FROM master_aturan_poin WHERE IDAturan='AT002'");
            $poin_dinilai = mysqli_fetch_assoc($q_at2)['BesaranPoin'] ?? 100;
            $total_poin_tambahan += $poin_dinilai;
            mysqli_query($koneksi, "INSERT INTO riwayat_poin (IDSiswa, IDAturan, IDPengumpulan, TanggalWaktu) VALUES ('$id_siswa_nilai', 'AT002', '$id_kumpul', NOW())");

            // Aturan 2: Nilai Sempurna 100 -> Dapat AT006 (Misal: 20XP)
            if($nilai_input == 100) {
                $q_at6 = mysqli_query($koneksi, "SELECT BesaranPoin FROM master_aturan_poin WHERE IDAturan='AT006'");
                $poin_sempurna = mysqli_fetch_assoc($q_at6)['BesaranPoin'] ?? 20;
                $total_poin_tambahan += $poin_sempurna;
                mysqli_query($koneksi, "INSERT INTO riwayat_poin (IDSiswa, IDAturan, IDPengumpulan, TanggalWaktu) VALUES ('$id_siswa_nilai', 'AT006', '$id_kumpul', NOW())");
            }

            // Tambahkan XP ke Profil Siswa
            mysqli_query($koneksi, "UPDATE gamifikasi SET TotalPoint = TotalPoint + $total_poin_tambahan WHERE IDSiswa = '$id_siswa_nilai'");
        }

        header("Location: rekapTugas.php?id_tugas=$id_tugas&kelas=".urlencode($kelas)."&status=sukses_nilai");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Pengumpulan: <?= htmlspecialchars($data_tugas['Judul']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        :root { --primary: #0f6cb6; --secondary: #e2e8f0; }
        body { background-color: #f8fafc; font-family: 'Segoe UI', system-ui, sans-serif; }
        .header-card { background: linear-gradient(135deg, var(--primary), #0a4f8a); color: white; border-radius: 15px; padding: 25px; margin-bottom: 25px; box-shadow: 0 10px 20px rgba(15, 108, 182, 0.15); }
        .student-row { transition: all 0.2s; cursor: pointer; }
        .student-row:hover { background-color: #f1f5f9; transform: translateX(5px); }
        .status-badge { font-size: 0.75rem; padding: 6px 12px; border-radius: 50px; font-weight: 700; letter-spacing: 0.5px; }
        .file-box { background: #fff; border: 1px solid #e2e8f0; padding: 10px 15px; border-radius: 10px; display: inline-flex; align-items: center; gap: 10px; }
        
        /* Modal Preview File Style */
        .modal-xl-custom { max-width: 90%; }
        #previewFrame { width: 100%; height: 75vh; border: none; border-radius: 8px; background: #e2e8f0; }
    </style>
</head>
<body>

    <div class="container-fluid py-4 px-lg-5">
        
        <a href="kelolaMapel.php?id_mapel=<?= $data_tugas['IDMapel'] ?>&kelas=<?= urlencode($kelas) ?>" class="btn btn-outline-secondary btn-sm mb-3 rounded-pill fw-bold">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Materi
        </a>

        <div class="header-card">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <span class="badge bg-white text-primary mb-2 rounded-pill px-3 py-2"><i class="bi bi-folder2-open me-1"></i> <?= htmlspecialchars($data_tugas['NamaMapel']) ?> - <?= htmlspecialchars($kelas) ?></span>
                    <h2 class="fw-bold mb-1"><?= htmlspecialchars($data_tugas['Judul']) ?></h2>
                    <p class="mb-0 text-white-50 mt-2"><i class="bi bi-clock-history me-1"></i> Batas Pengumpulan: <strong class="text-white"><?= $deadline_format ?></strong></p>
                </div>
                <div class="col-md-4 text-md-end mt-4 mt-md-0">
                    <div class="bg-white bg-opacity-10 p-3 rounded-3 d-inline-block text-start border border-white border-opacity-25">
                        <div class="small text-white-50 mb-1">Skor Maksimal</div>
                        <h3 class="mb-0 fw-bold text-warning"><i class="bi bi-star-fill me-2"></i><?= $data_tugas['PoinMaksimal'] ?? 100 ?> Poin</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-white border-bottom p-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
                <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-people-fill text-primary me-2"></i>Daftar Pengumpulan Siswa</h5>
                <div class="input-group" style="max-width: 300px;">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" id="cariSiswa" class="form-control border-start-0 bg-light" placeholder="Cari nama siswa..." onkeyup="filterSiswa()">
                </div>
            </div>
            
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="tabelRekap">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4 py-3" width="5%">No</th>
                                <th class="py-3" width="25%">Nama Siswa</th>
                                <th class="py-3" width="15%">Status</th>
                                <th class="py-3" width="30%">File & Waktu Pengumpulan</th>
                                <th class="py-3 text-center" width="15%">Nilai</th>
                                <th class="pe-4 py-3 text-end" width="10%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            while($row = mysqli_fetch_assoc($query_rekap)): 
                                $sdh_kumpul = !empty($row['IDPengumpulan']);
                                $status_badge = '<span class="status-badge bg-danger bg-opacity-10 text-danger"><i class="bi bi-x-circle-fill me-1"></i>Belum Mengumpulkan</span>';
                                
                                if($sdh_kumpul) {
                                    $tgl_kirim_waktu = strtotime($row['TanggalKirim']);
                                    $tgl_kirim_format = date('d M Y, H:i', $tgl_kirim_waktu) . " WIB";
                                    
                                    // Pengecekan Keterlambatan
                                    if($deadline_waktu > 0 && $tgl_kirim_waktu > $deadline_waktu) {
                                        $status_badge = '<span class="status-badge bg-warning bg-opacity-10 text-warning"><i class="bi bi-exclamation-triangle-fill me-1"></i>Terlambat</span>';
                                    } else {
                                        $status_badge = '<span class="status-badge bg-success bg-opacity-10 text-success"><i class="bi bi-check-circle-fill me-1"></i>Selesai Tepat Waktu</span>';
                                    }
                                }
                            ?>
                            <tr class="student-row" onclick="bukaModalNilai('<?= $row['IDSiswa'] ?>', '<?= addslashes($row['NamaSiswa']) ?>', '<?= $row['IDPengumpulan'] ?? '' ?>', '<?= $row['FileJawaban'] ?? '' ?>', '<?= $row['Nilai'] ?? '' ?>', '<?= addslashes($row['KomentarGuru'] ?? '') ?>')">
                                <td class="ps-4 text-muted fw-bold"><?= $no++ ?></td>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <img src="https://ui-avatars.com/api/?name=<?= urlencode($row['NamaSiswa']) ?>&background=random" class="rounded-circle" width="40" height="40">
                                        <div>
                                            <h6 class="mb-0 fw-bold text-dark siswa-nama"><?= htmlspecialchars($row['NamaSiswa']) ?></h6>
                                            <small class="text-muted">NISN: <?= htmlspecialchars($row['NISN']) ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td><?= $status_badge ?></td>
                                <td>
                                    <?php if($sdh_kumpul): ?>
                                        <div class="d-flex flex-column gap-2 align-items-start">
                                            <div class="file-box shadow-sm">
                                                <i class="bi bi-file-earmark-text fs-4 text-primary"></i>
                                                <div class="text-truncate" style="max-width: 200px;">
                                                    <span class="d-block small fw-bold text-dark text-truncate" title="<?= htmlspecialchars($row['FileJawaban']) ?>"><?= htmlspecialchars($row['FileJawaban']) ?></span>
                                                </div>
                                            </div>
                                            <small class="text-muted"><i class="bi bi-clock me-1"></i> <?= $tgl_kirim_format ?></small>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted small fst-italic">Menunggu siswa mengunggah file...</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if($sdh_kumpul && $row['Nilai'] !== null): ?>
                                        <span class="badge bg-primary fs-6 rounded-pill px-3"><?= $row['Nilai'] ?> / 100</span>
                                    <?php else: ?>
                                        <span class="text-muted small">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="pe-4 text-end">
                                    <?php if($sdh_kumpul): ?>
                                        <button class="btn btn-sm btn-primary rounded-pill fw-bold px-3">Beri Nilai</button>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-light text-muted border rounded-pill px-3" disabled>Kosong</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <div class="modal fade" id="modalPenilaian" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl-custom modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow-lg">
                <div class="modal-header border-bottom-0 pb-0 pt-4 px-4">
                    <h5 class="modal-title fw-bold text-dark"><i class="bi bi-pencil-square text-primary me-2"></i>Evaluasi Tugas Siswa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-4">
                        <div class="col-lg-8 border-end pe-lg-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-bold mb-0 text-secondary">Preview Dokumen: <span id="namaFilePreview" class="text-primary"></span></h6>
                                <a href="#" id="btnDownloadAsli" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold" download><i class="bi bi-download me-1"></i>Unduh Berkas Asli</a>
                            </div>
                            
                            <div id="previewContainer" class="bg-light rounded-3 d-flex align-items-center justify-content-center" style="height: 75vh;">
                                </div>
                        </div>

                        <div class="col-lg-4 ps-lg-4 d-flex flex-column">
                            <div class="text-center mb-4">
                                <div class="bg-primary bg-opacity-10 d-inline-block p-3 rounded-circle mb-2">
                                    <i class="bi bi-person-fill fs-2 text-primary"></i>
                                </div>
                                <h5 class="fw-bold mb-0 text-dark" id="namaSiswaDetail">Nama Siswa</h5>
                                <span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill mt-2">Sedang Dievaluasi</span>
                            </div>

                            <form action="" method="POST" class="flex-grow-1 d-flex flex-column">
                                <input type="hidden" name="id_pengumpulan" id="inputIDPengumpulan">
                                <input type="hidden" name="id_siswa_nilai" id="inputIDSiswaNilai">

                                <div class="mb-4 text-center">
                                    <label class="form-label fw-bold text-dark">Beri Nilai (0 - 100)</label>
                                    <input type="number" name="nilai" id="inputNilai" class="form-control form-control-lg text-center fs-1 fw-bold text-primary mx-auto" style="max-width: 150px; background: #f8fafc; border: 2px dashed #cbd5e1;" required min="0" max="100" placeholder="0">
                                </div>

                                <div class="mb-4 flex-grow-1">
                                    <label class="form-label fw-bold text-secondary small">Komentar / Umpan Balik (Opsional)</label>
                                    <textarea name="komentar" id="inputKomentar" class="form-control" rows="5" placeholder="Tulis masukan untuk siswa di sini..."></textarea>
                                </div>

                                <button type="submit" name="simpan_nilai" class="btn btn-primary btn-lg w-100 rounded-pill fw-bold mt-auto shadow-sm">
                                    <i class="bi bi-check2-circle me-2"></i>Simpan Penilaian
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Fitur Live Search Siswa
        function filterSiswa() {
            let input = document.getElementById('cariSiswa').value.toLowerCase();
            let rows = document.getElementsByClassName('student-row');
            for (let i = 0; i < rows.length; i++) {
                let name = rows[i].querySelector('.siswa-nama').innerText.toLowerCase();
                if (name.indexOf(input) > -1) { rows[i].style.display = ""; } 
                else { rows[i].style.display = "none"; }
            }
        }

        // Fitur Modal dan Preview File Berkelas
        const modalNilai = new bootstrap.Modal(document.getElementById('modalPenilaian'));
        
        function bukaModalNilai(idSiswa, namaSiswa, idKumpul, namaFile, nilaiLama, komentarLama) {
            if(!idKumpul) {
                Swal.fire({ title: 'Belum Mengumpulkan', text: 'Siswa ini belum mengunggah file tugas.', icon: 'info', confirmButtonColor: '#0f6cb6' });
                return;
            }

            document.getElementById('namaSiswaDetail').innerText = namaSiswa;
            document.getElementById('inputIDPengumpulan').value = idKumpul;
            document.getElementById('inputIDSiswaNilai').value = idSiswa;
            document.getElementById('inputNilai').value = nilaiLama;
            document.getElementById('inputKomentar').value = komentarLama;
            
            document.getElementById('namaFilePreview').innerText = namaFile;
            
            let targetPath = '../uploads/tugas/' + namaFile;
            document.getElementById('btnDownloadAsli').href = targetPath;

            // Logika Smart Preview: Hanya tampilkan iframe untuk PDF, JPG, PNG
            let ext = namaFile.split('.').pop().toLowerCase();
            let previewBox = document.getElementById('previewContainer');
            
            if(['pdf', 'jpg', 'jpeg', 'png'].includes(ext)) {
                previewBox.innerHTML = `<iframe src="${targetPath}" id="previewFrame"></iframe>`;
            } else {
                previewBox.innerHTML = `
                    <div class="text-center p-4">
                        <i class="bi bi-file-earmark-x text-muted mb-3" style="font-size: 4rem;"></i>
                        <h5 class="text-secondary fw-bold">Preview Tidak Tersedia</h5>
                        <p class="text-muted small">File berekstensi <b>.${ext}</b> tidak dapat dipreview langsung di browser.<br>Silakan klik tombol "Unduh Berkas Asli" di kanan atas untuk melihat isinya.</p>
                    </div>
                `;
            }

            modalNilai.show();
        }

        <?php if(isset($_GET['status']) && $_GET['status'] == 'sukses_nilai'): ?>
            Swal.fire({ title: 'Nilai Tersimpan! 🎉', text: 'Penilaian berhasil disimpan dan XP siswa telah bertambah.', icon: 'success', timer: 3000, showConfirmButton: false });
            window.history.replaceState(null, null, window.location.pathname);
        <?php endif; ?>
    </script>
</body>
</html>