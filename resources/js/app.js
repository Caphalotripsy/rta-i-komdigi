import './bootstrap';
import Alpine from 'alpinejs';
import JsBarcode from 'jsbarcode';

const rupiah = (n) => 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(Number(n) || 0));

// Form Tambah/Ubah Request: total estimasi dihitung langsung di layar (server tetap menghitung ulang).
Alpine.data('formRequest', (awal) => ({
    kuantitas: awal.kuantitas ?? '',
    harga: awal.harga ?? '',
    kirim: false,
    rupiah,
    get total() {
        return (Number(this.kuantitas) || 0) * (Number(this.harga) || 0);
    },
}));

// Form Lapor Harga Aktual: hitung total aktual dan sisa dana langsung di layar.
Alpine.data('laporHarga', (awal) => ({
    kuantitas: awal.kuantitas ?? '',
    harga: awal.harga ?? '',
    dana: Number(awal.dana) || 0,
    maxKuantitas: Number(awal.maxKuantitas) || 0,
    maxHarga: Number(awal.maxHarga) || 0,
    kirim: false,
    rupiah,
    get total() {
        return (Number(this.kuantitas) || 0) * (Number(this.harga) || 0);
    },
    get sisa() {
        return Math.max(this.dana - this.total, 0);
    },
    get melebihiHarga() {
        return Number(this.harga) > this.maxHarga;
    },
    get melebihiKuantitas() {
        return Number(this.kuantitas) > this.maxKuantitas;
    },
}));

// Halaman Batching: pilih request, hitung jumlah dan total estimasi terpilih.
Alpine.data('batching', (awal) => ({
    total: awal.total || {},
    dipilih: awal.dipilih || [],
    kirim: false,
    rupiah,
    get jumlah() {
        return this.dipilih.reduce((acc, id) => acc + (Number(this.total[id]) || 0), 0);
    },
    get semua() {
        const ids = Object.keys(this.total);
        return ids.length > 0 && ids.every((id) => this.dipilih.includes(id));
    },
    get sebagian() {
        return this.dipilih.length > 0 && !this.semua;
    },
    pilihSemua(centang) {
        this.dipilih = centang ? Object.keys(this.total) : [];
    },
}));

// Barcode Code 128 dari teks SKU: <svg data-barcode="AST-2026-0001"></svg>
const renderBarcodes = () => document.querySelectorAll('svg[data-barcode]').forEach((el) => {
    try {
        JsBarcode(el, el.dataset.barcode, { format: 'CODE128', displayValue: true, height: 38, fontSize: 12, margin: 0, width: 1.6 });
    } catch (e) { /* abaikan SKU tidak valid */ }
});
document.addEventListener('DOMContentLoaded', renderBarcodes);

// Registrasi aset: satu baris per unit; tombol untuk menyamakan isian dengan unit pertama.
Alpine.data('registrasiAset', (awal) => ({
    unit: awal.unit,
    kirim: false,
    samakan() {
        const [pertama, ...lain] = this.unit;
        lain.forEach((u) => Object.assign(u, pertama));
    },
}));

// Pembaruan stok: pratinjau saldo berdasarkan nama dan satuan (stok = total per nama+satuan).
Alpine.data('formStok', (awal) => ({
    nama: awal.nama,
    satuan: awal.satuan,
    jumlah: Number(awal.jumlah) || 0,
    stok: awal.stok || {},
    kirim: false,
    get kunci() { return this.nama.trim().toLowerCase() + '|' + this.satuan; },
    get saatIni() { return Number(this.stok[this.kunci]) || 0; },
    get akhir() { return this.saatIni + this.jumlah; },
}));

// Tampilkan/sembunyikan password di halaman login.
Alpine.data('formLogin', () => ({ lihat: false, kirim: false }));

window.Alpine = Alpine;
Alpine.start();
