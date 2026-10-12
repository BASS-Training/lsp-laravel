<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchemePaymentSetting extends Model
{
    protected $fillable = ['kegiatan_id', 'price', 'is_enabled'];

    /** @return BelongsTo<Kegiatan, $this> */
    public function kegiatan(): BelongsTo
    {
        return $this->belongsTo(Kegiatan::class);
    }

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'is_enabled' => 'boolean',
        ];
    }
}
