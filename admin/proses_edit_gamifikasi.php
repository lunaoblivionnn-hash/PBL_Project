<?php
session_start();
require '../login/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // =====================================================================
    // 1. UPDATE KE TABEL 'master_aturan_poin'
    // =====================================================================
    if (isset($_POST['rules']) && is_array($_POST['rules'])) {
        foreach ($_POST['rules'] as $id_aturan => $poin_value) {
            $id_aman   = mysqli_real_escape_string($koneksi, $id_aturan);
            $poin_aman = intval($poin_value);
            
            $query_update_rule = "UPDATE master_aturan_poin SET BesaranPoin = '$poin_aman' WHERE IDAturan = '$id_aman'";
            mysqli_query($koneksi, $query_update_rule);
        }
    }

    // =====================================================================
    // 2. UPDATE KE TABEL 'master_level' 
    // =====================================================================
    if (isset($_POST['ranks']) && is_array($_POST['ranks'])) {
        foreach ($_POST['ranks'] as $id_level => $rank_data) {
            $id_lvl_aman = mysqli_real_escape_string($koneksi, $id_level);
            $gelar_aman  = mysqli_real_escape_string($koneksi, $rank_data['gelar']);
            $poin_aman   = intval($rank_data['poin']);
            
            $query_update_rank = "UPDATE master_level SET Gelar = '$gelar_aman', BatasPoin = '$poin_aman' WHERE IDLevel = '$id_lvl_aman'";
            mysqli_query($koneksi, $query_update_rank);
        }
    }

    header("Location: aturanGamifikasi.php?status=sukses_update");
    exit;

} else {
    header("Location: aturanGamifikasi.php");
    exit;
}
?>