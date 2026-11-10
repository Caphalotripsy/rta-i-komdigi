@props(['judul', 'subjudul' => null])
<div class="mb-6 flex flex-wrap items-start justify-between gap-4">
    <div>
        <h1 class="font-display text-3xl font-bold text-slate-900">{{ $judul }}</h1>
        @if ($subjudul)<p class="mt-1 text-sm text-slate-500">{{ $subjudul }}</p>@endif
    </div>
    @isset($aksi)
        <div class="flex flex-wrap gap-2">{{ $aksi }}</div>
    @endisset
</div>
