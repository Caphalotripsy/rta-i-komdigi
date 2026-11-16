@extends('layouts.app')

@section('title', 'Riwayat Batch')

@section('breadcrumb')
    <x-breadcrumb :items="[['Beranda', route('dashboard')], ['Riwayat Batch']]" />
@endsection

@section('content')
    <x-page-header judul="Riwayat Batch" subjudul="Rekapitulasi seluruh batch yang telah dibuat dan diajukan ke PPK." />

    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Total batch" :nilai="$jumlah['semua']" keterangan="Seluruh batch yang pernah dibuat" ikon="arsip" />
        <x-stat-card label="Dalam penelaahan PPK" :nilai="$jumlah['pending_ppk']" keterangan="Menunggu keputusan PPK" ikon="jam" />
        <x-stat-card label="Perlu tindak lanjut" :nilai="$jumlah['direvisi']" keterangan="Dikembalikan oleh PPK" ikon="peringatan" :nada="$jumlah['direvisi'] > 0 ? 'bahaya' : 'biasa'" />
        <x-stat-card label="Disetujui" :nilai="$jumlah['disetujui']" keterangan="Siap dilanjutkan ke pencairan" ikon="centang" nada="sukses" />
    </div>

    <div class="card mb-4 p-4">
        <form method="GET" action="{{ route('batch.index') }}" class="flex flex-wrap items-center gap-3">
            <label class="sr-only" for="q">Cari batch</label>
            <input id="q" name="q" type="search" value="{{ $filter['q'] }}" placeholder="Cari kode batch atau nama pembuat..." class="input min-w-64 flex-1">
            @if ($filter['status'])<input type="hidden" name="status" value="{{ $filter['status'] }}">@endif
            <button class="btn btn-outline">Cari</button>
        </form>
        <div class="mt-3 flex flex-wrap gap-2 text-xs font-medium">
            <a href="{{ route('batch.index', array_filter(['q' => $filter['q']])) }}" @class(['rounded-full px-3 py-1.5 transition', 'bg-primary text-white' => ! $filter['status'], 'bg-slate-100 text-slate-700 hover:bg-slate-200' => (bool) $filter['status']])>Semua <span class="ml-1 font-bold">{{ $jumlah['semua'] }}</span></a>
            @foreach ($semuaStatus as $s)
                <a href="{{ route('batch.index', array_filter(['status' => $s->value, 'q' => $filter['q']])) }}" @class(['rounded-full px-3 py-1.5 transition', 'bg-primary text-white' => $filter['status'] === $s->value, 'bg-slate-100 text-slate-700 hover:bg-slate-200' => $filter['status'] !== $s->value])>{{ $s->label() }} <span class="ml-1 font-bold">{{ $jumlah[$s->value] }}</span></a>
            @endforeach
        </div>
    </div>

    <div class="card overflow-hidden">
        @if ($batch->isEmpty())
            <x-empty-state judul="Belum ada batch." teks="Batch yang Anda buat dari halaman Batching akan tercatat di sini." />
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[48rem] text-[13px]">
                    <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-3 py-3">Kode batch</th><th class="px-3 py-3">Periode</th><th class="px-3 py-3">Dibuat oleh</th>
                            <th class="px-3 py-3">Tanggal</th><th class="px-3 py-3 text-right">Request</th><th class="px-3 py-3 text-right">Total estimasi</th>
                            <th class="px-3 py-3">Status</th><th class="sticky right-0 bg-slate-50 px-3 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($batch as $b)
                            <tr class="bg-white">
                                <td class="whitespace-nowrap px-3 py-4 font-bold text-primary"><a href="{{ route('batch.show', $b) }}" class="hover:underline">{{ $b->kode_batch }}</a></td>
                                <td class="whitespace-nowrap px-3 py-4">{{ $b->labelPeriode() }}</td>
                                <td class="px-3 py-4"><p class="font-medium">{{ $b->pembuat->name }}</p><p class="text-xs text-slate-500">{{ $b->pembuat->role->label() }}</p></td>
                                <td class="whitespace-nowrap px-3 py-4 text-slate-600">{{ $b->created_at->translatedFormat('d M Y') }}<span class="block text-xs text-slate-500">{{ $b->created_at->format('H:i') }} WITA</span></td>
                                <td class="whitespace-nowrap px-3 py-4 text-right">{{ $b->jumlahRequest() }} request</td>
                                <td class="whitespace-nowrap px-3 py-4 text-right font-bold">@rupiah($b->totalEstimasi())</td>
                                <td class="px-3 py-4"><x-batch-badge :status="$b->status" /></td>
                                <td class="sticky right-0 bg-white px-3 py-4 text-right"><a href="{{ route('batch.show', $b) }}" class="inline-flex rounded-md bg-sky-50 px-3 py-1.5 text-xs font-bold text-primary hover:bg-sky-100">Lihat</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 px-5 py-3 text-xs text-slate-500">
                <span>Menampilkan {{ $batch->firstItem() }}&ndash;{{ $batch->lastItem() }} dari {{ $batch->total() }} batch</span>
                @if ($batch->hasPages())<div>{{ $batch->links() }}</div>@endif
            </div>
        @endif
    </div>
@endsection
