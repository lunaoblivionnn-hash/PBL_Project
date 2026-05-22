<?php
// 1. Mulai sesi untuk mengingat siapa yang sedang login
session_start();

// 2. Panggil kabel koneksi
include 'koneksi.php';

// 3. Jika tombol login ditekan
if (isset($_POST['login'])) {
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $password = mysqli_real_escape_string($koneksi, $_POST['password']);

    // Cari user di database yang username dan passwordnya cocok
    $query = "SELECT * FROM users WHERE username='$username' AND password='$password'";
    $cek_user = mysqli_query($koneksi, $query);
    
    if (mysqli_num_rows($cek_user) > 0) {
        $data = mysqli_fetch_assoc($cek_user); 

        // =========================================================
        // TAHAP 1: CEK SAKELAR MODE MAINTENANCE GLOBAL DARI DATABASE
        // =========================================================
        $cek_maint = mysqli_query($koneksi, "SELECT Nilai FROM pengaturan WHERE Kunci = 'maintenance'");
        $maint = mysqli_fetch_assoc($cek_maint);
        $status_maint = isset($maint['Nilai']) ? $maint['Nilai'] : '0';

        // Jika maintenance aktif dan yang login BUKAN admin, kunci pintu masuk!
        if ($status_maint == '1' && $data['Role'] != 'admin') {
            $error = "⛔ Sistem sedang dalam perbaikan berkala (Maintenance). Akses masuk untuk Guru & Siswa ditutup sementara waktu.";
        } 
        // =========================================================
        // TAHAP 2: CEK STATUS AKUN MANUAL (AKTIF / BLOKIR)
        // =========================================================
        else if ($data['Status'] == 'Non-Aktif') {
            $error = "Maaf, akun Anda sedang dinonaktifkan. Silakan hubungi admin.";
        } else {
            // =========================================================
            // TAHAP 3: BERSIHKAN MEMORI LAMA & SIMPAN DATA BARU
            // =========================================================
            session_unset(); 
            
            $_SESSION['IDUser']   = $data['IDUser']; 
            $_SESSION['username'] = $data['Username'];
            $_SESSION['role']     = $data['Role'];

            // =========================================================
            // TAHAP 4: ARAHKAN SESUAI JABATAN (ROLE)
            // =========================================================
            if ($data['Role'] == 'admin') {
                header("Location: ../admin/admin.php");
                exit;
            } else if ($data['Role'] == 'guru') {
                header("Location: ../guru/guru.php");
                exit;
            } else if ($data['Role'] == 'siswa') {
                header("Location: ../siswa/siswa.php");
                exit;
            }
        }
    } else {
        $error = "Username atau Password salah!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - LMS SMKN 1 Wongsorejo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f4f7f6; height: 100vh; display: flex; align-items: center; justify-content: center; }
        .login-card { width: 100%; max-width: 400px; padding: 30px; border: none; border-radius: 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); background: white; }
    </style>
</head>
<body>

    <div class="login-card">
        <div class="text-center mb-4">
            <h4 class="fw-bold text-danger">LMS SMKN 1 Wongsorejo</h4>
            <p class="text-muted small">Silakan masuk ke akun Anda</p>
        </div>

        <?php if(isset($error)): ?>
            <div class="alert alert-danger py-2.5 small text-center fw-semibold"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label small fw-bold">Username Login</label>
                <input type="text" name="username" class="form-control" placeholder="Masukkan Username" required>
            </div>
            <div class="mb-4">
                <label class="form-label small fw-bold">Password</label>
                <input type="password" name="password" class="form-control" placeholder="Masukkan Password" required>
            </div>
            <button type="submit" name="login" class="btn btn-danger w-100 fw-bold py-2 shadow-sm">Masuk ke Sistem</button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bundle.min.js"></script>
</body>
</html>