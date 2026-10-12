<x-mail::message>
# Pembayaran Berhasil

Halo {{ $payment->customer_name }},

Pembayaran Anda untuk **{{ $payment->scheme_title }}** telah kami terima.

- Nomor order: {{ $payment->order_id }}
- Total: Rp {{ number_format($payment->amount, 0, ',', '.') }}
- Metode pembayaran: {{ $payment->payment_type ?: 'Midtrans' }}
- Waktu pembayaran: {{ $payment->paid_at?->format('d M Y H:i') }} WIB

<x-mail::button :url="route('payments.show', $payment->public_id)">
Lihat Status Pembayaran
</x-mail::button>

Tim LSP FIT akan menghubungi Anda untuk proses pendaftaran berikutnya.

Terima kasih,<br>
LSP FIT
</x-mail::message>
