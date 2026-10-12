<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCheckoutRequest;
use App\Models\Kegiatan;
use App\Models\PaymentTransaction;
use App\Models\SchemePaymentSetting;
use App\PaymentStatus;
use App\Services\MidtransConfiguration;
use App\Services\MidtransGateway;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Throwable;

class CheckoutController extends Controller
{
    public function __construct(
        private MidtransConfiguration $configuration,
        private MidtransGateway $midtrans,
    ) {}

    public function create(Kegiatan $kegiatan): View
    {
        $paymentSetting = $this->paymentSetting($kegiatan);
        $attemptId = (string) Str::uuid();
        session()->put('payment_attempts.'.$attemptId, $kegiatan->getKey());

        return view('public.checkout', [
            'scheme' => $kegiatan,
            'paymentSetting' => $paymentSetting,
            'attemptId' => $attemptId,
        ]);
    }

    public function store(StoreCheckoutRequest $request, Kegiatan $kegiatan): RedirectResponse
    {
        $validated = $request->validated();
        $attemptKey = 'payment_attempts.'.$validated['attempt_id'];
        abort_unless((int) session()->pull($attemptKey) === (int) $kegiatan->getKey(), 419);
        $paymentSetting = $this->paymentSetting($kegiatan);

        $payment = PaymentTransaction::query()->create([
            'public_id' => $validated['attempt_id'],
            'order_id' => 'LSP-'.Str::ulid(),
            'kegiatan_id' => $kegiatan->getKey(),
            'scheme_title' => $kegiatan->title,
            'amount' => $paymentSetting->price,
            'customer_name' => $validated['name'],
            'customer_email' => $validated['email'],
            'customer_phone' => $validated['phone'],
            'status' => PaymentStatus::Pending,
        ]);

        try {
            $midtrans = $this->midtrans->createTransaction($payment);
            $payment->update(['midtrans_redirect_url' => $midtrans['redirect_url']]);

            return redirect()->away($midtrans['redirect_url']);
        } catch (Throwable $exception) {
            report($exception);
            $payment->update(['status_message' => 'Pembayaran belum dapat dibuka. Silakan coba beberapa saat lagi.']);

            return redirect()->route('payments.show', $payment->public_id)
                ->with('error', 'Midtrans belum dapat dihubungi. Gunakan tombol lanjutkan pembayaran untuk mencoba kembali.');
        }
    }

    public function show(PaymentTransaction $payment): View
    {
        return view('public.payment-status', ['payment' => $payment]);
    }

    public function retry(PaymentTransaction $payment): RedirectResponse
    {
        abort_unless($payment->status === PaymentStatus::Pending, 409);

        if ($payment->midtrans_redirect_url !== null) {
            return redirect()->away($payment->midtrans_redirect_url);
        }

        try {
            $midtrans = $this->midtrans->createTransaction($payment);
            $payment->update([
                'midtrans_redirect_url' => $midtrans['redirect_url'],
                'status_message' => null,
            ]);

            return redirect()->away($midtrans['redirect_url']);
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', 'Midtrans masih belum dapat dihubungi. Silakan coba kembali.');
        }
    }

    private function paymentSetting(Kegiatan $kegiatan): SchemePaymentSetting
    {
        abort_unless($kegiatan->is_active && $this->configuration->configured(), 404);
        $paymentSetting = $kegiatan->paymentSetting()->first();
        abort_unless($paymentSetting?->is_enabled && $paymentSetting->price > 0, 404);

        return $paymentSetting;
    }
}
