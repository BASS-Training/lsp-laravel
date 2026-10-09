@extends('layouts.public')
@section('title', $scheme->title)
@section('content')
<section class="bg-[#0d2b4e] px-5 py-16 text-white"><div class="mx-auto max-w-7xl lg:px-8"><a href="{{ route('schemes') }}" class="text-amber-300 underline underline-offset-4">← Kembali ke skema</a><p class="mt-6 text-amber-300">{{ $scheme->category }}</p><h1 class="mt-3 font-serif text-4xl font-semibold">{{ $scheme->title }}</h1><p class="mt-4 max-w-3xl text-slate-200">{{ $scheme->summary }}</p></div></section>
<section class="mx-auto grid max-w-7xl gap-8 px-5 py-16 lg:grid-cols-[1fr_2fr] lg:px-8"><img src="{{ $scheme->image_url }}" alt="Ilustrasi {{ $scheme->title }}" class="w-full rounded bg-white object-contain p-6"><div class="rounded bg-white p-8 shadow-sm"><h2 class="font-serif text-2xl font-semibold">Deskripsi skema</h2><div class="mt-5 whitespace-pre-line leading-relaxed text-slate-700">{{ $scheme->description }}</div><a href="{{ route('contact') }}" class="mt-8 inline-block bg-amber-400 px-6 py-3 font-semibold text-[#0d2b4e]">Tanyakan skema ini</a></div></section>
@endsection
