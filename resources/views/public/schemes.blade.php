@extends('layouts.public')
@section('title', 'Skema Sertifikasi')
@section('content')
<section class="bg-[#0d2b4e] px-5 py-16 text-white"><div class="mx-auto max-w-7xl lg:px-8"><p class="text-amber-300">Beranda / Skema Sertifikasi</p><h1 class="mt-3 font-serif text-4xl font-semibold">Skema Sertifikasi LSP FIT</h1><p class="mt-4 max-w-3xl text-slate-200">Skema untuk fasilitator, instruktur, dan tenaga kepelatihan.</p></div></section>
<section class="mx-auto max-w-7xl px-5 py-16 lg:px-8">
    <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">@forelse ($schemes as $scheme)<article class="border-t-4 border-amber-400 bg-white p-6 shadow-sm"><p class="text-xs font-semibold uppercase tracking-wider text-amber-700">{{ $scheme->category }}</p><h2 class="mt-3 font-serif text-xl font-semibold">{{ $scheme->title }}</h2><p class="mt-3 text-slate-600">{{ $scheme->summary }}</p><a href="{{ route('schemes.show', ['id' => $scheme->id]) }}" class="mt-6 inline-block font-semibold text-[#0d2b4e] underline decoration-amber-400 underline-offset-4">Lihat detail →</a></article>@empty<p class="text-slate-600">Belum ada skema aktif.</p>@endforelse</div>
    @if ($schemes->hasPages())
        <div class="mt-10">{{ $schemes->onEachSide(1)->links() }}</div>
    @endif
</section>
@endsection
