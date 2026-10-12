@extends('layouts.public')
@section('title', 'Status Pembayaran')
@section('content')
<section class="mx-auto max-w-3xl px-5 py-16">
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-9">
        <p class="text-sm font-semibold uppercase tracking-widest text-amber-700">Status pembayaran</p><h1 class="mt-3 font-serif text-3xl font-semibold">{{ $payment->status->label() }}</h1><p class="mt-3 text-slate-600">{{ $payment->scheme_title }}</p>
        @if (session('error'))<p role="alert" class="mt-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">{{ session('error') }}</p>@endif
        <dl class="mt-7 grid gap-4 rounded-xl bg-slate-50 p-5 text-sm sm:grid-cols-2"><div><dt class="text-slate-500">Nomor order</dt><dd class="mt-1 font-semibold">{{ $payment->order_id }}</dd></div><div><dt class="text-slate-500">Total</dt><dd class="mt-1 font-semibold">Rp {{ number_format($payment->amount, 0, ',', '.') }}</dd></div><div><dt class="text-slate-500">Nama</dt><dd class="mt-1 font-semibold">{{ $payment->customer_name }}</dd></div><div><dt class="text-slate-500">Email</dt><dd class="mt-1 font-semibold">{{ $payment->customer_email }}</dd></div></dl>
        @if ($payment->status === \App\PaymentStatus::Pending)<div class="mt-7"><p class="text-sm text-slate-600">Status akan diperbarui otomatis setelah Midtrans mengonfirmasi pembayaran.</p><form method="post" action="{{ route('payments.retry', $payment->public_id) }}" class="mt-4">@csrf<button type="submit" class="rounded-xl bg-amber-400 px-6 py-3 font-semibold text-[#0d2b4e] hover:bg-amber-300">Lanjutkan pembayaran</button></form></div>@elseif ($payment->status->isPaid())<p class="mt-7 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">Pembayaran telah diterima. Konfirmasi dikirim ke email Anda.</p>@endif
        <a href="{{ route('schemes') }}" class="mt-7 inline-block text-sm font-semibold text-[#0d2b4e] underline decoration-amber-400 underline-offset-4">Kembali ke daftar skema</a>
    </div>
</section>
@endsection
