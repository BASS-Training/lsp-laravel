<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login Admin · LSP FIT</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="grid min-h-screen place-items-center bg-slate-100 p-5 text-slate-800">
    <main class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-8 shadow-xl">
        <a href="{{ route('home') }}" class="flex items-center gap-3"><img src="{{ asset('asset/lspfit/logofit-nobg.png') }}" alt="" class="h-12 w-12 object-contain"><span class="font-semibold text-[#0d2b4e]">LSP FIT</span></a>
        <h1 class="mt-8 text-3xl font-semibold">Masuk ke dashboard</h1>
        <p class="mt-2 text-sm text-slate-500">Kelola konten website LSP FIT.</p>
        @if ($errors->any())<p role="alert" class="mt-6 rounded-lg bg-red-50 p-3 text-sm text-red-700">{{ $errors->first() }}</p>@endif
        <form method="post" action="{{ route('admin.login') }}" class="mt-7 grid gap-5">
            @csrf
            <label class="grid gap-2 text-sm font-medium">Username<input name="username" value="{{ old('username') }}" autocomplete="username" required autofocus class="rounded-lg border border-slate-300 px-4 py-3 outline-none focus:border-amber-500"></label>
            <label class="grid gap-2 text-sm font-medium">Password<input name="password" type="password" autocomplete="current-password" required class="rounded-lg border border-slate-300 px-4 py-3 outline-none focus:border-amber-500"></label>
            <button type="submit" class="rounded-lg bg-[#0d2b4e] px-5 py-3 font-semibold text-white hover:bg-[#143a68]">Masuk</button>
        </form>
    </main>
</body>
</html>
