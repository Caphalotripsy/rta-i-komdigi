{{-- Buka dengan: @click="$dispatch('buka-{{ $id }}')". Slot utama = isi, slot "aksi" = tombol. --}}
@props(['id', 'judul'])
<div x-data="{ buka: false }"
     x-on:buka-{{ $id }}.window="buka = true"
     x-on:keydown.escape.window="buka = false"
     x-show="buka" x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center p-4"
     role="dialog" aria-modal="true" aria-labelledby="judul-{{ $id }}">
    <div class="absolute inset-0 bg-slate-900/50" @click="buka = false"></div>
    <div class="card relative w-full max-w-md p-6" x-transition>
        <h2 id="judul-{{ $id }}" class="font-display text-xl font-bold text-slate-900">{{ $judul }}</h2>
        <div class="mt-2 text-sm text-slate-600">{{ $slot }}</div>
        <div class="mt-6 flex justify-end gap-2">
            <button type="button" class="btn btn-ghost" @click="buka = false">Batal</button>
            {{ $aksi }}
        </div>
    </div>
</div>
