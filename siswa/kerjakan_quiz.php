<?php
session_start();
require '../login/koneksi.php';

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'siswa'){ header("Location: ../login/login.php"); exit; }

$id_user = $_SESSION['IDUser'] ?? '';
$id_kuis = mysqli_real_escape_string($koneksi, $_GET['id_kuis'] ?? '');

if(empty($id_kuis)) { die("Kuis tidak ditemukan!"); }

// 1. Ambil Data Siswa
$q_siswa = mysqli_query($koneksi, "SELECT IDSiswa, NamaSiswa FROM siswa WHERE IDUser='$id_user'");
$siswa = mysqli_fetch_assoc($q_siswa);
$id_siswa = $siswa['IDSiswa'] ?? '';
$nama_siswa = $siswa['NamaSiswa'] ?? 'Siswa';

// 2. Ambil Info Kuis
$q_kuis = mysqli_query($koneksi, "SELECT * FROM kuis WHERE IDKuis='$id_kuis'");
if(mysqli_num_rows($q_kuis) == 0) { die("Kuis tidak ditemukan!"); }
$kuis = mysqli_fetch_assoc($q_kuis);
$id_mapel = $kuis['IDMapel'];

// 3. Kalkulasi Durasi / Waktu Mulai
$q_nilai = mysqli_query($koneksi, "SELECT WaktuMulai FROM kuis_nilai WHERE IDKuis='$id_kuis' AND IDSiswa='$id_siswa'");
if(mysqli_num_rows($q_nilai) > 0) {
    $d_nilai = mysqli_fetch_assoc($q_nilai);
    $waktu_mulai = strtotime($d_nilai['WaktuMulai']);
} else {
    // Baru pertama mulai
    mysqli_query($koneksi, "INSERT INTO kuis_nilai (IDKuis, IDSiswa, WaktuMulai) VALUES ('$id_kuis', '$id_siswa', NOW())");
    $waktu_mulai = time();
}

$durasi_detik = $kuis['DurasiMenit'] * 60;
$waktu_berjalan = time() - $waktu_mulai;
$sisa_detik = $durasi_detik - $waktu_berjalan;
if($sisa_detik < 0) $sisa_detik = 0; // Waktu habis

// 4. Ambil Semua Soal
$q_soal = mysqli_query($koneksi, "SELECT * FROM kuis_soal WHERE IDKuis='$id_kuis' ORDER BY Urutan ASC");
$semua_soal = [];
$id_soals = [];
while($row = mysqli_fetch_assoc($q_soal)) {
    $semua_soal[] = $row;
    $id_soals[] = $row['IDSoal'];
}

