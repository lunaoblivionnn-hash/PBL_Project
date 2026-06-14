<?php $current_page = basename($_SERVER['PHP_SELF']); ?>
<nav class="col-md-3 col-lg-2 d-none d-md-block sidebar">
    <div class="position-sticky top-0 pt-2">
        <div class="text-muted small fw-bold mb-3 px-3 text-uppercase" style="letter-spacing: 1px;">Menu Utama</div>
        <ul class="nav flex-column">
            <li class="nav-item"><a class="nav-link <?= ($current_page == 'siswa.php') ? 'active' : '' ?>" href="siswa.php"><i class="bi bi-grid-1x2-fill me-3 fs-5 align-middle"></i> Dashboard</a></li>
            <li class="nav-item"><a class="nav-link <?= ($current_page == 'jadwal.php') ? 'active' : '' ?>" href="jadwal.php"><i class="bi bi-calendar2-week-fill me-3 fs-5 align-middle"></i> Jadwal & Agenda</a></li>
            <li class="nav-item mt-4 mb-2"><div class="text-muted small fw-bold px-3 text-uppercase" style="letter-spacing: 1px;">Prestasi</div></li>
            <li class="nav-item"><a class="nav-link <?= ($current_page == 'gamifikasi.php') ? 'active text-warning' : '' ?>" href="gamifikasi.php"><i class="bi bi-trophy-fill me-3 fs-5 align-middle <?= ($current_page == 'gamifikasi.php') ? 'text-warning' : '' ?>"></i> Gamifikasi</a></li>
        </ul>
    </div>
</nav>