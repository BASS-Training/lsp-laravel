<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentTransaction;
use App\PaymentStatus;
use App\Services\PaymentConfirmationSender;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    public function __construct(private PaymentConfirmationSender $confirmation) {}

    public function index(Request $request): View
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['all', ...array_column(PaymentStatus::cases(), 'value')])],
        ]);
        $search = trim($validated['q'] ?? '');
        $status = $validated['status'] ?? 'all';
        $query = PaymentTransaction::query();

        if ($search !== '') {
            $query->where(function ($query) use ($search): void {
                $query->where('order_id', 'like', '%'.$search.'%')
                    ->orWhere('customer_name', 'like', '%'.$search.'%')
                    ->orWhere('customer_email', 'like', '%'.$search.'%');
            });
        }

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        return view('admin.payments.index', [
            'payments' => $query->latest()->paginate(20)->withQueryString(),
            'filters' => ['q' => $search, 'status' => $status],
            'counts' => [
                'total' => PaymentTransaction::query()->count(),
                'paid' => PaymentTransaction::query()->where('status', PaymentStatus::Paid)->count(),
                'pending' => PaymentTransaction::query()->where('status', PaymentStatus::Pending)->count(),
            ],
        ]);
    }

    public function show(PaymentTransaction $payment): View
    {
        return view('admin.payments.show', ['payment' => $payment]);
    }

    public function resend(PaymentTransaction $payment): RedirectResponse
    {
        abort_unless($payment->status->isPaid(), 409);

        return $this->confirmation->send($payment, force: true)
            ? back()->with('status', 'Email konfirmasi berhasil dikirim ulang.')
            : back()->withErrors(['email' => 'Email konfirmasi gagal dikirim. Periksa konfigurasi email.']);
    }
}
