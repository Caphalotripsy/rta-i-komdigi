@extends('layouts.app')

@section('title', 'Ubah Request')

@section('breadcrumb')
    <x-breadcrumb :items="[['Beranda', route('dashboard')], ['Request Saya', route('pengajuan.index')], [$pengajuan->kode_pengajuan, route('pengajuan.show', $pengajuan)], ['Ubah']]" />
@endsection

@section('content')
    <x-page-header :judul="$revisi ? 'Revisi Request' : 'Ubah Request'" :subjudul="$pengajuan->kode_pengajuan" />

    @if ($revisi)
        <div role="alert" class="mb-5 rounded-lg border border-red-200 bg-red-50 p-4 text-sm">
            <p class="font-bold text-danger">
                Catatan revisi dari {{ $revisi->aktor->role->label() }} ({{ $revisi->aktor->name }}, {{ $revisi->created_at->translatedFormat('d M Y, H:i') }} WITA)
            </p>
            <p class="mt-1 whitespace-pre-line text-slate-700">{{ $revisi->catatan }}</p>
        </div>
    @endif

    <form id="form-request" method="POST" action="{{ route('pengajuan.update', $pengajuan) }}" class="card p-6"
          x-data="formRequest({ kuantitas: @js(old('kuantitas', $pengajuan->kuantitas)), harga: @js(old('harga_estimasi', (float) $pengajuan->harga_estimasi)) })"
          @submit="setTimeout(() => kirim = true, 10)">
        @csrf
        @method('PUT')
        @include('pengajuan._form')

        <div class="mt-6 flex flex-wrap justify-end gap-2 border-t border-slate-100 pt-5">
            <a href="{{ route('pengajuan.show', $pengajuan) }}" class="btn btn-ghost">Batal</a>
            <button type="submit" name="aksi" value="draft" class="btn btn-outline" :disabled="kirim">Simpan Perubahan</button>
            <button type="button" class="btn btn-primary" :disabled="kirim" @click="$dispatch('buka-konfirmasi-kirim')">
                {{ $revisi ? 'Kirim Ulang' : 'Kirim Request' }}
            </button>
        </div>
    </form>

    <x-modal id="konfirmasi-kirim" :judul="$revisi ? 'Kirim ulang request?' : 'Kirim request?'">
        Setelah dikirim, request tidak dapat diubah kecuali dikembalikan untuk revisi.
        <x-slot:aksi>
            <button type="submit" form="form-request" name="aksi" value="ajukan" class="btn btn-primary">Ya, Kirim</button>
        </x-slot:aksi>
    </x-modal>
@endsection
