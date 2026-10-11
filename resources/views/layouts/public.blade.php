<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0d2b4e">
    <title>@yield('title', 'Beranda') · LSP FIT</title>
    <meta name="description" content="@yield('description', 'Lembaga Sertifikasi Profesi Fasilitator, Instruktur dan Tenaga Kepelatihan')">
    <link rel="icon" href="{{ asset('assets/lspfit/favicon.ico') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 text-slate-900 antialiased">
    <a class="sr-only focus:not-sr-only focus:absolute focus:z-50 focus:bg-white focus:p-3" href="#content">Lewati ke konten</a>
    <header class="sticky top-0 z-40 border-b border-slate-200 bg-white/95 backdrop-blur">
        <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-4 px-5 py-3 lg:px-8">
            <a href="{{ route('home') }}" aria-label="LSP FIT, beranda">
                <img src="{{ asset('assets/lspfit/logofit-nobg.png') }}" alt="Logo LSP FIT" class="h-12 w-auto object-contain sm:h-14">
            </a>
            <nav aria-label="Navigasi utama" class="flex w-full flex-wrap items-center gap-x-5 gap-y-2 text-sm font-medium lg:w-auto">
                <a href="{{ route('home') }}" @class(['border-b-2 border-amber-400 text-[#0d2b4e]' => request()->routeIs('home'), 'text-slate-600 hover:text-[#0d2b4e]' => ! request()->routeIs('home')])>Beranda</a>
                <a href="{{ route('about') }}" @class(['border-b-2 border-amber-400 text-[#0d2b4e]' => request()->routeIs('about'), 'text-slate-600 hover:text-[#0d2b4e]' => ! request()->routeIs('about')])>Tentang</a>
                <a href="{{ route('members') }}" @class(['border-b-2 border-amber-400 text-[#0d2b4e]' => request()->routeIs('members'), 'text-slate-600 hover:text-[#0d2b4e]' => ! request()->routeIs('members')])>Struktur</a>
                <a href="{{ route('instructors') }}" @class(['border-b-2 border-amber-400 text-[#0d2b4e]' => request()->routeIs('instructors'), 'text-slate-600 hover:text-[#0d2b4e]' => ! request()->routeIs('instructors')])>Instruktur &amp; Asesor</a>
                <a href="{{ route('schemes') }}" @class(['border-b-2 border-amber-400 text-[#0d2b4e]' => request()->routeIs('schemes'), 'text-slate-600 hover:text-[#0d2b4e]' => ! request()->routeIs('schemes')])>Skema</a>
                <a href="{{ route('contact') }}" @class(['border-b-2 border-amber-400 text-[#0d2b4e]' => request()->routeIs('contact'), 'text-slate-600 hover:text-[#0d2b4e]' => ! request()->routeIs('contact')])>Kontak</a>
                <a href="https://app.msdmup.id/" target="_blank" rel="noopener noreferrer" class="text-slate-600 hover:text-[#0d2b4e]">Portal LMS</a>
            </nav>
        </div>
    </header>
    <main id="content">@yield('content')</main>
    <footer class="bg-[#0d2b4e] text-slate-200">
        <div class="mx-auto grid max-w-7xl gap-8 px-5 py-10 md:grid-cols-2 lg:px-8">
            <div><strong class="text-lg text-white">LSP FIT</strong><p class="mt-2 max-w-lg text-sm">Lembaga Sertifikasi Profesi Fasilitator, Instruktur dan Tenaga Kepelatihan. Berlisensi BNSP No. BNSP-LSP-444-ID.</p></div>
            <div class="text-sm md:text-right"><p>Ruko Akasa Blok B No. 5, Serpong, Tangerang Selatan</p><a class="underline decoration-amber-400 underline-offset-4" href="mailto:info.lspfit@gmail.com">info.lspfit@gmail.com</a></div>
        </div>
    </footer>
</body>
</html>
