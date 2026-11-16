@extends('layouts.app')

@section('title', 'Riwayat Keputusan')

@section('breadcrumb')
    <x-breadcrumb :items="[['Beranda', route('dashboard')], ['Riwayat Keputusan']]" />
@endsection

@section('content')
    <x-page-header judul="Riwayat Keputusan" subjudul="Batch yang sudah Anda putuskan. Batch yang dikirim ulang setelah dikembalikan tampil kembali di Persetujuan Batch." />

    <div class="card mb-4 p-4">
        <form method="GET" action="{{ route('ppk.riwayat') }}" class="flex flex-wrap items-center gap-3">
            <label class="sr-only" for="q">Cari batch</label>
            <input id="q" name="q" type="search" value="{{ $filter['q'] }}" placeholder="Cari kode batch atau nama pembuat..." class="input min-w-64 flex-1">
            @if ($filter['keputusan'])<input type="hidden" name="keputusan" value="{{ $filter['keputusan'] }}">@endif
            <button class="btn btn-outline">Cari</button>
        </form>
        <div class="mt-3 flex flex-wrap gap-2 text-xs font-medium">
            @foreach ([['', 'Semua', $jumlah['semua']], ['disetujui', 'Disetujui', $jumlah['disetujui']], ['dikembalikan', 'Dikembalikan', $jumlah['dikembalikan']]] as [$nilai, $nama, $n])
                <a href="{{ route('ppk.riwayat', array_filter(['keputusan' => $nilai, 'q' => $filter['q']])) }}"
                   @class(['rounded-full px-3 py-1.5 transition', 'bg-primary text-white' => $filter['keputusan'] === $nilai, 'bg-slate-100 text-slate-700 hover:bg-slate-200' => $filter['keputusan'] !== $nilai])>{{ $nama }} <span class="ml-1 font-bold">{{ $n }}</span></a>
            @endforeach
        </div>
    </div>

    <div class="card overflow-hidden">
        @if ($batch->isEmpty())
            <x-empty-state judul="Belum ada keputusan." teks="Batch yang Anda setujui atau kembalikan akan tercatat di sini." />
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[48rem] text-[13px]">
                    <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-3 py-3">Kode batch</th><th class="px-3 py-3">Periode</th><th class="px-3 py-3 text-right">Request</th><th class="px-3 py-3 text-right">Total estimasi</th>
                            <th class="px-3 py-3">Keputusan</th><th class="px-3 py-3">Tanggal keputusan</th><th class="min-w-44 px-3 py-3">Catatan</th><th class="sticky right-0 bg-slate-50 px-3 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($batch as $b)
                            <tr class="bg-white">
                                <td class="whitespace-nowrap px-3 py-4 font-bold text-primary"><a href="{{ route('ppk.batch.show', $b) }}" class="hover:underline">{{ $b->kode_batch }}</a></td>
                                <td class="whitespace-nowrap px-3 py-4">{{ $b->labelPeriode() }}</td>
                                <td class="whitespace-nowrap px-3 py-4 text-right">{{ $b->jumlahRequest() }} request</td>
                                <td class="whitespace-nowrap px-3 py-4 text-right font-bold">@rupiah($b->totalEstimasi())</td>
                                <td class="px-3 py-4"><x-batch-badge :status="$b->status === \App\Enums\StatusBatch::Disetujui ? $b->status : \App\Enums\StatusBatch::Direvisi" :label="$b->status === \App\Enums\StatusBatch::Disetujui ? 'Disetujui' : 'Dikembalikan'" /></td>
                                <td class="whitespace-nowrap px-3 py-4 text-slate-600">{{ $b->diputuskan_pada->translatedFormat('d M Y') }}<span class="block text-xs text-slate-500">{{ $b->diputuskan_pada->format('H:i') }} WITA</span></td>
                                <td class="px-3 py-4 text-slate-700">{{ \Illuminate\Support\Str::limit($b->catatan_revisi, 70) ?: '—' }}</td>
                                <td class="sticky right-0 bg-white px-3 py-4 text-right"><a href="{{ route('ppk.batch.show', $b) }}" class="inline-flex rounded-md bg-sky-50 px-3 py-1.5 text-xs font-bold text-primary hover:bg-sky-100">Lihat</a></td>
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
