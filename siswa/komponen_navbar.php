<?php $current_page = basename($_SERVER['PHP_SELF']); ?>
<nav class="navbar navbar-expand-lg navbar-dark navbar-custom sticky-top">
    <div class="container-fluid px-4">
        <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="siswa.php">
            <i class="bi bi-mortarboard-fill fs-4 text-warning"></i>
            <span class="tracking-wide text-white">LMS Akuntansi dan Keuangan Lembaga</span>
        </a>
        
        <button class="navbar-toggler border-0 shadow-none text-white" type="button" data-bs-toggle="collapse" data-bs-target="#mobileMenu">
            <i class="bi bi-list fs-1"></i>
        </button>

        <div class="collapse navbar-collapse justify-content-end" id="mobileMenu">
            <ul class="navbar-nav d-lg-none mb-3 mt-2 border-top border-secondary pt-3">
                <li class="nav-item"><a class="nav-link <?= ($current_page == 'siswa.php') ? 'active fw-bold' : '' ?> text-white" href="siswa.php"><i class="bi bi-house-door me-2"></i>Dashboard</a></li>
                <li class="nav-item"><a class="nav-link <?= ($current_page == 'jadwal.php') ? 'active fw-bold' : '' ?> text-white-50" href="jadwal.php"><i class="bi bi-calendar-event me-2"></i>Jadwal Pelajaran</a></li>
                <li class="nav-item"><a class="nav-link <?= ($current_page == 'gamifikasi.php') ? 'active fw-bold' : '' ?> text-white-50" href="gamifikasi.php"><i class="bi bi-trophy me-2"></i>Pusat Gamifikasi</a></li>
                <li class="nav-item mt-3"><a class="nav-link text-danger fw-bold bg-white rounded text-center py-2" href="../login/logout.php">Keluar Akun</a></li>
            </ul>

            <div class="d-none d-lg-flex align-items-center gap-3">
                <div class="text-end text-white">
                    <h6 class="mb-0 fw-bold small text-nowrap" style="font-size: 1.05rem"><?= htmlspecialchars($nama_lengkap ?? 'Siswa') ?></h6>
                    <span class="badge rounded-pill mt-1 border border-white border-opacity-25" style="background: rgba(255, 255, 255, 0.15); color: #ffffff;"><i class="bi bi-building me-1 text-warning"></i><?= htmlspecialchars($kelas_siswa ?? 'Kelas') ?></span>
                </div>
                <div class="dropdown">
                    <a href="#" class="d-block" data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="https://ui-avatars.com/api/?name=<?= urlencode($nama_lengkap ?? 'Siswa') ?>&background=e0e7ff&color=1e1b4b" class="rounded-circle border border-2 border-white shadow" style="width: 45px; height: 45px;">
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg rounded-4 mt-3 p-2">
                        <li><a class="dropdown-item rounded-3 py-2 text-danger fw-bold" href="../login/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Keluar Sistem</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</nav>