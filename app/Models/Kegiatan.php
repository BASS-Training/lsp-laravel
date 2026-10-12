<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Kegiatan extends Model
{
    protected $table = 'kegiatan';

    protected $guarded = ['id'];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** @return HasOne<SchemePaymentSetting, $this> */
    public function paymentSetting(): HasOne
    {
        return $this->hasOne(SchemePaymentSetting::class);
    }

    /** @return HasMany<PaymentTransaction, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'is_highlight' => 'boolean', 'sort_order' => 'integer'];
    }
}
