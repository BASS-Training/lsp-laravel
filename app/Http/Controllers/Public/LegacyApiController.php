<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Anggota;
use App\Models\Instruktur;
use App\Models\Kegiatan;
use App\Models\Partner;
use App\Models\SiteSetting;
use App\Services\PublicContentCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LegacyApiController extends Controller
{
    public function __construct(private PublicContentCache $cache) {}

    public function authentication(): JsonResponse
    {
        $admin = auth()->user()?->is_active ? auth()->user() : null;

        return response()->json([
            'ok' => true,
            'authenticated' => $admin !== null,
            'role' => $admin?->role ?? '',
            'username' => $admin?->username,
            'name' => $admin?->name,
        ]);
    }

    public function kegiatan(Request $request): JsonResponse
    {
        $id = trim((string) $request->query('id', ''));

        if ($id !== '') {
            $item = $this->cache->remember('kegiatan', 'api.kegiatan.item.'.sha1($id), function () use ($id): ?array {
                $query = Kegiatan::active();
                $model = ctype_digit($id) ? $query->find($id) : $query->where('slug', $id)->first();

                if ($model === null && ! ctype_digit($id) && ! str_starts_with($id, 'kegiatan-')) {
                    $model = Kegiatan::active()->where('slug', 'kegiatan-'.$id)->first();
                }

                return $model === null ? null : $this->kegiatanItem($model);
            });

            if ($item === null) {
                return response()->json(['ok' => false, 'message' => 'Kegiatan tidak ditemukan.'], 404);
            }

            return response()->json(['ok' => true, 'item' => $item]);
        }

        $limit = max(1, min(50, (int) $request->query('limit', 50)));
        $items = $this->cache->remember(
            'kegiatan',
            'api.kegiatan.list.'.$limit,
            fn (): array => Kegiatan::active()
                ->orderByDesc('sort_order')
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->limit($limit)
                ->get()
                ->map(fn (Kegiatan $item): array => $this->kegiatanItem($item))
                ->all(),
        );

        return response()->json(['ok' => true, 'items' => $items, 'configured' => true]);
    }

    public function anggota(Request $request): JsonResponse
    {
        $includeInactive = $request->has('all') && auth()->user()?->is_active;
        $resolver = function () use ($includeInactive): array {
            $query = Anggota::query();

            if (! $includeInactive) {
                $query->active();
            }

            return $query->orderByDesc('sort_order')->orderBy('name')->get()->map(fn (Anggota $item): array => [
                'id' => (string) $item->id, 'name' => $item->name, 'roleLabel' => $item->role_label,
                'photo' => $item->photo_url, 'isActive' => $item->is_active, 'sortOrder' => $item->sort_order,
            ])->all();
        };
        $items = $includeInactive ? $resolver() : $this->cache->remember('anggota', 'api.anggota.active', $resolver);

        return response()->json(['ok' => true, 'items' => $items, 'configured' => true]);
    }

    public function instruktur(Request $request): JsonResponse
    {
        $includeInactive = $request->has('all') && auth()->user()?->is_active;
        $resolver = function () use ($includeInactive): array {
            $query = Instruktur::query();

            if (! $includeInactive) {
                $query->active();
            }

            return $query->orderByDesc('sort_order')->orderBy('name')->get()->map(fn (Instruktur $item): array => [
                'id' => (string) $item->id, 'name' => $item->name, 'photo' => $item->photo_url,
                'isActive' => $item->is_active, 'sortOrder' => $item->sort_order,
            ])->all();
        };
        $items = $includeInactive ? $resolver() : $this->cache->remember('instruktur', 'api.instruktur.active', $resolver);

        return response()->json(['ok' => true, 'items' => $items, 'configured' => true]);
    }

    public function partners(Request $request): JsonResponse
    {
        $includeInactive = $request->has('all') && auth()->user()?->is_active;
        $resolver = function () use ($includeInactive): array {
            $query = Partner::query();

            if (! $includeInactive) {
                $query->active();
            }

            return $query->orderByDesc('sort_order')->orderBy('name')->get()->map(fn (Partner $item): array => [
                'id' => (string) $item->id, 'name' => $item->name, 'logo' => $item->logo_url,
                'websiteUrl' => $item->website_url ?? '', 'isActive' => $item->is_active, 'sortOrder' => $item->sort_order,
            ])->all();
        };
        $items = $includeInactive ? $resolver() : $this->cache->remember('partners', 'api.partners.active', $resolver);

        return response()->json(['ok' => true, 'items' => $items, 'configured' => true]);
    }

    public function homepage(): JsonResponse
    {
        return $this->settings('api.settings.homepage', SiteSetting::query()->where('setting_key', 'like', 'home\_%'));
    }

    public function profile(): JsonResponse
    {
        return $this->settings('api.settings.profile', SiteSetting::query());
    }

    /** @param  Builder<SiteSetting>  $query */
    private function settings(string $key, Builder $query): JsonResponse
    {
        $settings = $this->cache->remember(
            'settings',
            $key,
            fn (): array => $query->pluck('setting_value', 'setting_key')->all(),
        );

        return response()->json(['ok' => true, 'settings' => $settings, 'configured' => true]);
    }

    /** @return array<string, bool|string|null> */
    private function kegiatanItem(Kegiatan $item): array
    {
        return [
            'id' => (string) $item->id,
            'slug' => $item->slug,
            'title' => $item->title,
            'category' => $item->category,
            'summary' => $item->summary,
            'description' => $item->description,
            'image' => $item->image_url,
            'isHighlight' => $item->is_highlight,
            'createdAt' => $item->created_at?->toDateTimeString(),
        ];
    }
}
