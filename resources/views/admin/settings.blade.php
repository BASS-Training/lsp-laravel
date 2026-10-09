@extends('layouts.admin')
@section('title', $title)
@section('content')
<a href="{{ route('admin.dashboard') }}" class="text-sm font-medium text-slate-500 hover:text-[#0d2b4e]">Dashboard</a><span class="mx-2 text-slate-300">/</span><span class="text-sm font-semibold text-[#0d2b4e]">{{ $title }}</span>
<h1 class="mt-3 text-3xl font-bold tracking-tight text-slate-900">Kelola {{ strtolower($title) }}</h1><p class="mt-2 text-sm text-slate-500">Perbarui teks, gambar, dan bagian yang tampil di website.</p>

@if (session('status'))<p role="status" class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-800">{{ session('status') }}</p>@endif
@if ($errors->any())<div role="alert" class="mt-6 rounded-xl border border-red-200 bg-red-50 p-5 text-sm text-red-800"><strong>Periksa isian berikut:</strong><ul class="mt-2 list-disc pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

<form method="post" enctype="multipart/form-data" action="{{ route('admin.settings.update', $group) }}" class="mt-7 grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_17rem]">
    @csrf @method('PUT')
    <div class="grid min-w-0 gap-6">
        @foreach ($sections as $section)
            <section id="{{ \Illuminate\Support\Str::slug($section['title']) }}" aria-labelledby="section-{{ $loop->index }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                <div class="border-b border-slate-100 pb-5"><h2 id="section-{{ $loop->index }}" class="text-lg font-semibold">{{ $section['title'] }}</h2><p class="mt-1 text-sm text-slate-500">Atur isi dan tampilan bagian ini.</p></div>
                <div class="mt-6 grid gap-5 sm:grid-cols-2">
                    @foreach ($section['fields'] as $setting)
                        @php($key = $setting->setting_key)
                        @if (str_starts_with($key, 'home_show_') || $key === 'about_show_video')
                            <label class="flex items-start gap-3 rounded-xl border border-slate-200 p-4 text-sm"><input type="checkbox" name="{{ $key }}" value="1" @checked(old($key, $setting->setting_value) === '1') class="mt-0.5 h-4 w-4 rounded border-slate-300"><span><strong class="block font-semibold">{{ $labels[$key] }}</strong><small class="mt-1 block text-slate-500">Tampilkan bagian ini di website.</small></span></label>
                        @else
                            <div @class(['sm:col-span-2' => preg_match('/description|mission|quote|address|section_order/', $key)])>
                                <label for="setting-{{ $key }}" class="block text-sm font-semibold text-slate-700">{{ $labels[$key] }}</label>
                                @if (preg_match('/description|mission|quote|address|section_order/', $key))
                                    <textarea id="setting-{{ $key }}" name="{{ $key }}" rows="{{ $key === 'mission' ? 6 : 3 }}" class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-[#0d2b4e]">{{ old($key, $setting->setting_value) }}</textarea>
                                @else
                                    <input id="setting-{{ $key }}" name="{{ $key }}" value="{{ old($key, $setting->setting_value) }}" class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-[#0d2b4e]">
                                @endif
                            </div>
                        @endif
                    @endforeach
                </div>
                @if ($group === 'profil' && $section['title'] === 'Sambutan pimpinan')
                    <div class="mt-6 border-t border-slate-100 pt-5">
                        <label for="leader-image-file" class="block text-sm font-semibold">Foto pimpinan</label>
                        @if ($media->get('about_leader_image'))
                            <img src="{{ $media->get('about_leader_image') }}" alt="Foto pimpinan saat ini" class="mt-3 h-48 w-full rounded-xl border border-slate-200 bg-slate-50 object-contain">
                        @else
                            <p class="mt-3 text-sm text-slate-500">Belum ada foto pimpinan.</p>
                        @endif
                        <p class="mt-3 text-xs text-slate-500">Unggah JPG, PNG, atau WEBP baru untuk mengganti foto. Maksimal 4 MB.</p>
                        <input id="leader-image-file" type="file" name="about_leader_image_file" accept="image/jpeg,image/png,image/webp" class="mt-3 block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-[#eaf1f9] file:px-3 file:py-2 file:font-semibold file:text-[#0d2b4e]">
                    </div>
                @endif
                @if ($group === 'profil' && $section['title'] === 'Video profil')
                    <div class="mt-6 border-t border-slate-100 pt-5">
                        <label for="profile-video-file" class="block text-sm font-semibold">Video profil</label>
                        @if (str_starts_with($media->get('about_video_url', ''), '/storage/uploads/profile/') || str_starts_with($media->get('about_video_url', ''), '/assets/uploads/profile/'))
                            <video controls preload="metadata" src="{{ $media->get('about_video_url') }}" class="mt-3 w-full rounded-xl bg-slate-900"></video>
                        @elseif (in_array(parse_url($media->get('about_video_url', ''), PHP_URL_SCHEME), ['http', 'https'], true))
                            <a href="{{ $media->get('about_video_url') }}" target="_blank" rel="noopener noreferrer" class="mt-3 inline-block font-medium text-[#0d2b4e] underline">Lihat video saat ini</a>
                        @else
                            <p class="mt-3 text-sm text-slate-500">Belum ada video profil.</p>
                        @endif
                        <p class="mt-3 text-xs text-slate-500">Unggah MP4 baru untuk mengganti video. Maksimal 50 MB.</p>
                        <input id="profile-video-file" type="file" name="about_video_file" accept="video/mp4" class="mt-3 block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-[#eaf1f9] file:px-3 file:py-2 file:font-semibold file:text-[#0d2b4e]">
                    </div>
                @endif
            </section>
        @endforeach
        <button type="submit" class="w-fit rounded-xl bg-[#0d2b4e] px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-[#143a68]">Simpan perubahan</button>
    </div>
    <aside class="hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-sm xl:sticky xl:top-26 xl:block"><h2 class="text-sm font-semibold">Isi halaman</h2><nav aria-label="Lompat ke bagian" class="mt-4 grid gap-1 text-sm">@foreach ($sections as $section)<a href="#{{ \Illuminate\Support\Str::slug($section['title']) }}" class="rounded-lg px-3 py-2 text-slate-600 hover:bg-slate-100 hover:text-[#0d2b4e]">{{ $section['title'] }}</a>@endforeach</nav><p class="mt-5 border-t border-slate-100 pt-4 text-xs leading-relaxed text-slate-500">Perubahan tersimpan setelah tombol Simpan perubahan ditekan.</p></aside>
</form>
@endsection
