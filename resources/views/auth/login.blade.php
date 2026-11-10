<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - RTA-I Komdigi</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen flex items-center justify-center bg-gray-100">
    {{-- Halaman sementara untuk uji backend. Frontend: ganti tampilan sesuai Figma, pertahankan name/@csrf/@error. --}}
    <form method="POST" action="{{ route('login') }}" class="w-full max-w-sm bg-white p-8 rounded-lg shadow space-y-4">
        @csrf
        <h1 class="text-xl font-bold text-primary">Masuk ke RTA-I</h1>

        <div>
            <label for="email" class="block text-sm mb-1">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                   class="w-full border rounded px-3 py-2">
            @error('email')
                <p class="text-sm text-danger mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="block text-sm mb-1">Password</label>
            <input id="password" name="password" type="password" required class="w-full border rounded px-3 py-2">
        </div>

        <button type="submit" class="w-full bg-primary text-white rounded py-2 hover:bg-secondary">Masuk</button>
    </form>
</body>
</html>
