@extends('layouts.app')

@section('title', 'Pembaruan Stok')

@section('breadcrumb')
    <x-breadcrumb :items="[['Beranda', route('dashboard')], ['Pembaruan Stok']]" />
@endsection

@section('content')
    <x-page-header judul="Pembaruan Stok" subjudul="Catat penambahan stok barang habis pakai dari request yang telah ditutup." />

    @if ($antrean->isEmpty())
        <div class="card"><x-empty-state judul="Tidak ada barang habis pakai yang menunggu dicatat." teks="Request habis pakai yang ditutup Divisi Keuangan akan muncul di sini." /></div>
    @else
        <div class="grid gap-6 lg:grid-cols-5">
            <aside class="card h-fit p-5 lg:col-span-2">
                <div class="flex items-center justify-between"><h2 class="font-display text-lg font-bold text-slate-900">Antrean Masuk Stok</h2><span class="rounded-full bg-sky-50 px-2.5 py-1 text-xs font-bold text-primary">{{ $antrean->count() }} request</span></div>
                <ul class="mt-4 space-y-2">
                    @foreach ($antrean as $a)
                        <li>
                            <a href="{{ route('stok.index', ['pengajuan' => $a->id]) }}" @class(['block rounded-lg border p-3 transition', 'border-primary bg-sky-50' => $a->id === $terpilih->id, 'border-slate-200 hover:bg-slate-50' => $a->id !== $terpilih->id])>
                                <p class="text-xs font-bold text-primary">{{ $a->kode_pengajuan }}</p>
                                <p class="font-bold text-slate-900">{{ $a->nama_barang }}</p>
                                <p class="text-xs text-slate-500">{{ $a->kuantitas_terbeli }} {{ $a->satuan->label() }} &middot; {{ $a->pemohon->name }} ({{ $a->pemohon->divisi ?? '—' }})</p>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </aside>

            <section class="card p-6 lg:col-span-3">
                @php $t = $terpilih; @endphp
                <h2 class="font-display text-xl font-bold text-slate-900">Formulir Pembaruan Stok</h2>
                <p class="mt-1 text-sm text-slate-500">Sesuaikan nama barang dan periksa jumlah sebelum menambah saldo.</p>

                <form id="form-stok" method="POST" action="{{ route('stok.simpan', $t) }}"
                      x-data="formStok({ nama: @js(old('nama_barang', $t->nama_barang)), satuan: @js(old('satuan', $t->satuan->value)), jumlah: @js($t->kuantitas_terbeli), stok: @js($stok) })"
                      @submit="setTimeout(() => kirim = true, 10)" class="mt-5 space-y-5">
                    @csrf
                    <div>
                        <label for="nama_barang" class="label">Nama barang persediaan <span class="text-danger">*</span></label>
                        <input id="nama_barang" name="nama_barang" type="text" maxlength="255" required x-model="nama" @class(['input', 'input-error' => $errors->has('nama_barang')])>
                        <p class="field-hint">Gunakan nama yang sama dengan stok yang sudah ada agar totalnya tergabung.</p>
                        @error('nama_barang')<p class="field-error" role="alert">{{ $message }}</p>@enderror
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="satuan" class="label">Satuan <span class="text-danger">*</span></label>
                            <select id="satuan" name="satuan" required x-model="satuan" @class(['input', 'input-error' => $errors->has('satuan')])>
                                @foreach ($satuan as $s)<option value="{{ $s->value }}">{{ $s->label() }}</option>@endforeach
                            </select>
                            @error('satuan')<p class="field-error" role="alert">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="label">Jumlah masuk</label>
                            <div class="flex items-center justify-between rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm font-bold">{{ $t->kuantitas_terbeli }} <span class="text-xs font-medium text-slate-500">dari kuantitas terbeli {{ $t->kode_pengajuan }}</span></div>
                        </div>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Pratinjau saldo stok</p>
                        <div class="mt-3 grid grid-cols-3 gap-3 text-center">
                            <div class="rounded-lg bg-white p-3"><p class="text-xs text-slate-500">Stok saat ini</p><p class="font-display text-2xl font-bold" x-text="saatIni"></p></div>
                            <div class="rounded-lg bg-white p-3"><p class="text-xs text-slate-500">Penambahan</p><p class="font-display text-2xl font-bold text-secondary" x-text="'+' + jumlah"></p></div>
                            <div class="rounded-lg bg-primary p-3 text-white"><p class="text-xs text-white/70">Estimasi akhir</p><p class="font-display text-2xl font-bold" x-text="akhir"></p></div>
                        </div>
                        <p class="mt-3 text-xs text-slate-500">Setiap request dicatat sebagai stok masuk terpisah; total stok dihitung per nama barang dan satuan.</p>
                    </div>

                    <div class="flex justify-end gap-2 border-t border-slate-100 pt-5">
                        <button type="button" class="btn btn-primary" :disabled="kirim || nama.trim() === ''" @click="$dispatch('buka-konfirmasi-stok')">Simpan Stok</button>
                    </div>
                </form>

                <x-modal id="konfirmasi-stok" judul="Simpan stok masuk?">
                    Stok <strong>{{ $t->kuantitas_terbeli }}</strong> akan ditambahkan. Request ini keluar dari antrean.
                    <x-slot:aksi><button type="submit" form="form-stok" class="btn btn-primary">Ya, Simpan Stok</button></x-slot:aksi>
                </x-modal>
            </section>
        </div>
    @endif
@endsection
