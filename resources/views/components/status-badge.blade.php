@props(['status'])
@php
    $kelas = match ($status->warna()) {
        'blue' => 'bg-blue-100 text-blue-800',
        'red' => 'bg-red-100 text-red-800',
        'yellow' => 'bg-yellow-100 text-yellow-800',
        'green' => 'bg-green-100 text-green-800',
        default => 'bg-gray-200 text-gray-700',
    };
@endphp
<span {{ $attributes->merge(['class' => "inline-block rounded-full px-3 py-1 text-xs font-semibold $kelas"]) }}>{{ $status->label() }}</span>
