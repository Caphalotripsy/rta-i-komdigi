@props(['items'])
<nav aria-label="Breadcrumb" class="hidden items-center gap-2 text-sm text-slate-500 sm:flex">
    @foreach ($items as $i => $item)
        @if ($i > 0)<span aria-hidden="true">/</span>@endif
        @if (isset($item[1]) && ! $loop->last)
            <a href="{{ $item[1] }}" class="hover:text-primary hover:underline">{{ $item[0] }}</a>
        @else
            <span class="font-bold text-primary" @if ($loop->last) aria-current="page" @endif>{{ $item[0] }}</span>
        @endif
    @endforeach
</nav>
