{{-- Field: nama_barang, kategori, spesifikasi, kuantitas, harga_estimasi. Tombol kirim memakai name="aksi" (draft|ajukan). --}}
@php $p = $pengajuan ?? null; @endphp
<div class="space-y-4">
    <div>
        <label for="nama_barang" class="block text-sm mb-1">Nama barang</label>
        <input id="nama_barang" name="nama_barang" type="text" required maxlength="255"
               value="{{ old('nama_barang', $p?->nama_barang) }}" class="w-full border rounded px-3 py-2">
    </div>

    <div>
        <label for="kategori" class="block text-sm mb-1">Kategori</label>
        <select id="kategori" name="kategori" required class="w-full border rounded px-3 py-2">
            <option value="">-- pilih --</option>
            @foreach ($kategori as $k)
                <option value="{{ $k->value }}" @selected(old('kategori', $p?->kategori?->value) === $k->value)>{{ $k->label() }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label for="spesifikasi" class="block text-sm mb-1">Spesifikasi</label>
        <textarea id="spesifikasi" name="spesifikasi" rows="3" maxlength="2000"
                  class="w-full border rounded px-3 py-2">{{ old('spesifikasi', $p?->spesifikasi) }}</textarea>
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div>
            <label for="kuantitas" class="block text-sm mb-1">Kuantitas</label>
            <input id="kuantitas" name="kuantitas" type="number" min="1" max="9999" required
                   value="{{ old('kuantitas', $p?->kuantitas) }}" class="w-full border rounded px-3 py-2">
        </div>
        <div>
            <label for="harga_estimasi" class="block text-sm mb-1">Harga estimasi per satuan (Rp)</label>
            <input id="harga_estimasi" name="harga_estimasi" type="number" min="1" step="any" required
                   value="{{ old('harga_estimasi', $p?->harga_estimasi ? (int) $p->harga_estimasi : null) }}"
                   class="w-full border rounded px-3 py-2">
        </div>
    </div>
</div>
