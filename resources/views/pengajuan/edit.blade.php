@extends('layouts.sementara')

@section('title', 'Ubah Request - RTA-I Komdigi')

@section('content')
    <h1 class="text-xl font-bold text-primary mb-1">Ubah Request {{ $pengajuan->kode_pengajuan }}</h1>

    @if ($catatanRevisi)
        <div class="my-4 rounded border border-danger bg-red-50 p-4">
            <p class="font-semibold text-danger">Catatan revisi dari Pejabat Pengadaan</p>
            <p class="whitespace-pre-line">{{ $catatanRevisi }}</p>
        </div>
    @endif

    <form method="POST" action="{{ route('pengajuan.update', $pengajuan) }}" class="bg-white p-6 rounded-lg shadow">
        @csrf
        @method('PUT')
        @include('pengajuan._form')

        <div class="mt-6 flex gap-2">
            <button type="submit" name="aksi" value="draft" class="border border-primary text-primary rounded px-4 py-2">Simpan</button>
            <button type="submit" name="aksi" value="ajukan" class="bg-primary text-white rounded px-4 py-2 hover:bg-secondary">
                {{ $catatanRevisi ? 'Kirim Ulang' : 'Kirim' }}
            </button>
            <a href="{{ route('pengajuan.show', $pengajuan) }}" class="px-4 py-2 text-gray-600">Batal</a>
        </div>
    </form>
@endsection
