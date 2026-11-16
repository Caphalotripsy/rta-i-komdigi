<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cetak Label Aset</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-page p-6 font-sans print:bg-white print:p-0">
    <div class="mx-auto max-w-4xl">
        <div class="mb-4 flex items-center justify-between print:hidden">
            <div>
                <h1 class="font-display text-2xl font-bold text-slate-900">Label Aset</h1>
                <p class="text-sm text-slate-500">{{ $aset->count() }} label. Gunakan tombol cetak, lalu pilih printer atau simpan sebagai PDF.</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('master.index') }}" class="btn btn-ghost">Kembali</a>
                <button type="button" class="btn btn-primary" onclick="window.print()">Cetak / Simpan PDF</button>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-3 print:gap-2">
            @foreach ($aset as $a)
                <div class="break-inside-avoid rounded-lg border border-slate-400 bg-white p-3">
                    <div class="flex items-center justify-between text-[10px] font-bold uppercase tracking-wide text-slate-600"><span>RTA-I &middot; Komdigi Banjarmasin</span><span>{{ $a->created_at->format('Y') }}</span></div>
                    <div class="mt-2 flex justify-center"><svg data-barcode="{{ $a->sku_barcode }}" class="h-16 w-48" aria-label="Barcode {{ $a->sku_barcode }}"></svg></div>
                    <p class="mt-2 text-center text-xs font-bold text-slate-900">{{ $a->inventaris->nama_barang }}</p>
                </div>
            @endforeach
        </div>
    </div>
</body>
</html>
