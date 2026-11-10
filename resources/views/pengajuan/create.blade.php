@extends('layouts.app')

@section('title', 'Tambah Request')

@section('breadcrumb')
    <x-breadcrumb :items="[['Beranda', route('dashboard')], ['Tambah Request']]" />
@endsection

@section('content')
    <x-page-header judul="Tambah Request Barang" subjudul="Isi rincian kebutuhan barang dan perkiraan anggaran." />

    <div class="mb-5 flex items-start gap-3 rounded-lg border border-sky-100 bg-sky-50 px-4 py-3 text-sm text-slate-700">
        <x-icon name="info" class="mt-0.5 size-5 shrink-0 text-primary" />
        <p>Satu request hanya untuk satu jenis barang dengan jumlah kuantitas yang dibutuhkan. Pastikan estimasi harga sesuai harga pasar terkini.</p>
    </div>

    <form id="form-request" method="POST" action="{{ route('pengajuan.store') }}" class="card p-6"
          x-data="formRequest({ kuantitas: @js(old('kuantitas', 1)), harga: @js(old('harga_estimasi')) })"
          @submit="setTimeout(() => kirim = true, 10)">
        @csrf
        @include('pengajuan._form')

        <div class="mt-6 flex flex-wrap justify-end gap-2 border-t border-slate-100 pt-5">
            <a href="{{ route('pengajuan.index') }}" class="btn btn-ghost">Batal</a>
            <button type="submit" name="aksi" value="draft" class="btn btn-outline" :disabled="kirim">Simpan Draft</button>
            <button type="button" class="btn btn-primary" :disabled="kirim" @click="$dispatch('buka-konfirmasi-kirim')">Kirim Request</button>
        </div>
    </form>

    <x-modal id="konfirmasi-kirim" judul="Kirim request?">
        Setelah dikirim, request tidak dapat diubah kecuali dikembalikan untuk revisi.
        <x-slot:aksi>
            <button type="submit" form="form-request" name="aksi" value="ajukan" class="btn btn-primary">Ya, Kirim</button>
        </x-slot:aksi>
    </x-modal>
@endsection
