<article class="flex h-full w-full flex-col items-center rounded-2xl border border-slate-200 border-t-4 border-t-amber-400 bg-white px-5 py-6 text-center shadow-sm">
    <img src="{{ $member->photo_url }}" alt="Foto {{ $member->name }}" loading="lazy" class="h-24 w-24 rounded-full border-4 border-slate-100 object-cover">
    <h4 class="mt-4 font-serif text-lg font-semibold text-[#0d2b4e]">{{ $member->name }}</h4>
    <p class="mt-1 text-sm font-medium text-slate-600">{{ $member->role_label }}</p>
</article>
