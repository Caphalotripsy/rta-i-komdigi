<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'RTA-I') · RTA-I Komdigi</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-page font-sans text-slate-800 antialiased" x-data="{ menuTerbuka: false }">
    @php
        $pengguna = auth()->user();
        $menu = \App\Support\Navigasi::untuk($pengguna);
        $inisial = \Illuminate\Support\Str::of($pengguna->name)->explode(' ')->take(2)->map(fn ($k) => mb_substr($k, 0, 1))->implode('');
    @endphp

    {{-- Latar gelap saat sidebar terbuka di layar kecil --}}
    <div x-show="menuTerbuka" x-cloak x-transition.opacity @click="menuTerbuka = false" class="fixed inset-0 z-30 bg-slate-900/50 lg:hidden"></div>

    <aside class="fixed inset-y-0 left-0 z-40 flex w-60 flex-col bg-primary text-white transition-transform duration-200"
           :class="menuTerbuka ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">
        <div class="flex items-center gap-3 px-5 py-5">
            <div class="flex size-10 items-center justify-center rounded-lg bg-accent font-display text-lg font-bold text-primary">R</div>
            <div class="leading-tight">
                <p class="font-display text-lg font-bold">RTA-I</p>
                <p class="text-[11px] font-medium tracking-wide text-white/70">Komdigi Banjarmasin</p>
            </div>
        </div>

        <nav class="mt-2 flex-1 space-y-1 px-3" aria-label="Navigasi utama">
            @foreach ($menu as $item)
                <a href="{{ $item['url'] }}"
                   @class([
                       'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition',
                       'bg-secondary text-white' => $item['aktif'],
                       'text-white/80 hover:bg-white/10 hover:text-white' => ! $item['aktif'],
                   ])
                   @if ($item['aktif']) aria-current="page" @endif>
                    <x-icon :name="$item['ikon']" />
                    {{ $item['label'] }}
                </a>
            @endforeach
        </nav>

        <form method="POST" action="{{ route('logout') }}" class="border-t border-white/10 p-3">
            @csrf
            <button class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-white/80 transition hover:bg-white/10 hover:text-white">
                <x-icon name="keluar" />
                Keluar
            </button>
        </form>
    </aside>

    <div class="lg:pl-60">
        <header class="sticky top-0 z-20 flex items-center justify-between gap-4 border-b border-slate-200 bg-white/95 px-4 py-3 backdrop-blur sm:px-6 lg:px-8">
            <div class="flex items-center gap-3">
                <button type="button" class="rounded-lg p-2 text-slate-600 hover:bg-slate-100 lg:hidden" @click="menuTerbuka = true" aria-label="Buka menu">
                    <x-icon name="menu" />
                </button>
                @yield('breadcrumb')
            </div>

            <div class="flex items-center gap-3 text-right">
                <div class="leading-tight">
                    <p class="text-sm font-bold text-slate-900">{{ $pengguna->name }}</p>
                    <p class="text-xs text-slate-500">{{ $pengguna->role->label() }}@if ($pengguna->divisi) &bull; {{ $pengguna->divisi }}@endif</p>
                </div>
                <div class="flex size-9 items-center justify-center rounded-full bg-primary text-xs font-bold text-white" aria-hidden="true">{{ $inisial }}</div>
            </div>
        </header>

        <main class="mx-auto max-w-6xl px-4 py-6 sm:px-6 lg:px-8">
            @if (session('status'))
                <div role="status" class="mb-4 flex items-center gap-2 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800">
                    <x-icon name="centang" class="size-5 shrink-0" />
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->has('status'))
                <div role="alert" class="mb-4 flex items-center gap-2 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-danger">
                    <x-icon name="peringatan" class="size-5 shrink-0" />
                    {{ $errors->first('status') }}
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</body>
</html>
