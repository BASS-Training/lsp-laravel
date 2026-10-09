@extends('layouts.admin')
@section('title', $definition['title'])
@section('content')
<div class="flex flex-wrap items-end justify-between gap-5">
    <div><a href="{{ route('admin.dashboard') }}" class="text-sm font-medium text-slate-500 hover:text-[#0d2b4e]">Dashboard</a><span class="mx-2 text-slate-300">/</span><span class="text-sm font-semibold text-[#0d2b4e]">{{ $definition['title'] }}</span><h1 class="mt-3 text-3xl font-bold tracking-tight text-slate-900">{{ $definition['title'] }}</h1><p class="mt-2 text-sm text-slate-500">{{ $definition['description'] }}</p></div>
    <a href="{{ route('admin.content.create', $module) }}" class="inline-flex items-center gap-2 rounded-xl bg-[#0d2b4e] px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-[#143a68]"><span class="text-lg leading-none" aria-hidden="true">+</span>Tambah baru</a>
</div>

@if (session('status'))<p role="status" class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-800">{{ session('status') }}</p>@endif

<div class="mt-8 grid gap-4 sm:grid-cols-3">
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Total konten</p><p class="mt-2 text-2xl font-bold text-slate-900">{{ $counts['total'] }}</p></div>
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Aktif di website</p><p class="mt-2 text-2xl font-bold text-emerald-700">{{ $counts['active'] }}</p></div>
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Nonaktif</p><p class="mt-2 text-2xl font-bold text-slate-600">{{ $counts['total'] - $counts['active'] }}</p></div>
</div>

<section aria-label="Daftar {{ $definition['title'] }}" class="mt-7 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-200 p-5 sm:p-6">
        <div class="flex flex-wrap items-center justify-between gap-2"><div><h2 class="text-lg font-semibold">Daftar konten</h2><p class="mt-1 text-sm text-slate-500">Cari dan saring konten tanpa mengubah urutan tampil.</p></div><span class="rounded-full bg-[#eaf1f9] px-3 py-1 text-xs font-semibold text-[#0d2b4e]">{{ $items->total() }} hasil</span></div>
        <form method="get" action="{{ route('admin.content.index', $module) }}" class="mt-5 grid gap-3 md:grid-cols-[minmax(0,1fr)_auto_auto_auto]">
            <label class="sr-only" for="content-search">Cari nama atau judul</label>
            <input id="content-search" name="q" value="{{ $filters['q'] }}" placeholder="Cari {{ $module === 'kegiatan' ? 'judul skema' : 'nama' }}..." class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm outline-none focus:border-[#0d2b4e]">
            <label class="sr-only" for="content-status">Filter status</label>
            <select id="content-status" name="status" class="rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm"><option value="all" @selected($filters['status'] === 'all')>Semua status</option><option value="active" @selected($filters['status'] === 'active')>Aktif</option><option value="inactive" @selected($filters['status'] === 'inactive')>Nonaktif</option></select>
            <label class="sr-only" for="content-per-page">Jumlah per halaman</label>
            <select id="content-per-page" name="per_page" class="rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm">@foreach ([10, 20, 50] as $size)<option value="{{ $size }}" @selected($filters['per_page'] === $size)>{{ $size }} per halaman</option>@endforeach</select>
            <button type="submit" class="rounded-xl bg-[#0d2b4e] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#143a68]">Terapkan</button>
        </form>
        @if ($filters['q'] !== '' || $filters['status'] !== 'all' || $filters['per_page'] !== 10)<a href="{{ route('admin.content.index', $module) }}" class="mt-3 inline-block text-sm font-medium text-[#0d2b4e] underline underline-offset-4">Hapus filter</a>@endif
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th scope="col" class="px-6 py-4 font-semibold">Konten</th><th scope="col" class="px-6 py-4 font-semibold">Status</th><th scope="col" class="px-6 py-4 font-semibold">Urutan</th><th scope="col" class="px-6 py-4 font-semibold">Diperbarui</th><th scope="col" class="px-6 py-4 text-right font-semibold">Tindakan</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($items as $item)
                    <tr class="hover:bg-slate-50/80">
                        <td class="min-w-64 px-6 py-4"><div class="flex items-center gap-3">@if ($item->{$definition['image']})<img src="{{ $item->{$definition['image']} }}" alt="" loading="lazy" class="h-12 w-12 shrink-0 rounded-lg border border-slate-100 bg-white object-cover">@else<span class="grid h-12 w-12 shrink-0 place-items-center rounded-lg bg-slate-100 font-bold text-slate-500">{{ mb_strtoupper(mb_substr($item->title ?? $item->name, 0, 1)) }}</span>@endif<div><a href="{{ route('admin.content.edit', [$module, $item->id]) }}" class="font-semibold text-slate-800 hover:text-[#0d2b4e] hover:underline">{{ $item->title ?? $item->name }}</a><p class="mt-1 max-w-xs truncate text-xs text-slate-500">{{ $item->category ?? $item->role_label ?? ($module === 'mitra' ? 'Mitra' : 'Instruktur & Asesor') }}</p></div></div></td>
                        <td class="px-6 py-4"><span @class(['inline-flex rounded-full px-3 py-1 text-xs font-semibold', 'bg-emerald-50 text-emerald-700' => $item->is_active, 'bg-slate-100 text-slate-600' => ! $item->is_active])>{{ $item->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                        <td class="px-6 py-4 font-medium text-slate-600">{{ $item->sort_order }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-slate-500">{{ $item->updated_at?->format('d M Y') ?? '—' }}</td>
                        <td class="px-6 py-4"><div class="flex items-center justify-end gap-3 whitespace-nowrap">@if ($module === 'kegiatan')<a href="{{ route('admin.content.preview', $item->id) }}" target="_blank" rel="noopener noreferrer" class="font-semibold text-slate-600 hover:text-[#0d2b4e]">Pratinjau</a>@endif<a href="{{ route('admin.content.edit', [$module, $item->id]) }}" class="font-semibold text-[#0d2b4e]">Edit</a><form method="post" action="{{ route('admin.content.destroy', [$module, $item->id]) }}" onsubmit="return confirm('Hapus konten ini?')">@csrf @method('DELETE')<button type="submit" class="font-semibold text-red-600 hover:text-red-800">Hapus</button></form></div></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-6 py-16 text-center"><p class="text-base font-semibold text-slate-700">{{ $counts['total'] === 0 ? 'Belum ada konten' : 'Tidak ada hasil yang cocok' }}</p><p class="mt-2 text-sm text-slate-500">{{ $counts['total'] === 0 ? 'Tambahkan konten pertama untuk mulai mengelola halaman ini.' : 'Coba kata kunci atau status yang berbeda.' }}</p></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="flex flex-wrap items-center justify-between gap-4 border-t border-slate-200 px-5 py-4 sm:px-6"><p class="text-sm text-slate-500">Menampilkan <strong class="text-slate-700">{{ $items->firstItem() ?? 0 }}–{{ $items->lastItem() ?? 0 }}</strong> dari <strong class="text-slate-700">{{ $items->total() }}</strong> hasil</p><div class="w-full sm:w-auto">{{ $items->onEachSide(1)->links() }}</div></div>
</section>
@endsection
