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
$id_siswa = $siswa['IDSiswa'];

// 2. Ambil Data Kuis
$q_kuis = mysqli_query($koneksi, "SELECT * FROM kuis WHERE IDKuis='$id_kuis'");
$kuis = mysqli_fetch_assoc($q_kuis);

if(!$kuis || $kuis['Status'] != 'Published') {
    die("<script>alert('Ujian ini sudah ditutup atau tidak tersedia.'); window.location='siswa.php';</script>");
}

// Cek Deadline
if(strtotime($kuis['Deadline']) < time()) {
    die("<script>alert('Waktu tenggat (Deadline) ujian ini telah berakhir.'); window.location='siswa.php';</script>");
}

// 3. Manajemen Waktu & Sesi Ujian (Tabel kuis_nilai)
$q_cek = mysqli_query($koneksi, "SELECT * FROM kuis_nilai WHERE IDKuis='$id_kuis' AND IDSiswa='$id_siswa'");
if(mysqli_num_rows($q_cek) == 0) {
    // Siswa baru pertama kali mulai klik
    mysqli_query($koneksi, "INSERT INTO kuis_nilai (IDKuis, IDSiswa, WaktuMulai) VALUES ('$id_kuis', '$id_siswa', NOW())");
    $waktu_mulai = time();
} else {
    $data_nilai = mysqli_fetch_assoc($q_cek);
    if(!is_null($data_nilai['WaktuSelesai'])) {
        die("<script>alert('Kamu sudah menyelesaikan ujian ini!'); window.location='siswa.php';</script>");
    }
    $waktu_mulai = strtotime($data_nilai['WaktuMulai']);
}

