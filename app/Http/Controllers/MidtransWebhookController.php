<?php

namespace App\Http\Controllers;

use App\Models\PaymentTransaction;
use App\PaymentStatus;
use App\Services\MidtransConfiguration;
use App\Services\MidtransGateway;
use App\Services\PaymentConfirmationSender;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MidtransWebhookController extends Controller
{
    public function __construct(
        private MidtransConfiguration $configuration,
        private MidtransGateway $midtrans,
        private PaymentConfirmationSender $confirmation,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'order_id' => ['required', 'string', 'max:50'],
            'status_code' => ['required', 'string', 'max:10'],
            'gross_amount' => ['required', 'numeric'],
            'transaction_status' => ['required', 'string', 'max:30'],
            'signature_key' => ['required', 'string'],
            'merchant_id' => ['nullable', 'string', 'max:80'],
            'transaction_id' => ['nullable', 'string', 'max:80'],
            'payment_type' => ['nullable', 'string', 'max:50'],
            'fraud_status' => ['nullable', 'string', 'max:30'],
            'status_message' => ['nullable', 'string', 'max:1000'],
        ]);

        abort_unless($this->midtrans->hasValidSignature($request->all()), 403);

        if (filled($payload['merchant_id'] ?? null)) {
            abort_unless(hash_equals((string) $this->configuration->merchantId(), (string) $payload['merchant_id']), 403);
        }

        $payment = PaymentTransaction::query()->where('order_id', $payload['order_id'])->firstOrFail();
        abort_unless(bccomp((string) $payload['gross_amount'], (string) $payment->amount, 2) === 0, 422);

        $payment = DB::transaction(function () use ($payment, $payload, $request): PaymentTransaction {
            $lockedPayment = PaymentTransaction::query()->lockForUpdate()->findOrFail($payment->getKey());
            $newStatus = PaymentStatus::fromMidtrans(
                $payload['transaction_status'],
                $payload['fraud_status'] ?? null,
            );

            if ($newStatus === PaymentStatus::Pending && $lockedPayment->status !== PaymentStatus::Pending) {
                $newStatus = $lockedPayment->status;
            }

            if ($lockedPayment->status === PaymentStatus::Refunded
                || ($lockedPayment->status === PaymentStatus::Paid && ! in_array($newStatus, [PaymentStatus::Paid, PaymentStatus::Refunded], true))) {
                $newStatus = $lockedPayment->status;
            }

            $lockedPayment->fill([
                'status' => $newStatus,
                'midtrans_status' => $payload['transaction_status'],
                'midtrans_transaction_id' => $payload['transaction_id'] ?? null,
                'payment_type' => $payload['payment_type'] ?? null,
                'fraud_status' => $payload['fraud_status'] ?? null,
                'status_message' => $payload['status_message'] ?? null,
                'notification_payload' => $request->all(),
            ]);

            if ($newStatus === PaymentStatus::Paid && $lockedPayment->paid_at === null) {
                $lockedPayment->paid_at = now();
            }

            if ($newStatus === PaymentStatus::Expired && $lockedPayment->expired_at === null) {
                $lockedPayment->expired_at = now();
            }

            $lockedPayment->save();

            return $lockedPayment;
        });

        if ($payment->status->isPaid()) {
            $this->confirmation->send($payment);
        }

        return response()->json(['accepted' => true]);
    }
}
