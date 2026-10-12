@extends('layouts.admin')
@section('title', 'Kategori Skema')
@section('content')
<div class="flex flex-wrap items-end justify-between gap-5">
    <div><a href="{{ route('admin.content.index', 'kegiatan') }}" class="text-sm font-medium text-slate-500 hover:text-[#0d2b4e]">Skema Sertifikasi</a><span class="mx-2 text-slate-300">/</span><span class="text-sm font-semibold text-[#0d2b4e]">Kategori</span><h1 class="mt-3 text-3xl font-bold tracking-tight text-slate-900">Kategori Skema</h1><p class="mt-2 text-sm text-slate-500">Rapikan pengelompokan skema dan gunakan nama kategori yang konsisten.</p></div>
    <a href="{{ route('admin.scheme-categories.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-[#0d2b4e] px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-[#143a68]"><span class="text-lg leading-none" aria-hidden="true">+</span>Tambah kategori</a>
</div>

@if (session('status'))<p role="status" class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-800">{{ session('status') }}</p>@endif
@if ($errors->any())<div role="alert" class="mt-6 rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-800"><strong>{{ $errors->first() }}</strong></div>@endif

<div class="mt-8 grid gap-4 sm:grid-cols-3">
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Total kategori</p><p class="mt-2 text-2xl font-bold text-slate-900">{{ $categories->count() }}</p></div>
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Sedang digunakan</p><p class="mt-2 text-2xl font-bold text-emerald-700">{{ $categories->where('usage', '>', 0)->count() }}</p></div>
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Belum digunakan</p><p class="mt-2 text-2xl font-bold text-slate-600">{{ $categories->where('usage', 0)->count() }}</p></div>
</div>

<section aria-label="Daftar kategori skema" class="mt-7 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-200 p-5 sm:p-6"><h2 class="text-lg font-semibold">Daftar kategori</h2><p class="mt-1 text-sm text-slate-500">Mengubah nama kategori akan memperbarui seluruh skema yang menggunakannya.</p></div>
    <div class="overflow-x-auto">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th scope="col" class="px-6 py-4 font-semibold">Nama kategori</th><th scope="col" class="px-6 py-4 font-semibold">Jumlah skema</th><th scope="col" class="px-6 py-4 text-right font-semibold">Tindakan</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($categories as $category)
                    <tr class="hover:bg-slate-50/80">
                        <td class="px-6 py-4 font-semibold text-slate-800">{{ $category['name'] }}</td>
                        <td class="px-6 py-4"><span @class(['inline-flex rounded-full px-3 py-1 text-xs font-semibold', 'bg-emerald-50 text-emerald-700' => $category['usage'] > 0, 'bg-slate-100 text-slate-600' => $category['usage'] === 0])>{{ $category['usage'] }} skema</span></td>
                        <td class="px-6 py-4"><div class="flex items-center justify-end gap-3"><a href="{{ route('admin.scheme-categories.edit', $category['id']) }}" class="font-semibold text-[#0d2b4e]">Edit</a>@if ($category['usage'] === 0)<form method="post" action="{{ route('admin.scheme-categories.destroy', $category['id']) }}" onsubmit="return confirm('Hapus kategori ini?')">@csrf @method('DELETE')<button type="submit" class="font-semibold text-red-600 hover:text-red-800">Hapus</button></form>@else<span class="text-xs font-medium text-slate-400" title="Pindahkan skema ke kategori lain sebelum menghapus">Sedang dipakai</span>@endif</div></td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="px-6 py-16 text-center"><p class="font-semibold text-slate-700">Belum ada kategori</p><p class="mt-2 text-sm text-slate-500">Tambahkan kategori pertama untuk mulai mengelompokkan skema.</p></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
