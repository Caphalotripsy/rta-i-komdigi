@extends('layouts.app')

@section('title', 'Batching Request')

@section('breadcrumb')
    <x-breadcrumb :items="[['Beranda', route('dashboard')], ['Batching']]" />
@endsection

@section('content')
    <x-page-header judul="Batching Request" subjudul="Pilih request berstatus Siap Batching untuk dikumpulkan menjadi satu batch dan diteruskan ke PPK." />

    @if ($antrean->isEmpty())
        <div class="card"><x-empty-state judul="Belum ada request siap batching." teks="Request yang lolos Approval Tahap 1 akan muncul di sini.">
            <a href="{{ route('approval.index') }}" class="btn btn-outline">Ke Approval Tahap 1</a>
        </x-empty-state></div>
    @else
        <form id="form-batching" method="POST" action="{{ route('batching.store') }}"
              x-data="batching({ total: @js($totalPerId), dipilih: @js(array_map('strval', old('ids', []))) })"
              @submit="setTimeout(() => kirim = true, 10)">
            @csrf

            <div class="mb-4 grid gap-4 sm:grid-cols-3">
                <div class="card p-5">
                    <label for="periode" class="label">Periode batch</label>
                    <select id="periode" name="periode" required @class(['input', 'input-error' => $errors->has('periode')])>
                        @foreach ($periode as $pr)
                            <option value="{{ $pr->value }}" @selected(old('periode', 'bulanan') === $pr->value)>{{ $pr->label() }}</option>
                        @endforeach
                    </select>
                    @error('periode')<p class="field-error" role="alert">{{ $message }}</p>@enderror
                </div>
                <x-stat-card label="Antrean siap batching" :nilai="$antrean->count().' request'" keterangan="Terverifikasi oleh Pejabat Pengadaan" ikon="arsip" />
                <x-stat-card label="Estimasi total tersedia" :nilai="'Rp '.number_format($antrean->sum('total_estimasi_biaya'), 0, ',', '.')" keterangan="Akumulasi seluruh antrean" ikon="dompet" />
            </div>

            @error('ids')<p class="field-error mb-2" role="alert">{{ $message }}</p>@enderror

            <div class="card overflow-hidden">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 px-5 py-3">
                    <h2 class="font-display text-lg font-bold text-slate-900">Daftar Request Siap Batching</h2>
                    <p class="text-xs text-slate-500">Centang request yang akan dimasukkan ke batch.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[44rem] text-[13px]">
                        <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="w-10 px-3 py-3"><input type="checkbox" class="size-4 rounded border-slate-300" aria-label="Pilih semua" :checked="semua" x-effect="$el.indeterminate = sebagian" @change="pilihSemua($event.target.checked)"></th>
                                <th class="px-3 py-3">Kode</th>
                                <th class="px-3 py-3">Pemohon &amp; Divisi</th>
                                <th class="min-w-48 px-3 py-3">Barang</th>
                                <th class="px-3 py-3">Kategori</th>
                                <th class="px-3 py-3 text-right">Kuantitas</th>
                                <th class="px-3 py-3 text-right">Total estimasi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($antrean as $p)
                                <tr :class="dipilih.includes('{{ $p->id }}') ? 'bg-sky-50' : 'bg-white'">
                                    <td class="px-3 py-4"><input type="checkbox" name="ids[]" value="{{ $p->id }}" x-model="dipilih" class="size-4 rounded border-slate-300" aria-label="Pilih {{ $p->kode_pengajuan }}"></td>
                                    <td class="whitespace-nowrap px-3 py-4 font-bold text-primary">{{ $p->kode_pengajuan }}</td>
                                    <td class="px-3 py-4"><p class="font-bold text-slate-900">{{ $p->pemohon->name }}</p><p class="text-xs text-slate-500">{{ $p->pemohon->divisi ?? '—' }}</p></td>
                                    <td class="px-3 py-4"><p class="font-bold text-slate-900">{{ $p->nama_barang }}</p>@if ($p->spesifikasi)<p class="text-xs text-slate-500">{{ \Illuminate\Support\Str::limit($p->spesifikasi, 50) }}</p>@endif</td>
                                    <td class="whitespace-nowrap px-3 py-4"><span class="rounded-md bg-slate-100 px-2 py-1 text-xs font-medium text-slate-700">{{ $p->kategori->label() }}</span></td>
                                    <td class="whitespace-nowrap px-3 py-4 text-right">{{ $p->kuantitas }} {{ $p->satuan->label() }}</td>
                                    <td class="whitespace-nowrap px-3 py-4 text-right font-bold">@rupiah($p->total_estimasi_biaya)</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Bar ringkasan menempel di bawah layar --}}
            <div class="sticky bottom-4 z-10 mt-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-lg">
                <p class="text-sm"><strong x-text="dipilih.length"></strong> request dipilih &middot; Total estimasi <strong class="text-primary" x-text="rupiah(jumlah)"></strong></p>
                <div class="flex gap-2">
                    <button type="button" class="btn btn-ghost" @click="dipilih = []" :disabled="dipilih.length === 0">Reset Pilihan</button>
                    <button type="button" class="btn btn-primary" :disabled="dipilih.length === 0 || kirim" @click="$dispatch('buka-konfirmasi-batch')">Buat Batch &amp; Teruskan ke PPK</button>
                </div>
            </div>

            <x-modal id="konfirmasi-batch" judul="Buat batch dan teruskan ke PPK?">
                <p><strong x-text="dipilih.length"></strong> request dengan total <strong x-text="rupiah(jumlah)"></strong> akan diteruskan ke PPK. Batch tidak dapat diubah setelah diteruskan, kecuali dikembalikan oleh PPK.</p>
                <x-slot:aksi>
                    <button type="submit" form="form-batching" class="btn btn-primary" :disabled="kirim">Ya, Teruskan</button>
                </x-slot:aksi>
            </x-modal>
        </form>
    @endif
@endsection
