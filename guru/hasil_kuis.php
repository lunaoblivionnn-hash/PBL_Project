<?php
session_start();
require '../login/koneksi.php';

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'guru'){ 
    header("Location: ../login/login.php"); 
    exit; 
}

$id_kuis = mysqli_real_escape_string($koneksi, $_GET['id_kuis'] ?? '');
$kelas   = mysqli_real_escape_string($koneksi, $_GET['kelas'] ?? '');

if(empty($id_kuis) || empty($kelas)){ die("Data tidak valid!"); }

// Publish nilai ke siswa
if(isset($_POST['publish_nilai'])) {
    mysqli_query($koneksi, "UPDATE kuis SET Status='Draft' WHERE IDKuis='$id_kuis'");
    header("Location: hasil_kuis.php?id_kuis=$id_kuis&kelas=" . urlencode($kelas));
    exit;
}

// Update nilai manual
if(isset($_POST['update_nilai_manual'])) {
    $id_s_manual = mysqli_real_escape_string($koneksi, $_POST['id_siswa']);
    $nilai_baru = (float)$_POST['nilai_manual'];
    mysqli_query($koneksi, "UPDATE kuis_nilai SET NilaiAkhir='$nilai_baru' WHERE IDKuis='$id_kuis' AND IDSiswa='$id_s_manual'");
    header("Location: hasil_kuis.php?id_kuis=$id_kuis&kelas=" . urlencode($kelas));
    exit;
}

// Ambil info kuis
$q_kuis = mysqli_query($koneksi, "SELECT * FROM kuis WHERE IDKuis = '$id_kuis'");
$kuis = mysqli_fetch_assoc($q_kuis);
if(!$kuis) { die("Ujian tidak ditemukan."); }

$id_mapel = $kuis['IDMapel'];

