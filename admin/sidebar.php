<style>
    /* ================= CSS KHUSUS SIDEBAR ================= */
    .sidebar {
        min-height: calc(100vh - 60px);
        box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
        background-color: #fff;
    }
    .sidebar-link {
        display: block;
        padding: 10px 15px;
        color: #495057;
        text-decoration: none;
        border-radius: 8px;
        margin-bottom: 5px;
        transition: all 0.3s ease;
        font-weight: 500;
    }
    .sidebar-link:hover {
        background-color: #f8f9fa;
        color: #dc3545;
        transform: translateX(6px); 
    }
    .sidebar-link.active {
        background-color: #dc3545 !important;
        color: white !important;
        box-shadow: 0 4px 10px rgba(220,53,69,0.2);
    }
    .sidebar-link.active:hover {
        transform: none; 
    }
    .menu-title {
        font-size: 0.75rem;
        font-weight: 700;
        color: #adb5bd;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin: 25px 0 10px 10px;
    }

    /* ================= GLOBAL DARK MODE OVERRIDES ================= */
    /* Kode ini akan memaksa semua warna putih/terang menjadi gelap saat Dark Mode aktif */
    [data-bs-theme="dark"] body {
        background-color: #121212 !important;
        color: #e0e0e0 !important;
    }
    [data-bs-theme="dark"] .bg-light,
    [data-bs-theme="dark"] .bg-white {
        background-color: #1e1e1e !important;
        color: #e0e0e0 !important;
    }
    [data-bs-theme="dark"] .text-dark {
        color: #e0e0e0 !important;
    }
    [data-bs-theme="dark"] .card,
    [data-bs-theme="dark"] .card-header,
    [data-bs-theme="dark"] .card-footer {
        background-color: #1e1e1e !important;
        border-color: #333 !important;
    }
    [data-bs-theme="dark"] .sidebar {
        background-color: #1e1e1e !important;
        border-right: 1px solid #333 !important;
    }
    [data-bs-theme="dark"] .sidebar-link {
        color: #adb5bd !important;
    }
    [data-bs-theme="dark"] .sidebar-link:hover {
        background-color: #2b2b2b !important;
        color: #ff6b6b !important;
    }
    [data-bs-theme="dark"] .table {
        color: #e0e0e0 !important;
    }
    [data-bs-theme="dark"] .table-light th,
    [data-bs-theme="dark"] .table thead th {
        background-color: #2b2b2b !important;
        color: #e0e0e0 !important;
        border-color: #444 !important;
    }
    [data-bs-theme="dark"] .border,
    [data-bs-theme="dark"] .border-end,
    [data-bs-theme="dark"] .border-bottom {
        border-color: #333 !important;
    }
    [data-bs-theme="dark"] .form-control,
    [data-bs-theme="dark"] .form-select {
        background-color: #2b2b2b !important;
        border-color: #444 !important;
        color: #e0e0e0 !important;
    }
    [data-bs-theme="dark"] .dropdown-menu {
        background-color: #1e1e1e !important;
        border-color: #444 !important;
    }
    [data-bs-theme="dark"] .dropdown-item {
        color: #e0e0e0 !important;
    }
    [data-bs-theme="dark"] .dropdown-item:hover {
        background-color: #2b2b2b !important;
    }
</style>

<?php 
    // Deteksi cerdas: Sedang buka halaman apa sekarang?
    $current_page = basename($_SERVER['PHP_SELF']); 
?>

<nav class="col-md-3 col-lg-2 d-none d-md-block bg-white sidebar py-3 border-end">
    
    <div class="menu-title mt-2">MENU UTAMA</div>
    <a href="admin.php" class="sidebar-link <?= ($current_page == 'admin.php') ? 'active' : '' ?>">
        <i class="bi bi-speedometer2 me-2"></i> Dashboard
    </a>
    
    <div class="menu-title">KELOLA DATA (MASTER)</div>
    <a href="daftarSiswa.php" class="sidebar-link <?= in_array($current_page, ['daftarSiswa.php', 'tambahSiswa.php', 'editSiswa.php']) ? 'active' : '' ?>">
        <i class="bi bi-people me-2"></i> Data Siswa
    </a>
    <a href="daftarGuru.php" class="sidebar-link <?= in_array($current_page, ['daftarGuru.php', 'tambahGuru.php', 'editGuru.php']) ? 'active' : '' ?>">
        <i class="bi bi-person-badge me-2"></i> Data Guru
    </a>
    <a href="mataPelajaran.php" class="sidebar-link <?= in_array($current_page, ['mataPelajaran.php', 'detailKelas.php', 'tambahMapel.php', 'editMapel.php']) ? 'active' : '' ?>">
        <i class="bi bi-book me-2"></i> Mata Pelajaran
    </a>
    
    <div class="menu-title">PENGATURAN</div>
    <a href="aturanGamifikasi.php" class="sidebar-link <?= ($current_page == 'aturanGamifikasi.php') ? 'active' : '' ?>">
        <i class="bi bi-award me-2"></i> Aturan Gamifikasi
    </a>
    <a href="pengaturanSistem.php" class="sidebar-link <?= ($current_page == 'pengaturanSistem.php') ? 'active' : '' ?>">
        <i class="bi bi-gear me-2"></i> Pengaturan Sistem
    </a>

</nav>

<script>
    // 1. Setel tema sebelum konten lain selesai dimuat agar tidak silau (FOUC)
    const htmlElement = document.documentElement;
    const currentTheme = localStorage.getItem('theme') || 'light';
    htmlElement.setAttribute('data-bs-theme', currentTheme);

    // 2. Trik Injeksi JS: Menyuntikkan tombol ke Navbar secara otomatis
    document.addEventListener("DOMContentLoaded", function() {
        const navList = document.querySelector('.navbar-nav');
        
        // Cek jika navbar ada dan tombol belum pernah disuntik
        if(navList && !document.getElementById('btnDarkMode')) {
            const li = document.createElement('li');
            li.className = 'nav-item me-2 d-flex align-items-center';
            
            // Buat tombol bulan/matahari
            const iconClass = currentTheme === 'dark' ? 'bi-sun-fill' : 'bi-moon-fill';
            li.innerHTML = `
                <button class="btn btn-sm btn-outline-light border-0" id="btnDarkMode" title="Ganti Tema Mode">
                    <i class="bi ${iconClass} fs-5"></i>
                </button>
            `;
            
            // Masukkan tombol ke posisi paling kiri di area profil
            navList.insertBefore(li, navList.firstChild);
        }

        // 3. Logika Klik Tombol
        const btnDarkMode = document.getElementById('btnDarkMode');
        if(btnDarkMode) {
            btnDarkMode.addEventListener('click', () => {
                const isDark = htmlElement.getAttribute('data-bs-theme') === 'dark';
                const newTheme = isDark ? 'light' : 'dark';
                
                htmlElement.setAttribute('data-bs-theme', newTheme);
                localStorage.setItem('theme', newTheme);
                
                // Ganti ikon
                btnDarkMode.innerHTML = `<i class="bi bi-${newTheme === 'dark' ? 'sun' : 'moon'}-fill fs-5"></i>`;
            });
        }
    });
</script>