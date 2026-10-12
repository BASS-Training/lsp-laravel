<?php

namespace App\Services;

use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MidtransGateway
{
    public function __construct(private MidtransConfiguration $configuration) {}

    /** @return array{token: string, redirect_url: string} */
    public function createTransaction(PaymentTransaction $payment): array
    {
        $serverKey = $this->configuration->serverKey();

        if (! $this->configuration->configured() || $serverKey === null) {
            throw new RuntimeException('Midtrans belum dikonfigurasi.');
        }

        $response = Http::withBasicAuth($serverKey, '')
            ->acceptJson()
            ->asJson()
            ->connectTimeout(5)
            ->timeout(15)
            ->post($this->configuration->snapTransactionUrl(), [
                'transaction_details' => [
                    'order_id' => $payment->order_id,
                    'gross_amount' => $payment->amount,
                ],
                'item_details' => [[
                    'id' => 'scheme-'.$payment->kegiatan_id,
                    'price' => $payment->amount,
                    'quantity' => 1,
                    'name' => mb_substr($payment->scheme_title, 0, 50),
                ]],
                'customer_details' => [
                    'first_name' => $payment->customer_name,
                    'email' => $payment->customer_email,
                    'phone' => $payment->customer_phone,
                ],
                'callbacks' => [
                    'finish' => route('payments.show', $payment->public_id),
                ],
                'credit_card' => ['secure' => true],
            ])
            ->throw();

        $token = $response->json('token');
        $redirectUrl = $response->json('redirect_url');

        if (! is_string($token) || ! is_string($redirectUrl) || $token === '' || $redirectUrl === '') {
            throw new RuntimeException('Respons Midtrans tidak lengkap.');
        }

        return ['token' => $token, 'redirect_url' => $redirectUrl];
    }

    /** @param  array<string, mixed>  $payload */
    public function hasValidSignature(array $payload): bool
    {
        $serverKey = $this->configuration->serverKey();

        if ($serverKey === null) {
            return false;
        }

        $signature = (string) ($payload['signature_key'] ?? '');
        $expected = hash('sha512',
            (string) ($payload['order_id'] ?? '').
            (string) ($payload['status_code'] ?? '').
            (string) ($payload['gross_amount'] ?? '').
            $serverKey,
        );

        return $signature !== '' && hash_equals($expected, $signature);
    }
}
