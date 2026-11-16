@extends('layouts.app')

@section('title', 'Master Inventory')

@section('breadcrumb')
    <x-breadcrumb :items="[['Beranda', route('dashboard')], ['Master Inventory']]" />
@endsection

@section('content')
    <x-page-header judul="Master Inventory" subjudul="Daftar aset aktif beserta lokasi, penanggung jawab, kondisi, dan stok barang habis pakai.">
        @if ($tab === 'aset')
            <x-slot:aksi>
                <a href="{{ route('cetak.laporan', array_filter(['q' => $q, 'kondisi' => $kondisi])) }}" target="_blank" class="btn btn-primary">Cetak Laporan</a>
            </x-slot:aksi>
        @endif
    </x-page-header>

    <div class="mb-4 flex gap-2 text-sm font-bold">
        <a href="{{ route('master.index') }}" @class(['rounded-lg px-4 py-2 transition', 'bg-primary text-white' => $tab === 'aset', 'bg-white text-slate-700 hover:bg-slate-100' => $tab !== 'aset'])>Aset <span class="ml-1 opacity-80">{{ $jumlah['aset'] }}</span></a>
        <a href="{{ route('master.index', ['tab' => 'stok']) }}" @class(['rounded-lg px-4 py-2 transition', 'bg-primary text-white' => $tab === 'stok', 'bg-white text-slate-700 hover:bg-slate-100' => $tab !== 'stok'])>Habis Pakai</a>
    </div>

    <div class="card mb-4 p-4">
        <form method="GET" action="{{ route('master.index') }}" class="flex flex-wrap items-center gap-3">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <label class="sr-only" for="q">Cari</label>
            <input id="q" name="q" type="search" value="{{ $q }}" placeholder="{{ $tab === 'aset' ? 'Cari SKU, nama, lokasi, atau penanggung jawab...' : 'Cari nama barang...' }}" class="input min-w-64 flex-1">
            @if ($tab === 'aset')
                <label class="sr-only" for="kondisi">Kondisi</label>
                <select id="kondisi" name="kondisi" class="input w-44" @change="$el.form.submit()">
                    <option value="">Semua Kondisi</option>
                    @foreach ($semuaKondisi as $k)<option value="{{ $k->value }}" @selected($kondisi === $k->value)>{{ $k->label() }}</option>@endforeach
                </select>
            @endif
            <button class="btn btn-outline">Cari</button>
        </form>
    </div>

    <div class="card overflow-hidden">
        @if ($tab === 'aset')
            @if ($aset->isEmpty())
                <x-empty-state judul="Belum ada aset terdaftar." teks="Aset yang didaftarkan dari Draft Antrean Aset akan muncul di sini." />
            @else
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[52rem] text-[13px]">
                        <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wide text-slate-500">
                            <tr><th class="px-3 py-3">SKU</th><th class="min-w-40 px-3 py-3">Nama barang</th><th class="px-3 py-3">Lokasi</th><th class="px-3 py-3">Penanggung jawab</th><th class="px-3 py-3">Kondisi</th><th class="px-3 py-3">Status</th><th class="px-3 py-3">Tanggal masuk</th><th class="sticky right-0 bg-slate-50 px-3 py-3 text-right">Aksi</th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($aset as $a)
                                <tr class="bg-white">
                                    <td class="whitespace-nowrap px-3 py-4 font-mono font-bold text-primary"><a href="{{ route('master.aset', $a) }}" class="hover:underline">{{ $a->sku_barcode }}</a></td>
                                    <td class="px-3 py-4 font-bold text-slate-900">{{ $a->inventaris->nama_barang }}</td>
                                    <td class="px-3 py-4">{{ $a->lokasi ?? '—' }}</td>
                                    <td class="px-3 py-4">{{ $a->nama_penanggung_jawab ?? '—' }}</td>
                                    <td class="px-3 py-4"><x-kondisi-badge :kondisi="$a->kondisi" /></td>
                                    <td class="whitespace-nowrap px-3 py-4"><span class="rounded-md px-2 py-1 text-xs font-bold {{ $a->aktif() ? 'bg-slate-100 text-slate-700' : 'bg-red-50 text-danger' }}">{{ $a->aktif() ? 'Aktif' : 'Tidak aktif' }}</span></td>
                                    <td class="whitespace-nowrap px-3 py-4 text-slate-600">{{ $a->created_at->translatedFormat('d M Y') }}</td>
                                    <td class="sticky right-0 bg-white px-3 py-4 text-right"><a href="{{ route('master.aset', $a) }}" class="inline-flex whitespace-nowrap rounded-md bg-sky-50 px-3 py-1.5 text-xs font-bold text-primary hover:bg-sky-100">Detail / Mutasi</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 px-5 py-3 text-xs text-slate-500">
                    <span>Menampilkan {{ $aset->firstItem() }}&ndash;{{ $aset->lastItem() }} dari {{ $aset->total() }} aset</span>
                    @if ($aset->hasPages())<div>{{ $aset->links() }}</div>@endif
                </div>
            @endif
        @else
            @if ($stok->isEmpty())
                <x-empty-state judul="Belum ada stok barang habis pakai." teks="Stok yang dicatat dari Pembaruan Stok akan muncul di sini." />
            @else
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[36rem] text-[13px]">
                        <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wide text-slate-500">
                            <tr><th class="px-3 py-3">Nama barang</th><th class="px-3 py-3">Satuan</th><th class="px-3 py-3 text-right">Total stok</th><th class="px-3 py-3 text-right">Jumlah pencatatan</th><th class="px-3 py-3">Terakhir diperbarui</th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($stok as $s)
                                <tr class="bg-white">
                                    <td class="px-3 py-4 font-bold text-slate-900">{{ $s->nama }}</td>
                                    <td class="px-3 py-4">{{ ucfirst($s->satuan) }}</td>
                                    <td class="px-3 py-4 text-right font-display text-lg font-bold text-primary">{{ $s->total }}</td>
                                    <td class="px-3 py-4 text-right">{{ $s->masuk }}</td>
                                    <td class="whitespace-nowrap px-3 py-4 text-slate-600">{{ \Illuminate\Support\Carbon::parse($s->terakhir)->translatedFormat('d M Y, H:i') }} WITA</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        @endif
    </div>
@endsection
