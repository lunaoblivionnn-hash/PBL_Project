<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Learning System Management SMKN ! Wongsorejo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="index.html">🎓 LMS SMKN 1 Wongsorejo</a>
            <div class="collapse navbar-collapse justify-content-end" id="topNav">
                <ul class="navbar-nav align-items-center">
                    <li class="nav-item me-3">
                        <a class="nav-link position-relative" href="#">
                            <i class="bi bi-bell-fill fs-5"></i>
                            <span class="position-absolute top-25 start-75 translate-middle p-1 bg-danger border border-light rounded-circle">
                                <span class="visually-hidden">Notifikasi Baru</span>
                            </span>
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                            <img src="https://ui-avatars.com/api/?name=Moh+Akbar+Perdana&background=random" class="rounded-circle me-2" width="30" height="30" alt="User">
                            Moh. Akbar Perdana
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <nav class="col-md-3 col-lg-2 d-none d-md-block sidebar py-3">
                <a href="index.php" class="sidebar-link"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a>
                <a href="#" class="sidebar-link"><i class="bi bi-journal-bookmark me-2"></i> Kursus Ku</a>
                <a href="#" class="sidebar-link active"><i class="bi bi-trophy me-2"></i> Leaderboard</a>
                <a href="#" class="sidebar-link"><i class="bi bi-calendar3 me-2"></i> Kalender</a>
            </nav>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <a href="index.php" class="btn btn-outline-secondary btn-sm mb-2">
                            <i class="bi bi-arrow-left"></i> Kembali ke Dashboard
                        </a>
                        <h3 class="fw-bold text-primary">Detail Peringkat & Poin</h3>
                    </div>
                    <span class="badge bg-primary fs-6">Informasi Akun V</span>
                </div>

                <div class="row">
                    <div class="col-lg-4">
                        
                        <div class="card border-0 shadow-sm mb-4 text-center py-4">
                            <img src="https://ui-avatars.com/api/?name=Moh+Akbar+Perdana&background=0d6efd&color=fff" class="rounded-circle mx-auto mb-3" width="90">
                            <h5 class="fw-bold mb-0">Moh. Akbar Perdana</h5>
                            <p class="text-muted small mb-3">NISN: 0087654321 | Kelas XI - AKL 1</p>
                            
                            <div class="bg-light p-3 mx-3 rounded text-start">
                                <div class="row">
                                    <div class="col-7 border-end">
                                        <small class="text-muted d-block">Gelar Saat Ini</small>
                                        <span class="fw-bold text-primary">Expert Accountant II</span>
                                    </div>
                                    <div class="col-5 text-end">
                                        <small class="text-muted d-block">Total Poin</small>
                                        <span class="fw-bold text-success fs-5">650 XP</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-white fw-bold py-3 text-primary">
                                <i class="bi bi-info-circle-fill me-2"></i> Tabel Aturan Perolehan Point (Earning Rules)
                            </div>
                            <div class="card-body p-0">
                                <table class="table table-hover table-custom mb-0" style="font-size: 13.5px;">
                                    <thead>
                                        <tr>
                                            <th class="ps-3 w-25">Aktivitas</th>
                                            <th class="text-center w-25">Point</th>
                                            <th class="pe-3 w-50">Keterangan/Syarat</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td class="ps-3 fw-bold">Baca Materi</td>
                                            <td class="text-center"><span class="point-badge">+20</span></td>
                                            <td class="pe-3 text-muted small">Otomatis didapat saat siswa membuka/mendownload materi.</td>
                                        </tr>
                                        <tr>
                                            <td class="ps-3 fw-bold">Nilai Tugas</td>
                                            <td class="text-center"><span class="point-badge text-dark bg-light">0 - 100</span></td>
                                            <td class="pe-3 text-muted small">Sesuai input nilai dari Guru di sistem.</td>
                                        </tr>
                                        <tr>
                                            <td class="ps-3 fw-bold">Bonus Kilat</td>
                                            <td class="text-center"><span class="point-badge">+50</span></td>
                                            <td class="pe-3 text-muted small">Mengumpulkan tugas dalam <strong>< 24 jam</strong> sejak diposting.</td>
                                        </tr>
                                        <tr>
                                            <td class="ps-3 fw-bold">Bonus Cepat</td>
                                            <td class="text-center"><span class="point-badge">+20</span></td>
                                            <td class="pe-3 text-muted small">Mengumpulkan tugas dalam <strong>< 48 jam</strong> sejak diposting.</td>
                                        </tr>
                                        <tr>
                                            <td class="ps-3 fw-bold">Bonus Disiplin</td>
                                            <td class="text-center"><span class="point-badge">+10</span></td>
                                            <td class="pe-3 text-muted small">Mengumpulkan tugas setelah lewat <strong>48 jam</strong>.</td>
                                        </tr>
                                        <tr>
                                            <td class="ps-3 fw-bold">Bonus sempurna</td>
                                            <td class="text-center"><span class="point-badge">+20</span></td>
                                            <td class="pe-3 text-muted small">Tambahan jika siswa mendapatkan <strong>Nilai Sempurna (100)</strong>.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>

                    <div class="col-lg-8">
                        
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-white fw-bold py-3 text-primary d-flex justify-content-between align-items-center">
                                <span><i class="bi bi-trophy-fill me-2"></i> Tabel Tingkatan Level & Gelar (Ranking System)</span>
                            </div>
                            <div class="card-body p-0" style="max-height: 480px; overflow-y: auto;">
                                <table class="table table-hover table-custom mb-0 align-middle">
                                    <thead class="sticky-top shadow-sm">
                                        <tr>
                                            <th class="text-center" style="width: 15%;">Level</th>
                                            <th style="width: 50%;">Nama Gelar</th>
                                            <th class="text-center" style="width: 35%;">Point yang dibutuhkan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr><td class="text-center">1</td><td>Beginner Accountant</td><td class="text-center">0 - 99</td></tr>
                                        <tr class="level-active"><td class="text-center text-primary">2</td><td class="text-primary">Scholar Accountant I <span class="badge bg-primary ms-2">Saat Ini</span></td><td class="text-center text-primary">100 - 199</td></tr>
                                        <tr><td class="text-center">3</td><td>Scholar Accountant II</td><td class="text-center">200 - 299</td></tr>
                                        <tr><td class="text-center">4</td><td>Veteran Accountant I</td><td class="text-center">300 - 399</td></tr>
                                        <tr><td class="text-center">5</td><td>Veteran Accountant II</td><td class="text-center">400 - 499</td></tr>
                                        <tr><td class="text-center">6</td><td>Expert Accountant I</td><td class="text-center">500 - 599</td></tr>
                                        <tr><td class="text-center">7</td><td>Expert Accountant II</td><td class="text-center">600 - 699</td></tr>
                                        <tr><td class="text-center">8</td><td>Master Accountant I</td><td class="text-center">700 - 799</td></tr>
                                        <tr><td class="text-center">9</td><td>Master Accountant II</td><td class="text-center">800 - 899</td></tr>
                                        <tr><td class="text-center">10</td><td>Grand Master Accountant I</td><td class="text-center">900 - 999</td></tr>
                                        <tr><td class="text-center">11</td><td>Grand Master Accountant II</td><td class="text-center">1000 - 1099</td></tr>
                                        <tr><td class="text-center">12</td><td>Grand Master Accountant III</td><td class="text-center">1100 - 1199</td></tr>
                                        <tr><td class="text-center">13</td><td>Challenger Accountant</td><td class="text-center">1200 - 2000</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white fw-bold">🕒 Riwayat Perolehan Terakhir</div>
                            <div class="list-group list-group-flush">
                                <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                                    <div><h6 class="mb-0 small fw-bold">Bonus Kilat</h6><small class="text-muted">Tugas Jurnal Umum (< 24 jam)</small></div>
                                    <span class="point-badge">+50 XP</span>
                                </div>
                                <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                                    <div><h6 class="mb-0 small fw-bold">Nilai Tugas</h6><small class="text-muted">Persamaan Dasar Akuntansi</small></div>
                                    <span class="point-badge">+80 XP</span>
                                </div>
                                <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                                    <div><h6 class="mb-0 small fw-bold">Baca Materi</h6><small class="text-muted">Konsep Debit Kredit</small></div>
                                    <span class="point-badge">+20 XP</span>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>