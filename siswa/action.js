let filterAktif = 'all';

// 1. FITUR TOGGLE BINTANG (Sinkron di Mode Kartu & Daftar)
function toggleBintang(clickedBtn) {
    let col = clickedBtn.closest('.course-col');
    let isStarred = col.getAttribute('data-starred') === 'true';

    // Perbarui atribut state
    if (isStarred) {
        col.setAttribute('data-starred', 'false');
        clickedBtn.title = "Bintangi kursus ini";
    } else {
        col.setAttribute('data-starred', 'true');
        clickedBtn.title = "Hapus dari berbintang";
    }

    // Sinkronkan SEMUA ikon bintang di dalam kartu ini
    let allStarIcons = col.querySelectorAll('i.bi-star, i.bi-star-fill');
    allStarIcons.forEach(icon => {
        if (col.getAttribute('data-starred') === 'true') {
            icon.className = 'bi bi-star-fill text-warning fs-5'; 
        } else {
            icon.className = 'bi bi-star text-muted fs-5';
        }
    });

    jalankanSemuaFilter(); // Segarkan tampilan
}

// 2. FITUR PENCARIAN & FILTER KATEGORI
function jalankanFilter(tipe, teksTombol) {
    filterAktif = tipe;
    document.getElementById('filterBtn').innerText = teksTombol;
    jalankanSemuaFilter();
}

function jalankanPencarian() {
    jalankanSemuaFilter();
}

function jalankanSemuaFilter() {
    let keyword = document.getElementById('searchInput').value.toLowerCase();
    let kursus = document.querySelectorAll('.course-col');
    let jumlahTerlihat = 0; 

    kursus.forEach(card => {
        let judul = card.getAttribute('data-title').toLowerCase();
        let bintang = card.getAttribute('data-starred');

        let cocokPencarian = judul.includes(keyword);
        let cocokFilter = (filterAktif === 'all') || (filterAktif === 'starred' && bintang === 'true');

        if (cocokPencarian && cocokFilter) {
            card.classList.remove('d-none-filter');
            jumlahTerlihat++;
        } else {
            card.classList.add('d-none-filter');
        }
    });

    let pesanKosong = document.getElementById('emptyStateMessage');
    if (pesanKosong) {
        if (jumlahTerlihat === 0) {
            pesanKosong.classList.remove('d-none');
        } else {
            pesanKosong.classList.add('d-none');
        }
    }
}

// 3. FITUR SORTING
function jalankanSorting(tipe, teksTombol) {
    document.getElementById('sortBtn').innerText = teksTombol;
    let container = document.getElementById('courseContainer');
    let kursus = Array.from(container.querySelectorAll('.course-col'));

    kursus.sort((a, b) => {
        if (tipe === 'name') {
            let judulA = a.getAttribute('data-title').toLowerCase();
            let judulB = b.getAttribute('data-title').toLowerCase();
            return judulA.localeCompare(judulB);
        } else if (tipe === 'recent') {
            let recentA = parseInt(a.getAttribute('data-recent')) || 0;
            let recentB = parseInt(b.getAttribute('data-recent')) || 0;
            return recentA - recentB; 
        }
    });

    kursus.forEach(card => container.appendChild(card));
}

// 4. FITUR UBAH TAMPILAN (KARTU / DAFTAR)
function ubahTampilan(mode, textHTML) {
    document.getElementById('viewBtn').innerHTML = textHTML;
    let container = document.getElementById('courseContainer');
    
    if (mode === 'list') {
        container.classList.add('list-mode');
        container.classList.remove('row-cols-1', 'row-cols-md-2', 'row-cols-lg-3', 'g-4');
    } else {
        container.classList.remove('list-mode');
        container.classList.add('row-cols-1', 'row-cols-md-2', 'row-cols-lg-3', 'g-4');
    }
}

// =========================================================
// 5. FITUR UBAH JUDUL & KONTEN MAPEL OTOMATIS
// =========================================================
document.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    const namaMapel = urlParams.get('mapel');
    const elemenJudul = document.getElementById('judulBesarMapel');

    if (namaMapel) {
        // 1. Ubah Judul Teks
        if (elemenJudul) elemenJudul.innerText = namaMapel.toUpperCase();
        document.title = "Belajar " + namaMapel + " - LMS";

        // 2. Trik Sembunyikan & Tampilkan Konten
        // Sembunyikan semua kotak materi yang ada di halaman
        let semuaMateri = document.querySelectorAll('.konten-materi');
        semuaMateri.forEach(materi => {
            materi.classList.add('d-none');
        });

        // Ubah nama mapel menjadi format ID (Contoh: "Akuntansi Pemerintah" jadi "materi-akuntansi-pemerintah")
        let idTarget = "materi-" + namaMapel.toLowerCase().replace(/\s+/g, '-');
        
        // Cari kotak materi yang cocok, lalu munculkan!
        let materiAktif = document.getElementById(idTarget);
        if (materiAktif) {
            materiAktif.classList.remove('d-none');
        }
    }
});

