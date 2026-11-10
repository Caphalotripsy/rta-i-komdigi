@extends('layouts.sementara')

@section('title', 'Pengajuan Saya - RTA-I Komdigi')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-xl font-bold text-primary">Pengajuan Saya</h1>
        <a href="{{ route('pengajuan.create') }}" class="bg-primary text-white rounded px-4 py-2 hover:bg-secondary">+ Tambah Request</a>
    </div>

    <form method="GET" class="mb-4 flex gap-2 items-center text-sm">
        <label for="status">Status:</label>
        <select id="status" name="status" onchange="this.form.submit()" class="border rounded px-2 py-1">
            <option value="">Semua</option>
            @foreach ($semuaStatus as $s)
                <option value="{{ $s->value }}" @selected($status === $s)>{{ $s->label() }}</option>
            @endforeach
        </select>
    </form>

    <div class="bg-white rounded-lg shadow overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-left">
                <tr>
                    <th class="p-3">Kode</th>
                    <th class="p-3">Barang</th>
                    <th class="p-3">Kategori</th>
                    <th class="p-3 text-right">Qty</th>
                    <th class="p-3 text-right">Total estimasi</th>
                    <th class="p-3">Status</th>
                    <th class="p-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pengajuan as $p)
                    <tr class="border-t">
                        <td class="p-3">{{ $p->kode_pengajuan }}</td>
                        <td class="p-3">{{ $p->nama_barang }}</td>
                        <td class="p-3">{{ $p->kategori->label() }}</td>
                        <td class="p-3 text-right">{{ $p->kuantitas }}</td>
                        <td class="p-3 text-right">@rupiah($p->total_estimasi_biaya)</td>
                        <td class="p-3"><x-status-badge :status="$p->status" /></td>
                        <td class="p-3 text-right"><a href="{{ route('pengajuan.show', $p) }}" class="text-secondary hover:underline">Detail</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="p-6 text-center text-gray-500">Belum ada pengajuan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $pengajuan->links() }}</div>
@endsection
