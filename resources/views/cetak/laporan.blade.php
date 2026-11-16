<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laporan Inventaris</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>@media print { @page { size: A4 landscape; margin: 12mm; } }</style>
</head>
<body class="min-h-screen bg-page p-6 font-sans text-slate-900 print:bg-white print:p-0">
    <div class="mx-auto max-w-6xl rounded-lg bg-white p-8 print:max-w-none print:rounded-none print:p-0">
        <div class="mb-4 flex justify-end gap-2 print:hidden">
            <a href="{{ route('master.index') }}" class="btn btn-ghost">Kembali</a>
            <button type="button" class="btn btn-primary" onclick="window.print()">Cetak / Simpan PDF</button>
        </div>

        <header class="border-b-2 border-slate-800 pb-3 text-center">
            <p class="text-sm font-bold uppercase tracking-wide">Komdigi Banjarmasin</p>
            <h1 class="font-display text-2xl font-bold">Laporan Inventaris Aset</h1>
            <p class="mt-1 text-xs text-slate-600">
                Dicetak {{ now()->translatedFormat('d F Y, H:i') }} WITA oleh {{ auth()->user()->name }}
                @if ($kondisi) &middot; Kondisi: {{ $kondisi->label() }}@endif
                @if ($q !== '') &middot; Pencarian: &ldquo;{{ $q }}&rdquo;@endif
            </p>
        </header>

        <table class="mt-4 w-full text-[11px]">
            <thead>
                <tr class="border-y border-slate-800 bg-slate-100 text-left font-bold">
                    <th class="px-2 py-2">No</th><th class="px-2 py-2">SKU</th><th class="px-2 py-2">Nama barang</th><th class="px-2 py-2">Lokasi</th>
                    <th class="px-2 py-2">Penanggung jawab</th><th class="px-2 py-2">Kondisi</th><th class="px-2 py-2">Tanggal masuk</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($aset as $i => $a)
                    <tr class="border-b border-slate-300">
                        <td class="px-2 py-1.5">{{ $i + 1 }}</td><td class="px-2 py-1.5 font-mono font-bold">{{ $a->sku_barcode }}</td><td class="px-2 py-1.5">{{ $a->inventaris->nama_barang }}</td>
                        <td class="px-2 py-1.5">{{ $a->lokasi ?? '—' }}</td><td class="px-2 py-1.5">{{ $a->nama_penanggung_jawab ?? '—' }}</td>
                        <td class="px-2 py-1.5">{{ $a->kondisi->label() }}</td><td class="px-2 py-1.5">{{ $a->created_at->translatedFormat('d M Y') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-2 py-6 text-center text-slate-500">Tidak ada aset.</td></tr>
                @endforelse
            </tbody>
        </table>

        <p class="mt-3 text-xs"><strong>Total {{ $aset->count() }} aset.</strong>
            Baik {{ $perKondisi['baik'] ?? 0 }} &middot; Rusak {{ $perKondisi['rusak'] ?? 0 }} &middot; Hilang {{ $perKondisi['hilang'] ?? 0 }}</p>

        <div class="mt-10 grid grid-cols-2 gap-10 text-center text-xs">
            <div><p>Mengetahui,</p><div class="h-16"></div><p class="border-t border-slate-800 pt-1">(...........................................)</p></div>
            <div><p>Pengelola Inventaris,</p><div class="h-16"></div><p class="border-t border-slate-800 pt-1">{{ auth()->user()->name }}</p></div>
        </div>
    </div>
</body>
</html>
