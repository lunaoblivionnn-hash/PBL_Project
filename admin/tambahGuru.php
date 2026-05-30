<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Guru - Admin LMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../style.css">
    <style>
        .btn-lms-blue { background-color: #0f6cb6; color: white; border: none; }
        .btn-lms-blue:hover { background-color: #0e5b9c; color: white; }
        .dropzone-box { border: 2px dashed #ced4da; background-color: #ffffff; border-radius: 8px; transition: all 0.3s ease; }
        .dropzone-box:hover { border-color: #0f6cb6; background-color: #f1f7fc; }
        .nav-pills .nav-link.active { background-color: #dc3545; }
        .mapel-box { transition: all 0.3s ease-in-out; }
        
        [data-bs-theme="dark"] body { background-color: #121212 !important; color: #e0e0e0 !important; }
        [data-bs-theme="dark"] .bg-light, [data-bs-theme="dark"] .bg-white, [data-bs-theme="dark"] .card, [data-bs-theme="dark"] .card-header, [data-bs-theme="dark"] .card-body {
            background-color: #1e1e1e !important; border-color: #333 !important; color: #e0e0e0 !important; box-shadow: 0 10px 30px rgba(0,0,0,0.5) !important;
        }
        [data-bs-theme="dark"] .form-control, [data-bs-theme="dark"] .form-select, [data-bs-theme="dark"] .input-group-text { background-color: #2b2b2b !important; border-color: #444 !important; color: #e0e0e0 !important; }
        [data-bs-theme="dark"] .dropzone-box { background-color: #2b2b2b !important; border: 2px dashed #444 !important; color: #e0e0e0 !important; }
        [data-bs-theme="dark"] .custom-checkbox-container, [data-bs-theme="dark"] .border-dashed { background-color: #2b2b2b !important; border-color: #444 !important; }
        [data-bs-theme="dark"] .btn-light { background-color: #343a40 !important; border-color: #444 !important; color: #fff !important; }
    </style>
</head>
<body class="bg-light">

    <?php
    session_start();
    require '../login/koneksi.php';

    if($_SESSION['role'] != 'admin'){
        header("Location: ../login/login.php");
        exit;
    }
    ?>

    <nav class="navbar navbar-dark bg-danger shadow-sm">
        <div class="container-fluid px-4">
            <a class="navbar-brand fw-bold" href="daftarGuru.php">
                <i class="bi bi-arrow-left-circle me-2"></i> Kembali ke Daftar Guru
            </a>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-9"> <h4 class="fw-bold mb-4">Registrasi Guru Baru</h4>

                <ul class="nav nav-pills mb-4 gap-2" id="guruTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-semibold shadow-sm border border-light" id="manual-tab" data-bs-toggle="pill" data-bs-target="#manual" type="button" role="tab"><i class="bi bi-keyboard me-2"></i> Input Manual</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-semibold shadow-sm border border-light text-dark bg-white" id="csv-tab" data-bs-toggle="pill" data-bs-target="#csv" type="button" role="tab"><i class="bi bi-file-earmark-spreadsheet me-2 text-success"></i> Upload Massal (CSV)</button>
                    </li>
                </ul>

                <div class="tab-content" id="guruTabContent">
                    
                    <div class="tab-pane fade show active" id="manual" role="tabpanel">
                        <div class="card border-0 shadow-sm rounded-3">
                            <div class="card-header bg-white border-bottom py-3">
                                <h6 class="mb-0 fw-bold text-danger">Form Input Data Guru</h6>
                            </div>
                            <div class="card-body p-4 p-md-5">
                                <form action="proses_tambah_guru.php" method="POST">
                                    
                                    <h6 class="fw-bold text-muted mb-3">INFORMASI AKUN (LOGIN)</h6>
                                    <div class="row g-3 mb-4">
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Username (NIP) <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="nip" placeholder="Contoh: 19880123..." autocomplete="off" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Password <span class="text-muted fw-normal">(Opsional)</span></label>
                                            <input type="password" class="form-control" name="password" placeholder="Kosongkan untuk default 'guru123'" autocomplete="new-password">
                                        </div>
                                        <div class="col-12 mt-2">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="force_password_change" id="forcePass" value="1" checked>
                                                <label class="form-check-label fw-bold text-danger" for="forcePass">Paksa perubahan kata sandi</label>
                                                <div class="form-text mt-0">Guru akan diminta mengganti password saat pertama kali masuk.</div>
                                            </div>
                                        </div>
                                    </div>

                                    <hr class="mb-4">

                                    <h6 class="fw-bold text-muted mb-3">PROFIL GURU</h6>
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label fw-semibold">ID Guru <span class="text-muted fw-normal">(Opsional)</span></label>
                                            <input type="text" class="form-control bg-light" name="id_guru" placeholder="Auto Generate">
                                        </div>
                                        <div class="col-md-8">
                                            <label class="form-label fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="nama_guru" placeholder="Contoh: Budi Santoso" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Nomor Telepon / WhatsApp</label>
                                            <input type="text" class="form-control" name="no_telp" placeholder="Contoh: 081234567890">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Email Guru</label>
                                            <input type="email" class="form-control" name="email" placeholder="Contoh: guru@smkn1wongsorejo.sch.id">
                                        </div>

                                        <div class="col-12 mt-4">
                                            <label class="form-label fw-semibold">Kelas & Mata Pelajaran yang Diampu <span class="text-danger">*</span></label>
                                            <div class="p-4 bg-light border rounded-3">
                                                
                                                <div class="row g-3 align-items-start">
                                                    <?php
                                                    // 1. Siapkan daftar kelas paten
                                                    $kelas_list = ['X AKL 1', 'X AKL 2', 'XI AKL 1', 'XI AKL 2', 'XII AKL 1', 'XII AKL 2'];
                                                    $ada_mapel_aktif = false;

                                                    foreach($kelas_list as $kelas): 
                                                        $id_safe = preg_replace('/[^a-zA-Z0-9]/', '', $kelas); 

                                                        // CARI MAPEL YANG MENGANDUNG KELAS INI (Gunakan LIKE karena formatnya JSON)
                                                        $q_mapel_db = mysqli_query($koneksi, "SELECT DISTINCT NamaMapel FROM mapel WHERE Kelas LIKE '%\"$kelas\"%' ORDER BY NamaMapel ASC");

                                                        // HANYA TAMPILKAN KOTAK KELAS JIKA ADA MAPEL DI DALAMNYA
                                                        if(mysqli_num_rows($q_mapel_db) > 0):
                                                            $ada_mapel_aktif = true;
                                                    ?>
                                                    <div class="col-md-6">
                                                        <div class="card border border-secondary-subtle shadow-sm h-100">
                                                            <div class="card-body p-3">
                                                                <div class="form-check form-switch border-bottom pb-2 mb-2">
                                                                    <input class="form-check-input switch-kelas" type="checkbox" name="kelas_diampu[]" value="<?= $kelas ?>" id="switch_<?= $id_safe ?>" onchange="toggleMapel('<?= $id_safe ?>')">
                                                                    <label class="form-check-label fw-bold text-danger ms-1" style="cursor:pointer;" for="switch_<?= $id_safe ?>"><?= $kelas ?></label> 
                                                                </div>

                                                                <div class="mapel-box d-none" id="box_<?= $id_safe ?>">
                                                                    <p class="small text-muted mb-2" style="font-size:0.75rem;">Pilih Mapel untuk kelas ini:</p>
                                                                    <div class="row g-2">
                                                                        <?php 
                                                                        $m_index = 0;
                                                                        while($row_m = mysqli_fetch_assoc($q_mapel_db)): 
                                                                            $mapel = $row_m['NamaMapel'];
                                                                        ?>
                                                                        <div class="col-12">
                                                                            <div class="form-check">
                                                                                <input class="form-check-input" type="checkbox" name="mapel_diampu[<?= $kelas ?>][]" value="<?= htmlspecialchars($mapel) ?>" id="mapel_<?= $id_safe ?>_<?= $m_index ?>">
                                                                                <label class="form-check-label small text-dark" style="cursor:pointer; font-size:0.8rem;" for="mapel_<?= $id_safe ?>_<?= $m_index ?>"><?= htmlspecialchars($mapel) ?></label>
                                                                            </div>
                                                                        </div>
                                                                        <?php 
                                                                        $m_index++;
                                                                        endwhile; 
                                                                        ?>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <?php 
                                                        endif;
                                                    endforeach; 

                                                    // Jika tidak ada satu pun mapel di database yang terhubung ke kelas
                                                    if(!$ada_mapel_aktif):
                                                    ?>
                                                        <div class="col-12">
                                                            <div class="alert alert-warning small mb-0"><i class="bi bi-info-circle me-2"></i>Belum ada mata pelajaran yang didaftarkan ke kelas manapun.</div>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-end mt-5 gap-2">
                                        <a href="daftarGuru.php" class="btn btn-light border px-4">Batal</a>
                                        <button type="submit" class="btn btn-primary px-5 fw-bold"><i class="bi bi-save me-2"></i> Simpan Data Guru</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="csv" role="tabpanel">
                        <div class="card border-0 shadow-sm rounded-3">
                            <div class="card-header bg-white border-bottom py-3">
                                <h6 class="mb-0 fw-bold text-danger">Upload Massal Guru via CSV</h6>
                            </div>
                            <div class="card-body p-4 p-md-5">
                                
                                <!-- PAPAN INFORMASI / PERINGATAN UPLOAD MASSAL GURU -->
                                <div class="alert alert-warning border-warning border-opacity-50 shadow-sm mb-4" role="alert" style="background-color: #fffbeb;">
                                    <div class="d-flex">
                                        <i class="bi bi-exclamation-triangle-fill fs-2 text-warning me-3 mt-1"></i>
                                        <div>
                                            <h6 class="fw-bold mb-2 text-dark">Penting! Panduan & Aturan Upload CSV Guru:</h6>
                                            <ul class="mb-0 small text-dark" style="padding-left: 1.2rem; line-height: 1.6;">
                                                <li><strong>Format File Wajib .CSV:</strong> Saat menyimpan di Excel (Save As), pastikan memilih format <b>CSV (Comma delimited)</b>. Ekstensi <i>.xlsx</i> akan ditolak.</li>
                                                <li><strong>Kolom ke-4 (kelas - mapel) Bersifat Opsional:</strong> Boleh dikosongkan jika guru belum memiliki jadwal. Jika ingin diisi, formatnya: <code>Kelas - Mata Pelajaran</code>. Pisahkan dengan koma jika lebih dari satu (Contoh: <i>X AKL 1 - Matematika, XI AKL 2 - Bahasa Inggris</i>), mapel tersebut harus sudah dibuat dan ada di sistem ini.</li>
                                                <li><strong>Hindari Username/NIP Ganda:</strong> Pastikan NIP yang ingin di-upload belum pernah didaftarkan ke dalam sistem sebelumnya.</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label fw-bold text-secondary small">File Template CSV</label>
                                    <div class="p-3 bg-light rounded border border-dashed">
                                        <a href="contoh_format_upload_guru.csv" class="text-decoration-none fw-semibold" style="color: #0f6cb6;" download>
                                            <i class="bi bi-download me-2"></i> Download format_guru.csv
                                        </a>
                                        <div class="small text-muted mt-2">
                                            Gunakan file ini sebagai format dasar. Perhatikan susunan 6 kolomnya (NIP, Password, Nama, Mapel, Email, Telp).
                                        </div>
                                    </div>
                                </div>

                                <form action="proses_upload_guru.php" method="POST" enctype="multipart/form-data">
                                    <div class="mb-4">
                                        <label class="form-label fw-bold text-secondary small">Upload File <span class="text-danger">*</span></label>
                                        <div class="dropzone-box text-center p-5 position-relative">
                                            <i class="bi bi-cloud-arrow-up text-muted" style="font-size: 3.5rem;"></i>
                                            <p class="mt-3 mb-2 fw-semibold text-secondary">Pilih atau letakkan file CSV Anda di sini</p>
                                            <p class="text-muted small mb-3">Maksimal 10 MB. Berekstensi .csv</p>
                                            <div id="namaFilePilihan" class="text-success fw-bold mt-2"></div>
                                            <input type="file" id="fileUploadInput" name="file_csv" class="form-control position-absolute top-0 start-0 w-100 h-100 opacity-0" accept=".csv" required style="cursor: pointer;">
                                        </div>
                                    </div>

                                    <div class="mb-4 bg-light p-3 rounded border">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="force_password_change_csv" id="forcePassCSV" value="1" checked>
                                            <label class="form-check-label fw-bold text-danger" for="forcePassCSV">
                                                Paksa perubahan kata sandi untuk semua guru
                                            </label>
                                            <div class="form-text mt-0">Seluruh guru di dalam file CSV ini akan diminta mengganti password saat pertama kali login.</div>
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-end">
                                        <button type="submit" name="upload_csv" class="btn btn-primary px-4 py-2 fw-bold">
                                            <i class="bi bi-upload me-2"></i> Upload Data
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div> 
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleMapel(id) {
            const box = document.getElementById('box_' + id);
            const switchBtn = document.getElementById('switch_' + id);
            
            if(switchBtn.checked) {
                box.classList.remove('d-none');
            } else {
                box.classList.add('d-none');
                const checkboxes = box.querySelectorAll('input[type="checkbox"]');
                checkboxes.forEach(cb => cb.checked = false);
            }
        }

        var tabEls = document.querySelectorAll('button[data-bs-toggle="pill"]');
        tabEls.forEach(function(tab) {
            tab.addEventListener('shown.bs.tab', function (event) {
                tabEls.forEach(t => { t.classList.remove('bg-white', 'text-dark', 'active'); t.classList.add('bg-white', 'text-dark'); });
                event.target.classList.remove('bg-white', 'text-dark');
                event.target.classList.add('active');
            })
        });

        document.getElementById('fileUploadInput').addEventListener('change', function(event) {
            var file = event.target.files[0];
            if(file) document.getElementById('namaFilePilihan').innerHTML = "<i class='bi bi-file-earmark-check-fill me-2'></i> File siap diupload: " + file.name;
            else document.getElementById('namaFilePilihan').innerHTML = "";
        });

        document.documentElement.setAttribute('data-bs-theme', localStorage.getItem('theme') || 'light');
    </script>
</body>
</html>