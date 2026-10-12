<?php

namespace App\Models;

use App\PaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentTransaction extends Model
{
    protected $fillable = [
        'public_id',
        'order_id',
        'kegiatan_id',
        'scheme_title',
        'amount',
        'customer_name',
        'customer_email',
        'customer_phone',
        'status',
        'midtrans_status',
        'midtrans_transaction_id',
        'payment_type',
        'fraud_status',
        'status_message',
        'midtrans_redirect_url',
        'notification_payload',
        'paid_at',
        'expired_at',
        'confirmation_sent_at',
    ];

    /** @return BelongsTo<Kegiatan, $this> */
    public function kegiatan(): BelongsTo
    {
        return $this->belongsTo(Kegiatan::class);
    }

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'status' => PaymentStatus::class,
            'notification_payload' => 'array',
            'paid_at' => 'datetime',
            'expired_at' => 'datetime',
            'confirmation_sent_at' => 'datetime',
        ];
    }
}
