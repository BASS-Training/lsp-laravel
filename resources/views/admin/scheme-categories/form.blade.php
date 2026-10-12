@extends('layouts.admin')
@section('title', $category ? 'Edit Kategori Skema' : 'Tambah Kategori Skema')
@section('content')
<a href="{{ route('admin.scheme-categories.index') }}" class="inline-flex items-center gap-2 text-sm font-medium text-slate-500 hover:text-[#0d2b4e]"><span aria-hidden="true">←</span>Kembali ke kategori</a>
<div class="mt-5"><p class="text-sm font-semibold text-[#0d2b4e]">Kategori Skema</p><h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-900">{{ $category ? 'Edit kategori' : 'Tambah kategori' }}</h1><p class="mt-2 text-sm text-slate-500">Gunakan nama yang singkat, jelas, dan mudah dikenali pengunjung.</p></div>

@if ($errors->any())<div role="alert" class="mt-6 rounded-xl border border-red-200 bg-red-50 p-5 text-sm text-red-800"><strong>Periksa isian berikut:</strong><ul class="mt-2 list-disc space-y-1 pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

<form method="post" action="{{ $category ? route('admin.scheme-categories.update', $categoryId) : route('admin.scheme-categories.store') }}" class="mt-7 max-w-2xl rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
    @csrf
    @if ($category) @method('PUT') @endif
    <label for="category-name" class="block text-sm font-semibold text-slate-700">Nama kategori</label>
    <input id="category-name" name="name" value="{{ old('name', $category) }}" maxlength="80" required autofocus placeholder="Contoh: Bidang Instruktur - Jenjang 3" class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-[#0d2b4e]">
    @if ($category)<p class="mt-2 text-xs text-slate-500">Semua skema dalam kategori ini ikut diperbarui saat nama disimpan.</p>@endif
    <div class="mt-7 flex flex-wrap gap-3"><button type="submit" class="rounded-xl bg-[#0d2b4e] px-5 py-3 text-sm font-semibold text-white hover:bg-[#143a68]">Simpan kategori</button><a href="{{ route('admin.scheme-categories.index') }}" class="rounded-xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-600 hover:bg-slate-50">Batal</a></div>
</form>
@endsection
