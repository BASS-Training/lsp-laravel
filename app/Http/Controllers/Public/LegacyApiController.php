<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Anggota;
use App\Models\Instruktur;
use App\Models\Kegiatan;
use App\Models\Partner;
use App\Models\SiteSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LegacyApiController extends Controller
{
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
        $query = Kegiatan::active();
        $id = trim((string) $request->query('id', ''));

        if ($id !== '') {
            $item = ctype_digit($id) ? $query->find($id) : $query->where('slug', $id)->first();

            if ($item === null && ! ctype_digit($id) && ! str_starts_with($id, 'kegiatan-')) {
                $item = Kegiatan::active()->where('slug', 'kegiatan-'.$id)->first();
            }

            if ($item === null) {
                return response()->json(['ok' => false, 'message' => 'Kegiatan tidak ditemukan.'], 404);
            }

            return response()->json(['ok' => true, 'item' => $this->kegiatanItem($item)]);
        }

        $limit = max(1, min(50, (int) $request->query('limit', 50)));
        $items = $query->orderByDesc('sort_order')->orderByDesc('created_at')->orderByDesc('id')->limit($limit)->get();

        return response()->json(['ok' => true, 'items' => $items->map(fn (Kegiatan $item): array => $this->kegiatanItem($item)), 'configured' => true]);
    }

    public function anggota(Request $request): JsonResponse
    {
        $query = Anggota::query();
        if (! ($request->has('all') && auth()->user()?->is_active)) {
            $query->active();
        }

        $items = $query->orderByDesc('sort_order')->orderBy('name')->get()->map(fn (Anggota $item): array => [
            'id' => (string) $item->id, 'name' => $item->name, 'roleLabel' => $item->role_label,
            'photo' => $item->photo_url, 'isActive' => $item->is_active, 'sortOrder' => $item->sort_order,
        ]);

        return response()->json(['ok' => true, 'items' => $items, 'configured' => true]);
    }

    public function instruktur(Request $request): JsonResponse
    {
        $query = Instruktur::query();
        if (! ($request->has('all') && auth()->user()?->is_active)) {
            $query->active();
        }

        $items = $query->orderByDesc('sort_order')->orderBy('name')->get()->map(fn (Instruktur $item): array => [
            'id' => (string) $item->id, 'name' => $item->name, 'photo' => $item->photo_url,
            'isActive' => $item->is_active, 'sortOrder' => $item->sort_order,
        ]);

        return response()->json(['ok' => true, 'items' => $items, 'configured' => true]);
    }

    public function partners(Request $request): JsonResponse
    {
        $query = Partner::query();
        if (! ($request->has('all') && auth()->user()?->is_active)) {
            $query->active();
        }

        $items = $query->orderByDesc('sort_order')->orderBy('name')->get()->map(fn (Partner $item): array => [
            'id' => (string) $item->id, 'name' => $item->name, 'logo' => $item->logo_url,
            'websiteUrl' => $item->website_url ?? '', 'isActive' => $item->is_active, 'sortOrder' => $item->sort_order,
        ]);

        return response()->json(['ok' => true, 'items' => $items, 'configured' => true]);
    }

    public function settings(): JsonResponse
    {
        return response()->json(['ok' => true, 'settings' => SiteSetting::query()->pluck('setting_value', 'setting_key'), 'configured' => true]);
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
