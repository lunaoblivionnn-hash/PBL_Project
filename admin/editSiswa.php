<?php
session_start();
if($_SESSION['role'] != 'admin'){
    header("Location: ../login/login.php");
    exit;
}

require '../login/koneksi.php';

// Cek apakah ada ID yang dikirim dari tombol edit
if (!isset($_GET['id'])) {
    header("Location: admin.php"); // Jika tidak ada, tendang balik
    exit;
}

$id_siswa = mysqli_real_escape_string($koneksi, $_GET['id']);

// Tambahkan USERS.Status di bagian SELECT agar datanya terbaca
$query = mysqli_query($koneksi, "SELECT SISWA.*, USERS.Password, USERS.WajibUbahPassword, USERS.Status 
                                 FROM SISWA 
                                 JOIN USERS ON SISWA.IDUser = USERS.IDUser 
                                 WHERE SISWA.IDSiswa = '$id_siswa'");
$data = mysqli_fetch_assoc($query);

// Jika ID palsu / data tidak ditemukan
if (!$data) {
    echo "<script>alert('Data siswa tidak ditemukan!'); window.location='admin.php';</script>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Siswa - Admin LMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../style.css">
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
                
                <h4 class="fw-bold mb-4">Edit Data Siswa</h4>

                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="mb-0 fw-bold text-primary">Form Perubahan Data</h6>
                    </div>
                    <div class="card-body p-4 p-md-5">
                        <form action="proses_edit_siswa.php" method="POST">
                            
                            <input type="hidden" name="id_siswa" value="<?= $data['IDSiswa'] ?>">
                            <input type="hidden" name="id_user" value="<?= $data['IDUser'] ?>">
                            <input type="hidden" name="nisn_lama" value="<?= $data['NISN'] ?>">

                            <h6 class="fw-bold text-muted mb-3">INFORMASI AKUN (LOGIN)</h6>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Username (NISN) <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="nisn" value="<?= $data['NISN'] ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Password <span class="text-muted fw-normal">(Opsional)</span></label>
                                    <input type="password" class="form-control" name="password" placeholder="Ketik sandi baru jika ingin diubah...">
                                    <div class="form-text text-warning">Biarkan kosong jika TIDAK INGIN mengganti password.</div>
                                </div>

                                <div class="col-12 mt-2">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="force_password_change" id="forcePass" value="1" <?= ($data['WajibUbahPassword'] == 1) ? 'checked' : '' ?>>
                                        <label class="form-check-label fw-bold text-danger" for="forcePass">
                                            Paksa perubahan kata sandi
                                        </label>
                                        <div class="form-text mt-0">Centang agar siswa diminta mengganti password saat login.</div>
                                    </div>
                                </div>

                                <div class="col-md-6 mt-3">
                                    <label class="form-label fw-semibold">Status Akun</label>
                                    <select class="form-select" name="status_akun">
                                        <option value="Aktif" <?= ($data['Status'] == 'Aktif') ? 'selected' : '' ?>>Aktif (Bisa Login)</option>
                                        <option value="Non-Aktif" <?= ($data['Status'] == 'Non-Aktif') ? 'selected' : '' ?>>Non-Aktif (Blokir Akses)</option>
                                    </select>
                                    <div class="form-text">Jika Non-Aktif, siswa tidak akan bisa masuk ke LMS.</div>
                                </div>
                            </div>

                            <hr class="mb-4">

                            <h6 class="fw-bold text-muted mb-3">PROFIL SISWA</h6>
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="nama_siswa" value="<?= $data['NamaSiswa'] ?>" required>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Kelas <span class="text-danger">*</span></label>
                                    <select class="form-select" name="kelas" required>
                                        <option value="X AKL 1" <?= ($data['Kelas'] == 'X AKL 1') ? 'selected' : '' ?>>X AKL 1</option>
                                        <option value="X AKL 2" <?= ($data['Kelas'] == 'X AKL 2') ? 'selected' : '' ?>>X AKL 2</option>
                                        <option value="XI AKL 1" <?= ($data['Kelas'] == 'XI AKL 1') ? 'selected' : '' ?>>XI AKL 1</option>
                                        <option value="XI AKL 2" <?= ($data['Kelas'] == 'XI AKL 2') ? 'selected' : '' ?>>XI AKL 2</option>
                                        <option value="XII AKL 1" <?= ($data['Kelas'] == 'XII AKL 1') ? 'selected' : '' ?>>XII AKL 1</option>
                                        <option value="XII AKL 2" <?= ($data['Kelas'] == 'XII AKL 2') ? 'selected' : '' ?>>XII AKL 2</option>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Nomor Telepon</label>
                                    <input type="text" class="form-control" name="no_telp" value="<?= $data['NoTelp'] ?>">
                                </div>

                                <div class="col-12">
                                    <label class="form-label fw-semibold">Email Siswa</label>
                                    <input type="email" class="form-control" name="email" value="<?= $data['Email'] ?>">
                                </div>
                            </div>

                            <div class="d-flex justify-content-end mt-5 gap-2">
                                <a href="admin.php" class="btn btn-light border px-4">Batal</a>
                                <button type="submit" name="simpan_perubahan" class="btn btn-primary px-5 fw-bold"><i class="bi bi-save me-2"></i> Update Data</button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>
</body>
</html>