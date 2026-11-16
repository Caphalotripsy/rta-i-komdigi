@extends('layouts.app')

@section('title', 'Persetujuan Batch')

@section('breadcrumb')
    <x-breadcrumb :items="[['Beranda', route('dashboard')], ['Persetujuan Batch']]" />
@endsection

@section('content')
    <x-page-header judul="Persetujuan Batch" subjudul="Batch yang diteruskan Pejabat Pengadaan dan menunggu keputusan Anda." />

    <div class="card mb-4 p-4">
        <form method="GET" action="{{ route('ppk.batch.index') }}" class="flex flex-wrap items-center gap-3">
            <label class="sr-only" for="q">Cari batch</label>
            <input id="q" name="q" type="search" value="{{ $q }}" placeholder="Cari kode batch atau nama pembuat..." class="input min-w-64 flex-1">
            <button class="btn btn-outline">Cari</button>
        </form>
    </div>

    <div class="card overflow-hidden">
        @if ($batch->isEmpty())
            <x-empty-state judul="Tidak ada batch yang menunggu keputusan." teks="Batch baru dari Pejabat Pengadaan akan muncul di sini." />
        @else
            @include('ppk._tabel', ['daftar' => $batch])
            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 px-5 py-3 text-xs text-slate-500">
                <span>Menampilkan {{ $batch->firstItem() }}&ndash;{{ $batch->lastItem() }} dari {{ $batch->total() }} batch</span>
                @if ($batch->hasPages())<div>{{ $batch->links() }}</div>@endif
            </div>
        @endif
    </div>
@endsection
