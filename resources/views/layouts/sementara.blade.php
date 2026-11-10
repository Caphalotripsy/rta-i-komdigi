<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'RTA-I Komdigi')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-100 text-gray-800">
    {{-- LAYOUT SEMENTARA untuk uji backend. Frontend: buat layout asli dari Figma (layouts/app.blade.php),
         lalu ganti @extends('layouts.sementara') di setiap view dengan @extends('layouts.app'). --}}
    <header class="bg-primary text-white">
        <div class="max-w-5xl mx-auto px-4 py-3 flex items-center justify-between">
            <nav class="flex gap-4 text-sm font-medium">
                <a href="{{ route('dashboard') }}" class="hover:text-accent">Dashboard</a>
                @if (auth()->user()->hasRole(\App\Enums\Role::KepalaDivisi))
                    <a href="{{ route('pengajuan.index') }}" class="hover:text-accent">Pengajuan</a>
                @endif
            </nav>
            <form method="POST" action="{{ route('logout') }}" class="flex items-center gap-3 text-sm">
                @csrf
                <span>{{ auth()->user()->name }} ({{ auth()->user()->role->label() }})</span>
                <button class="bg-white/10 hover:bg-white/20 rounded px-3 py-1">Keluar</button>
            </form>
        </div>
    </header>

    <main class="max-w-5xl mx-auto px-4 py-6">
        @if (session('status'))
            <div class="mb-4 rounded bg-green-100 text-green-800 px-4 py-2">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="mb-4 rounded bg-red-100 text-red-800 px-4 py-2">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $pesan)
                        <li>{{ $pesan }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>
</body>
</html>
