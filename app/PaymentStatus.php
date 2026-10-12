<?php

namespace App;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Failed = 'failed';
    case Expired = 'expired';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';

    public static function fromMidtrans(string $status, ?string $fraudStatus = null): self
    {
        return match ($status) {
            'settlement' => self::Paid,
            'capture' => $fraudStatus === 'accept' ? self::Paid : self::Pending,
            'deny', 'failure' => self::Failed,
            'expire' => self::Expired,
            'cancel' => self::Cancelled,
            'refund', 'partial_refund', 'chargeback', 'partial_chargeback' => self::Refunded,
            default => self::Pending,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu pembayaran',
            self::Paid => 'Lunas',
            self::Failed => 'Gagal',
            self::Expired => 'Kedaluwarsa',
            self::Cancelled => 'Dibatalkan',
            self::Refunded => 'Dikembalikan',
        };
    }

    public function isPaid(): bool
    {
        return $this === self::Paid;
    }
}
