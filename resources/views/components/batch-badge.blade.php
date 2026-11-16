@props(['status', 'label' => null])
@php
    $kelas = match ($status->warna()) {
        'blue' => 'bg-blue-100 text-blue-800',
        'green' => 'bg-green-100 text-green-800',
        'red' => 'bg-red-100 text-red-800',
        default => 'bg-slate-200 text-slate-700',
    };
@endphp
<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-3 py-1 text-xs font-bold $kelas"]) }}>
    <span class="size-1.5 shrink-0 rounded-full bg-current" aria-hidden="true"></span>{{ $label ?? $status->label() }}
</span>
