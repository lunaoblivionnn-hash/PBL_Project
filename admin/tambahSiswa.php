<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Siswa - Admin LMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../style.css">
    <style>
        .btn-lms-blue { background-color: #0f6cb6; color: white; border: none; }
        .btn-lms-blue:hover { background-color: #0e5b9c; color: white; }
        .dropzone-box {
            border: 2px dashed #ced4da;
            background-color: #ffffff;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .dropzone-box:hover { border-color: #0f6cb6; background-color: #f1f7fc; }
        .nav-pills .nav-link.active { background-color: #dc3545; } /* Mengikuti tema merah admin */

        /* 1. Body disamakan persis dengan warna background utama dashboard */
        [data-bs-theme="dark"] body {
            background-color: #121212 !important; 
            color: #e0e0e0 !important;
        }

        /* 2. Kartu Form disamakan dengan warna hitam Sidebar dashboard */
        [data-bs-theme="dark"] .bg-light,
        [data-bs-theme="dark"] .bg-white,
        [data-bs-theme="dark"] .card,
        [data-bs-theme="dark"] .card-header,
        [data-bs-theme="dark"] .card-body {
            background-color: #1e1e1e !important; 
            border-color: #333 !important; 
            color: #e0e0e0 !important;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5) !important;
        }

        /* 3. Kolom Input / Teks */
        [data-bs-theme="dark"] .form-control,
        [data-bs-theme="dark"] .form-select,
        [data-bs-theme="dark"] .input-group-text {
            background-color: #2b2b2b !important; 
            border-color: #444 !important;
            color: #e0e0e0 !important;
        }
        [data-bs-theme="dark"] .form-control:focus,
        [data-bs-theme="dark"] .form-select:focus {
            background-color: #333 !important;
            border-color: #dc3545 !important; 
            color: #fff !important;
            box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.25) !important;
        }

        /* ================= FIX KOTAK UPLOAD CSV (DROPZONE) YANG PUTIH ================= */
        [data-bs-theme="dark"] .dropzone-box {
            background-color: #2b2b2b !important;
            border: 2px dashed #444 !important;
            color: #e0e0e0 !important;
        }
        [data-bs-theme="dark"] .dropzone-box p,
        [data-bs-theme="dark"] .dropzone-box i,
        [data-bs-theme="dark"] .dropzone-box .text-muted {
            color: #adb5bd !important;
        }

        /* Khusus kotak checkbox massal & garis putus-putus template download */
        [data-bs-theme="dark"] .custom-checkbox-container,
        [data-bs-theme="dark"] .border-dashed {
            background-color: #2b2b2b !important;
            border-color: #444 !important;
        }

        /* Teks Keterangan & Muted */
        [data-bs-theme="dark"] .form-text,
        [data-bs-theme="dark"] .text-muted {
            color: #adb5bd !important;
        }

        /* Tombol Batal / Reset */
        [data-bs-theme="dark"] .btn-light {
            background-color: #343a40 !important;
            border-color: #444 !important;
            color: #fff !important;
        }
        [data-bs-theme="dark"] .btn-light:hover {
            background-color: #495057 !important;
        }
    </style>
</head>
<body class="bg-light">

    <nav class="navbar navbar-dark bg-danger shadow-sm">
        <div class="container-fluid px-4">
            <a class="navbar-brand fw-bold" href="admin.php">
                <i class="bi bi-arrow-left-circle me-2"></i> Kembali ke Dashboard
            </a>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                
                <h4 class="fw-bold mb-4">Registrasi Siswa Baru</h4>

                <ul class="nav nav-pills mb-4 gap-2" id="siswaTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-semibold shadow-sm border border-light" id="manual-tab" data-bs-toggle="pill" data-bs-target="#manual" type="button" role="tab"><i class="bi bi-keyboard me-2"></i> Input Manual</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-semibold shadow-sm border border-light text-dark bg-white" id="csv-tab" data-bs-toggle="pill" data-bs-target="#csv" type="button" role="tab"><i class="bi bi-file-earmark-spreadsheet me-2 text-success"></i> Upload Massal (CSV)</button>
                    </li>
                </ul>

                <div class="tab-content" id="siswaTabContent">
                    
                    <div class="tab-pane fade show active" id="manual" role="tabpanel">
                        <div class="card border-0 shadow-sm rounded-3">
                            <div class="card-header bg-white border-bottom py-3">
                                <h6 class="mb-0 fw-bold text-danger">Form Input Data Siswa</h6>
                            </div>
                            <div class="card-body p-4 p-md-5">
                                <form action="proses_tambah_siswa.php" method="POST">
                                    
                                    <h6 class="fw-bold text-muted mb-3">INFORMASI AKUN (LOGIN)</h6>
                                    <div class="row g-3 mb-4">
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Username (NISN) <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="nisn" placeholder="Contoh: 0011223344" autocomplete="off" required>
                                            <div class="form-text">NISN akan digunakan sebagai Username login.</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Password <span class="text-muted fw-normal">(Opsional)</span></label>
                                            <input type="password" class="form-control" name="password" placeholder="Kosongkan untuk default 'siswa123'" autocomplete="new-password">
                                        </div>

                                        <div class="col-12 mt-2">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="force_password_change" id="forcePass" value="1" checked>
                                                <label class="form-check-label fw-bold text-danger" for="forcePass">
                                                    Paksa perubahan kata sandi
                                                </label>
                                                <div class="form-text mt-0">Siswa akan diminta mengganti password saat pertama kali masuk.</div>
                                            </div>
                                        </div>
                                    </div>

                                    

                                    <hr class="mb-4">

                                    <h6 class="fw-bold text-muted mb-3">PROFIL SISWA</h6>
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label fw-semibold">ID Siswa <span class="text-muted fw-normal">(Opsional)</span></label>
                                            <input type="text" class="form-control bg-light" name="id_siswa" placeholder="Auto Generate">
                                        </div>
                                        <div class="col-md-8">
                                            <label class="form-label fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="nama_siswa" placeholder="Masukkan nama lengkap siswa" required>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Kelas <span class="text-danger">*</span></label>
                                            <select class="form-select" name="kelas" required>
                                                <option value="" selected disabled>Pilih Kelas...</option>
                                                <option value="X AKL 1">X AKL 1</option>
                                                <option value="X AKL 2">X AKL 2</option>
                                                <option value="XI AKL 1">XI AKL 1</option>
                                                <option value="XI AKL 2">XI AKL 2</option>
                                                <option value="XII AKL 1">XII AKL 1</option>
                                                <option value="XII AKL 2">XII AKL 2</option>
                                            </select>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Nomor Telepon / WhatsApp</label>
                                            <input type="text" class="form-control" name="no_telp" placeholder="Contoh: 081234567890">
                                        </div>

                                        <div class="col-12">
                                            <label class="form-label fw-semibold">Email Siswa</label>
                                            <input type="email" class="form-control" name="email" placeholder="Contoh: siswa@smkn1wongsorejo.sch.id">
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-end mt-5 gap-2">
                                        <a href="admin.php" class="btn btn-light border px-4">Batal</a>
                                        <button type="submit" class="btn btn-primary px-5 fw-bold"><i class="bi bi-save me-2"></i> Simpan Data Siswa</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="csv" role="tabpanel">
                        <div class="card border-0 shadow-sm rounded-3">
                            <div class="card-header bg-white border-bottom py-3">
                                <h6 class="mb-0 fw-bold text-danger">Upload Massal Siswa via CSV</h6>
                            </div>
                            <div class="card-body p-4 p-md-5">
                                
                                <div class="mb-4">
                                    <label class="form-label fw-bold text-secondary small">File Template CSV</label>
                                    <div class="p-3 bg-light rounded border border-dashed">
                                        <a href="contoh_format_upload_siswa.csv" class="text-decoration-none text-lms-blue fw-semibold" download>
                                            <i class="bi bi-download me-2"></i> Download format_siswa.csv
                                        </a>
                                        <div class="small text-muted mt-2">Gunakan file ini sebagai format dasar. Jangan ubah urutan kolom di baris pertama (NISN, NamaSiswa, Kelas, Email, NoTelp).</div>
                                    </div>
                                </div>

                                <form action="proses_upload_siswa.php" method="POST" enctype="multipart/form-data">
                                    <div class="mb-4">
                                        <label class="form-label fw-bold text-secondary small">Upload File <span class="text-danger">*</span></label>
                                        <div class="dropzone-box text-center p-5 position-relative">
                                        <i class="bi bi-cloud-arrow-up text-muted" style="font-size: 3.5rem;"></i>
                                        <p class="mt-3 mb-2 fw-semibold text-secondary">Pilih atau letakkan file CSV Anda di sini</p>
                                        <p class="text-muted small mb-3">Maksimal 10 MB. Berekstensi .csv</p>
                                        <div id="namaFilePilihan" class="text-primary fw-bold mt-2"></div>
                                        <input type="file" id="fileUploadInput" name="file_csv" class="form-control position-absolute top-0 start-0 w-100 h-100 opacity-0" accept=".csv" required style="cursor: pointer;">
                                    </div>
                                    </div>

                                    <div class="d-flex justify-content-end">
                                        <button type="submit" name="upload_csv" class="btn btn-lms-blue px-4 py-2 fw-bold">
                                            <i class="bi bi-upload me-2"></i> Upload & Simpan Data
                                        </button>
                                    </div>
                                </form>

                            </div>
                        </div>
                    </div>

                </div> </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sedikit script agar warna tab yang diklik ikut berubah menyesuaikan tema
        var tabEls = document.querySelectorAll('button[data-bs-toggle="pill"]');
        tabEls.forEach(function(tab) {
            tab.addEventListener('shown.bs.tab', function (event) {
                tabEls.forEach(t => {
                    t.classList.remove('bg-white', 'text-dark', 'active');
                    t.classList.add('bg-white', 'text-dark');
                });
                event.target.classList.remove('bg-white', 'text-dark');
                event.target.classList.add('active');
            })
        });
        // Script untuk memunculkan nama file yang dipilih saat upload CSV
         document.getElementById('fileUploadInput').addEventListener('change', function(event) {
        var namaFile = event.target.files[0].name;
        document.getElementById('namaFilePilihan').innerHTML = "<i class='bi bi-file-earmark-check me-2'></i>File siap diupload: " + namaFile;
    });

    // Deteksi tema aktif secara instan sebelum halaman selesai dirender (anti-silau)
    document.documentElement.setAttribute('data-bs-theme', localStorage.getItem('theme') || 'light');
    </script>
</body>
</html>