@extends('layouts.public')

@section('title', 'Beranda')
@section('description', 'Sertifikasi kompetensi untuk fasilitator, instruktur, dan tenaga kepelatihan di LSP FIT.')

@section('content')
<section class="border-b-8 border-amber-400 bg-[#0d2b4e] text-white">
    <div class="mx-auto max-w-7xl px-5 py-20 lg:px-8 lg:py-28">
        <div class="border-l-8 border-amber-400 pl-6 lg:pl-10">
            <p class="mb-5 text-sm font-semibold uppercase tracking-widest text-amber-300">LSP FIT · Berlisensi BNSP</p>
            <h1 class="max-w-4xl font-serif text-4xl font-bold leading-tight md:text-6xl">{{ $settings->get('home_hero_title', 'Kompetensi profesional yang diakui.') }}</h1>
            <p class="mt-6 max-w-2xl text-lg text-slate-200">{{ $settings->get('home_hero_description') }}</p>
            <div class="mt-9 flex flex-wrap gap-3"><a href="{{ route('schemes') }}" class="bg-amber-400 px-6 py-3 font-semibold text-[#0d2b4e] hover:bg-amber-300">{{ $settings->get('home_hero_button_text', 'Lihat skema sertifikasi') }}</a><a href="{{ route('contact') }}" class="border border-white px-6 py-3 font-semibold hover:bg-white/10">Hubungi kami</a></div>
        </div>
    </div>
