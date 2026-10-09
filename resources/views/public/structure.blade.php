@extends('layouts.public')
@section('title', 'Struktur LSP FIT')
@section('content')
<section class="bg-[#0d2b4e] px-5 py-16 text-white">
    <div class="mx-auto max-w-7xl lg:px-8">
        <p class="text-amber-300">Beranda / Struktur</p>
        <h1 class="mt-3 font-serif text-4xl font-semibold">Struktur LSP FIT</h1>
        <p class="mt-4 max-w-3xl text-slate-200">Susunan pengelola yang menjaga tata kelola, mutu, dan pelaksanaan sertifikasi kompetensi.</p>
    </div>
</section>

<section class="mx-auto max-w-7xl px-5 py-16 lg:px-8" aria-labelledby="structure-heading">
    <div class="mb-12 text-center">
        <p class="font-semibold uppercase tracking-widest text-amber-700">Organisasi</p>
        <h2 id="structure-heading" class="mt-2 font-serif text-3xl font-semibold">Bagan struktur organisasi</h2>
    </div>

    @if ($people->isEmpty())
        <p class="text-center text-slate-600">Belum ada data aktif.</p>
    @else
        <div aria-label="Bagan struktur organisasi LSP FIT">
            @if ($boardMembers->isNotEmpty())
                <div class="text-center">
                    <h3 class="text-sm font-bold uppercase tracking-widest text-amber-700">Dewan Komisaris</h3>
                    <div class="mx-auto mt-5 grid max-w-3xl gap-5 sm:grid-cols-2">
                        @foreach ($boardMembers as $member)
                            @include('public.structure-card', ['member' => $member])
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($boardMembers->isNotEmpty() && ($directors->isNotEmpty() || $divisionMembers->isNotEmpty()))
                <div aria-hidden="true" class="mx-auto h-12 w-px bg-amber-400"></div>
            @endif

            @if ($directors->isNotEmpty())
                <div class="text-center">
                    <h3 class="text-sm font-bold uppercase tracking-widest text-amber-700">Pimpinan</h3>
                    <div class="mx-auto mt-5 grid max-w-sm gap-5">
                        @foreach ($directors as $member)
                            @include('public.structure-card', ['member' => $member])
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($divisionMembers->isNotEmpty())
                @if ($directors->isNotEmpty())
                    <div aria-hidden="true" class="mx-auto h-12 w-px bg-amber-400"></div>
                @endif
                <div class="text-center">
                    <h3 class="text-sm font-bold uppercase tracking-widest text-amber-700">Bidang Operasional</h3>
                    <div aria-hidden="true" class="mx-auto mt-5 hidden h-px w-3/4 bg-amber-400 lg:block"></div>
                    <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-4 lg:gap-4">
                        @foreach ($divisionMembers as $member)
                            <div class="flex flex-col items-center">
                                <div aria-hidden="true" class="hidden h-6 w-px bg-amber-400 lg:block"></div>
                                @include('public.structure-card', ['member' => $member])
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @endif
</section>
@endsection
