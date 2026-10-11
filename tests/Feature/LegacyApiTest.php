<?php

use App\Models\Anggota;
use App\Models\Kegiatan;
use App\Models\SiteSetting;

function kegiatanRow(string $slug, array $overrides = []): Kegiatan
{
    return Kegiatan::create([
        'title' => 'Judul '.$slug,
        'slug' => $slug,
        'category' => 'Kegiatan',
        'summary' => 's',
        'description' => 'd',
        'image_url' => '/assets/uploads/kegiatan/'.$slug.'.jpg',
        'is_active' => true,
        'is_highlight' => false,
        'sort_order' => 0,
        ...$overrides,
    ]);
}

it('lists active kegiatan ordered by sort_order desc with the legacy shape', function (): void {
    kegiatanRow('rendah', ['sort_order' => 1]);
    kegiatanRow('tinggi', ['sort_order' => 9, 'is_highlight' => true]);
    kegiatanRow('mati', ['sort_order' => 99, 'is_active' => false]);

    $response = $this->getJson('/api/kegiatan.php')->assertOk();

    expect(collect($response->json('items'))->pluck('slug')->all())->toBe(['tinggi', 'rendah']);
    expect($response->json('ok'))->toBeTrue();
    expect($response->json('items.0'))->toHaveKeys(['id', 'slug', 'title', 'category', 'summary', 'description', 'image', 'isHighlight', 'createdAt']);
    expect($response->json('items.0.id'))->toBeString();
    expect($response->json('items.0.isHighlight'))->toBeTrue();
});

it('honours the limit parameter within 1 to 50', function (): void {
    foreach (['a', 'b', 'c'] as $slug) {
        kegiatanRow($slug);
    }

    expect($this->getJson('/api/kegiatan.php?limit=2')->json('items'))->toHaveCount(2);
    expect($this->getJson('/api/kegiatan.php?limit=0')->json('items'))->toHaveCount(1);
});

it('finds a single kegiatan by id or slug and 404s otherwise', function (): void {
    $item = kegiatanRow('skema-satu');

    $this->getJson('/api/kegiatan.php?id='.$item->id)->assertOk()->assertJsonPath('item.slug', 'skema-satu');
    $this->getJson('/api/kegiatan.php?id=skema-satu')->assertOk()->assertJsonPath('item.id', (string) $item->id);
    $this->getJson('/api/kegiatan.php?id=999')->assertNotFound()->assertJsonPath('ok', false);
});

it('only exposes inactive members to a logged in admin with all=1', function (): void {
    Anggota::create(['name' => 'Aktif', 'role_label' => 'Direktur', 'photo_url' => '/a.jpg', 'is_active' => true, 'sort_order' => 1]);
    Anggota::create(['name' => 'Mati', 'role_label' => 'Staf', 'photo_url' => '/b.jpg', 'is_active' => false, 'sort_order' => 0]);

    expect($this->getJson('/api/anggota.php?all=1')->json('items'))->toHaveCount(1);

    $this->actingAs(createAdmin());
    $items = $this->getJson('/api/anggota.php?all=1')->json('items');

    expect($items)->toHaveCount(2);
    expect($items[0])->toHaveKeys(['id', 'name', 'roleLabel', 'photo', 'isActive', 'sortOrder']);
});

it('reports authentication state like api/auth.php', function (): void {
    $this->getJson('/api/auth.php')->assertOk()->assertJson(['ok' => true, 'authenticated' => false, 'role' => '']);

    $this->actingAs(createAdmin());

    $this->getJson('/api/auth.php')->assertJson(['authenticated' => true, 'role' => 'super_admin', 'username' => 'admin', 'name' => 'Admin Uji']);
});

it('does not accept writes on the legacy api routes', function (string $endpoint): void {
    $this->postJson($endpoint, [])->assertStatus(405);
    $this->deleteJson($endpoint.'?id=1')->assertStatus(405);
})->with(['/api/kegiatan.php', '/api/anggota.php', '/api/instruktur.php', '/api/partners.php', '/api/homepage.php', '/api/profile.php', '/api/auth.php']);

it('limits homepage.php to home_ settings while profile.php returns every setting', function (): void {
    SiteSetting::create(['setting_key' => 'home_hero_title', 'setting_value' => 'Halo']);
    SiteSetting::create(['setting_key' => 'homeXtra', 'setting_value' => 'bukan home_']);
    SiteSetting::create(['setting_key' => 'vision', 'setting_value' => 'Visi']);

    expect($this->getJson('/api/homepage.php')->json('settings'))->toBe(['home_hero_title' => 'Halo']);
    expect($this->getJson('/api/profile.php')->json('settings'))->toHaveKeys(['home_hero_title', 'vision']);
});
