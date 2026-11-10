{{-- Field form request. Dipakai oleh create dan edit; pembungkus <form> + tombol ada di view masing-masing. --}}
@php $p = $pengajuan ?? null; @endphp

<div class="grid gap-5 md:grid-cols-2">
    <div class="md:col-span-2">
        <label for="nama_barang" class="label">Nama barang <span class="text-danger">*</span></label>
        <input id="nama_barang" name="nama_barang" type="text" maxlength="255" required value="{{ old('nama_barang', $p?->nama_barang) }}"
               @class(['input', 'input-error' => $errors->has('nama_barang')])>
        <p class="field-hint">Tuliskan nama barang secara spesifik tanpa singkatan.</p>
        @error('nama_barang')<p class="field-error" role="alert">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="kategori" class="label">Kategori <span class="text-danger">*</span></label>
        <select id="kategori" name="kategori" required @class(['input', 'input-error' => $errors->has('kategori')])>
            <option value="">Pilih kategori</option>
            @foreach ($kategori as $k)
                <option value="{{ $k->value }}" @selected(old('kategori', $p?->kategori?->value) === $k->value)>{{ $k->label() }}</option>
            @endforeach
        </select>
        <p class="field-hint">Kategori menentukan apakah barang didaftarkan sebagai aset atau stok.</p>
        @error('kategori')<p class="field-error" role="alert">{{ $message }}</p>@enderror
    </div>

    <div class="grid grid-cols-2 gap-3">
        <div>
            <label for="kuantitas" class="label">Kuantitas <span class="text-danger">*</span></label>
            <input id="kuantitas" name="kuantitas" type="number" min="1" max="9999" required x-model.number="kuantitas"
                   @class(['input', 'input-error' => $errors->has('kuantitas')])>
            @error('kuantitas')<p class="field-error" role="alert">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="satuan" class="label">Satuan <span class="text-danger">*</span></label>
            <select id="satuan" name="satuan" required @class(['input', 'input-error' => $errors->has('satuan')])>
                @foreach ($satuan as $s)
                    <option value="{{ $s->value }}" @selected(old('satuan', $p?->satuan?->value ?? 'unit') === $s->value)>{{ $s->label() }}</option>
                @endforeach
            </select>
            @error('satuan')<p class="field-error" role="alert">{{ $message }}</p>@enderror
        </div>
    </div>

    <div>
        <label for="harga_estimasi" class="label">Harga estimasi per satuan (Rp) <span class="text-danger">*</span></label>
        <div class="relative">
            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm font-medium text-slate-500">Rp</span>
            <input id="harga_estimasi" name="harga_estimasi" type="number" min="1" step="any" required x-model.number="harga"
                   @class(['input pl-10', 'input-error' => $errors->has('harga_estimasi')])>
        </div>
        <p class="field-hint">Isi angka saja, tanpa titik atau koma.</p>
        @error('harga_estimasi')<p class="field-error" role="alert">{{ $message }}</p>@enderror
    </div>

    <div class="flex items-center justify-between rounded-lg border border-sky-100 bg-sky-50 px-4 py-3">
        <div>
            <p class="text-xs font-bold uppercase tracking-wide text-slate-600">Total estimasi</p>
            <p class="text-xs text-slate-500" x-text="(Number(kuantitas) || 0) + ' × ' + rupiah(harga)"></p>
        </div>
        <p class="font-display text-2xl font-bold text-primary" x-text="rupiah(total)"></p>
    </div>

    <div class="md:col-span-2">
        <label for="spesifikasi" class="label">Spesifikasi &amp; keperluan</label>
        <textarea id="spesifikasi" name="spesifikasi" rows="5" maxlength="2000"
                  @class(['input', 'input-error' => $errors->has('spesifikasi')])>{{ old('spesifikasi', $p?->spesifikasi) }}</textarea>
        <p class="field-hint">Jelaskan spesifikasi teknis dan alasan kebutuhan. Maksimal 2000 karakter.</p>
        @error('spesifikasi')<p class="field-error" role="alert">{{ $message }}</p>@enderror
    </div>
</div>
