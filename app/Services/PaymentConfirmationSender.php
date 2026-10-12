<?php

namespace App\Services;

use App\Mail\PaymentConfirmation;
use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Throwable;

class PaymentConfirmationSender
{
    public function send(PaymentTransaction $payment, bool $force = false): bool
    {
        $claimed = DB::transaction(function () use ($payment, $force): bool {
            $lockedPayment = PaymentTransaction::query()->lockForUpdate()->findOrFail($payment->getKey());

            if ($force) {
                $lockedPayment->confirmation_sent_at = null;
            }

            if ($lockedPayment->confirmation_sent_at !== null) {
                return false;
            }

            $lockedPayment->confirmation_sent_at = now();
            $lockedPayment->save();

            return true;
        });

        if (! $claimed) {
            return false;
        }

        try {
            Mail::to($payment->customer_email)->send(new PaymentConfirmation($payment->fresh()));

            return true;
        } catch (Throwable $exception) {
            PaymentTransaction::query()
                ->whereKey($payment->getKey())
                ->update(['confirmation_sent_at' => null]);
            report($exception);

            return false;
        }
    }
}
