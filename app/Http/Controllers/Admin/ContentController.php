<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Anggota;
use App\Models\Instruktur;
use App\Models\Kegiatan;
use App\Models\Partner;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ContentController extends Controller
{
    /**
     * @return array{title: string, description: string, model: class-string<Model>, fields: array<string, string>, image: string, folder: string}
     */
    private function module(string $module): array
    {
        return match ($module) {
            'kegiatan' => ['title' => 'Skema Sertifikasi', 'description' => 'Kelola skema yang ditampilkan di website.', 'model' => Kegiatan::class, 'fields' => ['title' => 'Judul', 'category' => 'Kategori', 'summary' => 'Ringkasan', 'description' => 'Deskripsi'], 'image' => 'image_url', 'folder' => 'kegiatan'],
            'anggota' => ['title' => 'Struktur Organisasi', 'description' => 'Atur anggota dan jabatan dalam struktur LSP FIT.', 'model' => Anggota::class, 'fields' => ['name' => 'Nama', 'role_label' => 'Jabatan'], 'image' => 'photo_url', 'folder' => 'anggota'],
            'instruktur' => ['title' => 'Instruktur & Asesor', 'description' => 'Kelola profil instruktur dan asesor dalam satu tempat.', 'model' => Instruktur::class, 'fields' => ['name' => 'Nama'], 'image' => 'photo_url', 'folder' => 'instruktur'],
            'mitra' => ['title' => 'Mitra', 'description' => 'Kelola logo mitra yang aktif.', 'model' => Partner::class, 'fields' => ['name' => 'Nama'], 'image' => 'logo_url', 'folder' => 'mitra'],
            default => abort(404),
        };
    }

    public function index(Request $request, string $module): View
    {
        $definition = $this->module($module);
        $model = $definition['model'];
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['all', 'active', 'inactive'])],
            'per_page' => ['nullable', Rule::in(['10', '20', '50'])],
        ]);
        $search = trim($filters['q'] ?? '');
        $status = $filters['status'] ?? 'all';
        $perPage = (int) ($filters['per_page'] ?? 10);
        $query = $model::query();

        if ($search !== '') {
            $query->where($module === 'kegiatan' ? 'title' : 'name', 'like', '%'.$search.'%');
        }

        if ($status !== 'all') {
            $query->where('is_active', $status === 'active');
        }

        return view('admin.content.index', [
            'module' => $module,
            'definition' => $definition,
            'items' => $query->orderByDesc('sort_order')->orderByDesc('id')->paginate($perPage)->withQueryString(),
            'filters' => ['q' => $search, 'status' => $status, 'per_page' => $perPage],
            'counts' => ['total' => $model::count(), 'active' => $model::where('is_active', true)->count()],
        ]);
    }

    public function create(string $module): View
    {
        return view('admin.content.form', ['module' => $module, 'definition' => $this->module($module), 'item' => null]);
    }

    public function edit(string $module, int $id): View
    {
        $definition = $this->module($module);
        $model = $definition['model'];

        return view('admin.content.form', ['module' => $module, 'definition' => $definition, 'item' => $model::findOrFail($id)]);
    }

    public function preview(int $id): View
    {
        return view('public.scheme', ['scheme' => Kegiatan::findOrFail($id)]);
    }

    public function store(Request $request, string $module): RedirectResponse
    {
        $definition = $this->module($module);
        $data = $this->validatedData($request, $module);
        $model = $definition['model'];
        $model::create($data);

        return redirect()->route('admin.content.index', $module)->with('status', 'Konten berhasil ditambahkan.');
    }

    public function update(Request $request, string $module, int $id): RedirectResponse
    {
        $definition = $this->module($module);
        $model = $definition['model'];
        $item = $model::findOrFail($id);
        $previousImageUrl = $item->{$definition['image']};
        $data = $this->validatedData($request, $module, $item);
        $item->update($data);

        if ($previousImageUrl !== $item->{$definition['image']}) {
            $this->deleteOwnedUpload($previousImageUrl, $definition['folder']);
        }

        return redirect()->route('admin.content.index', $module)->with('status', 'Konten berhasil diperbarui.');
    }

    public function destroy(string $module, int $id): RedirectResponse
    {
        $definition = $this->module($module);
        $model = $definition['model'];
        $item = $model::findOrFail($id);
        $imageUrl = $item->{$definition['image']};
        $item->delete();
        $this->deleteOwnedUpload($imageUrl, $definition['folder']);

        return redirect()->route('admin.content.index', $module)->with('status', 'Konten berhasil dihapus.');
    }

    /**
     * @return array<string, bool|int|string|null>
     */
    private function validatedData(Request $request, string $module, ?Model $item = null): array
    {
        if ($module === 'kegiatan') {
            $slug = $item?->slug;

            if ($slug === null) {
                $baseSlug = Str::slug((string) $request->input('title')) ?: 'skema';
                $slug = $baseSlug;
                $suffix = 2;

                while (Kegiatan::query()->where('slug', $slug)->exists()) {
                    $slug = $baseSlug.'-'.$suffix++;
                }
            }

            $request->merge(['slug' => $slug]);
        }

        $rules = match ($module) {
            'kegiatan' => [
                'title' => ['required', 'string', 'max:180'],
                'slug' => ['required', 'string', 'max:220', Rule::unique('kegiatan', 'slug')->ignore($item?->getKey())],
                'category' => ['required', 'string', 'max:80'],
                'summary' => ['required', 'string'],
                'description' => ['required', 'string'],
            ],
            'anggota' => ['name' => ['required', 'string', 'max:140'], 'role_label' => ['required', 'string', 'max:120']],
            'instruktur' => ['name' => ['required', 'string', 'max:140']],
            'mitra' => ['name' => ['required', 'string', 'max:140']],
            default => abort(404),
        };

        $data = $request->validate([
            ...$rules,
            'sort_order' => ['required', 'integer'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        unset($data['image']);
        $data['is_active'] = $request->boolean('is_active');

        if ($module === 'kegiatan') {
            $data['is_highlight'] = $request->boolean('is_highlight');
            $data['image_url'] = $item?->image_url ?? '/assets/lspfit/logofit-nobg.png';
        }

        if (in_array($module, ['anggota', 'instruktur'], true)) {
            $data['photo_url'] = $item?->photo_url ?? 'https://ui-avatars.com/api/?name='.rawurlencode($data['name']).'&size=600';
        }

        if ($module === 'mitra') {
            $data['logo_url'] = $item?->logo_url ?? '';
        }

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $fileName = Str::random(24).'.'.$file->extension();
            $path = $file->storeAs('uploads/'.$this->module($module)['folder'], $fileName, 'public');
            abort_if($path === false, 500, 'Gambar gagal disimpan.');
            $imageField = $this->module($module)['image'];
            $data[$imageField] = '/storage/'.$path;
        }

        return $data;
    }

    private function deleteOwnedUpload(?string $imageUrl, string $folder): void
    {
        if (! is_string($imageUrl)) {
            return;
        }

        if (preg_match('~^/storage/uploads/'.preg_quote($folder, '~').'/[A-Za-z0-9]{24}\.(?:jpe?g|png|webp)$~', $imageUrl) === 1) {
            Storage::disk('public')->delete(substr($imageUrl, strlen('/storage/')));

            return;
        }

        if (preg_match('~^/assets/uploads/'.preg_quote($folder, '~').'/[A-Za-z0-9]{24}\.(?:jpe?g|png|webp)$~', $imageUrl) !== 1) {
            return;
        }

        $path = public_path(ltrim($imageUrl, '/'));

        if (is_file($path)) {
            unlink($path);
        }
    }
}
