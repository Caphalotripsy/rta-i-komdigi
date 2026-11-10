<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AsetInventaris extends Model
{
    protected $table = 'aset_inventaris';

    protected $guarded = ['id'];

    public function inventaris(): BelongsTo
    {
        return $this->belongsTo(Inventaris::class, 'inventaris_id');
    }

    public function pengajuan(): BelongsTo
    {
        return $this->belongsTo(PengajuanPengadaan::class, 'pengajuan_id');
    }
}
