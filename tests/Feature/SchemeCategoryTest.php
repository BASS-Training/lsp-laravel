<?php

use App\Models\Kegiatan;
use App\Models\SiteSetting;
use App\Services\SchemeCategoryCatalog;

beforeEach(function (): void {
    $this->actingAs(createAdmin());
});

function categoryKegiatan(string $category): Kegiatan
{
    return Kegiatan::create([
        'title' => 'Skema '.$category,
        'slug' => 'skema-'.sha1($category),
        'category' => $category,
        'summary' => 'Ringkasan',
        'description' => 'Deskripsi',
        'image_url' => '/assets/lspfit/logofit.jpeg',
        'is_active' => true,
        'is_highlight' => false,
        'sort_order' => 0,
    ]);
}

it('requires authentication to manage scheme categories', function (): void {
    auth()->logout();

    $this->get('/admin/scheme-categories')->assertRedirect('/admin/login');
});

it('lists categories already used by legacy schemes', function (): void {
    categoryKegiatan('Bidang Instruktur - Jenjang 3');

    $this->get('/admin/scheme-categories')
        ->assertOk()
        ->assertSee('Bidang Instruktur - Jenjang 3')
        ->assertSee('1 skema');
});

it('creates a category and rejects a duplicate name regardless of case', function (): void {
    $this->post('/admin/scheme-categories', ['name' => '  Bidang   Baru  '])
        ->assertRedirect('/admin/scheme-categories');

    expect(SiteSetting::query()->where('setting_value', 'Bidang Baru')->exists())->toBeTrue();

    $this->post('/admin/scheme-categories', ['name' => 'bidang baru'])
        ->assertSessionHasErrors('name');
});

it('renames a category across its schemes and refreshes public cache', function (): void {
    $item = categoryKegiatan('Kategori Lama');
    $catalog = app(SchemeCategoryCatalog::class);
    $this->getJson('/api/kegiatan.php?id='.$item->id)->assertJsonPath('item.category', 'Kategori Lama');

    $this->put('/admin/scheme-categories/'.$catalog->idFor('Kategori Lama'), ['name' => 'Kategori Baru'])
        ->assertRedirect('/admin/scheme-categories');

    expect($item->refresh()->category)->toBe('Kategori Baru');
    $this->getJson('/api/kegiatan.php?id='.$item->id)->assertJsonPath('item.category', 'Kategori Baru');
});

it('does not delete a category while a scheme still uses it', function (): void {
    categoryKegiatan('Kategori Terpakai');
    $categoryId = app(SchemeCategoryCatalog::class)->idFor('Kategori Terpakai');

    $this->delete('/admin/scheme-categories/'.$categoryId)
        ->assertRedirect()
        ->assertSessionHasErrors('category');

    expect(Kegiatan::query()->where('category', 'Kategori Terpakai')->exists())->toBeTrue();
});

it('deletes an unused category', function (): void {
    $catalog = app(SchemeCategoryCatalog::class);
    $catalog->create('Kategori Kosong');

    $this->delete('/admin/scheme-categories/'.$catalog->idFor('Kategori Kosong'))
        ->assertRedirect('/admin/scheme-categories');

    expect(SiteSetting::query()->where('setting_value', 'Kategori Kosong')->exists())->toBeFalse();
});
