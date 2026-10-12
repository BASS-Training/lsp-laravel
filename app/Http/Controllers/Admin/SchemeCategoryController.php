<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PublicContentCache;
use App\Services\SchemeCategoryCatalog;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SchemeCategoryController extends Controller
{
    public function __construct(private SchemeCategoryCatalog $categories, private PublicContentCache $cache) {}

    public function index(): View
    {
        $usageCounts = $this->categories->usageCounts();
        $categories = $this->categories->names()->map(fn (string $name): array => [
            'id' => $this->categories->idFor($name),
            'name' => $name,
            'usage' => $usageCounts->get($name, 0),
        ]);

        return view('admin.scheme-categories.index', ['categories' => $categories]);
    }

    public function create(): View
    {
        return view('admin.scheme-categories.form', ['category' => null, 'categoryId' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->categories->create($this->validatedName($request));

        return redirect()->route('admin.scheme-categories.index')->with('status', 'Kategori berhasil ditambahkan.');
    }

    public function edit(string $category): View
    {
        $name = $this->findCategory($category);

        return view('admin.scheme-categories.form', ['category' => $name, 'categoryId' => $category]);
    }

    public function update(Request $request, string $category): RedirectResponse
    {
        $currentName = $this->findCategory($category);
        $newName = $this->validatedName($request, $currentName);
        $this->categories->rename($currentName, $newName);
        $this->cache->flush('kegiatan');

        return redirect()->route('admin.scheme-categories.index')->with('status', 'Kategori berhasil diperbarui.');
    }

    public function destroy(string $category): RedirectResponse
    {
        $name = $this->findCategory($category);

        if (! $this->categories->delete($name)) {
            return back()->withErrors(['category' => 'Kategori tidak dapat dihapus karena masih digunakan oleh skema sertifikasi.']);
        }

        return redirect()->route('admin.scheme-categories.index')->with('status', 'Kategori berhasil dihapus.');
    }

    private function findCategory(string $id): string
    {
        $category = $this->categories->find($id);
        abort_if($category === null, 404);

        return $category;
    }

    private function validatedName(Request $request, ?string $currentName = null): string
    {
        $request->merge(['name' => $this->categories->normalize((string) $request->input('name'))]);
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:80',
                function (string $attribute, mixed $value, Closure $fail) use ($currentName): void {
                    if ($this->categories->contains((string) $value, $currentName)) {
                        $fail('Nama kategori sudah digunakan.');
                    }
                },
            ],
        ]);

        return $validated['name'];
    }
}
