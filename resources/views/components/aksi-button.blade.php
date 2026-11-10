@props(['pengajuan'])
@php
    $aksi = $pengajuan->aksiUtama();
    $kelas = match ($aksi['gaya']) {
        'merah' => 'bg-red-50 text-danger hover:bg-red-100',
        'amber' => 'bg-amber-50 text-amber-800 hover:bg-amber-100',
        default => 'bg-sky-50 text-primary hover:bg-sky-100',
    };
@endphp
<a href="{{ $aksi['url'] }}" class="inline-flex whitespace-nowrap rounded-md px-3 py-1.5 text-xs font-bold transition {{ $kelas }}">{{ $aksi['label'] }}</a>
