<?php

namespace App\Http\Requests;

use App\Enums\Kategori;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PengajuanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // akses dijaga oleh middleware role dan Policy di controller
    }

    public function rules(): array
    {
        // Batas dipilih agar kuantitas x harga selalu muat di kolom decimal(15,2).
        return [
            'nama_barang' => ['required', 'string', 'max:255'],
            'kategori' => ['required', Rule::enum(Kategori::class)],
            'spesifikasi' => ['nullable', 'string', 'max:2000'],
            'kuantitas' => ['required', 'integer', 'min:1', 'max:9999'],
            'harga_estimasi' => ['required', 'numeric', 'min:1', 'max:1000000000'],
            'aksi' => ['nullable', Rule::in(['draft', 'ajukan'])],
        ];
    }

    public function attributes(): array
    {
        return [
            'nama_barang' => 'nama barang',
            'harga_estimasi' => 'harga estimasi',
        ];
    }

    public function messages(): array
    {
        return [
            'nama_barang.required' => 'Nama barang wajib diisi.',
            'nama_barang.max' => 'Nama barang maksimal 255 karakter.',
            'kategori.required' => 'Kategori wajib dipilih.',
            'kategori.enum' => 'Kategori tidak valid.',
            'spesifikasi.max' => 'Spesifikasi maksimal 2000 karakter.',
            'kuantitas.required' => 'Kuantitas wajib diisi.',
            'kuantitas.integer' => 'Kuantitas harus berupa bilangan bulat.',
            'kuantitas.min' => 'Kuantitas minimal 1.',
            'kuantitas.max' => 'Kuantitas maksimal 9.999.',
            'harga_estimasi.required' => 'Harga estimasi wajib diisi.',
            'harga_estimasi.numeric' => 'Harga estimasi harus berupa angka (tanpa titik atau koma pemisah ribuan).',
            'harga_estimasi.min' => 'Harga estimasi minimal Rp 1.',
            'harga_estimasi.max' => 'Harga estimasi maksimal Rp 1.000.000.000 per satuan.',
        ];
    }
}
