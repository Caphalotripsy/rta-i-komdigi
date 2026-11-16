<?php

namespace App\Http\Controllers;

use App\Enums\KondisiAset;
use App\Models\AsetInventaris;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Halaman cetak (dicetak lewat browser; bisa disimpan sebagai PDF). */
class CetakController extends Controller
{
    public function label(Request $request): View
    {
        $aset = AsetInventaris::with('inventaris')
            ->when($request->query('pengajuan'), fn ($w, $id) => $w->where('pengajuan_id', (int) $id))
            ->when($request->query('aset'), fn ($w, $id) => $w->where('id', (int) $id))
            ->orderBy('sku_barcode')
            ->get();

        abort_if($aset->isEmpty(), 404);

        return view('cetak.label', ['aset' => $aset]);
    }

    public function laporan(Request $request): View
    {
        $q = trim((string) $request->query('q'));
        $kondisi = (string) $request->query('kondisi');

        $aset = AsetInventaris::with('inventaris')->cari($q, $kondisi)->orderBy('sku_barcode')->get();

        return view('cetak.laporan', [
            'aset' => $aset, 'q' => $q, 'kondisi' => KondisiAset::tryFrom($kondisi),
            'perKondisi' => $aset->countBy(fn ($a) => $a->kondisi->value),
        ]);
    }
}
