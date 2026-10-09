<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Anggota;
use App\Models\Instruktur;
use App\Models\Kegiatan;
use App\Models\Partner;
use App\Models\SiteSetting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class SiteController extends Controller
{
    public function home(): View
    {
        return view('public.home', [
            'settings' => SiteSetting::query()->pluck('setting_value', 'setting_key'),
            'highlights' => Kegiatan::active()->where('is_highlight', true)->orderByDesc('sort_order')->limit(3)->get(),
            'members' => Anggota::active()->orderByDesc('sort_order')->orderBy('name')->limit(4)->get(),
            'instructors' => Instruktur::active()->orderByDesc('sort_order')->orderBy('name')->limit(4)->get(),
            'partners' => Partner::active()->orderByDesc('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function about(): View
    {
        return view('public.about', ['settings' => SiteSetting::query()->pluck('setting_value', 'setting_key')]);
    }

    public function schemes(): View
    {
        return view('public.schemes', ['schemes' => Kegiatan::active()->orderByDesc('sort_order')->orderByDesc('created_at')->orderByDesc('id')->paginate(6)]);
    }

    public function scheme(Request $request): View
    {
        $validated = $request->validate(['id' => ['required', 'integer', 'min:1']]);

        return view('public.scheme', ['scheme' => Kegiatan::active()->findOrFail($validated['id'])]);
    }

    public function members(): View
    {
        $people = Anggota::active()->orderByDesc('sort_order')->orderBy('name')->get();
        $boardMembers = $people->filter(fn (Anggota $member): bool => str_contains(mb_strtolower($member->role_label), 'komisaris'));
        $directors = $people->filter(fn (Anggota $member): bool => str_contains(mb_strtolower($member->role_label), 'direktur'));

        return view('public.structure', [
            'people' => $people,
            'boardMembers' => $boardMembers,
            'directors' => $directors,
            'divisionMembers' => $people->reject(fn (Anggota $member): bool => $boardMembers->contains($member) || $directors->contains($member)),
        ]);
    }

    public function instructors(): View
    {
        return view('public.people', [
            'title' => 'Instruktur & Asesor LSP FIT',
            'people' => Instruktur::active()->orderByDesc('sort_order')->orderBy('name')->orderBy('id')->paginate(8),
            'roleField' => null,
        ]);
    }

    public function contact(): View
    {
        $settings = SiteSetting::query()->pluck('setting_value', 'setting_key');
        $phone = preg_replace('/\D+/', '', $settings->get('contact_phone', '0811-1210-1007'));

        return view('public.contact', [
            'settings' => $settings,
            'whatsAppNumber' => str_starts_with($phone, '0') ? '62'.substr($phone, 1) : $phone,
        ]);
    }
}
