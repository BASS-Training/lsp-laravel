<?php

use App\Models\Anggota;
use App\Models\Kegiatan;
use App\Models\SiteSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('uploads');
    $this->actingAs(createAdmin());
});

function kegiatanPayload(array $overrides = []): array
{
    return [
        'title' => 'Skema Baru',
        'category' => 'Kegiatan',
        'summary' => 'Ringkasan',
        'description' => 'Deskripsi',
        'sort_order' => 3,
        'is_active' => 1,
        'image' => UploadedFile::fake()->image('foto.jpg'),
        ...$overrides,
    ];
}

it('creates a kegiatan with a unique slug and stores the image under /assets/uploads', function (): void {
    $this->post('/admin/content/kegiatan', kegiatanPayload())->assertRedirect('/admin/content/kegiatan');
    $this->post('/admin/content/kegiatan', kegiatanPayload());

    $items = Kegiatan::orderBy('id')->get();

    expect($items->pluck('slug')->all())->toBe(['skema-baru', 'skema-baru-2']);
    expect($items[0]->image_url)->toMatch('~^/assets/uploads/kegiatan/[A-Za-z0-9]{24}\.jpg$~');
    Storage::disk('uploads')->assertExists(substr($items[0]->image_url, strlen('/assets/uploads/')));
});

it('requires an image when creating kegiatan, instruktur and mitra but not anggota', function (): void {
    $this->post('/admin/content/kegiatan', kegiatanPayload(['image' => null]))->assertSessionHasErrors('image');
    $this->post('/admin/content/instruktur', ['name' => 'A', 'sort_order' => 0])->assertSessionHasErrors('image');
    $this->post('/admin/content/mitra', ['name' => 'A', 'sort_order' => 0])->assertSessionHasErrors('image');
    $this->post('/admin/content/anggota', ['name' => 'A', 'role_label' => 'B', 'sort_order' => 0])->assertSessionHasNoErrors();

    expect(Anggota::first()->photo_url)->toStartWith('https://ui-avatars.com/');
});

it('rejects non-image uploads and files over 4 MB', function (): void {
    $this->post('/admin/content/kegiatan', kegiatanPayload(['image' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf')]))->assertSessionHasErrors('image');
    $this->post('/admin/content/kegiatan', kegiatanPayload(['image' => UploadedFile::fake()->image('besar.jpg')->size(4097)]))->assertSessionHasErrors('image');

    expect(Kegiatan::count())->toBe(0);
});

it('replaces and deletes only app-owned upload files', function (): void {
    $this->post('/admin/content/kegiatan', kegiatanPayload());
    $item = Kegiatan::first();
    $oldPath = substr($item->image_url, strlen('/assets/uploads/'));

    $this->put('/admin/content/kegiatan/'.$item->id, kegiatanPayload(['image' => UploadedFile::fake()->image('baru.png')]));
    Storage::disk('uploads')->assertMissing($oldPath);

    $item->refresh();
    $newPath = substr($item->image_url, strlen('/assets/uploads/'));
    Storage::disk('uploads')->assertExists($newPath);

    $this->delete('/admin/content/kegiatan/'.$item->id)->assertRedirect();
    Storage::disk('uploads')->assertMissing($newPath);
    expect(Kegiatan::count())->toBe(0);
});

it('never deletes a file outside the module upload folder', function (): void {
    Storage::disk('uploads')->put('profile/'.str_repeat('a', 24).'.jpg', 'x');
    $item = Kegiatan::create([
        'title' => 'T', 'slug' => 't', 'category' => 'K', 'summary' => 's', 'description' => 'd',
        'image_url' => '/assets/uploads/profile/'.str_repeat('a', 24).'.jpg', 'is_active' => true, 'sort_order' => 0,
    ]);

    $this->delete('/admin/content/kegiatan/'.$item->id);

    Storage::disk('uploads')->assertExists('profile/'.str_repeat('a', 24).'.jpg');
});

it('returns 404 for an unknown module', function (): void {
    $this->get('/admin/content/rahasia')->assertNotFound();
});

it('saves profile settings and a leader image to /assets/uploads/profile', function (): void {
    SiteSetting::create(['setting_key' => 'vision', 'setting_value' => 'lama']);
    SiteSetting::create(['setting_key' => 'about_leader_image', 'setting_value' => '']);

    $this->put('/admin/settings/profil', ['vision' => 'baru', 'about_leader_image_file' => UploadedFile::fake()->image('p.jpg')])->assertRedirect();

    expect(SiteSetting::find('vision')->setting_value)->toBe('baru');
    expect(SiteSetting::find('about_leader_image')->setting_value)->toMatch('~^/assets/uploads/profile/[A-Za-z0-9]{24}\.jpg$~');
});

it('rejects a profile video that is not mp4', function (): void {
    SiteSetting::create(['setting_key' => 'vision', 'setting_value' => 'x']);

    $this->put('/admin/settings/profil', ['about_video_file' => UploadedFile::fake()->create('v.avi', 10, 'video/x-msvideo')])->assertSessionHasErrors('about_video_file');
});

it('rejects unknown settings groups', function (): void {
    $this->get('/admin/settings/lain')->assertNotFound();
});
