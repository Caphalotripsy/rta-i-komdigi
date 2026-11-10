<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LaporHargaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('lapor', $this->route('pengajuan'));
    }

    public function rules(): array
    {
        $p = $this->route('pengajuan');

        return [
            'kuantitas_terbeli' => ['required', 'integer', 'min:1', 'max:'.$p->kuantitas],
            'harga_aktual' => ['required', 'numeric', 'min:1', 'max:'.$p->harga_estimasi],
            'nama_toko' => ['required', 'string', 'max:255'],
            'nomor_nota' => ['required', 'string', 'max:100'],
            'tanggal_nota' => ['required', 'date', 'before_or_equal:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'kuantitas_terbeli.required' => 'Kuantitas terbeli wajib diisi.',
            'kuantitas_terbeli.integer' => 'Kuantitas terbeli harus berupa bilangan bulat.',
            'kuantitas_terbeli.min' => 'Kuantitas terbeli minimal 1.',
            'kuantitas_terbeli.max' => 'Kuantitas terbeli tidak boleh melebihi kuantitas yang diminta (:max).',
            'harga_aktual.required' => 'Harga aktual wajib diisi.',
            'harga_aktual.numeric' => 'Harga aktual harus berupa angka (tanpa titik atau koma pemisah ribuan).',
            'harga_aktual.min' => 'Harga aktual minimal Rp 1.',
            'harga_aktual.max' => 'Harga aktual tidak boleh melebihi harga estimasi.',
            'nama_toko.required' => 'Nama toko wajib diisi.',
            'nomor_nota.required' => 'Nomor nota wajib diisi.',
            'tanggal_nota.required' => 'Tanggal nota wajib diisi.',
            'tanggal_nota.date' => 'Tanggal nota tidak valid.',
            'tanggal_nota.before_or_equal' => 'Tanggal nota tidak boleh di masa depan.',
        ];
    }
}
