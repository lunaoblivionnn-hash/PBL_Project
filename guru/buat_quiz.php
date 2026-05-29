<?php
session_start();
require '../login/koneksi.php';

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'guru'){ header("Location: ../login/login.php"); exit; }

$id_mapel = $_REQUEST['id_mapel'] ?? '';
$mode = $_REQUEST['mode'] ?? 'single';

if ($mode == 'multi') {
    $kelas_arr = $_POST['kelas_pilih'] ?? [];
    $kelas_string = implode(',', $kelas_arr);
    $nama_topik = $_POST['nama_topik'] ?? '';
    $id_topik = '';
    $judul_header = "Sebarkan ke " . count($kelas_arr) . " Kelas";
} else {
    $kelas_string = $_GET['kelas'] ?? '';
    $id_topik = $_GET['id_topik'] ?? '';
    $nama_topik = '';
    $judul_header = "Kelas " . htmlspecialchars($kelas_string);
}

if(empty($id_mapel) || empty($kelas_string)){
    die("Akses tidak valid. Harap pilih minimal 1 kelas.");
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Ujian Baru - LMS Guru</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        :root { --primary: #4f46e5; --bg-light: #f8fafc; }
        body { background-color: var(--bg-light); font-family: 'Segoe UI', system-ui, sans-serif; padding-bottom: 80px;}
        
        .quiz-header-card { border-top: 10px solid var(--primary); border-radius: 12px; background: #fff; box-shadow: 0 4px 15px rgba(0,0,0,0.03); padding: 30px; margin-bottom: 25px; }
        .quiz-title-input { font-size: 2rem; font-weight: 800; border: none; border-bottom: 2px solid #e2e8f0; border-radius: 0; padding-left: 0; color: #1e293b; transition: 0.3s; }
        .quiz-title-input:focus { box-shadow: none; border-bottom-color: var(--primary); background: transparent; }
        .quiz-desc-input { border: none; border-bottom: 1px solid #e2e8f0; border-radius: 0; padding-left: 0; resize: none; margin-top: 15px; }
        .quiz-desc-input:focus { box-shadow: none; border-bottom-color: var(--primary); }

        .question-card { background: #fff; border-left: 5px solid transparent; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.02); padding: 25px; margin-bottom: 20px; transition: 0.3s; position: relative; }
        .question-card:focus-within, .question-card:hover { border-left-color: var(--primary); box-shadow: 0 10px 25px rgba(79,70,229,0.1); }
        
        .q-type-selector { width: 250px; border-radius: 8px; font-weight: 600; color: #475569; }
        .q-input { font-size: 1.1rem; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 15px; font-weight: 600; background: #f8fafc; }
        .q-input:focus { border-color: var(--primary); box-shadow: 0 0 0 0.2rem rgba(79,70,229,0.1); background: #fff;}
        
        .option-row { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; }
        .option-marker { width: 20px; height: 20px; border-radius: 50%; border: 2px solid #cbd5e1; display: inline-block; flex-shrink: 0; }
        .option-marker.square { border-radius: 4px; }
        .option-input { border: none; border-bottom: 1px solid transparent; flex-grow: 1; padding: 5px 0; outline: none; transition: 0.2s;}
        .option-input:focus { border-bottom-color: var(--primary); }
        .btn-remove-opt { color: #94a3b8; background: transparent; border: none; font-size: 1.2rem; }
        .btn-remove-opt:hover { color: #ef4444; }

        .add-option-text { color: #64748b; cursor: pointer; display: inline-block; margin-top: 5px; font-size: 0.9rem;}
        .add-option-text:hover { color: var(--primary); text-decoration: underline; }

        .q-footer { border-top: 1px solid #e2e8f0; margin-top: 20px; padding-top: 15px; display: flex; justify-content: flex-end; align-items: center; gap: 15px; }
        .q-action-btn { color: #64748b; background: transparent; border: none; font-size: 1.3rem; transition: 0.2s; cursor: pointer;}
        .q-action-btn:hover { color: var(--primary); }
        .q-action-btn.delete:hover { color: #ef4444; }

        .img-upload-btn { color: #64748b; border: 1px solid #e2e8f0; border-radius: 8px; padding: 8px 15px; background: #fff; cursor: pointer; transition: 0.2s;}
        .img-upload-btn:hover { background: #f8fafc; border-color: #cbd5e1; }
        .preview-img-container { position: relative; display: inline-block; margin-top: 15px; border-radius: 8px; overflow: hidden; border: 1px solid #e2e8f0;}
        .preview-img { max-height: 200px; display: block; }
        .remove-img-btn { position: absolute; top: 5px; right: 5px; background: rgba(0,0,0,0.5); color: white; border: none; border-radius: 50%; width: 25px; height: 25px; display: flex; align-items: center; justify-content: center; cursor: pointer;}

        .floating-action-bar { position: fixed; bottom: 30px; right: 30px; background: #fff; border-radius: 50px; padding: 10px 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); border: 1px solid #e2e8f0; display: flex; gap: 15px; z-index: 1000;}
        .fab-btn { background: transparent; border: none; font-size: 1.5rem; color: #475569; transition: 0.2s; cursor: pointer; display: flex; align-items: center; gap: 8px;}
        .fab-btn:hover { color: var(--primary); transform: scale(1.1); }
        .fab-btn.save { background: var(--primary); color: white; border-radius: 50px; font-size: 1.1rem; font-weight: 700; padding: 5px 20px;}
        .fab-btn.save:hover { background: #4338ca; transform: none; box-shadow: 0 4px 15px rgba(79,70,229,0.3); }

        .text-answer-preview { border-bottom: 1px dotted #cbd5e1; color: #94a3b8; padding-bottom: 5px; margin-top: 15px; width: 60%; font-style: italic;}
        
        .key-answer-radio { transform: scale(1.3); cursor: pointer; }
        .key-answer-radio:checked { background-color: #198754; border-color: #198754; }
    </style>
</head>
<body>

    <nav class="navbar navbar-light bg-white border-bottom sticky-top shadow-sm py-3">
        <div class="container d-flex justify-content-between">
            <div class="d-flex align-items-center gap-3">
                <a href="guru.php" class="text-secondary fs-4"><i class="bi bi-x-lg"></i></a>
                <h5 class="mb-0 fw-bold text-dark d-none d-md-block"><i class="bi bi-patch-question-fill text-warning me-2"></i> Perakit Ujian / Quiz</h5>
            </div>
            <div class="text-end d-flex align-items-center gap-3">
                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle px-3 py-2 fs-6 mb-0 d-inline-block">
                    <i class="bi bi-people-fill me-1"></i> <?= $judul_header ?>
                </span>
            </div>
        </div>
    </nav>

    <div class="container mt-4" style="max-width: 850px;">
        <form id="quizForm" action="Proses_Quiz.php" method="POST" enctype="multipart/form-data">
            
            <input type="hidden" name="id_mapel" value="<?= htmlspecialchars($id_mapel) ?>">
            <input type="hidden" name="mode" value="<?= htmlspecialchars($mode) ?>">
            <input type="hidden" name="kelas_string" value="<?= htmlspecialchars($kelas_string) ?>">
            <input type="hidden" name="id_topik" value="<?= htmlspecialchars($id_topik) ?>">
            <input type="hidden" name="nama_topik" value="<?= htmlspecialchars($nama_topik) ?>">

            <div class="quiz-header-card">
                <input type="text" name="judul_kuis" class="form-control quiz-title-input" placeholder="Kuis Tanpa Judul" required>
                <textarea name="deskripsi_kuis" class="form-control quiz-desc-input mb-4" rows="2" placeholder="Deskripsi atau instruksi kuis (Opsional)..."></textarea>
                
                <div class="row g-3 bg-light p-3 rounded border">
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-secondary small"><i class="bi bi-hourglass-split me-1"></i> Durasi Pengerjaan</label>
                        <div class="input-group">
                            <input type="number" name="durasi_menit" class="form-control fw-bold" value="60" min="5" required>
                            <span class="input-group-text bg-white text-muted">Menit</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-secondary small"><i class="bi bi-calendar-x me-1"></i> Batas Akhir (Deadline)</label>
                        <input type="datetime-local" name="deadline" class="form-control fw-bold" required value="<?= date('Y-m-d\T23:59', strtotime('+1 day')) ?>">
                    </div>
                </div>
            </div>

            <div id="questionsContainer"></div>

            <div class="floating-action-bar">
                <button type="button" class="fab-btn" onclick="tambahSoal()" title="Tambah Pertanyaan"><i class="bi bi-plus-circle-fill text-success"></i></button>
                <div class="vr mx-1"></div>
                <button type="submit" class="fab-btn save"><i class="bi bi-send-fill me-1"></i> Sebarkan & Posting</button>
            </div>
        </form>
    </div>

    <template id="questionTemplate">
        <div class="question-card" id="q_card_{id}">
            <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-3">
                <div class="flex-grow-1">
                    <input type="text" name="soal[{id}][teks_soal]" class="form-control q-input" placeholder="Tulis pertanyaan di sini..." required>
                </div>
                <select name="soal[{id}][tipe_soal]" class="form-select q-type-selector" onchange="ubahTipeSoal(this, '{id}')">
                    <option value="pilgan" selected>&#9678; Pilihan Ganda</option>
                    <option value="checkbox">&#9745; Kotak Centang</option>
                    <option value="dropdown">&#9660; Dropdown</option>
                    <option value="singkat">&#9776; Jawaban Singkat</option>
                    <option value="paragraf">&#9776; Paragraf</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="img-upload-btn">
                    <i class="bi bi-image me-1"></i> Lampirkan Gambar
                    <input type="file" name="soal_gambar_{id}" class="d-none" accept="image/*" onchange="previewGambarSoal(this, '{id}')">
                </label>
                <div id="previewBox_{id}" class="preview-img-container d-none">
                    <img src="" id="img_{id}" class="preview-img">
                    <button type="button" class="remove-img-btn" onclick="hapusGambarSoal('{id}')"><i class="bi bi-x"></i></button>
                </div>
            </div>

            <div id="optionsArea_{id}">
                <div class="option-row">
                    <input type="radio" name="soal[{id}][kunci_opsi]" value="0" class="form-check-input key-answer-radio me-2 shadow-none" title="Pilih sebagai kunci jawaban" required>
                    <span class="option-marker opt-icon_{id}"></span>
                    <input type="text" name="soal[{id}][opsi][]" class="option-input" placeholder="Opsi 1" required>
                    <button type="button" class="btn-remove-opt" style="visibility:hidden;"><i class="bi bi-x"></i></button>
                </div>
            </div>

            <div id="addOptBtn_{id}">
                <div class="option-row mt-2 ps-4 ms-2">
                    <span class="add-option-text" onclick="tambahOpsi('{id}')"><i class="bi bi-plus-circle me-1"></i> Tambah opsi lain</span>
                </div>
            </div>

            <div class="q-footer">
                <div class="d-flex align-items-center gap-2 me-auto">
                    <span class="fw-bold small text-secondary">Poin Soal:</span>
                    <input type="number" name="soal[{id}][poin]" class="form-control text-center" style="width: 70px; font-weight:bold;" value="10" min="1">
                </div>
                <div class="form-check form-switch me-3">
                    <input class="form-check-input" type="checkbox" name="soal[{id}][wajib]" value="1" id="wajib_{id}" checked>
                    <label class="form-check-label fw-bold small text-dark" for="wajib_{id}">Wajib Diisi</label>
                </div>
                <button type="button" class="q-action-btn delete" title="Hapus Soal" onclick="hapusSoal('{id}')"><i class="bi bi-trash"></i></button>
            </div>
        </div>
    </template>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let questionCounter = 0;

        document.addEventListener('DOMContentLoaded', () => { tambahSoal(); });

        function tambahSoal() {
            questionCounter++;
            const idStr = questionCounter.toString();
            const template = document.getElementById('questionTemplate').innerHTML;
            const newHtml = template.replace(/{id}/g, idStr);
            const container = document.getElementById('questionsContainer');
            container.insertAdjacentHTML('beforeend', newHtml);
            document.getElementById(`q_card_${idStr}`).scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        function hapusSoal(id) {
            const container = document.getElementById('questionsContainer');
            if(container.children.length <= 1) { alert("Ujian harus memiliki minimal 1 soal!"); return; }
            if(confirm("Hapus soal ini?")) { document.getElementById(`q_card_${id}`).remove(); }
        }

        function tambahOpsi(id) {
            const optionsArea = document.getElementById(`optionsArea_${id}`);
            const typeSelector = document.querySelector(`#q_card_${id} .q-type-selector`).value;
            const currentOpts = optionsArea.querySelectorAll('.option-row').length;
            
            let shapeClass = ''; let content = '';
            let inputType = 'radio'; let inputName = `soal[${id}][kunci_opsi]`;
            
            if(typeSelector === 'pilgan') { shapeClass = ''; }
            else if(typeSelector === 'checkbox') { shapeClass = 'square'; inputType = 'checkbox'; inputName = `soal[${id}][kunci_opsi][]`; }
            else if(typeSelector === 'dropdown') { shapeClass = 'border-0 fw-bold text-muted'; content = (currentOpts + 1) + '.'; }

            const newRowHtml = `
                <div class="option-row">
                    <input type="${inputType}" name="${inputName}" value="${currentOpts}" class="form-check-input key-answer-radio me-2 shadow-none" title="Pilih sebagai kunci jawaban">
                    <span class="option-marker ${shapeClass} opt-icon_${id}">${content}</span>
                    <input type="text" name="soal[${id}][opsi][]" class="option-input" placeholder="Opsi ${currentOpts + 1}" required>
                    <button type="button" class="btn-remove-opt" onclick="this.parentElement.remove()"><i class="bi bi-x"></i></button>
                </div>
            `;
            optionsArea.insertAdjacentHTML('beforeend', newRowHtml);
            
            const firstCross = optionsArea.querySelector('.option-row:first-child .btn-remove-opt');
            if(firstCross) firstCross.style.visibility = 'visible';
        }

        function ubahTipeSoal(selectElement, id) {
            const val = selectElement.value;
            const optArea = document.getElementById(`optionsArea_${id}`);
            const btnAdd = document.getElementById(`addOptBtn_${id}`);
            
            if(val === 'singkat' || val === 'paragraf') {
                let pText = val === 'singkat' ? 'Teks jawaban singkat' : 'Teks jawaban panjang';
                optArea.innerHTML = `
                    <div class="text-answer-preview">${pText}</div>
                    <div class="mt-3 bg-success bg-opacity-10 p-3 rounded border border-success-subtle w-75">
                        <label class="fw-bold text-success small mb-1"><i class="bi bi-key-fill"></i> Kunci Jawaban (Opsional)</label>
                        <input type="text" name="soal[${id}][kunci_teks]" class="form-control border-success shadow-sm" placeholder="Ketik jawaban yang benar di sini...">
                    </div>
                `;
                btnAdd.classList.add('d-none');
            } else {
                optArea.innerHTML = '';
                btnAdd.classList.remove('d-none');
                
                let shapeClass = ''; let inputType = 'radio'; let inputName = `soal[${id}][kunci_opsi]`;
                if(val === 'checkbox') { shapeClass = 'square'; inputType = 'checkbox'; inputName = `soal[${id}][kunci_opsi][]`; }
                
                const newRowHtml = `
                    <div class="option-row">
                        <input type="${inputType}" name="${inputName}" value="0" class="form-check-input key-answer-radio me-2 shadow-none" required>
                        <span class="option-marker ${shapeClass} opt-icon_${id}">${val === 'dropdown' ? '1.' : ''}</span>
                        <input type="text" name="soal[${id}][opsi][]" class="option-input" placeholder="Opsi 1" required>
                        <button type="button" class="btn-remove-opt" style="visibility:hidden;"><i class="bi bi-x"></i></button>
                    </div>
                `;
                optArea.innerHTML = newRowHtml;
            }
        }

        function previewGambarSoal(input, id) {
            const file = input.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById(`img_${id}`).src = e.target.result;
                    document.getElementById(`previewBox_${id}`).classList.remove('d-none');
                }
                reader.readAsDataURL(file);
            }
        }

        function hapusGambarSoal(id) {
            document.getElementById(`img_${id}`).src = "";
            document.getElementById(`previewBox_${id}`).classList.add('d-none');
            const fileInput = document.querySelector(`#q_card_${id} input[type="file"]`);
            if(fileInput) fileInput.value = '';
        }
    </script>
</body>
</html>