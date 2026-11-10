import './bootstrap';
import Alpine from 'alpinejs';

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

// Tampilkan/sembunyikan password di halaman login.
Alpine.data('formLogin', () => ({ lihat: false, kirim: false }));

window.Alpine = Alpine;
Alpine.start();
