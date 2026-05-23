<?php
session_start();
require '../login/koneksi.php';

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'admin' || !isset($_GET['id'])){
    header("Location: ../login/login.php");
    exit;
}

$id_guru = mysqli_real_escape_string($koneksi, $_GET['id']);

// Ambil data guru dan username loginnya secara join langsung dari database
$query = mysqli_query($koneksi, "SELECT g.*, u.Username FROM guru g JOIN users u ON g.IDUser = u.IDUser WHERE g.IDGuru = '$id_guru'");
$data = mysqli_fetch_assoc($query);

if(!$data) {
    header("Location: daftarGuru.php");
    exit;
}

// BACA DATA JSON MAPEL GURU SAAT INI UNTUK AUTO-CHECKBOX
$mapel_guru_terpilih = json_decode($data['MataPelajaran'], true);
if(!is_array($mapel_guru_terpilih)) {
    $mapel_guru_terpilih = [];
}

// Proses Eksekusi Update Data
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nip       = mysqli_real_escape_string($koneksi, $_POST['nip_guru']);
    $nama      = mysqli_real_escape_string($koneksi, $_POST['nama_guru']);
    $email     = mysqli_real_escape_string($koneksi, $_POST['email']);
    $no_telp   = mysqli_real_escape_string($koneksi, $_POST['no_telp']);
    $pass_baru = mysqli_real_escape_string($koneksi, $_POST['password_baru']);
    $id_user   = $data['IDUser'];
    
    // Proses Array Mapel menjadi Format JSON
    $mapel_diampu = [];
    if(isset($_POST['kelas_diampu'])) {
        foreach($_POST['kelas_diampu'] as $kelas) {
            // Ambil array mapel hanya untuk kelas yang dicentang
            if(isset($_POST['mapel_diampu'][$kelas])) {
                $mapel_diampu[$kelas] = $_POST['mapel_diampu'][$kelas];
            }
        }
    }
    $json_mapel = mysqli_real_escape_string($koneksi, json_encode($mapel_diampu));

    // 1. Update profil beserta pembaruan Hak Akses Mapel (JSON)
    $update_guru = mysqli_query($koneksi, "UPDATE guru SET NamaGuru = '$nama', NIP_NUPTK = '$nip', Email = '$email', NoTelp = '$no_telp', MataPelajaran = '$json_mapel' WHERE IDGuru = '$id_guru'");
    
    if($update_guru) {
        // 2. Sinkronisasi NIP ke Username
        mysqli_query($koneksi, "UPDATE users SET Username = '$nip' WHERE IDUser = '$id_user'");

        // 3. Update password plain text jika form diisi
        if(!empty($pass_baru)) {
            mysqli_query($koneksi, "UPDATE users SET Password = '$pass_baru' WHERE IDUser = '$id_user'");
        }

        header("Location: daftarGuru.php?status=sukses_edit");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Data Guru - Admin LMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; }
        .mapel-box { transition: all 0.3s ease-in-out; }

        [data-bs-theme="dark"] body { background-color: #121212 !important; color: #e0e0e0 !important; }
        [data-bs-theme="dark"] .card, [data-bs-theme="dark"] .card-header, [data-bs-theme="dark"] .bg-light { background-color: #1e1e1e !important; border-color: #333 !important; color: #e0e0e0 !important; }
        [data-bs-theme="dark"] .form-control { background-color: #2b2b2b !important; border-color: #444 !important; color: #e0e0e0 !important; }
        [data-bs-theme="dark"] .btn-light { background-color: #343a40 !important; color: #fff !important; border: 1px solid #444; }
    </style>
    <script>document.documentElement.setAttribute('data-bs-theme', localStorage.getItem('theme') || 'light');</script>
</head>
<body class="bg-light py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="card-header bg-danger text-white p-4 d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0 fw-bold"><i class="bi bi-person-gear me-2"></i> Edit Profil Guru Pengampu</h5>
                        <span class="badge bg-white text-danger"><?= htmlspecialchars($id_guru) ?></span>
                    </div>
                    <div class="card-body p-4 p-md-5">
                        <form action="" method="POST">
                            
                            <h6 class="fw-bold text-muted mb-3 pb-2 border-bottom">IDENTITAS GURU</h6>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-secondary">NIP (Username Login)</label>
                                    <input type="text" name="nip_guru" class="form-control fw-semibold text-primary" value="<?= isset($data['NIP_NUPTK']) ? htmlspecialchars($data['NIP_NUPTK']) : '' ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-secondary">Nama Lengkap</label>
                                    <input type="text" name="nama_guru" class="form-control" value="<?= htmlspecialchars($data['NamaGuru']) ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-secondary">Alamat Email</label>
                                    <input type="email" name="email" class="form-control" value="<?= isset($data['Email']) ? htmlspecialchars($data['Email']) : '' ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-secondary">No. Telepon / WhatsApp</label>
                                    <input type="text" name="no_telp" class="form-control" value="<?= isset($data['NoTelp']) ? htmlspecialchars($data['NoTelp']) : '' ?>">
                                </div>
                            </div>

                            <h6 class="fw-bold text-muted mb-3 pb-2 border-bottom">HAK AKSES KELAS & MATA PELAJARAN</h6>
                            <div class="p-4 bg-light border rounded-3 mb-4">
                                <div class="row g-3 align-items-start">
                                <?php
                                    // 1. Siapkan daftar kelas paten
                                    $kelas_list = ['X AKL 1', 'X AKL 2', 'XI AKL 1', 'XI AKL 2', 'XII AKL 1', 'XII AKL 2'];
                                    $ada_mapel_aktif = false;

                                    foreach($kelas_list as $kelas): 
                                        $id_safe = preg_replace('/[^a-zA-Z0-9]/', '', $kelas); 
                                        
                                        // 2. LOGIKA AUTO-CHECK JIKA GURU INI SUDAH MENGAMPU KELAS INI
                                        $is_kelas_checked = isset($mapel_guru_terpilih[$kelas]) ? 'checked' : '';
                                        $box_display = isset($mapel_guru_terpilih[$kelas]) ? '' : 'd-none';

                                        // 3. CARI MAPEL YANG MENGANDUNG KELAS INI (Gunakan LIKE karena formatnya JSON)
                                        $q_mapel_db = mysqli_query($koneksi, "SELECT DISTINCT NamaMapel FROM mapel WHERE Kelas LIKE '%\"$kelas\"%' ORDER BY NamaMapel ASC");

                                        // 4. HANYA TAMPILKAN KOTAK KELAS JIKA ADA MAPEL DI DALAMNYA
                                        if(mysqli_num_rows($q_mapel_db) > 0):
                                            $ada_mapel_aktif = true;
                                    ?>
                                    <div class="col-md-6">
                                        <div class="card border border-secondary-subtle shadow-sm h-100">
                                            <div class="card-body p-3">
                                                <div class="form-check form-switch border-bottom pb-2 mb-2">
                                                    <input class="form-check-input switch-kelas" type="checkbox" name="kelas_diampu[]" value="<?= $kelas ?>" id="switch_<?= $id_safe ?>" <?= $is_kelas_checked ?> onchange="toggleMapel('<?= $id_safe ?>')">
                                                    <label class="form-check-label fw-bold text-danger ms-1" style="cursor:pointer;" for="switch_<?= $id_safe ?>"><?= $kelas ?></label>
                                                </div>

                                                <div class="mapel-box <?= $box_display ?>" id="box_<?= $id_safe ?>">
                                                    <div class="row g-2">
                                                        <?php 
                                                        $m_index = 0;
                                                        while($row_m = mysqli_fetch_assoc($q_mapel_db)): 
                                                            $mapel = $row_m['NamaMapel'];
                                                            // LOGIKA AUTO-CHECK JIKA MAPEL INI ADA DI DALAM ARRAY KELAS TERSEBUT
                                                            $is_mapel_checked = (isset($mapel_guru_terpilih[$kelas]) && in_array($mapel, $mapel_guru_terpilih[$kelas])) ? 'checked' : '';
                                                        ?>
                                                        <div class="col-12">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" name="mapel_diampu[<?= $kelas ?>][]" value="<?= htmlspecialchars($mapel) ?>" id="mapel_<?= $id_safe ?>_<?= $m_index ?>" <?= $is_mapel_checked ?>>
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
                                        <div class="col-12"><div class="alert alert-warning small mb-0">Belum ada mata pelajaran yang didaftarkan ke kelas manapun.</div></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <h6 class="fw-bold text-muted mb-3 pb-2 border-bottom">KEAMANAN AKUN</h6>
                            <div class="p-3 bg-warning bg-opacity-10 rounded-3 border border-warning-subtle mb-4">
                                <label class="form-label small fw-bold text-dark mb-2"><i class="bi bi-shield-lock-fill me-1 text-warning"></i> Reset Password (Opsional)</label>
                                <input type="text" name="password_baru" class="form-control bg-white" placeholder="Ketik kata sandi baru untuk mereset...">
                            </div>

                            <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                                <a href="daftarGuru.php" class="btn btn-light px-4 border border-secondary-subtle">Batal</a>
                                <button type="submit" class="btn btn-danger px-4 fw-bold shadow-sm">
                                    <i class="bi bi-check-circle-fill me-1"></i> Simpan Perubahan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Fungsi memunculkan mapel saat switch kelas dicentang
        function toggleMapel(id) {
            const box = document.getElementById('box_' + id);
            const switchBtn = document.getElementById('switch_' + id);
            
            if(switchBtn.checked) {
                box.classList.remove('d-none');
            } else {
                box.classList.add('d-none');
                // Hapus centang mapel di dalamnya
                const checkboxes = box.querySelectorAll('input[type="checkbox"]');
                checkboxes.forEach(cb => cb.checked = false);
            }
        }
    </script>
</body>
</html>