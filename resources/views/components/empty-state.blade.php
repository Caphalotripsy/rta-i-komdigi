@props(['judul', 'teks' => null])
<div class="flex flex-col items-center px-6 py-14 text-center">
    <span class="flex size-14 items-center justify-center rounded-full bg-sky-50 text-primary"><x-icon name="berkas" class="size-7" /></span>
    <p class="mt-4 font-display text-lg font-bold text-slate-900">{{ $judul }}</p>
    @if ($teks)<p class="mt-1 max-w-md text-sm text-slate-500">{{ $teks }}</p>@endif
    @if (trim((string) $slot) !== '')<div class="mt-5">{{ $slot }}</div>@endif
</div>
