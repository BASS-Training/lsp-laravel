@extends('layouts.public')
@section('title', $title)
@section('content')
<section class="bg-[#0d2b4e] px-5 py-16 text-white"><div class="mx-auto max-w-7xl lg:px-8"><p class="text-amber-300">Beranda / {{ $title }}</p><h1 class="mt-3 font-serif text-4xl font-semibold">{{ $title }}</h1></div></section>
<section class="mx-auto max-w-7xl px-5 py-16 lg:px-8">
    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">@forelse ($people as $person)<article class="overflow-hidden bg-white shadow-sm"><img src="{{ $person->photo_url }}" alt="Foto {{ $person->name }}" loading="lazy" class="h-56 w-full object-cover"><div class="border-t-4 border-amber-400 p-5"><h2 class="font-serif text-lg font-semibold">{{ $person->name }}</h2>@if ($roleField)<p class="mt-1 text-sm text-slate-600">{{ $person->{$roleField} }}</p>@endif</div></article>@empty<p class="text-slate-600">Belum ada data aktif.</p>@endforelse</div>
    @if ($people instanceof \Illuminate\Contracts\Pagination\Paginator && $people->hasPages())
        <div class="mt-10">{{ $people->onEachSide(1)->links() }}</div>
    @endif
</section>
@endsection
