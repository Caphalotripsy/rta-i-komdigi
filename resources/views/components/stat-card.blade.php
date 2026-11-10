@props(['label', 'nilai', 'keterangan' => null, 'ikon' => 'berkas', 'nada' => 'biasa'])
@php
    $warnaIkon = match ($nada) {
        'bahaya' => 'bg-red-50 text-danger',
        'sukses' => 'bg-green-50 text-green-700',
        default => 'bg-sky-50 text-primary',
    };
    $warnaKet = $nada === 'bahaya' ? 'text-danger font-medium' : 'text-slate-500';
@endphp
<div class="card p-5">
    <div class="flex items-start justify-between gap-3">
        <p class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ $label }}</p>
        <span class="flex size-9 shrink-0 items-center justify-center rounded-lg {{ $warnaIkon }}"><x-icon :name="$ikon" /></span>
    </div>
    <p class="mt-2 font-display text-3xl font-bold text-slate-900">{{ $nilai }}</p>
    @if ($keterangan)
        <p class="mt-1 text-xs {{ $warnaKet }}">{{ $keterangan }}</p>
    @endif
</div>
