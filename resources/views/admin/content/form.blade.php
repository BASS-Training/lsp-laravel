@extends('layouts.admin')
@section('title', ($item ? 'Edit ' : 'Tambah ').$definition['title'])
@section('content')
<a href="{{ route('admin.content.index', $module) }}" class="inline-flex items-center gap-2 text-sm font-medium text-slate-500 hover:text-[#0d2b4e]"><span aria-hidden="true">←</span>Kembali ke daftar</a>
<div class="mt-5 flex flex-wrap items-end justify-between gap-4"><div><p class="text-sm font-semibold text-[#0d2b4e]">{{ $definition['title'] }}</p><h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-900">{{ $item ? 'Edit konten' : 'Tambah konten' }}</h1><p class="mt-2 text-sm text-slate-500">{{ $definition['description'] }}</p></div>@if ($item && $module === 'kegiatan')<a href="{{ route('admin.content.preview', $item->id) }}" target="_blank" rel="noopener noreferrer" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-[#0d2b4e] hover:border-[#0d2b4e]">Pratinjau ↗</a>@endif</div>

@if ($errors->any())<div role="alert" class="mt-6 rounded-xl border border-red-200 bg-red-50 p-5 text-sm text-red-800"><strong>Periksa isian berikut:</strong><ul class="mt-2 list-disc space-y-1 pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

<form method="post" enctype="multipart/form-data" action="{{ $item ? route('admin.content.update', [$module, $item->id]) : route('admin.content.store', $module) }}" class="mt-7 grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]">
    @csrf
    @if ($item) @method('PUT') @endif

    <section aria-labelledby="content-fields-title" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
        <h2 id="content-fields-title" class="text-lg font-semibold">Informasi utama</h2><p class="mt-1 text-sm text-slate-500">Isi data yang akan muncul di halaman publik.</p>
        <div class="mt-6 grid gap-5">
            @foreach ($definition['fields'] as $field => $label)
                <div>
                    <label for="field-{{ $field }}" class="block text-sm font-semibold text-slate-700">{{ $label }}</label>
                    @if (in_array($field, ['summary', 'description'], true))
                        <textarea id="field-{{ $field }}" name="{{ $field }}" rows="{{ $field === 'description' ? 8 : 4 }}" required class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-[#0d2b4e]">{{ old($field, $item?->{$field}) }}</textarea>
                    @else
                        <input id="field-{{ $field }}" name="{{ $field }}" value="{{ old($field, $item?->{$field}) }}" required class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-[#0d2b4e]">
                    @endif
                </div>
            @endforeach
        </div>
    </section>

    <div class="grid gap-6">
        <section aria-labelledby="image-title" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><h2 id="image-title" class="text-base font-semibold">Gambar</h2><p class="mt-1 text-xs text-slate-500">JPG, PNG, atau WEBP. Maksimal 4 MB.</p>@if ($item && $item->{$definition['image']})<div class="mt-4 flex h-40 items-center justify-center overflow-hidden rounded-xl border border-slate-200 bg-slate-50"><img src="{{ $item->{$definition['image']} }}" alt="Gambar konten saat ini" class="max-h-full max-w-full object-contain"></div>@endif<label for="content-image" class="mt-4 block text-sm font-medium text-slate-700">{{ $item ? 'Ganti gambar' : 'Unggah gambar' }}</label><input id="content-image" type="file" name="image" accept="image/jpeg,image/png,image/webp" class="mt-2 block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-[#eaf1f9] file:px-3 file:py-2 file:font-semibold file:text-[#0d2b4e]"></section>

        <section aria-labelledby="visibility-title" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><h2 id="visibility-title" class="text-base font-semibold">Tampilan</h2><label class="mt-5 flex items-start gap-3 text-sm"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $item?->is_active ?? true)) class="mt-0.5 h-4 w-4 rounded border-slate-300 text-[#0d2b4e]"><span><strong class="block font-semibold">Aktif di website</strong><small class="mt-1 block text-slate-500">Konten dapat dilihat pengunjung.</small></span></label>@if ($module === 'kegiatan')<label class="mt-5 flex items-start gap-3 border-t border-slate-100 pt-5 text-sm"><input type="checkbox" name="is_highlight" value="1" @checked(old('is_highlight', $item?->is_highlight ?? false)) class="mt-0.5 h-4 w-4 rounded border-slate-300 text-[#0d2b4e]"><span><strong class="block font-semibold">Skema unggulan</strong><small class="mt-1 block text-slate-500">Tampilkan dalam sorotan beranda.</small></span></label>@endif<label for="sort-order" class="mt-5 block text-sm font-semibold">Urutan tampil</label><input id="sort-order" type="number" name="sort_order" value="{{ old('sort_order', $item?->sort_order ?? 0) }}" required class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-[#0d2b4e]"><p class="mt-1 text-xs text-slate-500">Angka lebih besar tampil lebih dahulu.</p></section>

        <div class="flex flex-wrap gap-3"><button type="submit" class="rounded-xl bg-[#0d2b4e] px-5 py-3 text-sm font-semibold text-white hover:bg-[#143a68]">Simpan konten</button><a href="{{ route('admin.content.index', $module) }}" class="rounded-xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-600 hover:bg-slate-50">Batal</a></div>
    </div>
</form>
@endsection
