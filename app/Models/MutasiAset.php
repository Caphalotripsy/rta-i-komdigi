<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MutasiAset extends Model
{
    protected $table = 'mutasi_aset';

    protected $guarded = ['id'];

    public function aset(): BelongsTo
    {
        return $this->belongsTo(AsetInventaris::class, 'aset_inventaris_id');
    }

    public function pelaksana(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dilakukan_oleh');
    }
}