// Ambil data nilai siswa
$q_nilai = mysqli_query($koneksi, "
    SELECT s.IDSiswa, s.NamaSiswa, kn.WaktuMulai, kn.WaktuSelesai, kn.Benar, kn.Salah, kn.NilaiAkhir
    FROM siswa s
    LEFT JOIN kuis_nilai kn ON s.IDSiswa = kn.IDSiswa AND kn.IDKuis = '$id_kuis'
    WHERE s.Kelas = '$kelas'
    ORDER BY s.NamaSiswa ASC
");

$data_siswa = [];
$total_nilai = 0;
$siswa_mengerjakan = 0;

while($row = mysqli_fetch_assoc($q_nilai)){
    $data_siswa[] = $row;
    if(!is_null($row['NilaiAkhir'])){
        $total_nilai += $row['NilaiAkhir'];
        $siswa_mengerjakan++;
    }
}
$rata_rata = $siswa_mengerjakan > 0 ? round($total_nilai / $siswa_mengerjakan, 2) : 0;
$total_siswa = count($data_siswa);

// Ambil data soal dan simpan ke array
$q_soal = mysqli_query($koneksi, "SELECT IDSoal, Pertanyaan, TipeSoal FROM kuis_soal WHERE IDKuis = '$id_kuis' ORDER BY Urutan ASC");
$data_soal = [];
while($soal = mysqli_fetch_assoc($q_soal)) {
    $data_soal[] = $soal;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Ujian - LMS Guru</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        :root { --primary: #4f46e5; --bg-light: #f8fafc; }
        body { background-color: var(--bg-light); font-family: 'Segoe UI', system-ui, sans-serif; padding-bottom: 50px; }
        .header-box { background: linear-gradient(135deg, #1e1b4b, #312e81); color: white; border-radius: 16px; padding: 30px; box-shadow: 0 10px 30px rgba(30,27,75,0.1); margin-top: 20px; }
        .stat-card { background: rgba(255,255,255,0.1); border-radius: 12px; padding: 15px 20px; border: 1px solid rgba(255,255,255,0.15); }
        .nav-tabs .nav-link { color: #64748b; font-weight: 600; border: none; border-bottom: 3px solid transparent; padding: 12px 25px; transition: 0.3s; cursor: pointer; }
        .nav-tabs .nav-link:hover { color: var(--primary); }
        .nav-tabs .nav-link.active { color: var(--primary); border-bottom-color: var(--primary); background: transparent; }
        .table-custom { background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.03); }
        .table-custom th { background: #f1f5f9; color: #475569; font-size: 0.85rem; text-transform: uppercase; padding: 15px; }
        .table-custom td { padding: 15px; vertical-align: middle; border-bottom: 1px solid #f1f5f9; }
        .table-custom tr:hover td { background: #f8fafc; }
        .score-badge { font-weight: 800; font-size: 1.1rem; padding: 8px 15px; border-radius: 8px; }
        .score-high { background: #dcfce7; color: #16a34a; }
        .score-mid { background: #fef9c3; color: #ca8a04; }
        .score-low { background: #fee2e2; color: #dc2626; }
        .score-null { background: #f1f5f9; color: #94a3b8; }
        .analysis-card { background: #fff; border-radius: 12px; padding: 20px; border: 1px solid #e2e8f0; margin-bottom: 15px; }
        .progress-correct { height: 10px; background-color: #f1f5f9; border-radius: 10px; overflow: hidden; margin-top: 10px; }
        .progress-correct .bar { background-color: #10b981; height: 100%; }
        .tab-content > .tab-pane { display: none; }
        .tab-content > .tab-pane.active { display: block; }
        .action-button { transition: all 0.2s ease; }
        .action-button:hover { transform: scale(1.1); }
    </style>
</head>
<body>
    <nav class="navbar navbar-light bg-white border-bottom sticky-top shadow-sm py-3">
        <div class="container d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
                <a href="kelolaMapel.php?id_mapel=<?= urlencode($id_mapel) ?>&kelas=<?= urlencode($kelas) ?>" class="text-secondary fs-4"><i class="bi bi-arrow-left"></i></a>
                <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-bar-chart-fill text-primary me-2"></i> Laporan Ujian</h5>
            </div>
            <div class="d-flex align-items-center gap-3">
                <form method="POST" class="m-0">
                    <button type="submit" name="publish_nilai" class="btn <?= $kuis['Status'] == 'Draft' ? 'btn-success disabled' : 'btn-warning' ?> fw-bold shadow-sm px-4">
                        <i class="bi bi-megaphone-fill me-1"></i> <?= $kuis['Status'] == 'Draft' ? 'Nilai Sudah Diumumkan' : 'Umumkan Nilai ke Siswa' ?>
                    </button>
                </form>
                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle px-3 py-2 fs-6">
                    Kelas <?= htmlspecialchars($kelas) ?>
                </span>
            </div>
        </div>
    </nav>

    <div class="container pb-5">
        <div class="header-box mb-4">
            <h2 class="fw-bold mb-1"><?= htmlspecialchars($kuis['Judul']) ?></h2>
            <p class="text-white-50 mb-4"><i class="bi bi-clock-history me-1"></i> Dibuat: <?= date('d M Y, H:i', strtotime($kuis['TanggalDibuat'])) ?> | <i class="bi bi-hourglass-split ms-2 me-1"></i> Durasi: <?= $kuis['DurasiMenit'] ?> Menit</p>
            <div class="row g-3">
                <div class="col-md-3 col-6">
                    <div class="stat-card">
                        <div class="small text-white-50 fw-bold text-uppercase mb-1">Status Kuis</div>
                        <div class="fs-5 fw-bold <?= $kuis['Status'] == 'Published' ? 'text-success' : 'text-warning' ?>">
                            <?= $kuis['Status'] == 'Published' ? '<i class="bi bi-record-circle-fill me-1"></i> Sedang Berjalan' : '<i class="bi bi-check-circle-fill me-1"></i> Selesai' ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-card">
                        <div class="small text-white-50 fw-bold text-uppercase mb-1">Deadline</div>
                        <div class="fs-6 fw-bold text-white"><?= date('d M Y, H:i', strtotime($kuis['Deadline'])) ?></div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-card">
                        <div class="small text-white-50 fw-bold text-uppercase mb-1">Partisipasi</div>
                        <div class="fs-4 fw-bold text-white"><?= $siswa_mengerjakan ?> <span class="fs-6 text-white-50 fw-normal">/ <?= $total_siswa ?></span></div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-card">
                        <div class="small text-white-50 fw-bold text-uppercase mb-1">Rata-rata</div>
                        <div class="fs-4 fw-bold text-warning"><?= $rata_rata ?> / 100</div>
                    </div>
                </div>
            </div>
        </div>

        <ul class="nav nav-tabs mb-4" id="reportTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="nilai-tab" data-bs-toggle="tab" data-bs-target="#tab-nilai" type="button" role="tab"><i class="bi bi-people-fill me-2"></i>Nilai Siswa</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="analisis-tab" data-bs-toggle="tab" data-bs-target="#tab-analisis" type="button" role="tab"><i class="bi bi-graph-up-arrow me-2"></i>Analisis Soal</button>
            </li>
        </ul>

        <div class="tab-content" id="reportTabsContent">
            <div class="tab-pane fade show active" id="tab-nilai" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-dark mb-0">Daftar Hasil Ujian</h5>
                    <select id="sortSelect" class="form-select w-auto fw-bold text-secondary" onchange="urutkanTabel()">
                        <option value="nama_asc">Urutkan: Nama (A-Z)</option>
                        <option value="nilai_desc">Urutkan: Nilai Tertinggi</option>
                        <option value="nilai_asc">Urutkan: Nilai Terendah</option>
                    </select>
                </div>

                <div class="table-responsive table-custom">
                    <table class="table mb-0" id="tabelNilai">
                        <thead>
                            <tr>
                                <th width="35%">Nama Siswa</th>
                                <th width="20%">Status</th>
                                <th width="15%" class="text-center">Benar / Salah</th>
                                <th width="15%" class="text-center">Waktu</th>
                                <th width="15%" class="text-center">Nilai Akhir</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($data_siswa as $ds): 
                                $status = is_null($ds['NilaiAkhir']) ? 'Belum Mengerjakan' : 'Selesai';
                                $badge_class = 'score-null';
                                $nilai = $ds['NilaiAkhir'];
                                
                                if($status == 'Selesai'){
                                    if($nilai >= 80) $badge_class = 'score-high';
                                    elseif($nilai >= 60) $badge_class = 'score-mid';
                                    else $badge_class = 'score-low';
                                }
                                
                                $waktu_teks = '-';
                                if($ds['WaktuMulai'] && $ds['WaktuSelesai']) {
                                    $awal = strtotime($ds['WaktuMulai']);
                                    $akhir = strtotime($ds['WaktuSelesai']);
                                    $menit = round(abs($akhir - $awal) / 60, 0);
                                    $waktu_teks = $menit . ' min';
                                }
                            ?>
                            <tr class="item-siswa" data-nama="<?= strtolower($ds['NamaSiswa']) ?>" data-nilai="<?= is_null($nilai) ? -1 : $nilai ?>">
                                <td class="fw-bold text-dark"><?= htmlspecialchars($ds['NamaSiswa']) ?></td>
                                <td>
                                    <?php if($status == 'Selesai'): ?>
                                        <span class="badge bg-success bg-opacity-10 text-success"><i class="bi bi-check-circle-fill me-1"></i>Diserahkan</span><br>
                                        <small class="text-muted"><?= date('d M H:i', strtotime($ds['WaktuSelesai'])) ?></small>
                                    <?php else: ?>
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary"><i class="bi bi-dash-circle me-1"></i>Belum</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center fw-semibold text-secondary">
                                    <?= $status == 'Selesai' ? "<span class='text-success'>{$ds['Benar']}</span> / <span class='text-danger'>{$ds['Salah']}</span>" : '-' ?>
                                </td>
                                <td class="text-center text-secondary fw-semibold"><?= $waktu_teks ?></td>
                                <td class="text-center">
                                    <div class="d-flex align-items-center justify-content-center gap-2">
                                        <span class="score-badge <?= $badge_class ?>"><?= is_null($nilai) ? '-' : $nilai ?></span>
                                        <?php if($status == 'Selesai'): ?>
                                            <button class="btn btn-sm btn-outline-primary shadow-sm action-button" type="button" data-bs-toggle="modal" data-bs-target="#modalReview<?= $ds['IDSiswa'] ?>" title="Review"><i class="bi bi-search"></i></button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="tab-pane fade" id="tab-analisis" role="tabpanel">
                <div class="row g-4 mt-1">
                    <?php 
                    $no = 1;
                    foreach($data_soal as $soal): 
                        $id_s = $soal['IDSoal'];
                        $q_benar = mysqli_query($koneksi, "SELECT COUNT(IDJawaban) as tot FROM kuis_jawaban WHERE IDSoal='$id_s' AND IsBenar=1");
                        $tot_benar = mysqli_fetch_assoc($q_benar)['tot'] ?? 0;
                        $persen = $siswa_mengerjakan > 0 ? round(($tot_benar / $siswa_mengerjakan) * 100) : 0;
                        $salah = $siswa_mengerjakan - $tot_benar;
                    ?>
                    <div class="col-md-6">
                        <div class="analysis-card h-100">
                            <div class="d-flex align-items-start gap-3">
                                <div class="bg-primary bg-opacity-10 text-primary fw-bold rounded-circle d-flex justify-content-center align-items-center flex-shrink-0" style="width: 40px; height: 40px;">
                                    <?= $no ?>
                                </div>
                                <div class="flex-grow-1">
                                    <p class="fw-bold text-dark mb-2" style="font-size: 0.95rem; line-height: 1.4;">
                                        <?= htmlspecialchars(substr($soal['Pertanyaan'], 0, 100)) ?><?= strlen($soal['Pertanyaan']) > 100 ? '...' : '' ?>
                                    </p>
                                    <div class="d-flex justify-content-between small fw-bold mt-3">
                                        <span class="text-success"><i class="bi bi-check2-circle me-1"></i><?= $tot_benar ?> Benar</span>
                                        <span class="text-danger"><i class="bi bi-x-circle me-1"></i><?= $salah ?> Salah</span>
                                    </div>
                                    <div class="progress-correct" title="<?= $persen ?>% Benar">
                                        <div class="bar" style="width: <?= $persen ?>%;"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php $no++; endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <?php foreach($data_siswa as $ds): if(is_null($ds['NilaiAkhir'])) continue; ?>
    <div class="modal fade" id="modalReview<?= $ds['IDSiswa'] ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bi bi-person-check-fill me-2"></i>Review: <?= htmlspecialchars($ds['NamaSiswa']) ?></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body bg-light">
                    <?php
                    $q_rev = mysqli_query($koneksi, "
                        SELECT s.*, j.JawabanTeks, j.IsBenar
                        FROM kuis_soal s 
                        LEFT JOIN kuis_jawaban j ON s.IDSoal = j.IDSoal AND j.IDSiswa = '{$ds['IDSiswa']}' AND j.IDKuis = '$id_kuis'
                        WHERE s.IDKuis = '$id_kuis' ORDER BY s.Urutan ASC
                    ");
                    $no_rev = 1;
                    while($rev = mysqli_fetch_assoc($q_rev)):
                        $is_essay = in_array($rev['TipeSoal'], ['singkat', 'paragraf']);
                    ?>
                    <div class="card mb-3 border-0 shadow-sm <?= $rev['IsBenar'] ? 'border-start border-4 border-success' : 'border-start border-4 border-danger' ?>">
                        <div class="card-body">
                            <h6 class="fw-bold mb-3"><?= $no_rev ?>. <?= nl2br(htmlspecialchars($rev['Pertanyaan'])) ?></h6>
                            <?php if(!$is_essay): ?>
                                <div class="text-muted small mb-1">Jawaban Siswa:</div>
                                <div class="fw-bold <?= $rev['IsBenar'] ? 'text-success' : 'text-danger' ?>"><?= $rev['JawabanTeks'] ?: '<i>Tidak dijawab</i>' ?></div>
                            <?php else: ?>
                                <div class="text-muted small mb-1">Jawaban Siswa:</div>
                                <div class="p-2 bg-white border rounded mb-2"><?= nl2br(htmlspecialchars($rev['JawabanTeks'] ?: 'Tidak dijawab')) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php $no_rev++; endwhile; ?>
                </div>
                <div class="modal-footer bg-white d-flex justify-content-between align-items-center border-top">
                    <form method="POST" class="d-flex align-items-center gap-2 m-0">
                        <input type="hidden" name="id_siswa" value="<?= $ds['IDSiswa'] ?>">
                        <label class="fw-bold text-secondary mb-0">Nilai Akhir:</label>
                        <input type="number" name="nilai_manual" class="form-control text-center fw-bold" style="width: 80px; color:var(--primary);" value="<?= $ds['NilaiAkhir'] ?>" max="100" min="0" step="1">
                        <button type="submit" name="update_nilai_manual" class="btn btn-success fw-bold px-3"><i class="bi bi-save-fill me-1"></i>Simpan</button>
                    </form>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function urutkanTabel() {
            const table = document.getElementById("tabelNilai");
            const tbody = table.querySelector("tbody");
            const rows = Array.from(tbody.querySelectorAll(".item-siswa"));
            const sortVal = document.getElementById("sortSelect").value;

            rows.sort((a, b) => {
                let valA = parseFloat(a.dataset.nilai);
                let valB = parseFloat(b.dataset.nilai);
                let namaA = a.dataset.nama;
                let namaB = b.dataset.nama;
                
                if (sortVal === 'nama_asc') {
                    return namaA.localeCompare(namaB);
                } else if (sortVal === 'nilai_desc') {
                    return valB - valA;
                } else if (sortVal === 'nilai_asc') {
                    return valA - valB;
                }
                return 0;
            });

            rows.forEach(row => tbody.appendChild(row));
        }

        document.addEventListener('DOMContentLoaded', function() {
            const tabs = document.querySelectorAll('[data-bs-toggle="tab"]');
            tabs.forEach(tab => {
                tab.addEventListener('shown.bs.tab', function() {
                    console.log('Tab aktif:', this.getAttribute('data-bs-target'));
                });
            });
        });
    </script>
</body>
</html>