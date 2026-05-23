<?php
session_start();
require '../login/koneksi.php';
if(!isset($_SESSION['role'])||$_SESSION['role']!='guru'){header("Location: ../login/login.php");exit;}
$id_mapel=mysqli_real_escape_string($koneksi,$_GET['id_mapel']??'');
$nama_mapel='Mata Pelajaran';
if($id_mapel){$r=mysqli_fetch_assoc(mysqli_query($koneksi,"SELECT NamaMapel FROM mapel WHERE IDMapel='$id_mapel'"));if($r)$nama_mapel=$r['NamaMapel'];}
$default_dl=date('Y-m-d\T23:59',strtotime('+1 day'));
?>

<!DOCTYPE html><html lang="id">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Buat Penugasan – LMS</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
<style>
:root{--primary:#4f46e5;--grad:linear-gradient(135deg,#4f46e5,#3730a3);}
*{font-family:'Segoe UI',system-ui,sans-serif;}
body{background:#f0f2f8;}
.topbar{background:linear-gradient(135deg,#059669,#047857);color:#fff;padding:0 16px;height:56px;display:flex;align-items:center;gap:12px;position:sticky;top:0;z-index:100;box-shadow:0 2px 10px rgba(0,0,0,.2);}
.topbar a{color:#fff;text-decoration:none;}
.card-form{background:#fff;border-radius:20px;padding:28px;box-shadow:0 4px 20px rgba(0,0,0,.06);}
.form-label{font-weight:700;font-size:.78rem;color:#64748b;text-transform:uppercase;letter-spacing:.5px;}
.form-control,textarea{border-radius:10px;background:#f8fafc;border:2px solid #e8edf5;padding:11px 14px;font-size:.88rem;transition:.3s;}
.form-control:focus,textarea:focus{background:#fff;border-color:#059669;box-shadow:none;}
.btn-submit{background:linear-gradient(135deg,#059669,#047857);color:#fff;border-radius:12px;padding:13px;font-weight:700;border:none;transition:.3s;width:100%;}
.btn-submit:hover{transform:translateY(-2px);box-shadow:0 6px 15px rgba(5,150,105,.3);color:#fff;}
.poin-card{background:#f0fdf4;border:2px solid #bbf7d0;border-radius:14px;padding:16px;text-align:center;}
.tip-box{background:#eff6ff;border-left:4px solid #6366f1;border-radius:0 10px 10px 0;padding:10px 14px;font-size:.78rem;color:#3730a3;}
</style></head><body>
<div class="topbar">
    <a href="kelolaMapel.php?id_mapel=<?= htmlspecialchars($id_mapel) ?>&tab=tugas"><i class="bi bi-arrow-left fs-5"></i></a>
    <div><div style="font-weight:700;font-size:.92rem;">Buat Penugasan Baru</div><div style="font-size:.72rem;opacity:.8;"><?= htmlspecialchars($nama_mapel) ?></div></div>
</div>
<div class="container py-4" style="max-width:580px;">
<div class="card-form">
    <h5 class="fw-bold mb-4"><i class="bi bi-journal-plus text-success me-2"></i>Penugasan Baru</h5>
    <form action="prosesupTugas.php" method="POST" id="frmTugas">
        <input type="hidden" name="id_mapel" value="<?= htmlspecialchars($id_mapel) ?>">
        <div class="mb-3">
            <label class="form-label">Judul Tugas <span class="text-danger">*</span></label>
            <input type="text" name="judul" class="form-control" placeholder="Contoh: Analisis Jurnal Umum Bulan April" required maxlength="100">
        </div>
        <div class="mb-3">
            <label class="form-label">Instruksi / Deskripsi</label>
            <textarea name="deskripsi" class="form-control" rows="4" maxlength="500" id="deskInput"
                      placeholder="Tuliskan instruksi pengerjaan tugas secara detail..."></textarea>
            <div class="text-end mt-1" style="font-size:.72rem;color:#94a3b8;"><span id="cc">0</span>/500</div>
        </div>
        <div class="row g-3 mb-3">
            <div class="col-7">
                <label class="form-label">Deadline <span class="text-danger">*</span></label>
                <input type="datetime-local" name="deadline" class="form-control" required
                       value="<?= $default_dl ?>" min="<?= date('Y-m-d\TH:i') ?>">
            </div>
            <div class="col-5">
                <label class="form-label">Poin Maksimal</label>
                <input type="number" name="poin_maksimal" class="form-control" id="poinInput"
                       value="100" min="10" max="1000" step="10">
            </div>
        </div>
        <div class="poin-card mb-3">
            <div style="font-size:.74rem;color:#64748b;font-weight:600;">Poin akan diberikan</div>
            <div id="poinPreview" style="font-size:2.2rem;font-weight:800;color:#059669;line-height:1.2;">100</div>
            <div style="font-size:.72rem;color:#64748b;">untuk nilai sempurna</div>
        </div>
        <div class="tip-box mb-4">
            <i class="bi bi-info-circle me-2"></i>
            Siswa yang mengumpulkan <strong>tepat waktu</strong> otomatis mendapat <strong>bonus poin gamifikasi</strong>.
        </div>
        <div class="d-grid gap-2">
            <button type="submit" class="btn-submit btn" id="btnS"><i class="bi bi-send me-2"></i>Publikasikan Tugas</button>
            <a href="kelolaMapel.php?id_mapel=<?= htmlspecialchars($id_mapel) ?>&tab=tugas" class="btn btn-light border" style="border-radius:12px;">Batal</a>
        </div>
    </form>
</div></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('deskInput').addEventListener('input',function(){document.getElementById('cc').textContent=this.value.length;});
document.getElementById('poinInput').addEventListener('input',function(){document.getElementById('poinPreview').textContent=this.value||'0';});
document.getElementById('frmTugas').addEventListener('submit',function(){const b=document.getElementById('btnS');b.innerHTML='<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...';b.disabled=true;});
</script></body></html>