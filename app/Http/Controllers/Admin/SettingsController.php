<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Services\PublicContentCache;
use App\Services\UploadStorage;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function __construct(private UploadStorage $uploads, private PublicContentCache $cache) {}

    private const HIDDEN_SETTING_KEYS = [
        'home_hero_image',
        'home_hero_button_url',
        'home_cta_button_url',
        'home_section_order',
        'about_leader_image',
        'about_video_url',
    ];

    public function edit(string $group): View
    {
        $settings = $this->settings($group);
        $sectionPrefixes = $group === 'homepage'
            ? [
                'Tampilan utama' => ['home_hero_'],
                'Profil singkat' => ['home_profile_'],
                'Struktur dan instruktur' => ['home_members_', 'home_instructors_'],
                'Mitra' => ['home_partners_'],
                'Ajakan bertindak' => ['home_cta_'],
                'Kegiatan' => ['home_activities_'],
                'Bagian yang ditampilkan' => ['home_show_'],
            ]
            : [
                'Profil lembaga' => ['about_title', 'about_description', 'about_profile_', 'vision', 'mission'],
                'Dukungan dan jangkauan' => ['about_support_', 'about_reach_'],
                'Sambutan pimpinan' => ['about_leader_'],
                'Video profil' => ['about_video_', 'about_show_video'],
                'Informasi kontak' => ['contact_'],
            ];

        $sections = [];

        foreach ($sectionPrefixes as $title => $prefixes) {
            $fields = $settings->filter(fn (SiteSetting $setting): bool => ! in_array($setting->setting_key, self::HIDDEN_SETTING_KEYS, true) && collect($prefixes)->contains(fn (string $prefix): bool => str_starts_with($setting->setting_key, $prefix)));

            if ($fields->isNotEmpty() || ($group === 'profil' && in_array($title, ['Sambutan pimpinan', 'Video profil'], true))) {
                $sections[] = ['title' => $title, 'fields' => $fields];
            }
        }

        return view('admin.settings', [
            'group' => $group,
            'title' => $group === 'homepage' ? 'Beranda' : 'Profil Website',
            'sections' => $sections,
            'labels' => $settings->mapWithKeys(fn (SiteSetting $setting): array => [$setting->setting_key => $this->label($setting->setting_key)]),
            'media' => $settings->pluck('setting_value', 'setting_key'),
        ]);
    }

    public function update(Request $request, string $group): RedirectResponse
    {
        $settings = $this->settings($group);
        $rules = [];

        foreach ($settings as $setting) {
            if (! in_array($setting->setting_key, self::HIDDEN_SETTING_KEYS, true)) {
                $rules[$setting->setting_key] = ['nullable', 'string', 'max:10000'];
            }
        }

        $validated = $request->validate([
            ...$rules,
            'about_leader_image_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'about_video_file' => ['nullable', 'file', 'mimetypes:video/mp4,application/mp4', 'max:51200'],
        ]);

        $replacedUploads = [];
        $uploadedSettings = [];

        foreach (['about_leader_image_file' => ['about_leader_image', 'profile'], 'about_video_file' => ['about_video_url', 'profile']] as $fileField => [$settingKey, $folder]) {
            if ($request->hasFile($fileField)) {
                $replacedUploads[] = [$settings->firstWhere('setting_key', $settingKey)?->setting_value, $folder];
                $validated[$settingKey] = $this->uploads->store($request->file($fileField), $folder);
                $uploadedSettings[$settingKey] = $validated[$settingKey];
            }
            unset($validated[$fileField]);
        }

        foreach ($settings as $setting) {
            $key = $setting->setting_key;
            if (str_starts_with($key, 'home_show_') || $key === 'about_show_video') {
                $setting->update(['setting_value' => $request->boolean($key) ? '1' : '0']);
            } elseif (array_key_exists($key, $validated)) {
                $setting->update(['setting_value' => (string) ($validated[$key] ?? '')]);
            }
        }

        foreach ($uploadedSettings as $settingKey => $url) {
            if ($settings->firstWhere('setting_key', $settingKey) === null) {
                SiteSetting::create(['setting_key' => $settingKey, 'setting_value' => $url]);
            }
        }
        $this->cache->flush('settings');

        foreach ($replacedUploads as [$previousUrl, $folder]) {
            $this->uploads->deleteOwned($previousUrl, $folder);
        }

        return back()->with('status', 'Pengaturan berhasil disimpan.');
    }

    private function label(string $key): string
    {
        if ($key === 'home_section_order') {
            return 'Urutan bagian beranda';
        }

        $translations = [
            'activities' => 'kegiatan', 'address' => 'alamat', 'button' => 'tombol',
            'card' => 'kartu', 'contact' => 'kontak', 'cta' => 'ajakan',
            'description' => 'deskripsi', 'email' => 'email', 'hero' => 'utama',
            'image' => 'gambar', 'instructors' => 'instruktur', 'kicker' => 'label',
            'layout' => 'tampilan', 'leader' => 'pimpinan', 'members' => 'struktur',
            'mission' => 'misi', 'name' => 'nama', 'note' => 'catatan',
            'partners' => 'mitra', 'phone' => 'telepon', 'profile' => 'profil',
            'quote' => 'kutipan', 'reach' => 'jangkauan', 'role' => 'jabatan',
            'show' => 'tampilkan', 'support' => 'dukungan', 'title' => 'judul',
            'url' => 'URL', 'video' => 'video', 'vision' => 'visi', 'website' => 'website',
        ];
        $parts = array_values(array_filter(explode('_', $key), fn (string $part): bool => ! in_array($part, ['home', 'about', 'contact'], true)));

        return ucfirst(implode(' ', array_map(fn (string $part): string => $translations[$part] ?? $part, $parts)));
    }

    /** @return Collection<int, SiteSetting> */
    private function settings(string $group): Collection
    {
        abort_unless(in_array($group, ['homepage', 'profil'], true), 404);

        return SiteSetting::query()
            ->where(function ($query) use ($group): void {
                if ($group === 'homepage') {
                    $query->where('setting_key', 'like', 'home_%');
                } else {
                    $query->where('setting_key', 'like', 'about_%')
                        ->orWhere('setting_key', 'like', 'contact_%')
                        ->orWhereIn('setting_key', ['vision', 'mission']);
                }
            })->orderBy('setting_key')->get();
    }
}
