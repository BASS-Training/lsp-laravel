<?php

use App\Models\Kegiatan;

function makeKegiatan(array $overrides = []): Kegiatan
{
    return Kegiatan::create([
        'title' => 'Skema Uji',
        'slug' => 'skema-uji',
        'category' => 'Kegiatan',
        'summary' => 'Ringkasan',
        'description' => 'Deskripsi',
        'image_url' => '/assets/lspfit/logofit.jpeg',
        'is_active' => true,
        'is_highlight' => false,
        'sort_order' => 0,
        ...$overrides,
    ]);
}

it('serves every public page', function (string $path): void {
    $this->get($path)->assertOk();
})->with(['/', '/aboutus', '/kegiatan', '/anggota', '/instruktur', '/kontak']);

it('shows scheme detail only for active schemes', function (): void {
    $active = makeKegiatan();
    $inactive = makeKegiatan(['slug' => 'nonaktif', 'is_active' => false]);

    $this->get('/kegiatan-detail?id='.$active->id)->assertOk()->assertSee('Skema Uji');
    $this->get('/kegiatan-detail?id='.$inactive->id)->assertNotFound();
});

it('hides inactive schemes from the list', function (): void {
    makeKegiatan(['title' => 'Terlihat']);
    makeKegiatan(['title' => 'Tersembunyi', 'slug' => 'x', 'is_active' => false]);

    $this->get('/kegiatan')->assertSee('Terlihat')->assertDontSee('Tersembunyi');
});

it('serves paginated public data from cache on repeated requests', function (): void {
    $item = makeKegiatan(['title' => 'Judul tersimpan']);
    $this->get('/kegiatan')->assertOk()->assertSee('Judul tersimpan');
    $item->update(['title' => 'Judul database']);

    $this->get('/kegiatan')->assertOk()->assertSee('Judul tersimpan')->assertDontSee('Judul database');
});

it('redirects legacy html urls permanently', function (string $legacy, string $target): void {
    $this->get($legacy)->assertStatus(301)->assertRedirect($target);
})->with([
    ['/index.html', '/'],
    ['/aboutus.html', '/aboutus'],
    ['/kegiatan.html', '/kegiatan'],
    ['/anggota.html', '/anggota'],
    ['/instruktur.html', '/instruktur'],
    ['/asesor.html', '/instruktur'],
    ['/asesor', '/instruktur'],
    ['/gallery', '/kegiatan'],
    ['/kontak.html', '/kontak'],
    ['/admin-dashboard.html', '/admin'],
]);

it('keeps the legacy detail url and its id', function (): void {
    $this->get('/kegiatan-detail.html?id=7')->assertStatus(301)->assertRedirect('/kegiatan-detail?id=7');
});

it('references only assets that exist in public', function (): void {
    foreach (['assets/lspfit/favicon.ico', 'assets/lspfit/logofit-nobg.png', 'assets/lspfit/logofit.jpeg'] as $path) {
        expect(public_path($path))->toBeFile();
    }
});
