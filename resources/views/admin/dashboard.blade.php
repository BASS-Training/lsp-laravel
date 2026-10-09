@extends('layouts.admin')
@section('title', 'Dashboard')
@section('content')
<div class="flex flex-wrap items-end justify-between gap-5">
    <div><p class="text-sm font-semibold text-[#0d2b4e]">Selamat datang, {{ auth()->user()->name }}</p><h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-900">Ringkasan website</h1><p class="mt-2 text-sm text-slate-500">Pantau konten aktif dan buka modul yang ingin Anda kelola.</p></div>
    <a href="{{ route('home') }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-[#0d2b4e] shadow-sm hover:border-[#0d2b4e]">Lihat website <span aria-hidden="true">↗</span></a>
</div>

<section aria-labelledby="content-summary-title" class="mt-8">
    <h2 id="content-summary-title" class="text-base font-semibold text-slate-800">Konten publik</h2>
    <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($summaries as $summary)
            <a href="{{ route('admin.content.index', $summary['module']) }}" class="group rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-slate-300 hover:shadow-md">
                <div class="flex items-start justify-between gap-3"><span class="grid h-10 w-10 place-items-center rounded-xl bg-[#eaf1f9] font-bold text-[#0d2b4e]">{{ mb_strtoupper(mb_substr($summary['label'], 0, 1)) }}</span><span class="text-slate-400 group-hover:text-[#0d2b4e]" aria-hidden="true">↗</span></div>
                <p class="mt-5 text-3xl font-bold tracking-tight text-slate-900">{{ number_format($summary['total']) }}</p>
                <h3 class="mt-1 text-sm font-medium text-slate-600">{{ $summary['label'] }}</h3>
                <p class="mt-4 border-t border-slate-100 pt-3 text-xs font-medium text-emerald-700">{{ $summary['active'] }} aktif di website</p>
            </a>
        @endforeach
    </div>
</section>

<section aria-labelledby="manage-content-title" class="mt-9 grid gap-5 xl:grid-cols-[1.5fr_1fr]">
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3"><div><h2 id="manage-content-title" class="text-lg font-semibold">Kelola konten</h2><p class="mt-1 text-sm text-slate-500">Pilih modul untuk membuka daftar, pencarian, dan halaman edit.</p></div></div>
        <div class="mt-5 divide-y divide-slate-100">
            @foreach ($summaries as $summary)
                <a href="{{ route('admin.content.index', $summary['module']) }}" class="flex items-center justify-between gap-3 py-3.5 text-sm hover:text-[#0d2b4e]"><span class="font-medium">{{ $summary['label'] }}</span><span class="text-slate-400" aria-hidden="true">→</span></a>
            @endforeach
        </div>
    </div>
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold">Pengaturan halaman</h2><p class="mt-1 text-sm text-slate-500">Perbarui isi beranda dan profil lembaga.</p>
        <div class="mt-5 grid gap-3"><a href="{{ route('admin.settings.edit', 'homepage') }}" class="flex items-center justify-between rounded-xl bg-slate-50 px-4 py-4 text-sm font-semibold hover:bg-[#eaf1f9]">Beranda <span aria-hidden="true">→</span></a><a href="{{ route('admin.settings.edit', 'profil') }}" class="flex items-center justify-between rounded-xl bg-slate-50 px-4 py-4 text-sm font-semibold hover:bg-[#eaf1f9]">Profil Website <span aria-hidden="true">→</span></a></div>
    </div>
</section>
@endsection
