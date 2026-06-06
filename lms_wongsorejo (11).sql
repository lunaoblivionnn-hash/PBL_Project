SET FOREIGN_KEY_CHECKS = 0;

-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 06 Jun 2026 pada 09.17
-- Versi server: 8.0.45
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `lms_wongsorejo`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `gamifikasi`
--

CREATE TABLE `gamifikasi` (
  `IDGamifikasi` char(5) NOT NULL,
  `IDSiswa` char(5) DEFAULT NULL,
  `IDLevel` char(5) DEFAULT NULL,
  `TotalPoint` mediumint DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `guru`
--

CREATE TABLE `guru` (
  `IDGuru` char(5) NOT NULL,
  `IDUser` char(5) DEFAULT NULL,
  `NamaGuru` varchar(60) NOT NULL,
  `NIP_NUPTK` char(18) DEFAULT NULL,
  `Email` varchar(100) DEFAULT NULL,
  `NoTelp` varchar(20) DEFAULT NULL,
  `MataPelajaran` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `guru`
--

INSERT INTO `guru` (`IDGuru`, `IDUser`, `NamaGuru`, `NIP_NUPTK`, `Email`, `NoTelp`, `MataPelajaran`) VALUES
('GR001', 'US032', 'HADI APRIANTO', '198503172010011005', 'rezahuditama@gmail.com', '0851 3254 3725', '{\"XI AKL 1\":[\"Akuntansi Keuangan\"],\"XI AKL 2\":[\"Akuntansi Keuangan\"]}'),
('GR002', 'US033', 'NUR AFIFAH', '199211242019022003', 'ekokurniady187@gmail.com', '0851 3763 8746', '[]'),
('GR003', 'US034', 'SRI WAHYUNI', '198703042007012008', 'sriwahyuni122@gmail.com', '0852 5346 3234', '{\"XI AKL 1\":[\"Akuntansi Pemerintahan\"],\"XI AKL 2\":[\"Akuntansi Pemerintahan\"]}'),
('GR004', 'US035', 'ELIN CHOLIFAH', '199211292019022025', 'elincholifah87@gmail.com', '0852 2367 1408', '{\"XI AKL 1\":[\"Akuntansi Perpajakan\"],\"XI AKL 2\":[\"Akuntansi Perpajakan\"]}'),
('GR005', 'US036', 'ADRIAN ZAINI', '198503242010011079', 'adrianzaini@gmail.com', '0853 3564 2478', '{\"XI AKL 1\":[\"Akuntansi Manufaktur\"],\"XI AKL 2\":[\"Akuntansi Manufaktur\"]}'),
('GR006', 'US037', 'AGUS SOESIANTO', '199503112010011033', 'agussoesianto@gmail.com', '0853 8463 3455', '[]');

-- --------------------------------------------------------

--
-- Struktur dari tabel `jadwal_siswa`
--

CREATE TABLE `jadwal_siswa` (
  `IDJadwal` int NOT NULL,
  `IDSiswa` char(5) NOT NULL,
  `Hari` varchar(10) NOT NULL,
  `JamMulai` time NOT NULL,
  `JamSelesai` time NOT NULL,
  `Kegiatan` varchar(100) NOT NULL,
  `WarnaLabel` varchar(20) DEFAULT 'primary'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `kuis`
--

CREATE TABLE `kuis` (
  `IDKuis` varchar(10) NOT NULL,
  `IDMapel` varchar(10) NOT NULL,
  `IDTopik` int NOT NULL,
  `Judul` varchar(255) NOT NULL,
  `Deskripsi` text,
  `DurasiMenit` int DEFAULT '60',
  `Deadline` datetime DEFAULT NULL,
  `TanggalDibuat` datetime NOT NULL,
  `Status` enum('Draft','Published') DEFAULT 'Published'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `kuis_jawaban`
--

CREATE TABLE `kuis_jawaban` (
  `IDJawaban` int NOT NULL,
  `IDNilai` int NOT NULL,
  `IDSoal` int NOT NULL,
  `IDOpsi` int DEFAULT NULL,
  `JawabanTeks` text,
  `IsBenar` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `kuis_nilai`
--

CREATE TABLE `kuis_nilai` (
  `IDNilai` int NOT NULL,
  `IDKuis` varchar(10) NOT NULL,
  `IDSiswa` char(5) NOT NULL,
  `WaktuMulai` datetime NOT NULL,
  `WaktuSelesai` datetime DEFAULT NULL,
  `Benar` int DEFAULT '0',
  `Salah` int DEFAULT '0',
  `NilaiAkhir` float DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `kuis_opsi`
--

CREATE TABLE `kuis_opsi` (
  `IDOpsi` int NOT NULL,
  `IDSoal` int NOT NULL,
  `TeksOpsi` varchar(255) NOT NULL,
  `IsBenar` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `kuis_soal`
--

CREATE TABLE `kuis_soal` (
  `IDSoal` int NOT NULL,
  `IDKuis` varchar(10) NOT NULL,
  `TipeSoal` enum('pilgan','checkbox','dropdown','singkat','paragraf') NOT NULL,
  `Pertanyaan` text NOT NULL,
  `Gambar` varchar(255) DEFAULT NULL,
  `Poin` int DEFAULT '10',
  `Wajib` tinyint(1) DEFAULT '1',
  `KunciJawaban` text,
  `Urutan` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `mapel`
--

CREATE TABLE `mapel` (
  `IDMapel` char(5) NOT NULL,
  `IDGuru` char(5) DEFAULT NULL,
  `Kelas` text,
  `TahunAjaran` varchar(20) DEFAULT NULL,
  `NamaMapel` varchar(60) NOT NULL,
  `Deskripsi` text,
  `Gambar` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `mapel`
--

INSERT INTO `mapel` (`IDMapel`, `IDGuru`, `Kelas`, `TahunAjaran`, `NamaMapel`, `Deskripsi`, `Gambar`) VALUES
('MP001', 'IG001', '[\"X AKL 1\",\"X AKL 2\"]', '2025/2026', 'Akuntansi Dasar', 'fundamental akuntansi\r\n', ''),
('MP002', 'GR001', '[\"XI AKL 1\",\"XI AKL 2\"]', '2025/2026', 'Akuntansi Keuangan', 'Mata pelajaran Akuntansi Keuangan berfokus pada pencatatan, pengelompokan, dan penyusunan laporan keuangan yang ditujukan bagi pihak eksternal. Secara umum, materi yang dipelajari mencakup konsep dasar akuntansi hingga penyusunan laporan keuangan secara menyeluruh', ''),
('MP003', 'GR003', '[\"XI AKL 1\",\"XI AKL 2\"]', '2025/2026', 'Akuntansi Pemerintahan', 'Mata pelajaran Akuntansi Pemerintahan mempelajari pengelolaan keuangan negara atau daerah, mulai dari perencanaan anggaran, pencatatan transaksi, hingga penyusunan laporan keuangan instansi pemerintah. Tujuannya adalah memastikan transparansi dan akuntabilitas dana publik', ''),
('MP004', 'GR004', '[\"XI AKL 1\",\"XI AKL 2\"]', '2025/2026', 'Akuntansi Perpajakan', 'Mata pelajaran Akuntansi Perpajakan mempelajari prinsip, konsep, dan tata cara pencatatan keuangan yang disesuaikan dengan undang-undang dan peraturan perpajakan yang berlaku. Fokus utamanya adalah menyusun laporan keuangan komersial menjadi laporan keuangan fiskal', ''),
('MP005', 'GR005', '[\"XI AKL 1\",\"XI AKL 2\"]', '2025/2026', 'Akuntansi Manufaktur', 'Mata pelajaran Akuntansi Manufaktur berfokus pada pelacakan, analisis, dan pengelolaan biaya produksi untuk menghasilkan barang. Pembelajaran mencakup tiga elemen utama biaya produksi, perhitungan Harga Pokok Produksi (HPP), serta pengelolaan tiga jenis persediaan (bahan baku, barang dalam proses, dan barang jadi) dari awal hingga produk siap dijual', ''),
('MP006', NULL, '[\"XI AKL 1\",\"XI AKL 2\"]', '2025/2026', 'Komputer Akuntansi', 'Mapel Komputer Akuntansi (sering disebut MYOB) adalah mata pelajaran yang mempelajari proses pengolahan data transaksi keuangan perusahaan menggunakan software komputer khusus. Tujuannya agar pencatatan lebih cepat, akurat, dan otomatis menghasilkan laporan keuangan', '');

-- --------------------------------------------------------

--
-- Struktur dari tabel `master_aturan_poin`
--

CREATE TABLE `master_aturan_poin` (
  `IDAturan` char(5) NOT NULL,
  `JenisAktivitas` varchar(50) DEFAULT NULL,
  `BesaranPoin` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `master_aturan_poin`
--

INSERT INTO `master_aturan_poin` (`IDAturan`, `JenisAktivitas`, `BesaranPoin`) VALUES
('AT001', 'Baca Materi', 20),
('AT002', 'Nilai Tugas', 100),
('AT003', 'Bonus Kilat', 50),
('AT004', 'Bonus Cepat', 20),
('AT005', 'Bonus Disiplin', 10),
('AT006', 'Bonus Sempurna', 20);

-- --------------------------------------------------------

--
-- Struktur dari tabel `master_level`
--

CREATE TABLE `master_level` (
  `IDLevel` char(5) NOT NULL,
  `BatasPoin` mediumint DEFAULT NULL,
  `LevelAngka` tinyint DEFAULT NULL,
  `Gelar` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `master_level`
--

INSERT INTO `master_level` (`IDLevel`, `BatasPoin`, `LevelAngka`, `Gelar`) VALUES
('LV001', 0, 1, 'Beginner Accountant'),
('LV002', 100, 2, 'Scholar Accountant I'),
('LV003', 200, 3, 'Scholar Accountant II'),
('LV004', 300, 4, 'Veteran Accountant I'),
('LV005', 400, 5, 'Veteran Accountant II'),
('LV006', 500, 6, 'Expert Accountant I'),
('LV007', 600, 7, 'Expert Accountant II'),
('LV008', 700, 8, 'Master Accountant I'),
('LV009', 800, 9, 'Master Accountant II'),
('LV010', 900, 10, 'Grand Master Accountant I'),
('LV011', 1000, 11, 'Grand Master Accountant II'),
('LV012', 1100, 12, 'Grand Master Accountant III'),
('LV013', 1200, 13, 'Challenger Accountant');

-- --------------------------------------------------------

--
-- Struktur dari tabel `materi`
--

CREATE TABLE `materi` (
  `IDMateri` char(5) NOT NULL,
  `IDMapel` char(5) DEFAULT NULL,
  `IDTopik` int DEFAULT NULL,
  `Judul` varchar(100) NOT NULL,
  `Deskripsi` varchar(200) DEFAULT NULL,
  `Filepath` varchar(255) DEFAULT NULL,
  `TipeFile` varchar(10) DEFAULT NULL,
  `TanggalUpload` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `notifikasi`
--

CREATE TABLE `notifikasi` (
  `IDNotif` int NOT NULL,
  `IDUser` char(5) DEFAULT NULL,
  `JudulNotif` varchar(100) DEFAULT NULL,
  `Pesan` text,
  `IsRead` tinyint(1) DEFAULT '0',
  `CreatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `pengaturan`
--

CREATE TABLE `pengaturan` (
  `Kunci` varchar(50) NOT NULL,
  `Nilai` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `pengaturan`
--

INSERT INTO `pengaturan` (`Kunci`, `Nilai`) VALUES
('maintenance', '0');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pengumpulan_tugas`
--

CREATE TABLE `pengumpulan_tugas` (
  `IDPengumpulan` int NOT NULL,
  `IDTugas` char(5) DEFAULT NULL,
  `IDSiswa` char(5) DEFAULT NULL,
  `FileJawaban` varchar(255) DEFAULT NULL,
  `TanggalKirim` datetime DEFAULT NULL,
  `Nilai` int DEFAULT NULL,
  `Status` enum('belum_dinilai','sudah_dinilai','terlambat') DEFAULT NULL,
  `KomentarGuru` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `quotes`
--

CREATE TABLE `quotes` (
  `IDQuote` int NOT NULL,
  `TeksQuote` text NOT NULL,
  `Tokoh` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `quotes`
--

INSERT INTO `quotes` (`IDQuote`, `TeksQuote`, `Tokoh`) VALUES
(1, 'Pendidikan adalah senjata paling mematikan di dunia, karena dengannya Anda dapat mengubah dunia.', 'Nelson Mandela'),
(2, 'Orang bijak belajar ketika mereka bisa. Orang bodoh belajar ketika mereka terpaksa.', 'Arthur Wellesley'),
(3, 'Jangan pernah berhenti belajar, karena hidup tak pernah berhenti mengajarkan.', 'Anonim'),
(4, 'Hiduplah seolah engkau mati besok. Belajarlah seolah engkau hidup selamanya.', 'Mahatma Gandhi'),
(5, 'Masa depan adalah milik mereka yang menyiapkan hari ini.', 'Malcolm X'),
(6, 'Pendidikan bukanlah proses mengisi wadah yang kosong, melainkan menyalakan api pikiran.', 'William Butler Yeats');

-- --------------------------------------------------------

--
-- Struktur dari tabel `riwayat_poin`
--

CREATE TABLE `riwayat_poin` (
  `IDRiwayat` int NOT NULL,
  `IDSiswa` char(5) DEFAULT NULL,
  `IDPengumpulan` int DEFAULT NULL,
  `IDAturan` char(5) DEFAULT NULL,
  `TanggalWaktu` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `siswa`
--

CREATE TABLE `siswa` (
  `IDSiswa` char(5) NOT NULL,
  `IDUser` char(5) DEFAULT NULL,
  `NamaSiswa` varchar(60) NOT NULL,
  `NISN` char(10) DEFAULT NULL,
  `Kelas` varchar(20) DEFAULT NULL,
  `Email` varchar(100) DEFAULT NULL,
  `NoTelp` varchar(20) DEFAULT NULL,
  `dibuat_pada` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `FotoProfil` varchar(255) DEFAULT NULL,
  `Bio` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `siswa`
--

INSERT INTO `siswa` (`IDSiswa`, `IDUser`, `NamaSiswa`, `NISN`, `Kelas`, `Email`, `NoTelp`, `dibuat_pada`, `FotoProfil`, `Bio`) VALUES
('IS001', 'US001', 'BUNGA HENDRYANDA RAMADHANI', '3625583020', 'XI AKL 1', 'bunga117@gmail.com', '0812 3456 7001', '2026-05-30 09:36:31', NULL, NULL),
('IS002', 'US002', 'AHMAD ILZAM ZAUQI RAMADAN', '3625583021', 'XI AKL 1', 'ahmadilzam12@gmail.com', '0813 3457 7002', '2026-05-30 09:36:31', NULL, NULL),
('IS003', 'US003', 'DEWI MAHIYATUL KHABIBAH', '3625583022', 'XI AKL 1', 'dewimahiya29@gmail.com', '0813 3458 7003', '2026-05-30 09:36:31', NULL, NULL),
('IS004', 'US004', 'AFIFA NUR FITRIA', '3625583023', 'XI AKL 1', 'afifanur77@gmail.com', '0814 3457 7004', '2026-05-30 09:36:31', NULL, NULL),
('IS005', 'US005', 'JUWITA SRI WAHYU NINGSIH', '3625583024', 'XI AKL 1', 'juwitaswn@gmail.com', '0814 3458 7005', '2026-05-30 09:36:31', NULL, NULL),
('IS006', 'US006', 'RAIDO OCTAVIANDY', '3625583025', 'XI AKL 1', 'raido2006@gmail.com', '', '2026-05-30 09:36:31', NULL, NULL),
('IS007', 'US007', 'RISMA SETIO MUHTAFIROH', '3625583026', 'XI AKL 1', 'rismasetyoa04@gmail.com', '0815 3458 7007', '2026-05-30 09:36:31', NULL, NULL),
('IS008', 'US008', 'CAHAYA WULANDARI', '3625583027', 'XI AKL 1', 'cahayawulandari@gmail.com', '0816 3457 7008', '2026-05-30 09:36:31', NULL, NULL),
('IS009', 'US009', 'MOCH RIZKI AGUNG', '3625583028', 'XI AKL 1', 'mochrizkiagung@gmail.com', '0816 3458 7009', '2026-05-30 09:36:31', NULL, NULL),
('IS010', 'US010', 'SHINTA NURIA', '3625583029', 'XI AKL 1', '', '0817 3457 7010', '2026-05-30 09:36:31', NULL, NULL),
('IS011', 'US011', 'FASA UFA FIAUNILLA', '3625583030', 'XI AKL 1', '', '0817 3458 7011', '2026-05-30 09:36:31', NULL, NULL),
('IS012', 'US012', 'MOH FAUZI FIRDAUS', '3625583031', 'XI AKL 1', '', '0818 3457 7012', '2026-05-30 09:36:31', NULL, NULL),
('IS013', 'US013', 'ADAM MARCHELINO', '3625583032', 'XI AKL 1', 'marchelino02@gmail.com', '0818 3458 7013', '2026-05-30 09:36:31', NULL, NULL),
('IS014', 'US014', 'YUDISTA APRILIO RAMI FIRMANSYA', '3625583033', 'XI AKL 1', 'yudista2007@gmail.com', '', '2026-05-30 09:36:31', NULL, NULL),
('IS015', 'US015', 'AYU NANDHA RIZKIE', '3625583034', 'XI AKL 1', 'ayunandha@gmail.com', '', '2026-05-30 09:36:31', NULL, NULL),
('IS016', 'US016', 'RIZKY TRI ANGGARA', '3625583035', 'XI AKL 1', '', '0820 3457 7016', '2026-05-30 09:36:31', NULL, NULL),
('IS017', 'US017', 'KHAIRAN ADIOKTA ARUN NUGRAHA', '3625583036', 'XI AKL 1', '', '0820 3458 7017', '2026-05-30 09:36:31', NULL, NULL),
('IS018', 'US018', 'WILDAN DAFFA AKMAL PUTRA', '3625583037', 'XI AKL 1', 'wildandaffaa@gmail.com', '0821 3457 7018', '2026-05-30 09:36:31', NULL, NULL),
('IS019', 'US019', 'DELA KARTIKA', '3625583038', 'XI AKL 1', 'delakartikaa@gmail.com', '0821 3458 7019', '2026-05-30 09:36:31', NULL, NULL),
('IS020', 'US020', 'ICHWAN AR RAFFY', '3625583039', 'XI AKL 1', 'ichwanara@gmail.com', '0822 3457 7020', '2026-05-30 09:36:31', NULL, NULL),
('IS021', 'US021', 'HENDRA YUDHA PRATAMA', '3625583040', 'XI AKL 1', 'hendrayudhaa@gmail.com', '0822 3458 7021', '2026-05-30 09:36:31', NULL, NULL),
('IS022', 'US022', 'FACHREZA HUDITAMA', '3625583041', 'XI AKL 1', 'fachrezahuditamaa@gmail.com', '0823 3457 7022', '2026-05-30 09:36:31', NULL, NULL),
('IS023', 'US023', 'TAUFIQ HIDAYAT', '3625583042', 'XI AKL 1', 'taufiqhidayata@gmail.com', '', '2026-05-30 09:36:31', NULL, NULL),
('IS024', 'US024', 'DIMAS TRI IBRAHUL GOZI', '3625583043', 'XI AKL 1', 'dimastria@gmail.com', '0824 3457 7024', '2026-05-30 09:36:31', NULL, NULL),
('IS025', 'US025', 'MOHAMMAD SALIM', '3625583044', 'XI AKL 1', 'mohammadsalima@gmail.com', '0824 3458 7025', '2026-05-30 09:36:31', NULL, NULL),
('IS026', 'US026', 'MUHAMMAD HASBIALLAH HABIBI', '3625583045', 'XI AKL 1', '', '', '2026-05-30 09:36:31', NULL, NULL),
('IS027', 'US027', 'MOCH RUSDI', '3625583046', 'XI AKL 1', 'mochrusdia@gmail.com', '0825 3458 7027', '2026-05-30 09:36:31', NULL, NULL),
('IS028', 'US028', 'AYASKA FERNANDO', '3625583047', 'XI AKL 1', 'ayaskafernandoa@gmail.com', '0826 3457 7028', '2026-05-30 09:36:31', NULL, NULL),
('IS029', 'US029', 'HAFIZH ALIF KURNIAWAN', '3625583048', 'XI AKL 1', 'hafizhalifa@gmail.com', '0826 3458 7029', '2026-05-30 09:36:31', NULL, NULL),
('IS030', 'US030', 'GAPAY TALENTA', '3625583049', 'XI AKL 1', 'gapaytalentaa@gmail.com', '0827 3457 7030', '2026-05-30 09:36:31', NULL, NULL),
('IS031', 'US031', 'ERIKO AFRIYANTO', '3625583050', 'XI AKL 1', 'erikoafriyantoa@gmail.com', '0827 3458 7031', '2026-05-30 09:36:31', NULL, NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `topik_mapel`
--

CREATE TABLE `topik_mapel` (
  `IDTopik` int NOT NULL,
  `IDMapel` varchar(10) NOT NULL,
  `Kelas` varchar(50) DEFAULT NULL,
  `NamaTopik` varchar(100) NOT NULL,
  `Urutan` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `topik_mapel`
--

INSERT INTO `topik_mapel` (`IDTopik`, `IDMapel`, `Kelas`, `NamaTopik`, `Urutan`) VALUES
(18, 'MP002', 'XI AKL 1', 'Umum / Pengumuman', 1),
(19, 'MP002', 'XI AKL 1', 'Bab 1: Pendahuluan', 2);

-- --------------------------------------------------------

--
-- Struktur dari tabel `tugas`
--

CREATE TABLE `tugas` (
  `IDTugas` char(5) NOT NULL,
  `IDMapel` char(5) DEFAULT NULL,
  `IDTopik` int DEFAULT NULL,
  `Judul` varchar(30) NOT NULL,
  `Deskripsi` varchar(200) DEFAULT NULL,
  `TanggalDibuat` datetime DEFAULT NULL,
  `Deadline` datetime DEFAULT NULL,
  `TipeFileDiizinkan` varchar(255) DEFAULT NULL,
  `PoinMaksimal` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `IDUser` char(5) NOT NULL,
  `Username` varchar(18) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `Role` enum('admin','guru','siswa') NOT NULL,
  `LastAccess` datetime DEFAULT NULL,
  `WajibUbahPassword` tinyint(1) DEFAULT '0',
  `Status` enum('Aktif','Non-Aktif') DEFAULT 'Aktif'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`IDUser`, `Username`, `Password`, `Role`, `LastAccess`, `WajibUbahPassword`, `Status`) VALUES
('SU001', 'admin1', 'admin123', 'admin', '2026-05-31 20:52:42', 0, 'Aktif'),
('US001', '3625583020', '123456', 'siswa', NULL, 1, 'Aktif'),
('US002', '3625583021', '123456', 'siswa', NULL, 1, 'Aktif'),
('US003', '3625583022', '123456', 'siswa', NULL, 1, 'Aktif'),
('US004', '3625583023', '123456', 'siswa', NULL, 1, 'Aktif'),
('US005', '3625583024', '123456', 'siswa', NULL, 1, 'Aktif'),
('US006', '3625583025', '123456', 'siswa', NULL, 1, 'Aktif'),
('US007', '3625583026', '123456', 'siswa', NULL, 1, 'Aktif'),
('US008', '3625583027', '123456', 'siswa', NULL, 1, 'Aktif'),
('US009', '3625583028', '123456', 'siswa', NULL, 1, 'Aktif'),
('US010', '3625583029', '123456', 'siswa', NULL, 1, 'Aktif'),
('US011', '3625583030', '123456', 'siswa', NULL, 1, 'Aktif'),
('US012', '3625583031', '123456', 'siswa', NULL, 1, 'Aktif'),
('US013', '3625583032', '123456', 'siswa', NULL, 1, 'Aktif'),
('US014', '3625583033', '123456', 'siswa', NULL, 1, 'Aktif'),
('US015', '3625583034', '123456', 'siswa', NULL, 1, 'Aktif'),
('US016', '3625583035', '123456', 'siswa', NULL, 1, 'Aktif'),
('US017', '3625583036', '123456', 'siswa', NULL, 1, 'Aktif'),
('US018', '3625583037', '123456', 'siswa', NULL, 1, 'Aktif'),
('US019', '3625583038', '123456', 'siswa', NULL, 1, 'Aktif'),
('US020', '3625583039', '123456', 'siswa', NULL, 1, 'Aktif'),
('US021', '3625583040', '123456', 'siswa', NULL, 1, 'Aktif'),
('US022', '3625583041', '123456', 'siswa', NULL, 1, 'Aktif'),
('US023', '3625583042', '123456', 'siswa', NULL, 1, 'Aktif'),
('US024', '3625583043', '123456', 'siswa', NULL, 1, 'Aktif'),
('US025', '3625583044', '123456', 'siswa', NULL, 1, 'Aktif'),
('US026', '3625583045', '123456', 'siswa', NULL, 1, 'Aktif'),
('US027', '3625583046', '123456', 'siswa', NULL, 1, 'Aktif'),
('US028', '3625583047', '123456', 'siswa', NULL, 1, 'Aktif'),
('US029', '3625583048', '123456', 'siswa', NULL, 1, 'Aktif'),
('US030', '3625583049', '123456', 'siswa', NULL, 1, 'Aktif'),
('US031', '3625583050', '123456', 'siswa', NULL, 1, 'Aktif'),
('US032', '198503172010011005', 'guru123', 'guru', NULL, 0, 'Aktif'),
('US033', '199211242019022003', '123456', 'guru', NULL, 1, 'Aktif'),
('US034', '198703042007012008', '123456', 'guru', NULL, 1, 'Aktif'),
('US035', '199211292019022025', '123456', 'guru', NULL, 1, 'Aktif'),
('US036', '198503242010011079', '123456', 'guru', NULL, 1, 'Aktif'),
('US037', '199503112010011033', '123456', 'guru', NULL, 1, 'Aktif');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `gamifikasi`
--
ALTER TABLE `gamifikasi`
  ADD PRIMARY KEY (`IDGamifikasi`),
  ADD KEY `IDSiswa` (`IDSiswa`),
  ADD KEY `IDLevel` (`IDLevel`);

--
-- Indeks untuk tabel `guru`
--
ALTER TABLE `guru`
  ADD PRIMARY KEY (`IDGuru`),
  ADD KEY `IDUser` (`IDUser`);

--
-- Indeks untuk tabel `jadwal_siswa`
--
ALTER TABLE `jadwal_siswa`
  ADD PRIMARY KEY (`IDJadwal`);

--
-- Indeks untuk tabel `kuis`
--
ALTER TABLE `kuis`
  ADD PRIMARY KEY (`IDKuis`);

--
-- Indeks untuk tabel `kuis_jawaban`
--
ALTER TABLE `kuis_jawaban`
  ADD PRIMARY KEY (`IDJawaban`),
  ADD KEY `IDNilai` (`IDNilai`),
  ADD KEY `IDSoal` (`IDSoal`);

--
-- Indeks untuk tabel `kuis_nilai`
--
ALTER TABLE `kuis_nilai`
  ADD PRIMARY KEY (`IDNilai`),
  ADD KEY `IDKuis` (`IDKuis`),
  ADD KEY `IDSiswa` (`IDSiswa`);

--
-- Indeks untuk tabel `kuis_opsi`
--
ALTER TABLE `kuis_opsi`
  ADD PRIMARY KEY (`IDOpsi`),
  ADD KEY `IDSoal` (`IDSoal`);

--
-- Indeks untuk tabel `kuis_soal`
--
ALTER TABLE `kuis_soal`
  ADD PRIMARY KEY (`IDSoal`),
  ADD KEY `IDKuis` (`IDKuis`);

--
-- Indeks untuk tabel `mapel`
--
ALTER TABLE `mapel`
  ADD PRIMARY KEY (`IDMapel`),
  ADD KEY `IDGuru` (`IDGuru`);

--
-- Indeks untuk tabel `master_aturan_poin`
--
ALTER TABLE `master_aturan_poin`
  ADD PRIMARY KEY (`IDAturan`);

--
-- Indeks untuk tabel `master_level`
--
ALTER TABLE `master_level`
  ADD PRIMARY KEY (`IDLevel`);

--
-- Indeks untuk tabel `materi`
--
ALTER TABLE `materi`
  ADD PRIMARY KEY (`IDMateri`),
  ADD KEY `IDMapel` (`IDMapel`);

--
-- Indeks untuk tabel `notifikasi`
--
ALTER TABLE `notifikasi`
  ADD PRIMARY KEY (`IDNotif`),
  ADD KEY `IDUser` (`IDUser`);

--
-- Indeks untuk tabel `pengaturan`
--
ALTER TABLE `pengaturan`
  ADD PRIMARY KEY (`Kunci`);

--
-- Indeks untuk tabel `pengumpulan_tugas`
--
ALTER TABLE `pengumpulan_tugas`
  ADD PRIMARY KEY (`IDPengumpulan`),
  ADD KEY `IDTugas` (`IDTugas`),
  ADD KEY `IDSiswa` (`IDSiswa`);

--
-- Indeks untuk tabel `quotes`
--
ALTER TABLE `quotes`
  ADD PRIMARY KEY (`IDQuote`);

--
-- Indeks untuk tabel `riwayat_poin`
--
ALTER TABLE `riwayat_poin`
  ADD PRIMARY KEY (`IDRiwayat`),
  ADD KEY `IDSiswa` (`IDSiswa`),
  ADD KEY `IDPengumpulan` (`IDPengumpulan`),
  ADD KEY `IDAturan` (`IDAturan`);

--
-- Indeks untuk tabel `siswa`
--
ALTER TABLE `siswa`
  ADD PRIMARY KEY (`IDSiswa`),
  ADD KEY `IDUser` (`IDUser`);

--
-- Indeks untuk tabel `topik_mapel`
--
ALTER TABLE `topik_mapel`
  ADD PRIMARY KEY (`IDTopik`);

--
-- Indeks untuk tabel `tugas`
--
ALTER TABLE `tugas`
  ADD PRIMARY KEY (`IDTugas`),
  ADD KEY `IDMapel` (`IDMapel`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`IDUser`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `jadwal_siswa`
--
ALTER TABLE `jadwal_siswa`
  MODIFY `IDJadwal` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `kuis_jawaban`
--
ALTER TABLE `kuis_jawaban`
  MODIFY `IDJawaban` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `kuis_nilai`
--
ALTER TABLE `kuis_nilai`
  MODIFY `IDNilai` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `kuis_opsi`
--
ALTER TABLE `kuis_opsi`
  MODIFY `IDOpsi` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `kuis_soal`
--
ALTER TABLE `kuis_soal`
  MODIFY `IDSoal` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `notifikasi`
--
ALTER TABLE `notifikasi`
  MODIFY `IDNotif` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `pengumpulan_tugas`
--
ALTER TABLE `pengumpulan_tugas`
  MODIFY `IDPengumpulan` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `quotes`
--
ALTER TABLE `quotes`
  MODIFY `IDQuote` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `riwayat_poin`
--
ALTER TABLE `riwayat_poin`
  MODIFY `IDRiwayat` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `topik_mapel`
--
ALTER TABLE `topik_mapel`
  MODIFY `IDTopik` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `gamifikasi`
--
ALTER TABLE `gamifikasi`
  ADD CONSTRAINT `gamifikasi_ibfk_1` FOREIGN KEY (`IDSiswa`) REFERENCES `siswa` (`IDSiswa`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `gamifikasi_ibfk_2` FOREIGN KEY (`IDLevel`) REFERENCES `master_level` (`IDLevel`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `guru`
--
ALTER TABLE `guru`
  ADD CONSTRAINT `guru_ibfk_1` FOREIGN KEY (`IDUser`) REFERENCES `users` (`IDUser`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `kuis_jawaban`
--
ALTER TABLE `kuis_jawaban`
  ADD CONSTRAINT `kuis_jawaban_ibfk_1` FOREIGN KEY (`IDNilai`) REFERENCES `kuis_nilai` (`IDNilai`) ON DELETE CASCADE,
  ADD CONSTRAINT `kuis_jawaban_ibfk_2` FOREIGN KEY (`IDSoal`) REFERENCES `kuis_soal` (`IDSoal`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `kuis_nilai`
--
ALTER TABLE `kuis_nilai`
  ADD CONSTRAINT `kuis_nilai_ibfk_1` FOREIGN KEY (`IDKuis`) REFERENCES `kuis` (`IDKuis`) ON DELETE CASCADE,
  ADD CONSTRAINT `kuis_nilai_ibfk_2` FOREIGN KEY (`IDSiswa`) REFERENCES `siswa` (`IDSiswa`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `kuis_opsi`
--
ALTER TABLE `kuis_opsi`
  ADD CONSTRAINT `kuis_opsi_ibfk_1` FOREIGN KEY (`IDSoal`) REFERENCES `kuis_soal` (`IDSoal`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `kuis_soal`
--
ALTER TABLE `kuis_soal`
  ADD CONSTRAINT `kuis_soal_ibfk_1` FOREIGN KEY (`IDKuis`) REFERENCES `kuis` (`IDKuis`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `mapel`
--
ALTER TABLE `mapel`
  ADD CONSTRAINT `mapel_ibfk_1` FOREIGN KEY (`IDGuru`) REFERENCES `guru` (`IDGuru`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `materi`
--
ALTER TABLE `materi`
  ADD CONSTRAINT `materi_ibfk_1` FOREIGN KEY (`IDMapel`) REFERENCES `mapel` (`IDMapel`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `notifikasi`
--
ALTER TABLE `notifikasi`
  ADD CONSTRAINT `notifikasi_ibfk_1` FOREIGN KEY (`IDUser`) REFERENCES `users` (`IDUser`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `pengumpulan_tugas`
--
ALTER TABLE `pengumpulan_tugas`
  ADD CONSTRAINT `pengumpulan_tugas_ibfk_1` FOREIGN KEY (`IDTugas`) REFERENCES `tugas` (`IDTugas`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `pengumpulan_tugas_ibfk_2` FOREIGN KEY (`IDSiswa`) REFERENCES `siswa` (`IDSiswa`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `riwayat_poin`
--
ALTER TABLE `riwayat_poin`
  ADD CONSTRAINT `riwayat_poin_ibfk_1` FOREIGN KEY (`IDSiswa`) REFERENCES `siswa` (`IDSiswa`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `riwayat_poin_ibfk_2` FOREIGN KEY (`IDPengumpulan`) REFERENCES `pengumpulan_tugas` (`IDPengumpulan`) ON DELETE CASCADE,
  ADD CONSTRAINT `riwayat_poin_ibfk_3` FOREIGN KEY (`IDAturan`) REFERENCES `master_aturan_poin` (`IDAturan`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `siswa`
--
ALTER TABLE `siswa`
  ADD CONSTRAINT `siswa_ibfk_1` FOREIGN KEY (`IDUser`) REFERENCES `users` (`IDUser`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `tugas`
--
ALTER TABLE `tugas`
  ADD CONSTRAINT `tugas_ibfk_1` FOREIGN KEY (`IDMapel`) REFERENCES `mapel` (`IDMapel`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

SET FOREIGN_KEY_CHECKS = 1;