</section>
<section class="mx-auto max-w-7xl px-5 py-16 lg:px-8"><div class="flex flex-wrap items-end justify-between gap-6"><div><p class="font-semibold uppercase tracking-widest text-amber-600">Sertifikasi profesi</p><h2 class="mt-2 font-serif text-3xl font-semibold">Skema unggulan</h2></div><a href="{{ route('schemes') }}" class="font-semibold text-[#0d2b4e] underline decoration-amber-400 underline-offset-4">Semua skema →</a></div><div class="mt-8 grid gap-5 md:grid-cols-3">@forelse ($highlights as $scheme)<a href="{{ route('schemes.show', ['id' => $scheme->id]) }}" class="bg-white p-6 shadow-sm hover:shadow-md"><p class="text-xs font-semibold uppercase tracking-wide text-amber-700">{{ $scheme->category }}</p><h3 class="mt-3 font-serif text-xl font-semibold">{{ $scheme->title }}</h3><p class="mt-2 text-slate-600">{{ $scheme->summary }}</p></a>@empty<p class="text-slate-600">Belum ada skema unggulan.</p>@endforelse</div></section>
@foreach (explode(',', $settings->get('home_section_order', 'profile,members,partners,cta')) as $section)
    @if ($section === 'profile' && $settings->get('home_show_profile', '1') === '1')
        <section class="bg-white py-16"><div class="mx-auto grid max-w-7xl items-center gap-10 px-5 lg:grid-cols-2 lg:px-8"><div><p class="font-semibold uppercase tracking-widest text-amber-600">{{ $settings->get('home_profile_kicker', 'Tentang LSP FIT') }}</p><h2 class="mt-3 font-serif text-3xl font-semibold md:text-4xl">{{ $settings->get('home_profile_title') }}</h2><p class="mt-5 text-slate-600">{{ $settings->get('home_profile_card_description') }}</p><a href="{{ route('about') }}" class="mt-7 inline-block border-b-2 border-amber-400 pb-1 font-semibold text-[#0d2b4e]">Kenali LSP FIT →</a></div><a href="{{ asset('assets/penghargaan/sertifikat-penghargaan-bnsp-2025.png') }}" target="_blank" rel="noopener noreferrer" aria-label="Lihat sertifikat penghargaan BNSP dalam ukuran penuh"><img src="{{ asset('assets/penghargaan/sertifikat-penghargaan-bnsp-2025.png') }}" alt="Sertifikat penghargaan BNSP untuk LSP FIT" loading="lazy" class="w-full rounded-lg border border-slate-200 object-contain shadow-sm"></a></div></section>
    @elseif ($section === 'members' && $settings->get('home_show_members', '1') === '1')
        <section class="mx-auto max-w-7xl px-5 py-16 lg:px-8"><h2 class="font-serif text-3xl font-semibold">{{ $settings->get('home_members_title', 'Tim LSP FIT') }}</h2><div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">@foreach ($members as $member)<a href="{{ route('members') }}" class="bg-white p-5 shadow-sm"><img src="{{ $member->photo_url }}" alt="Foto {{ $member->name }}" class="h-44 w-full object-cover"><h3 class="mt-4 font-semibold">{{ $member->name }}</h3><p class="text-sm text-slate-600">{{ $member->role_label }}</p></a>@endforeach</div></section>
    @elseif ($section === 'instructors' && $settings->get('home_show_instructors', '0') === '1')
        <section class="mx-auto max-w-7xl px-5 py-16 lg:px-8">
            <h2 class="font-serif text-3xl font-semibold">{{ $settings->get('home_instructors_title', 'Instruktur & Asesor LSP FIT') }}</h2>
            <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">@foreach ($instructors as $instructor)<a href="{{ route('instructors') }}" class="bg-white p-5 shadow-sm"><img src="{{ $instructor->photo_url }}" alt="Foto {{ $instructor->name }}" class="h-44 w-full object-cover"><h3 class="mt-4 font-semibold">{{ $instructor->name }}</h3></a>@endforeach</div>
            <a href="{{ route('instructors') }}" class="mt-8 inline-flex items-center gap-2 border-b-2 border-amber-400 pb-1 font-semibold text-[#0d2b4e] hover:text-amber-700">Lihat semua instruktur &amp; asesor <span aria-hidden="true">→</span></a>
        </section>
    @elseif ($section === 'partners' && $settings->get('home_show_partners', '1') === '1')
        <section class="bg-white py-16">
            <div class="mx-auto max-w-7xl px-5 lg:px-8" data-carousel>
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <h2 class="font-serif text-3xl font-semibold">{{ $settings->get('home_partners_title', 'Mitra LSP FIT') }}</h2>
                    <div class="flex gap-2" aria-label="Navigasi logo mitra">
                        <button type="button" data-carousel-prev aria-label="Mitra sebelumnya" aria-controls="partner-carousel" class="grid h-10 w-10 place-items-center rounded-full border border-slate-300 text-[#0d2b4e] hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-40">←</button>
                        <button type="button" data-carousel-next aria-label="Mitra berikutnya" aria-controls="partner-carousel" class="grid h-10 w-10 place-items-center rounded-full border border-slate-300 text-[#0d2b4e] hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-40">→</button>
                    </div>
                </div>
                <ul id="partner-carousel" data-carousel-track tabindex="0" aria-label="Logo mitra, geser untuk melihat lainnya" class="mt-8 flex snap-x snap-mandatory gap-4 overflow-x-auto pb-4">
                    @foreach ($partners as $partner)
                        <li class="flex h-28 w-40 shrink-0 snap-start items-center justify-center rounded-lg border border-slate-200 p-4 sm:w-48 lg:w-52"><img src="{{ $partner->logo_url }}" alt="Logo {{ $partner->name }}" loading="lazy" class="max-h-full max-w-full object-contain"></li>
                    @endforeach
                </ul>
            </div>
        </section>
    @elseif ($section === 'cta' && $settings->get('home_show_cta', '1') === '1')
        <section class="bg-[#0d2b4e] py-14 text-white"><div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-6 px-5 lg:px-8"><div><h2 class="font-serif text-3xl font-semibold">{{ $settings->get('home_cta_title') }}</h2><p class="mt-2 text-slate-200">{{ $settings->get('home_cta_description') }}</p></div><a href="{{ route('contact') }}" class="bg-amber-400 px-6 py-3 font-semibold text-[#0d2b4e] hover:bg-amber-300">{{ $settings->get('home_cta_button_text', 'Konsultasi sekarang') }}</a></div></section>
    @elseif ($section === 'activities' && $settings->get('home_show_activities', '0') === '1')
        <section class="mx-auto max-w-7xl px-5 py-16 lg:px-8"><p class="font-semibold uppercase tracking-widest text-amber-700">{{ $settings->get('home_activities_kicker', 'LSP FIT') }}</p><h2 class="mt-2 font-serif text-3xl font-semibold">{{ $settings->get('home_activities_title') }}</h2><p class="mt-3 text-slate-600">{{ $settings->get('home_activities_description') }}</p></section>
    @endif
@endforeach
@endsection