// Hitung Sisa Detik
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
        .opsi-input:checked + .opsi-label { border-color: var(--primary); background: #e0e7ff; color: var(--primary); box-shadow: 0 4px 10px rgba(79,70,229,0.1); }
        
        /* FITUR RAGU-RAGU */
        .ragu-box { display: flex; align-items: center; gap: 10px; background: #fffbeb; border: 1px solid #fde68a; padding: 10px 20px; border-radius: 8px; cursor: pointer; user-select: none; width: max-content;}
        .ragu-checkbox { transform: scale(1.3); cursor: pointer; }
        .ragu-text { color: #d97706; font-weight: 700; }

        /* SIDEBAR NAVIGASI SOAL */
        .nav-grid-card { background: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 4px 10px rgba(0,0,0,0.02); border: 1px solid #e2e8f0; position: sticky; top: 100px; }
        .nav-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 10px; }
        .btn-nav-soal { width: 100%; aspect-ratio: 1; border-radius: 8px; font-weight: 700; display: flex; justify-content: center; align-items: center; border: 2px solid #cbd5e1; background: #fff; color: #64748b; transition: 0.2s; padding: 0;}
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

    <nav class="navbar navbar-exam fixed-top py-3">
        <div class="container d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0 fw-bold text-dark text-truncate" style="max-width: 300px;"><i class="bi bi-journal-text text-primary me-2"></i><?= htmlspecialchars($kuis['Judul']) ?></h5>
                <span class="small text-muted d-none d-md-block">Peserta: <?= htmlspecialchars($siswa['NamaSiswa']) ?></span>
            </div>
            <div class="timer-box" id="timerDisplay">
                <i class="bi bi-alarm-fill"></i> <span id="timeText">--:--:--</span>
            </div>
        </div>
    </nav>

    <div class="container mb-5">
        <form id="formUjian" action="simpan_jawaban_quiz.php" method="POST">
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
                        
                        <div class="question-card" id="soal-<?= $no ?>">
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
                                // RENDER OPSI JAWABAN BERDASARKAN TIPE
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
                    echo "</div>"; // Tutup div halaman terakhir
                    ?>

                    <div class="bottom-nav">
                        <button type="button" class="btn btn-outline-secondary btn-page" id="btnPrev" onclick="gantiHalaman(-1)" style="visibility: hidden;"><i class="bi bi-arrow-left me-2"></i> Sebelumnya</button>
                        <span class="fw-bold text-muted" id="pageIndicator">Halaman 1 dari <?= $total_halaman ?></span>
                        <button type="button" class="btn btn-primary btn-page" id="btnNext" onclick="gantiHalaman(1)">Selanjutnya <i class="bi bi-arrow-right ms-2"></i></button>
                    </div>

                </div>

                <div class="col-lg-4">
                    <div class="nav-grid-card">
                        <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                            <h6 class="fw-bold text-dark mb-0"><i class="bi bi-grid-3x3-gap-fill text-primary me-2"></i> Peta Soal</h6>
                            <span class="badge bg-light text-dark border"><?= $total_soal ?> Soal</span>
                        </div>
                        
                        <div class="nav-grid mb-4">
                            <?php for($i=1; $i<=$total_soal; $i++): ?>
                                <button type="button" class="btn-nav-soal <?= $i==1 ? 'active' : '' ?>" id="nav-btn-<?= $i ?>" onclick="lompatKeSoal(<?= $i ?>)"><?= $i ?></button>
                            <?php endfor; ?>
                        </div>
                        
                        <div class="d-flex flex-wrap gap-2 mb-4" style="font-size: 0.8rem;">
                            <div class="d-flex align-items-center"><span class="badge bg-success d-inline-block me-1" style="width:15px; height:15px;"></span> Dijawab</div>
                            <div class="d-flex align-items-center"><span class="badge bg-warning d-inline-block me-1" style="width:15px; height:15px;"></span> Ragu-ragu</div>
                            <div class="d-flex align-items-center"><span class="badge bg-white border d-inline-block me-1" style="width:15px; height:15px;"></span> Kosong</div>
                        </div>

                        <button type="button" class="btn btn-success w-100 fw-bold py-3 rounded-pill shadow-sm" onclick="konfirmasiSelesai()">
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
        // 1. SISTEM TIMER UJIAN
        // ==========================================
        let sisaDetik = <?= $sisa_detik ?>;
        const timerDisplay = document.getElementById('timerDisplay');
        const timeText = document.getElementById('timeText');
        const inputDurasi = document.getElementById('inputDurasiTerpakai');
        const totalDurasiDetik = <?= $durasi_detik ?>;

        function updateTimer() {
            if (sisaDetik <= 0) {
                timeText.innerHTML = "WAKTU HABIS!";
                clearInterval(timerInterval);
                Swal.fire({
                    title: 'Waktu Habis!',
                    text: 'Sistem akan otomatis menyimpan dan menyerahkan jawabanmu.',
                    icon: 'warning',
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    timer: 3000
                }).then(() => {
                    document.getElementById('formUjian').submit();
                });
                return;
            }

            const jam = Math.floor(sisaDetik / 3600);
            const menit = Math.floor((sisaDetik % 3600) / 60);
            const detik = sisaDetik % 60;
            
            timeText.innerHTML = `${String(jam).padStart(2, '0')}:${String(menit).padStart(2, '0')}:${String(detik).padStart(2, '0')}`;
            
            // Catat durasi terpakai untuk laporan guru
            inputDurasi.value = totalDurasiDetik - sisaDetik;

            if(sisaDetik < 300) { // Sisa 5 menit warna jadi oranye
                timerDisplay.classList.add('warning');
            }

            sisaDetik--;
        }
        const timerInterval = setInterval(updateTimer, 1000);
        updateTimer();

        // ==========================================
        // 2. SISTEM PAGINATION (HALAMAN)
        // ==========================================
        let currentPage = 1;
        const totalPages = <?= $total_halaman ?>;
        const soalPerHalaman = <?= $soal_per_halaman ?>;

        function gantiHalaman(step) {
            document.getElementById(`page-${currentPage}`).classList.remove('active-page');
            currentPage += step;
            document.getElementById(`page-${currentPage}`).classList.add('active-page');
            
            updatePaginatorUI();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function updatePaginatorUI() {
            document.getElementById('btnPrev').style.visibility = (currentPage === 1) ? 'hidden' : 'visible';
            document.getElementById('btnNext').style.visibility = (currentPage === totalPages) ? 'hidden' : 'visible';
            document.getElementById('pageIndicator').innerText = `Halaman ${currentPage} dari ${totalPages}`;
        }

        function lompatKeSoal(nomor) {
            // Tentukan soal ini ada di halaman berapa
            const targetPage = Math.ceil(nomor / soalPerHalaman);
            if(targetPage !== currentPage) {
                document.getElementById(`page-${currentPage}`).classList.remove('active-page');
                currentPage = targetPage;
                document.getElementById(`page-${currentPage}`).classList.add('active-page');
                updatePaginatorUI();
            }

            // Ganti border highlight di navigasi
            document.querySelectorAll('.btn-nav-soal').forEach(btn => btn.classList.remove('active'));
            document.getElementById(`nav-btn-${nomor}`).classList.add('active');

            // Scroll mulus ke soal tujuan
            document.getElementById(`soal-${nomor}`).scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        // ==========================================
        // 3. WARNA NAVIGASI & AUTO-SAVE LOKAL
        // ==========================================
        const idKuis = "<?= $id_kuis ?>";
        
        // Event Listener untuk semua input jawaban
        document.querySelectorAll('.jawaban-trigger').forEach(input => {
            input.addEventListener('change', function() {
                const no = this.getAttribute('data-no');
                updateWarnaNav(no);
                simpanKeLokal(); // Auto save
            });
            input.addEventListener('keyup', function() { // Khusus untuk text/textarea
                const no = this.getAttribute('data-no');
                updateWarnaNav(no);
                simpanKeLokal();
            });
        });

        function toggleRagu(checkbox, no) {
            const btnNav = document.getElementById(`nav-btn-${no}`);
            if(checkbox.checked) {
                btnNav.classList.add('doubt');
            } else {
                btnNav.classList.remove('doubt');
                updateWarnaNav(no); // kembalikan ke warna hijau jika sudah diisi
            }
            simpanKeLokal();
        }

        function updateWarnaNav(no) {
            const btnNav = document.getElementById(`nav-btn-${no}`);
            if(btnNav.classList.contains('doubt')) return; // Jangan timpa jika sedang ragu

            // Cek apakah soal ini punya jawaban yang terisi
            const inputs = document.querySelectorAll(`.jawaban-trigger[data-no="${no}"]`);
            let isFilled = false;

            inputs.forEach(inp => {
                if(inp.type === 'radio' || inp.type === 'checkbox') {
                    if(inp.checked) isFilled = true;
                } else {
                    if(inp.value.trim() !== '') isFilled = true;
                }
            });

            if(isFilled) {
                btnNav.classList.add('answered');
                
                // Ubah icon radio buatan jadi tercentang (UI Kosmetik)
                const card = document.getElementById(`soal-${no}`);
                if(card) {
                    card.querySelectorAll('.uncheck-icon').forEach(i => i.style.display = 'inline-block');
                    card.querySelectorAll('.check-icon').forEach(i => i.style.display = 'none');
                    const checkedInput = card.querySelector('input:checked');
                    if(checkedInput) {
                        const label = checkedInput.nextElementSibling;
                        label.querySelector('.uncheck-icon').style.display = 'none';
                        label.querySelector('.check-icon').style.display = 'inline-block';
                    }
                }
            } else {
                btnNav.classList.remove('answered');
            }
        }

        // ==========================================
        // 4. FITUR AUTO-SAVE KE LOCALSTORAGE BROWSER
        // ==========================================
        function simpanKeLokal() {
            const formData = new FormData(document.getElementById('formUjian'));
            const dataObj = {};
            for (let [key, value] of formData.entries()) {
                if(dataObj[key]) {
                    if(!Array.isArray(dataObj[key])) dataObj[key] = [dataObj[key]];
                    dataObj[key].push(value);
                } else {
                    dataObj[key] = value;
                }
            }
            
            // Simpan state ragu-ragu
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

                // Pulihkan Ragu-ragu
                if(dataObj['ragu_states']) {
                    const ragus = dataObj['ragu_states'];
                    document.querySelectorAll('.ragu-checkbox').forEach((cb, idx) => {
                        const no = idx + 1;
                        if(ragus[no]) {
                            cb.checked = true;
                            toggleRagu(cb, no);
                        }
                    });
                }

                // Warnai nav
                for(let i=1; i<=<?= $total_soal ?>; i++) { updateWarnaNav(i); }
            }
        }
        
        // Panggil pemulihan saat halaman termuat
        pulihkanDariLokal();

        // ==========================================
        // 5. KONFIRMASI SEBELUM SUBMIT
        // ==========================================
        function konfirmasiSelesai() {
            // Cek apakah ada yang masih ragu atau kosong
            const totalSoal = <?= $total_soal ?>;
            let terjawab = document.querySelectorAll('.btn-nav-soal.answered').length;
            let ragu = document.querySelectorAll('.btn-nav-soal.doubt').length;
            
            // Note: yang ragu tapi sudah dijawab tetap dihitung 'answered' secara logika HTML kita jika check warna hijau ditimpa kuning
            // Mari kita hitung ulang berdasarkan input murni
            let kosong = 0;
            for(let i=1; i<=totalSoal; i++) {
                const nav = document.getElementById(`nav-btn-${i}`);
                if(!nav.classList.contains('answered') && !nav.classList.contains('doubt')) kosong++;
            }

            let pesan = "Pastikan semua soal telah dijawab dengan yakin.";
            if(kosong > 0 || ragu > 0) {
                pesan = `<span class="text-danger fw-bold">Peringatan:</span> Ada <b>${kosong}</b> soal belum dijawab dan <b>${ragu}</b> soal masih ragu-ragu. Yakin ingin menyerahkan?`;
            }

            Swal.fire({
                title: 'Serahkan Ujian?',
                html: pesan,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#10b981',
                cancelButtonColor: '#cbd5e1',
                confirmButtonText: 'Ya, Serahkan!',
                cancelButtonText: 'Cek Kembali',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    localStorage.removeItem(`ujian_terakhir_${idKuis}`); // Bersihkan memori lokal
                    document.getElementById('formUjian').submit();
                }
            });
        }
    </script>
</body>
</html>