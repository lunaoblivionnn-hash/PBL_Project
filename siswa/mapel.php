<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Materi Mapel - LMS Siswa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-light">

    <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom py-2 sticky-top">
        <div class="container-fluid px-4">
            <a class="navbar-brand fw-bold d-flex align-items-center" href="index.html">
                <img src="image/logosmk.png" alt="Logo" width="35" height="35" class="me-3">
            </a>
            
            <ul class="navbar-nav me-auto d-flex flex-row gap-4">
                <li class="nav-item"><a class="nav-link text-dark py-0" href="index.html">Beranda</a></li>
                <li class="nav-item"><a class="nav-link text-dark py-0" href="index.html">Kursusku</a></li>
            </ul>

            <div class="d-flex align-items-center gap-4">
                <a href="#" class="text-dark"><i class="bi bi-bell fs-5"></i></a>
                <a href="#" class="text-dark"><i class="bi bi-chat-left-text fs-5"></i></a>
                <div class="dropdown">
                    <a class="text-dark text-decoration-none dropdown-toggle d-flex align-items-center" href="#" data-bs-toggle="dropdown">
                        <span class="badge bg-secondary rounded-circle p-2 me-1">MP</span>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <nav class="navbar navbar-expand navbar-dark bg-primary menu-mapel-nav">
        <div class="container-fluid px-5">
            <ul class="navbar-nav gap-3">
                <li class="nav-item"><a class="nav-link active fw-bold border-bottom border-3 border-white" href="#">Kursus</a></li>
                <li class="nav-item"><a class="nav-link" href="#">Peserta</a></li>
                <li class="nav-item"><a class="nav-link" href="#">Nilai</a></li>
                <li class="nav-item"><a class="nav-link" href="#">Kompetensi</a></li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Selengkapnya</a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="container-fluid">
        
        <div class="row konten-materi d-none" id="materi-akuntansi-pemerintah">
            
            <nav class="col-md-4 col-lg-3 d-none d-md-block bg-white border-end py-3 sidebar-materi" style="min-height: calc(100vh - 110px); overflow-y: auto;">
                <div class="mb-3 px-3">
                    <i class="bi bi-x-lg fs-5" style="cursor:pointer;" title="Tutup sidebar"></i>
                </div>
                
                <div class="list-group list-group-flush">
                    <a href="#" class="list-group-item list-group-item-action bg-primary text-white border-0 rounded mx-2 mb-1 fw-semibold">
                        <i class="bi bi-chevron-right me-2 small"></i> Umum
                    </a>
                    <a href="#" class="list-group-item list-group-item-action border-0 mb-1 fw-semibold text-dark">
                        <i class="bi bi-chevron-down me-2 small text-muted"></i> Pertemuan 1: Konsep Dasar
                    </a>
                    
                    <div class="ps-4 mb-2">
                        <a href="#" class="d-block text-decoration-none text-muted small py-2"><i class="bi bi-circle-fill text-primary me-2"></i>Modul 1: Karakteristik Pemda</a>
                        <a href="#" class="d-block text-decoration-none text-muted small py-2"><i class="bi bi-circle-fill text-success me-2"></i>Forum: Pemda vs Swasta</a>
                    </div>

                    <a href="#" class="list-group-item list-group-item-action border-0 mb-1">
                        <i class="bi bi-chevron-right me-2 small text-muted"></i> Pertemuan 2: Jurnal APBD
                    </a>
                </div>
            </nav>

            <main class="col-md-8 col-lg-9 px-md-5 py-5">
                <h2 class="judulBesarMapel fw-bold mb-4 text-uppercase text-dark" style="font-size: 2rem;">AKUNTANSI PEMERINTAH</h2>

                <div class="bg-white p-5 rounded shadow-sm border">
                    <div class="accordion accordion-flush" id="accordionAP">
                        
                        <div class="accordion-item mb-4 border-0 border-bottom">
                            <h2 class="accordion-header position-relative">
                                <button class="accordion-button fs-4 fw-bold text-dark px-0 bg-transparent shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#collapseUmum-AP">
                                    <i class="bi bi-chevron-down me-3 fs-5"></i> Umum
                                </button>
                            </h2>
                            <div id="collapseUmum-AP" class="accordion-collapse collapse show">
                                <div class="accordion-body px-0 pt-3 pb-0 ms-5">
                                    <div class="border rounded p-4 mb-3">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="d-flex align-items-center">
                                                <div class="bg-primary text-white rounded d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;">
                                                    <i class="bi bi-file-earmark-text fs-3"></i>
                                                </div>
                                                <a href="#" class="text-primary text-decoration-none fs-6 fw-semibold">Silabus Mata Pelajaran</a>
                                            </div>
                                            <button class="btn btn-outline-secondary btn-sm rounded-1 px-3">Tandai selesai</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item mb-4 border-0 border-bottom">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed fs-4 fw-bold text-dark px-0 bg-transparent shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSatu-AP">
                                    <i class="bi bi-chevron-right me-3 fs-5"></i> Pertemuan 1: Konsep Dasar Akuntansi Pemda
                                </button>
                            </h2>
                            <div id="collapseSatu-AP" class="accordion-collapse collapse">
                                <div class="accordion-body px-0 pt-3 ms-5 text-muted">Isi materi pertemuan 1 akan tampil di sini.</div>
                            </div>
                        </div>

                    </div>
                </div>
            </main>
        </div>


        <div class="row konten-materi d-none" id="materi-matematika">
            
            <nav class="col-md-4 col-lg-3 d-none d-md-block bg-white border-end py-3 sidebar-materi" style="min-height: calc(100vh - 110px); overflow-y: auto;">
                <div class="mb-3 px-3">
                    <i class="bi bi-x-lg fs-5" style="cursor:pointer;" title="Tutup sidebar"></i>
                </div>
                <div class="list-group list-group-flush">
                    <a href="#" class="list-group-item list-group-item-action bg-primary text-white border-0 rounded mx-2 mb-1 fw-semibold">
                        <i class="bi bi-chevron-right me-2 small"></i> Umum
                    </a>
                    <a href="#" class="list-group-item list-group-item-action border-0 mb-1 fw-semibold text-dark">
                        <i class="bi bi-chevron-down me-2 small text-muted"></i> Pertemuan 1: Operasi Matriks
                    </a>
                    <div class="ps-4 mb-2">
                        <a href="#" class="d-block text-decoration-none text-muted small py-2"><i class="bi bi-circle-fill text-primary me-2"></i>Modul 1: Penjumlahan Matriks</a>
                    </div>
                </div>
            </nav>

            <main class="col-md-8 col-lg-9 px-md-5 py-5">
                <h2 class="judulBesarMapel fw-bold mb-4 text-uppercase text-dark" style="font-size: 2rem;">MATEMATIKA</h2>

                <div class="bg-white p-5 rounded shadow-sm border">
                    <div class="accordion accordion-flush" id="accordionMTK">
                        
                        <div class="accordion-item mb-4 border-0 border-bottom">
                            <h2 class="accordion-header position-relative">
                                <button class="accordion-button collapsed fs-4 fw-bold text-dark px-0 bg-transparent shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#collapseUmum-MTK">
                                    <i class="bi bi-chevron-right me-3 fs-5"></i> Umum
                                </button>
                            </h2>
                            <div id="collapseUmum-MTK" class="accordion-collapse collapse">
                                <div class="accordion-body px-0 pt-3 ms-5 text-muted">Silabus Matematika.</div>
                            </div>
                        </div>

                        <div class="accordion-item mb-4 border-0 border-bottom">
                            <h2 class="accordion-header">
                                <button class="accordion-button fs-4 fw-bold text-dark px-0 bg-transparent shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSatu-MTK">
                                    <i class="bi bi-chevron-down me-3 fs-5"></i> Pertemuan 1: Operasi Matriks
                                </button>
                            </h2>
                            <div id="collapseSatu-MTK" class="accordion-collapse collapse show">
                                <div class="accordion-body px-0 pt-3 pb-0 ms-5">
                                    <div class="border rounded p-4 mb-3">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="d-flex align-items-center">
                                                <div class="bg-primary text-white rounded d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;">
                                                    <i class="bi bi-file-earmark-pdf fs-3"></i>
                                                </div>
                                                <a href="#" class="text-primary text-decoration-none fs-6 fw-semibold">Modul 1: Penjumlahan & Pengurangan Matriks</a>
                                            </div>
                                            <button class="btn btn-outline-secondary btn-sm rounded-1 px-3">Tandai selesai</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </main>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="action.js"></script>
</body>
</html>