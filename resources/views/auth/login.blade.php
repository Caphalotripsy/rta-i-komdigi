<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk · RTA-I Komdigi</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-page font-sans text-slate-800 antialiased">
    <div class="grid min-h-screen lg:grid-cols-2">
        {{-- Panel merek (disembunyikan di layar kecil) --}}
        <section class="relative hidden overflow-hidden bg-primary p-12 text-white lg:flex lg:flex-col lg:justify-between">
            <div class="absolute -right-24 -top-24 size-72 rounded-full bg-secondary/40" aria-hidden="true"></div>
            <div class="absolute -bottom-40 -left-20 size-[28rem] rounded-full bg-white/5" aria-hidden="true"></div>

            <div class="relative flex items-center gap-3">
                <div class="flex size-10 items-center justify-center rounded-lg bg-accent font-display text-lg font-bold text-primary">R</div>
                <span class="text-sm font-bold tracking-widest">RTA-I</span>
            </div>

            <div class="relative max-w-md">
                <div class="mb-6 h-1 w-14 bg-accent"></div>
                <h1 class="font-display text-4xl font-bold leading-tight">Satu alur. Setiap keputusan terlacak.</h1>
                <p class="mt-4 text-white/85">Ruang kerja untuk memantau permintaan, persetujuan, dan inventaris dalam satu alur yang jelas.</p>
            </div>

            <p class="relative text-xs font-medium tracking-widest text-white/70">REQUEST TRACKING &middot; APPROVAL &middot; INVENTORY</p>
        </section>

        {{-- Formulir --}}
        <section class="flex items-center justify-center p-6">
            <div class="w-full max-w-sm">
                <p class="text-xs font-bold uppercase tracking-widest text-secondary">Komdigi Banjarmasin</p>
                <h2 class="mt-1 font-display text-3xl font-bold text-slate-900">Masuk ke Ruang Kerja</h2>

                <form method="POST" action="{{ route('login') }}" x-data="formLogin" @submit="setTimeout(() => kirim = true, 10)" class="card mt-6 space-y-4 p-6">
                    @csrf

                    <div>
                        <label for="username" class="label">Username</label>
                        <input id="username" name="username" type="text" value="{{ old('username') }}" required autofocus autocomplete="username"
                               @class(['input', 'input-error' => $errors->has('username')])>
                        @error('username')
                            <p class="field-error" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="label">Password</label>
                        <div class="relative">
                            <input id="password" name="password" required autocomplete="current-password"
                                   :type="lihat ? 'text' : 'password'"
                                   @class(['input pr-11', 'input-error' => $errors->has('password')])>
                            <button type="button" @click="lihat = !lihat" class="absolute inset-y-0 right-0 px-3 text-slate-500 hover:text-primary"
                                    :aria-label="lihat ? 'Sembunyikan password' : 'Tampilkan password'">
                                <x-icon name="mata" class="size-5" />
                            </button>
                        </div>
                        @error('password')
                            <p class="field-error" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-primary w-full" :disabled="kirim">Masuk</button>
                </form>

                <p class="mt-4 text-xs text-slate-500">Lupa password? Hubungi administrator sistem.</p>
            </div>
        </section>
    </div>
</body>
</html>
