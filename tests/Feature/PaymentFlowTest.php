<?php

use App\Mail\PaymentConfirmation;
use App\Models\Kegiatan;
use App\Models\PaymentTransaction;
use App\Models\SchemePaymentSetting;
use App\PaymentStatus;
use App\Services\MidtransConfiguration;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

function payableKegiatan(array $overrides = []): Kegiatan
{
    return Kegiatan::create([
        'title' => 'Skema Berbayar',
        'slug' => 'skema-berbayar',
        'category' => 'Kegiatan',
        'summary' => 'Ringkasan',
        'description' => 'Deskripsi',
        'image_url' => '/assets/lspfit/logofit.jpeg',
        'is_active' => true,
        'is_highlight' => false,
        'sort_order' => 0,
        ...$overrides,
    ]);
}

function configureMidtrans(): void
{
    app(MidtransConfiguration::class)->save([
        'enabled' => true,
        'environment' => 'sandbox',
        'merchant_id' => 'G123456789',
        'server_key' => 'SB-Mid-server-test',
        'client_key' => 'SB-Mid-client-test',
    ]);
}

function paymentRecord(array $overrides = []): PaymentTransaction
{
    return PaymentTransaction::create([
        'public_id' => (string) Str::uuid(),
        'order_id' => 'LSP-'.Str::ulid(),
        'kegiatan_id' => null,
        'scheme_title' => 'Skema Berbayar',
        'amount' => 500000,
        'customer_name' => 'Budi Peserta',
        'customer_email' => 'budi@example.com',
        'customer_phone' => '08123456789',
        'status' => PaymentStatus::Pending,
        ...$overrides,
    ]);
}

it('creates a server-priced checkout and redirects to Midtrans', function (): void {
    configureMidtrans();
    $scheme = payableKegiatan();
    SchemePaymentSetting::create(['kegiatan_id' => $scheme->id, 'price' => 500000, 'is_enabled' => true]);
    Http::preventStrayRequests();
    Http::fake([
        'https://app.sandbox.midtrans.com/snap/v1/transactions' => Http::response([
            'token' => 'snap-token',
            'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v2/vtweb/snap-token',
        ], 201),
    ]);
    $checkout = $this->get('/skema/'.$scheme->id.'/bayar')->assertOk();
    $attemptId = $checkout->viewData('attemptId');

    $this->post('/skema/'.$scheme->id.'/bayar', [
        'attempt_id' => $attemptId,
        'name' => 'Budi Peserta',
        'email' => 'BUDI@example.com',
        'phone' => '08123456789',
    ])->assertRedirect('https://app.sandbox.midtrans.com/snap/v2/vtweb/snap-token');

    $payment = PaymentTransaction::firstOrFail();
    expect($payment->amount)->toBe(500000)
        ->and($payment->customer_email)->toBe('budi@example.com')
        ->and($payment->status)->toBe(PaymentStatus::Pending);
    Http::assertSent(fn (Request $request): bool => $request['transaction_details']['gross_amount'] === 500000
        && $request->hasHeader('Authorization', 'Basic '.base64_encode('SB-Mid-server-test:')));
});

it('hides checkout when payment is disabled or Midtrans is not configured', function (): void {
    $scheme = payableKegiatan();
    SchemePaymentSetting::create(['kegiatan_id' => $scheme->id, 'price' => 500000, 'is_enabled' => false]);

    $this->get('/skema/'.$scheme->id.'/bayar')->assertNotFound();
});

it('accepts a valid paid webhook once and sends one confirmation email', function (): void {
    configureMidtrans();
    Mail::fake();
    $payment = paymentRecord();
    $payload = [
        'order_id' => $payment->order_id,
        'status_code' => '200',
        'gross_amount' => '500000.00',
        'transaction_status' => 'settlement',
        'transaction_id' => 'midtrans-transaction-id',
        'payment_type' => 'qris',
        'merchant_id' => 'G123456789',
    ];
    $payload['signature_key'] = hash('sha512', $payment->order_id.'200500000.00SB-Mid-server-test');

    $this->postJson('/midtrans/notification', $payload)->assertOk()->assertJson(['accepted' => true]);
    $this->postJson('/midtrans/notification', $payload)->assertOk();

    $payment->refresh();
    expect($payment->status)->toBe(PaymentStatus::Paid)
        ->and($payment->paid_at)->not->toBeNull()
        ->and($payment->confirmation_sent_at)->not->toBeNull();
    Mail::assertSent(PaymentConfirmation::class, 1);
});

it('rejects an invalid webhook signature and amount mismatch', function (): void {
    configureMidtrans();
    $payment = paymentRecord();
    $payload = [
        'order_id' => $payment->order_id,
        'status_code' => '200',
        'gross_amount' => '500000.00',
        'transaction_status' => 'settlement',
        'merchant_id' => 'G123456789',
        'signature_key' => 'invalid',
    ];

    $this->postJson('/midtrans/notification', $payload)->assertForbidden();

    $payload['gross_amount'] = '400000.00';
    $payload['signature_key'] = hash('sha512', $payment->order_id.'200400000.00SB-Mid-server-test');
    $this->postJson('/midtrans/notification', $payload)->assertUnprocessable();
    expect($payment->fresh()->status)->toBe(PaymentStatus::Pending);
});

it('does not regress a terminal payment when an older pending webhook arrives', function (): void {
    configureMidtrans();
    $payment = paymentRecord(['status' => PaymentStatus::Expired, 'expired_at' => now()]);
    $payload = [
        'order_id' => $payment->order_id,
        'status_code' => '201',
        'gross_amount' => '500000.00',
        'transaction_status' => 'pending',
        'merchant_id' => 'G123456789',
    ];
    $payload['signature_key'] = hash('sha512', $payment->order_id.'201500000.00SB-Mid-server-test');

    $this->postJson('/midtrans/notification', $payload)->assertOk();

    expect($payment->fresh()->status)->toBe(PaymentStatus::Expired);
});
