<?php
require '../login/koneksi.php';

$kelas = mysqli_real_escape_string($koneksi, $_GET['kelas']);
$query = mysqli_query($koneksi, "SELECT * FROM siswa WHERE Kelas = '$kelas' ORDER BY NamaSiswa ASC");

if(mysqli_num_rows($query) == 0) {
    echo '<tr><td colspan="2" class="text-center text-muted py-4 small">Belum ada data siswa di kelas ini.</td></tr>';
} else {
    while($row = mysqli_fetch_assoc($query)) {
        echo '<tr>';
        echo '<td><div class="fw-semibold text-dark">' . htmlspecialchars($row['NamaSiswa']) . '</div></td>';
        echo '<td class="text-center">
                <button type="button" class="btn btn-link text-danger p-0" onclick="konfirmasiKeluarkan(\'' . $row['IDSiswa'] . '\', \'' . addslashes($row['NamaSiswa']) . '\', \'' . $kelas . '\')">
                    <i class="bi bi-trash3-fill fs-5"></i>
                </button>
              </td>';
        echo '</tr>';
    }
}
?>