// 5. Ambil Semua Opsi Jawaban (Digabung agar tidak query berulang)
$semua_opsi = [];
if(count($id_soals) > 0) {
    $in_ids = implode(',', $id_soals);
    $q_opsi = mysqli_query($koneksi, "SELECT * FROM kuis_opsi WHERE IDSoal IN ($in_ids) ORDER BY IDOpsi ASC");
    while($op = mysqli_fetch_assoc($q_opsi)) {
        $semua_opsi[$op['IDSoal']][] = $op;
    }
}
$total_soal = count($semua_soal);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mengerjakan: <?= htmlspecialchars($kuis['Judul']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        :root { --primary: #4f46e5; --bg-light: #f8fafc; }
        body { background-color: var(--bg-light); font-family: 'Segoe UI', system-ui, sans-serif; padding-top: 80px;}
        
        /* NAVBAR & STICKY TIMER */
        .navbar-exam { background: #fff; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .timer-box { background: #ef4444; color: white; padding: 8px 20px; border-radius: 50px; font-weight: 800; font-size: 1.2rem; display: flex; align-items: center; gap: 8px; box-shadow: 0 4px 10px rgba(239,68,68,0.3); letter-spacing: 1px;}
        .timer-box.warning { background: #f59e0b; box-shadow: 0 4px 10px rgba(245,158,11,0.3); }

        /* KARTU SOAL */
        .question-container { display: none; } /* Disembunyikan dulu untuk pagination */
        .question-container.active-page { display: block; animation: fadeIn 0.5s; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        .question-card { background: #fff; border-radius: 12px; padding: 30px; box-shadow: 0 4px 10px rgba(0,0,0,0.02); border: 1px solid #e2e8f0; margin-bottom: 25px; }
        .q-number { background: var(--primary); color: white; width: 35px; height: 35px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 1.1rem; flex-shrink: 0;}
        .q-text { font-size: 1.15rem; color: #1e293b; line-height: 1.6; font-weight: 600;}
        .q-image { max-width: 100%; max-height: 300px; border-radius: 8px; margin: 15px 0; border: 1px solid #e2e8f0; }
        
        /* OPSI JAWABAN */
        .opsi-label { display: flex; align-items: center; padding: 12px 20px; border: 2px solid #e2e8f0; border-radius: 10px; cursor: pointer; transition: 0.2s; margin-bottom: 10px; font-weight: 500; color: #475569;}
        .opsi-label:hover { background: #f1f5f9; border-color: #cbd5e1; }
        
        /* Warna latar & garis saat opsi dipilih */
        .opsi-input:checked + .opsi-label { border-color: var(--primary); background: #e0e7ff; color: var(--primary); box-shadow: 0 4px 10px rgba(79,70,229,0.1); }
        
        /* Logika Cerdas Ikon: Sembunyikan yang kosong, munculkan yang BENAR saja */
        .opsi-input:checked + .opsi-label .uncheck-icon { display: none !important; }
        .opsi-input:checked + .opsi-label .check-icon:not(.d-none) { display: inline-block !important; }
        
        /* FITUR RAGU-RAGU */
        .ragu-box { display: flex; align-items: center; gap: 10px; background: #fffbeb; border: 1px solid #fde68a; padding: 10px 20px; border-radius: 8px; cursor: pointer; user-select: none; width: max-content;}
        .ragu-checkbox { transform: scale(1.3); cursor: pointer; }
        .ragu-text { color: #d97706; font-weight: 700; }

        /* SIDEBAR NAVIGASI SOAL */
        .nav-grid-card { background: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 4px 10px rgba(0,0,0,0.02); border: 1px solid #e2e8f0; position: sticky; top: 100px; }
        
        /* ++ CSS YANG BARU UNTUK MENGECILKAN TOMBOL ++ */
        .nav-grid { display: flex; flex-wrap: wrap; gap: 8px; justify-content: center; }
        .btn-nav-soal { width: 42px; height: 42px; border-radius: 8px; font-weight: 700; display: flex; justify-content: center; align-items: center; border: 2px solid #cbd5e1; background: #fff; color: #64748b; transition: 0.2s; padding: 0; }
        
        .btn-nav-soal:hover { border-color: var(--primary); color: var(--primary); }
        .btn-nav-soal.answered { background: #10b981; color: white; border-color: #10b981; }
        .btn-nav-soal.doubt { background: #f59e0b; color: white; border-color: #f59e0b; }
        .btn-nav-soal.active { transform: scale(1.1); box-shadow: 0 0 0 3px rgba(79,70,229,0.3); border-color: var(--primary); }

        /* PAGINATION BUTTONS */
        .bottom-nav { background: #fff; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 10px rgba(0,0,0,0.02); }
        .btn-page { font-weight: 700; padding: 10px 25px; border-radius: 50px; }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-exam fixed-top py-3">
        <div class="container-fluid px-4 d-flex justify-content-between align-items-center">
            <div>
                <h5 class="fw-bold text-dark mb-0 d-flex align-items-center"><i class="bi bi-journal-text text-primary fs-3 me-2"></i> <?= htmlspecialchars($kuis['Judul']) ?></h5>
                <div class="text-secondary small mt-1">Peserta: <strong class="text-dark"><?= htmlspecialchars($nama_siswa) ?></strong></div>
            </div>
            <div id="timer-display" class="timer-box">
                <i class="bi bi-alarm-fill"></i> <span id="time-text">--:--:--</span>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-4 py-4">
        
        <form id="formKuis" action="simpan_jawaban_quiz.php" method="POST">
            <input type="hidden" name="id_mapel" value="<?= $id_mapel ?>">
            <input type="hidden" name="id_kuis" value="<?= $id_kuis ?>">
            <input type="hidden" name="durasi_terpakai" id="inputDurasiTerpakai" value="0">
            
            <div class="row g-4">
                
                <div class="col-lg-8">
                    
                    <?php 
                    $soal_per_halaman = 10;
                    $total_halaman = ceil($total_soal / $soal_per_halaman);
                    $halaman_sekarang = 1;
                    $no = 1;
                    
                    foreach($semua_soal as $index => $soal): 
                        $id_s = $soal['IDSoal'];
                        $tipe = $soal['TipeSoal'];
                        
                        // Logika Pembagian Halaman
                        if ($index % $soal_per_halaman == 0) {
                            if ($index > 0) echo "</div>"; // Tutup div halaman sebelumnya
                            $active_class = ($halaman_sekarang == 1) ? 'active-page' : '';
                            echo "<div class='question-container $active_class' id='page-$halaman_sekarang'>";
                            $halaman_sekarang++;
                        }
                    ?>
                        
                        <div class="question-card card-soal" id="soal-<?= $no ?>">
                            <div class="d-flex gap-3 align-items-start mb-3">
                                <div class="q-number"><?= $no ?></div>
                                <div class="flex-grow-1">
                                    <div class="q-text"><?= nl2br(htmlspecialchars($soal['Pertanyaan'])) ?></div>
                                    <?php if(!empty($soal['Gambar'])): ?>
                                        <img src="../uploads/quiz/<?= $soal['Gambar'] ?>" class="q-image" alt="Lampiran Soal">
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="ps-md-5">
                                <?php 
                                if(in_array($tipe, ['pilgan', 'checkbox'])): 
                                    $opsi_list = $semua_opsi[$id_s] ?? [];
                                    $input_type = ($tipe == 'pilgan') ? 'radio' : 'checkbox';
                                    $input_name = ($tipe == 'pilgan') ? "jawaban[$id_s]" : "jawaban[$id_s][]";
                                    
                                    foreach($opsi_list as $op):
                                ?>
                                    <div>
                                        <input type="<?= $input_type ?>" name="<?= $input_name ?>" value="<?= $op['IDOpsi'] ?>" id="opt_<?= $op['IDOpsi'] ?>" class="d-none opsi-input jawaban-trigger" data-no="<?= $no ?>">
                                        <label for="opt_<?= $op['IDOpsi'] ?>" class="opsi-label">
                                            <div class="me-3 fs-5">
                                                <i class="bi bi-circle <?= $tipe == 'pilgan' ? '' : 'd-none' ?> uncheck-icon"></i>
                                                <i class="bi bi-check-circle-fill <?= $tipe == 'pilgan' ? '' : 'd-none' ?> check-icon" style="display:none;"></i>
                                                <i class="bi bi-square <?= $tipe == 'checkbox' ? '' : 'd-none' ?> uncheck-icon"></i>
                                                <i class="bi bi-check-square-fill <?= $tipe == 'checkbox' ? '' : 'd-none' ?> check-icon" style="display:none;"></i>
                                            </div>
                                            <?= htmlspecialchars($op['TeksOpsi']) ?>
                                        </label>
                                    </div>
                                <?php 
                                    endforeach; 
                                elseif($tipe == 'dropdown'): 
                                    $opsi_list = $semua_opsi[$id_s] ?? [];
                                ?>
                                    <select name="jawaban[<?= $id_s ?>]" class="form-select form-select-lg border-2 mb-3 jawaban-trigger" data-no="<?= $no ?>">
                                        <option value="" selected disabled>-- Pilih Jawaban --</option>
                                        <?php foreach($opsi_list as $op): ?>
                                            <option value="<?= $op['IDOpsi'] ?>"><?= htmlspecialchars($op['TeksOpsi']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php elseif($tipe == 'singkat'): ?>
                                    <input type="text" name="jawaban[<?= $id_s ?>]" class="form-control form-control-lg border-2 mb-3 jawaban-trigger" placeholder="Ketik jawaban singkatmu..." data-no="<?= $no ?>">
                                <?php elseif($tipe == 'paragraf'): ?>
                                    <textarea name="jawaban[<?= $id_s ?>]" class="form-control border-2 mb-3 jawaban-trigger" rows="4" placeholder="Jelaskan jawabanmu dengan detail..." data-no="<?= $no ?>"></textarea>
                                <?php endif; ?>

                                <label class="ragu-box mt-3">
                                    <input type="checkbox" class="ragu-checkbox" onchange="toggleRagu(this, <?= $no ?>)">
                                    <span class="ragu-text">Ragu-ragu</span>
                                </label>
                            </div>
                        </div>
                    
                    <?php 
                        $no++;
                    endforeach; 
                    if($total_soal > 0) echo "</div>"; // Tutup div halaman terakhir
                    ?>

                    <div class="d-flex justify-content-between align-items-center mt-4 p-3 bg-white border rounded shadow-sm">
                        <button type="button" id="btn-prev" class="btn btn-outline-primary px-4 fw-bold" onclick="prevPage()">
                            <i class="bi bi-arrow-left me-1"></i> Sebelumnya
                        </button>
                        
                        <span id="page-indicator" class="fw-bold text-secondary">Halaman 1 dari <?= $total_halaman ?></span>
                        
                        <button type="button" id="btn-next" class="btn btn-primary px-4 fw-bold" onclick="nextPage()">
                            Selanjutnya <i class="bi bi-arrow-right ms-1"></i>
                        </button>
                    </div>

                </div>

                <div class="col-lg-4">
                    <div class="nav-grid-card">
                        <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-3">
                            <h6 class="fw-bold text-dark mb-0"><i class="bi bi-grid-3x3-gap-fill text-primary me-2"></i> Peta Soal</h6>
                            <span class="badge bg-light text-secondary border"><?= $total_soal ?> Soal</span>
                        </div>
                        
                        <div class="nav-grid mb-4">
                            <?php for($i=1; $i<=$total_soal; $i++): ?>
                                <button type="button" class="btn btn-nav-soal" id="nav-btn-<?= $i ?>" onclick="lompatKeSoal(<?= $i ?>)"><?= $i ?></button>
                            <?php endfor; ?>
                        </div>
        
                        <div class="d-flex flex-wrap gap-2 mb-4" style="font-size: 0.8rem;">
                            <div class="d-flex align-items-center"><span class="badge bg-success d-inline-block me-1" style="width:15px; height:15px;"></span> Dijawab</div>
                            <div class="d-flex align-items-center"><span class="badge bg-warning d-inline-block me-1" style="width:15px; height:15px;"></span> Ragu-ragu</div>
                            <div class="d-flex align-items-center"><span class="badge bg-white border d-inline-block me-1" style="width:15px; height:15px;"></span> Kosong</div>
                        </div>

                        <button type="submit" class="btn btn-success w-100 fw-bold py-3 rounded-pill shadow-sm">
                            <i class="bi bi-send-check-fill me-2"></i> Serahkan Ujian
                        </button>
                    </div>
                </div>

            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        // ==========================================
        // 1. FITUR COUNTDOWN TIMER
        // ==========================================
        let sisaWaktu = <?= $sisa_detik ?>;
        const durasiAwal = <?= $durasi_detik ?>;
        const timeText = document.getElementById('time-text');
        const timerBox = document.getElementById('timer-display');
        const inputDurasiTerpakai = document.getElementById('inputDurasiTerpakai');

        function updateTimer() {
            if(sisaWaktu <= 0) {
                clearInterval(intervalTimer);
                timeText.innerText = "00:00:00";
                Swal.fire({
                    title: 'Waktu Habis!', text: 'Sistem akan otomatis mengirimkan jawabanmu.', icon: 'warning',
                    allowOutsideClick: false, showConfirmButton: false, timer: 3000
                }).then(() => {
                    document.getElementById('formKuis').dispatchEvent(new Event('submit'));
                });
                return;
            }

            let jam = Math.floor(sisaWaktu / 3600);
            let menit = Math.floor((sisaWaktu % 3600) / 60);
            let detik = sisaWaktu % 60;

            jam = jam < 10 ? '0'+jam : jam;
            menit = menit < 10 ? '0'+menit : menit;
            detik = detik < 10 ? '0'+detik : detik;

            timeText.innerText = `${jam}:${menit}:${detik}`;
            
            inputDurasiTerpakai.value = durasiAwal - sisaWaktu;

            if(sisaWaktu < 300) { // 5 Menit terakhir
                timerBox.classList.remove('bg-ef4444');
                timerBox.classList.add('warning');
            }

            sisaWaktu--;
        }

        const intervalTimer = setInterval(updateTimer, 1000);
        updateTimer();

        // ==========================================
        // 2. FITUR UPDATE WARNA PETA SOAL
        // ==========================================
        function updateWarnaNav(no) {
            const btnNav = document.getElementById(`nav-btn-${no}`);
            const questionCard = document.getElementById(`soal-${no}`);
            
            if(!btnNav || !questionCard) return;

            let isDijawab = false;
            
            // Cek Radio & Checkbox
            const inputs = questionCard.querySelectorAll('input[type="radio"], input[type="checkbox"]:not(.ragu-checkbox)');
            inputs.forEach(inp => { if(inp.checked) isDijawab = true; });

            // Cek Teks & Dropdown
            const textInputs = questionCard.querySelectorAll('input[type="text"], textarea, select');
            textInputs.forEach(inp => { if(inp.value.trim() !== '') isDijawab = true; });

            const isRagu = questionCard.querySelector('.ragu-checkbox').checked;

            btnNav.classList.remove('answered', 'doubt');
            
            if(isRagu) {
                btnNav.classList.add('doubt');
            } else if(isDijawab) {
                btnNav.classList.add('answered');
            }
        }

        function toggleRagu(checkboxEle, no) {
            updateWarnaNav(no);
            simpanKeLokal();
        }

        function lompatKeSoal(no) {
            // Hitung soal ini ada di halaman berapa
            const targetPage = Math.ceil(no / questionsPerPage);
            
            // Ganti halaman jika diperlukan
            if(targetPage !== currentPage) {
                currentPage = targetPage;
                renderPagination();
            }

            // Hapus animasi 'active' dari semua tombol nav
            document.querySelectorAll('.btn-nav-soal').forEach(b => b.classList.remove('active'));
            document.getElementById(`nav-btn-${no}`).classList.add('active');

            // Scroll ke soal
            const targetCard = document.getElementById(`soal-${no}`);
            const y = targetCard.getBoundingClientRect().top + window.scrollY - 150;
            window.scrollTo({top: y, behavior: 'smooth'});
        }

        // ==========================================
        // 3. FITUR PAGINASI SOAL (MAX 10 PER HALAMAN)
        // ==========================================
        const questionsPerPage = 10;
        const questionCards = document.querySelectorAll('.card-soal'); 
        const totalPages = Math.ceil(questionCards.length / questionsPerPage);
        let currentPage = 1;

        function renderPagination() {
            document.querySelectorAll('.question-container').forEach((container, index) => {
                if (index === currentPage - 1) {
                    container.classList.add('active-page');
                } else {
                    container.classList.remove('active-page');
                }
            });

            document.getElementById('page-indicator').innerText = `Halaman ${currentPage} dari ${totalPages}`;
            
            const btnPrev = document.getElementById('btn-prev');
            const btnNext = document.getElementById('btn-next');

            if(btnPrev) btnPrev.style.display = currentPage === 1 ? 'none' : 'inline-block';
            if(btnNext) btnNext.style.display = currentPage === totalPages ? 'none' : 'inline-block';
        }

        function nextPage() {
            if (currentPage < totalPages) {
                currentPage++;
                renderPagination();
                window.scrollTo({top: 0, behavior: 'smooth'});
            }
        }

        function prevPage() {
            if (currentPage > 1) {
                currentPage--;
                renderPagination();
                window.scrollTo({top: 0, behavior: 'smooth'});
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            if(questionCards.length > 0) renderPagination();
        });

        // ==========================================
        // 4. FITUR AUTO-SAVE KE LOCALSTORAGE BROWSER
        // ==========================================
        const idKuis = "<?= $id_kuis ?>"; 

        function simpanKeLokal() {
            const formElement = document.getElementById('formUjian') || document.getElementById('formKuis');
            if(!formElement) return;

            const formData = new FormData(formElement);
            const dataObj = {};
            for (let [key, value] of formData.entries()) {
                if(dataObj[key]) {
                    if(!Array.isArray(dataObj[key])) dataObj[key] = [dataObj[key]];
                    dataObj[key].push(value);
                } else {
                    dataObj[key] = value;
                }
            }
            
            const raguStates = {};
            document.querySelectorAll('.ragu-checkbox').forEach((cb, idx) => {
                if(cb.checked) raguStates[idx + 1] = true;
            });
            dataObj['ragu_states'] = raguStates;

            localStorage.setItem(`ujian_terakhir_${idKuis}`, JSON.stringify(dataObj));
        }

        // Pulihkan data jika ada di LocalStorage (berguna jika browser ter-refresh)
        function pulihkanDariLokal() {
            const savedData = localStorage.getItem(`ujian_terakhir_${idKuis}`);
            if(savedData) {
                const dataObj = JSON.parse(savedData);
                
                for (let key in dataObj) {
                    if(key === 'ragu_states' || key === 'id_kuis' || key === 'durasi_terpakai') continue;
                    
                    const value = dataObj[key];
                    const inputs = document.querySelectorAll(`[name="${key}"]`);
                    
                    if(inputs.length === 0) continue;

                    if(inputs[0].type === 'radio') {
                        inputs.forEach(inp => { if(inp.value === value) inp.checked = true; });
                    } else if(inputs[0].type === 'checkbox') {
                        const valArray = Array.isArray(value) ? value : [value];
                        inputs.forEach(inp => { if(valArray.includes(inp.value)) inp.checked = true; });
                    } else {
                        inputs[0].value = value;
                    }
                }

                if(dataObj['ragu_states']) {
                    const ragus = dataObj['ragu_states'];
                    document.querySelectorAll('.ragu-checkbox').forEach((cb, idx) => {
                        const no = idx + 1;
                        if(ragus[no]) {
                            cb.checked = true;
                            if(typeof toggleRagu === "function") toggleRagu(cb, no); 
                        }
                    });
                }

                if(typeof updateWarnaNav === "function") {
                    for(let i=1; i<=<?= $total_soal ?>; i++) { updateWarnaNav(i); }
                }
            }
        }
        
        // SENSOR PENYIMPANAN: Panggil simpanKeLokal tiap kali siswa nge-klik atau ngetik
        document.querySelectorAll('input, select, textarea').forEach(input => {
            input.addEventListener('change', function() {
                simpanKeLokal(); 
                if(this.dataset && this.dataset.no) {
                    updateWarnaNav(this.dataset.no);
                }
            });
            
            if((input.tagName === 'INPUT' && input.type === 'text') || input.tagName === 'TEXTAREA') {
                input.addEventListener('keyup', function() {
                    simpanKeLokal();
                    if(this.dataset && this.dataset.no) {
                        updateWarnaNav(this.dataset.no);
                    }
                });
            }
        });

        pulihkanDariLokal();

        // ==========================================
        // 5. FITUR POP-UP & SUBMIT AJAX SWEETALERT
        // ==========================================
        document.getElementById('formKuis').addEventListener('submit', function(e) {
            e.preventDefault(); 

            Swal.fire({
                title: 'Serahkan Ujian?',
                text: 'Pastikan kamu sudah memeriksa semua jawaban. Tindakan ini tidak bisa dibatalkan!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#10b981', 
                cancelButtonColor: '#cbd5e1', 
                confirmButtonText: '<i class="bi bi-send-fill me-1"></i> Ya, Serahkan!',
                cancelButtonText: 'Cek Lagi',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    
                    Swal.fire({
                        title: 'Menyimpan Jawaban...',
                        html: 'Tunggu sebentar, jangan tutup halaman ini.',
                        allowOutsideClick: false,
                        didOpen: () => { Swal.showLoading(); }
                    });

                    const formData = new FormData(this);
                    fetch('simpan_jawaban_quiz.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if(data.status === 'sukses') {
                            // Hapus Auto-Save dari browser
                            localStorage.removeItem('ujian_terakhir_' + idKuis);
                            
                            Swal.fire({
                                title: 'Ujian Selesai! 🎉',
                                text: 'Jawabanmu berhasil dikirim ke guru.',
                                icon: 'success',
                                confirmButtonColor: '#4f46e5',
                                allowOutsideClick: false
                            }).then(() => {
                                window.location.href = `mapel.php?id_mapel=${data.id_mapel}#itemKuis${data.id_kuis}`;
                            });
                        } else {
                            Swal.fire('Gagal!', data.pesan || 'Terjadi kesalahan.', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire('Error Koneksi', 'Sistem gagal menghubungi server. Cek internetmu.', 'error');
                    });
                }
            });
        });
    </script>
</body>
</html>