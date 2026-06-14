<?php
// 1. Mulai sesi untuk mendeteksi sesi yang sedang berjalan
session_start();

// 2. Kosongkan semua variabel sesi
session_unset();

// 3. Hancurkan sesi secara total
session_destroy();

// 4. Arahkan / tendang paksa kembali ke halaman login
header("Location: ../login/login.php");
exit;
?>