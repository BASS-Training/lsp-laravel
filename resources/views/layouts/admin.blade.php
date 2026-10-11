<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Dashboard') · Admin LSP FIT</title>
    <link rel="icon" href="{{ asset('assets/lspfit/favicon.ico') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#f7f9fc] text-slate-800 antialiased">
    <div class="min-h-screen lg:flex">
        <aside class="hidden h-screen w-68 shrink-0 flex-col border-r border-slate-200 bg-white lg:sticky lg:top-0 lg:flex">
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 border-b border-slate-100 px-6 py-6">
                <img src="{{ asset('assets/lspfit/logofit-nobg.png') }}" alt="" class="h-11 w-11 rounded-xl border border-slate-100 object-contain p-1">
                <span><strong class="block text-base text-[#0d2b4e]">LSP FIT</strong><small class="text-xs font-medium text-slate-500">Panel administrator</small></span>
            </a>
            <div class="flex-1 overflow-y-auto px-4 py-6">@include('admin.partials.navigation')</div>
            <div class="border-t border-slate-100 p-4"><a href="{{ route('home') }}" target="_blank" rel="noopener noreferrer" class="flex items-center justify-between rounded-xl px-3 py-3 text-sm font-medium text-slate-600 hover:bg-slate-100 hover:text-[#0d2b4e]">Lihat website <span aria-hidden="true">↗</span></a></div>
        </aside>

        <div class="min-w-0 flex-1">
            <header class="sticky top-0 z-20 border-b border-slate-200 bg-white/95 backdrop-blur">
                <div class="flex min-h-18 flex-wrap items-center justify-between gap-3 px-4 py-3 sm:px-6 lg:px-10">
                    <div class="flex items-center gap-4">
                        <details class="relative lg:hidden"><summary class="cursor-pointer list-none rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-[#0d2b4e]">Menu</summary><div class="absolute top-12 left-0 z-30 w-64 rounded-xl border border-slate-200 bg-white p-3 shadow-xl">@include('admin.partials.navigation')<a href="{{ route('home') }}" class="mt-3 block border-t border-slate-100 px-3 pt-3 text-sm text-slate-600">Lihat website ↗</a></div></details>
                        <div><span class="block text-xs font-semibold uppercase tracking-wider text-slate-400">Panel admin</span><span class="block text-sm font-semibold text-slate-800">@yield('title', 'Dashboard')</span></div>
                    </div>
                    <div class="flex items-center gap-3 sm:gap-5"><span class="hidden text-right sm:block"><strong class="block text-sm">{{ auth()->user()->name }}</strong><small class="block text-xs capitalize text-slate-500">{{ str_replace('_', ' ', auth()->user()->role) }}</small></span><span class="grid h-9 w-9 place-items-center rounded-full bg-[#e8eef6] text-sm font-bold text-[#0d2b4e]">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span><form method="post" action="{{ route('admin.logout') }}">@csrf<button type="submit" class="text-sm font-semibold text-slate-600 hover:text-red-700">Keluar</button></form></div>
                </div>
            </header>
            <main class="mx-auto max-w-screen-2xl px-4 py-7 sm:px-6 lg:px-10 lg:py-9">@yield('content')</main>
        </div>
    </div>
</body>
</html>
