@extends('layouts.sementara')

@section('title', 'Request Baru - RTA-I Komdigi')

@section('content')
    <h1 class="text-xl font-bold text-primary mb-4">Request Barang Baru</h1>

    <form method="POST" action="{{ route('pengajuan.store') }}" class="bg-white p-6 rounded-lg shadow">
        @csrf
        @include('pengajuan._form')

        <div class="mt-6 flex gap-2">
            <button type="submit" name="aksi" value="draft" class="border border-primary text-primary rounded px-4 py-2">Simpan Draft</button>
            <button type="submit" name="aksi" value="ajukan" class="bg-primary text-white rounded px-4 py-2 hover:bg-secondary">Kirim</button>
            <a href="{{ route('pengajuan.index') }}" class="px-4 py-2 text-gray-600">Batal</a>
        </div>
    </form>
@endsection
