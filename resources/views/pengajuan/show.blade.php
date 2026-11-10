@extends('layouts.sementara')

@section('title', 'Detail Request - RTA-I Komdigi')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-xl font-bold text-primary">{{ $pengajuan->nama_barang }}</h1>
            <p class="text-sm text-gray-500">{{ $pengajuan->kode_pengajuan }}</p>
        </div>
        <x-status-badge :status="$pengajuan->status" />
    </div>

    @if ($catatanRevisi)
        <div class="mb-4 rounded border border-danger bg-red-50 p-4">
            <p class="font-semibold text-danger">Catatan revisi</p>
            <p class="whitespace-pre-line">{{ $catatanRevisi }}</p>
        </div>
    @endif

    <div class="grid md:grid-cols-3 gap-6">
        <div class="md:col-span-2 bg-white p-6 rounded-lg shadow space-y-2 text-sm">
            <p>Kategori: <strong>{{ $pengajuan->kategori->label() }}</strong></p>
            <p>Spesifikasi: <strong>{{ $pengajuan->spesifikasi ?: '-' }}</strong></p>
            <p>Kuantitas: <strong>{{ $pengajuan->kuantitas }}</strong></p>
            <p>Harga estimasi satuan: <strong>@rupiah($pengajuan->harga_estimasi)</strong></p>
            <p>Total estimasi: <strong>@rupiah($pengajuan->total_estimasi_biaya)</strong></p>
            @if ($pengajuan->batch)
                <p>Batch: <strong>{{ $pengajuan->batch->kode_batch }}</strong></p>
            @endif

            <div class="pt-4 flex gap-2">
                @can('update', $pengajuan)
                    <a href="{{ route('pengajuan.edit', $pengajuan) }}" class="border border-primary text-primary rounded px-4 py-2">Ubah</a>
                    @if ($pengajuan->status === \App\Enums\StatusPengajuan::Draft)
                        <form method="POST" action="{{ route('pengajuan.ajukan', $pengajuan) }}">
                            @csrf
                            <button class="bg-primary text-white rounded px-4 py-2 hover:bg-secondary">Kirim</button>
                        </form>
                    @endif
                @endcan
                @can('delete', $pengajuan)
                    <form method="POST" action="{{ route('pengajuan.destroy', $pengajuan) }}" onsubmit="return confirm('Hapus draft ini?')">
                        @csrf
                        @method('DELETE')
                        <button class="bg-danger text-white rounded px-4 py-2">Hapus Draft</button>
                    </form>
                @endcan
            </div>
        </div>

        {{-- Riwayat / timeline status (UC-03) --}}
        <div class="bg-white p-6 rounded-lg shadow">
            <h2 class="font-semibold mb-3">Riwayat</h2>
            <ol class="space-y-3 text-sm border-l-2 border-secondary pl-4">
                @foreach ($pengajuan->riwayat as $r)
                    <li>
                        <p class="font-medium">{{ \App\Enums\StatusPengajuan::tryFrom($r->status_ke)?->label() ?? $r->status_ke }}</p>
                        <p class="text-gray-500">{{ $r->aktor->name }} &middot; {{ $r->created_at->translatedFormat('d M Y H:i') }}</p>
                        @if ($r->catatan)
                            <p class="text-gray-700 whitespace-pre-line">{{ $r->catatan }}</p>
                        @endif
                    </li>
                @endforeach
            </ol>
        </div>
    </div>
@endsection
