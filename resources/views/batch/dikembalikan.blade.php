@extends('layouts.app')

@section('title', 'Batch Dikembalikan')

@section('breadcrumb')
    <x-breadcrumb :items="[['Beranda', route('dashboard')], ['Batch Dikembalikan']]" />
@endsection

@section('content')
    <x-page-header judul="Batch Dikembalikan" subjudul="Batch yang dikembalikan PPK dan perlu Anda tindak lanjuti: keluarkan request bermasalah, lalu kirim ulang atau batalkan." />

    <div class="card overflow-hidden">
        @if ($batch->isEmpty())
            <x-empty-state judul="Tidak ada batch yang dikembalikan." teks="Batch yang dikembalikan PPK akan muncul di sini." />
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[44rem] text-[13px]">
                    <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-3 py-3">Kode batch</th><th class="px-3 py-3">Periode</th><th class="px-3 py-3 text-right">Request</th>
                            <th class="px-3 py-3 text-right">Total estimasi</th><th class="min-w-56 px-3 py-3">Catatan PPK</th><th class="sticky right-0 bg-slate-50 px-3 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($batch as $b)
                            <tr class="bg-white">
                                <td class="whitespace-nowrap px-3 py-4 font-bold text-primary"><a href="{{ route('batch.show', $b) }}" class="hover:underline">{{ $b->kode_batch }}</a></td>
                                <td class="whitespace-nowrap px-3 py-4">{{ $b->labelPeriode() }}</td>
                                <td class="whitespace-nowrap px-3 py-4 text-right">{{ $b->jumlahRequest() }} request</td>
                                <td class="whitespace-nowrap px-3 py-4 text-right font-bold">@rupiah($b->totalEstimasi())</td>
                                <td class="px-3 py-4 text-danger">{{ $b->catatan_revisi ?: '—' }}</td>
                                <td class="sticky right-0 bg-white px-3 py-4 text-right"><a href="{{ route('batch.show', $b) }}" class="inline-flex rounded-md bg-red-50 px-3 py-1.5 text-xs font-bold text-danger hover:bg-red-100">Tindak lanjuti</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
