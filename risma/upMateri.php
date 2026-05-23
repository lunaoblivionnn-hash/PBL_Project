<?php
session_start();
require '../login/koneksi.php';
if(!isset($_SESSION['role'])||$_SESSION['role']!='guru'){header("Location: ../login/login.php");exit;}
$id_mapel = mysqli_real_escape_string($koneksi,$_GET['id_mapel']??'');
$nama_mapel='Mata Pelajaran';
if($id_mapel){$r=mysqli_fetch_assoc(mysqli_query($koneksi,"SELECT NamaMapel FROM mapel WHERE IDMapel='$id_mapel'"));if($r)$nama_mapel=$r['NamaMapel'];}
?>
<!DOCTYPE html><html lang="id">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Upload Materi – LMS</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
<style>
:root{--primary:#4f46e5;--grad:linear-gradient(135deg,#4f46e5,#3730a3);}
*{font-family:'Segoe UI',system-ui,sans-serif;}
body{background:#f0f2f8;}
.topbar{background:var(--grad);color:#fff;padding:0 16px;height:56px;display:flex;align-items:center;gap:12px;position:sticky;top:0;z-index:100;box-shadow:0 2px 10px rgba(0,0,0,.2);}
.topbar a{color:#fff;text-decoration:none;}
.card-form{background:#fff;border-radius:20px;padding:28px;box-shadow:0 4px 20px rgba(0,0,0,.06);}
.form-label{font-weight:700;font-size:.78rem;color:#64748b;text-transform:uppercase;letter-spacing:.5px;}
.form-control,textarea{border-radius:10px;background:#f8fafc;border:2px solid #e8edf5;padding:11px 14px;font-size:.88rem;transition:.3s;}
.form-control:focus,textarea:focus{background:#fff;border-color:var(--primary);box-shadow:none;}
.upload-zone{border:2.5px dashed #c7d2fe;border-radius:14px;padding:30px 20px;text-align:center;cursor:pointer;transition:.3s;background:#f8fafc;}
.upload-zone:hover,.upload-zone.over{border-color:var(--primary);background:#eff6ff;}
.btn-submit{background:var(--grad);color:#fff;border-radius:12px;padding:13px;font-weight:700;border:none;transition:.3s;width:100%;}
.btn-submit:hover{transform:translateY(-2px);box-shadow:0 6px 15px rgba(79,70,229,.3);color:#fff;}
.file-preview{background:#eff6ff;border-radius:10px;padding:10px 14px;display:none;align-items:center;gap:10px;margin-top:10px;}
.file-preview.show{display:flex;}
.type-badge{background:#e0e7ff;color:#4338ca;font-size:.68rem;padding:2px 8px;border-radius:6px;font-weight:700;}
</style></head><body>
<div class="topbar">
    <a href="kelolaMapel.php?id_mapel=<?= htmlspecialchars($id_mapel) ?>&tab=materi"><i class="bi bi-arrow-left fs-5"></i></a>
    <div><div style="font-weight:700;font-size:.92rem;">Upload Materi Baru</div><div style="font-size:.72rem;opacity:.75;"><?= htmlspecialchars($nama_mapel) ?></div></div>
</div>
<div class="container py-4" style="max-width:560px;">
<div class="card-form">
    <h5 class="fw-bold mb-4"><i class="bi bi-file-earmark-plus text-primary me-2"></i>Materi Baru</h5>
    <form action="prosesupMateri.php" method="POST" enctype="multipart/form-data" id="frmMateri">
        <input type="hidden" name="id_mapel" value="<?= htmlspecialchars($id_mapel) ?>">
        <div class="mb-3">
            <label class="form-label">Judul Materi <span class="text-danger">*</span></label>
            <input type="text" name="judul" class="form-control" placeholder="Contoh: Pengenalan Akuntansi Dasar" required maxlength="100">
        </div>
        <div class="mb-3">
            <label class="form-label">Deskripsi / Keterangan</label>
            <textarea name="deskripsi" class="form-control" rows="3" maxlength="200" id="deskInput"
                      placeholder="Tulis keterangan singkat..."></textarea>
            <div class="text-end mt-1" style="font-size:.72rem;color:#94a3b8;"><span id="cc">0</span>/200</div>
        </div>
        <div class="mb-4">
            <label class="form-label">File Materi <span class="text-danger">*</span></label>
            <div class="upload-zone" id="zone" onclick="document.getElementById('fi').click()">
                <i class="bi bi-cloud-arrow-up" style="font-size:2.5rem;color:#a5b4fc;"></i>
                <div class="fw-semibold text-muted mt-2 small">Klik atau seret file ke sini</div>
                <div class="mt-2 d-flex justify-content-center flex-wrap gap-1">
                    <span class="type-badge">PDF</span><span class="type-badge">DOC/DOCX</span><span class="type-badge">PPT/PPTX</span>
                    <span class="type-badge">XLS/XLSX</span><span class="type-badge">JPG/PNG</span><span class="type-badge">MP4</span>
                </div>
                <div class="text-muted mt-1" style="font-size:.72rem;">Maks. 50MB</div>
                <input type="file" name="materi_file" id="fi" class="d-none" required
                       accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.jpg,.jpeg,.png,.mp4"
                       onchange="showFile(this)">
            </div>
            <div class="file-preview" id="fp">
                <i class="bi bi-file-earmark-check text-success fs-4"></i>
                <div><div id="fn" class="fw-semibold" style="font-size:.85rem;"></div><div id="fs" class="text-muted" style="font-size:.72rem;"></div></div>
                <button type="button" class="ms-auto btn btn-sm btn-light" onclick="clearFile()"><i class="bi bi-x"></i></button>
            </div>
        </div>
        <div class="d-grid gap-2">
            <button type="submit" class="btn-submit btn" id="btnS"><i class="bi bi-cloud-upload me-2"></i>Simpan Materi</button>
            <a href="kelolaMapel.php?id_mapel=<?= htmlspecialchars($id_mapel) ?>&tab=materi" class="btn btn-light border" style="border-radius:12px;">Batal</a>
        </div>
    </form>
</div></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('deskInput').addEventListener('input',function(){document.getElementById('cc').textContent=this.value.length;});
function showFile(i){const f=i.files[0];if(!f)return;if(f.size>50*1024*1024){alert('File melebihi 50MB!');i.value='';return;}
document.getElementById('fn').textContent=f.name;document.getElementById('fs').textContent=(f.size/1024/1024).toFixed(2)+' MB';
document.getElementById('fp').classList.add('show');document.getElementById('zone').style.opacity='.6';}
function clearFile(){document.getElementById('fi').value='';document.getElementById('fp').classList.remove('show');document.getElementById('zone').style.opacity='1';}
const z=document.getElementById('zone');
z.addEventListener('dragover',e=>{e.preventDefault();z.classList.add('over');});
z.addEventListener('dragleave',()=>z.classList.remove('over'));
z.addEventListener('drop',e=>{e.preventDefault();z.classList.remove('over');document.getElementById('fi').files=e.dataTransfer.files;showFile(document.getElementById('fi'));});
document.getElementById('frmMateri').addEventListener('submit',function(){const b=document.getElementById('btnS');b.innerHTML='<span class="spinner-border spinner-border-sm me-2"></span>Mengupload...';b.disabled=true;});
</script></body></html>