<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Anggota;
use App\Models\Instruktur;
use App\Models\Kegiatan;
use App\Models\Partner;
use App\Models\SiteSetting;
use App\Services\MidtransConfiguration;
use App\Services\PublicContentCache;
use App\Services\SchemeCategoryCatalog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class SiteController extends Controller
{
    public function __construct(private PublicContentCache $cache, private MidtransConfiguration $midtrans) {}

    public function home(): View
    {
        return view('public.home', [
            'settings' => $this->settings(),
            'highlights' => $this->cache->collection('kegiatan', 'public.home.highlights', Kegiatan::active()->where('is_highlight', true)->orderByDesc('sort_order')->limit(3)),
            'members' => $this->cache->collection('anggota', 'public.home.members', Anggota::active()->orderByDesc('sort_order')->orderBy('name')->limit(4)),
            'instructors' => $this->cache->collection('instruktur', 'public.home.instructors', Instruktur::active()->orderByDesc('sort_order')->orderBy('name')->limit(4)),
            'partners' => $this->cache->collection('partners', 'public.home.partners', Partner::active()->orderByDesc('sort_order')->orderBy('name')),
        ]);
    }

    public function about(): View
    {
        return view('public.about', ['settings' => $this->settings()]);
    }

    public function schemes(): View
    {
        $page = max(1, (int) request()->query('page', 1));
        $schemes = $this->cache->paginate(
            'kegiatan',
            'public.schemes.page.'.$page,
            Kegiatan::active()->orderByDesc('sort_order')->orderByDesc('created_at')->orderByDesc('id'),
            6,
            ['path' => request()->url(), 'query' => request()->query()],
        );

        return view('public.schemes', ['schemes' => $schemes]);
    }

    public function scheme(Request $request): View
    {
        $validated = $request->validate(['id' => ['required', 'integer', 'min:1']]);

        $scheme = $this->cache->model('kegiatan', 'public.scheme.'.$validated['id'], Kegiatan::active()->whereKey($validated['id']));

        return view('public.scheme', [
            'scheme' => $scheme,
            'paymentSetting' => $scheme->paymentSetting()->first(),
            'midtransConfigured' => $this->midtrans->configured(),
        ]);
    }

    public function members(): View
    {
        $people = $this->cache->collection('anggota', 'public.members', Anggota::active()->orderByDesc('sort_order')->orderBy('name'));
        $roleHas = fn (Anggota $member, array $keywords): bool => str_contains(mb_strtolower($member->role_label), $keywords[0])
            || (isset($keywords[1]) && str_contains(mb_strtolower($member->role_label), $keywords[1]));
        $chairs = $people->filter(fn (Anggota $member): bool => str_contains(mb_strtolower($member->role_label), 'ketua')
            && $roleHas($member, ['pengarah', 'komisaris']));
        $boardMembers = $people->reject(fn (Anggota $member): bool => $chairs->contains($member))
            ->filter(fn (Anggota $member): bool => $roleHas($member, ['pengarah', 'komisaris']));
        $directors = $people->filter(fn (Anggota $member): bool => str_contains(mb_strtolower($member->role_label), 'direktur'));

        return view('public.structure', [
            'people' => $people,
            'chairs' => $chairs,
            'boardMembers' => $boardMembers,
            'directors' => $directors,
            'divisionMembers' => $people->reject(fn (Anggota $member): bool => $chairs->contains($member) || $boardMembers->contains($member) || $directors->contains($member)),
        ]);
    }

    public function instructors(): View
    {
        $page = max(1, (int) request()->query('page', 1));

        return view('public.people', [
            'title' => 'Instruktur & Asesor LSP FIT',
            'people' => $this->cache->paginate(
                'instruktur',
                'public.instructors.page.'.$page,
                Instruktur::active()->orderByDesc('sort_order')->orderBy('name')->orderBy('id'),
                8,
                ['path' => request()->url(), 'query' => request()->query()],
            ),
            'roleField' => null,
        ]);
    }

    public function contact(): View
    {
        $settings = $this->settings();
        $phone = preg_replace('/\D+/', '', $settings->get('contact_phone', '0811-1210-1007'));

        return view('public.contact', [
            'settings' => $settings,
            'whatsAppNumber' => str_starts_with($phone, '0') ? '62'.substr($phone, 1) : $phone,
        ]);
    }

    /** @return Collection<string, string|null> */
    private function settings(): Collection
    {
        return collect($this->cache->remember(
            'settings',
            'public.settings.all',
            fn (): array => SiteSetting::query()
                ->where('setting_key', 'not like', SchemeCategoryCatalog::SETTING_PREFIX.'%')
                ->where('setting_key', 'not like', MidtransConfiguration::SETTING_PREFIX.'%')
                ->pluck('setting_value', 'setting_key')
                ->all(),
        ));
    }
}
