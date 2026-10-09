<?php

use App\Models\AdminUser;
use App\Models\Anggota;
use App\Models\Instruktur;
use App\Models\Kegiatan;
use App\Models\Partner;
use App\Models\SiteSetting;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Schema::create('site_settings', function (Blueprint $table): void {
        $table->string('setting_key')->primary();
        $table->text('setting_value');
        $table->timestamp('updated_at')->nullable();
    });

    Schema::create('kegiatan', function (Blueprint $table): void {
        $table->increments('id');
        $table->string('title');
        $table->string('slug')->unique();
        $table->string('category');
        $table->text('summary');
        $table->text('description');
        $table->string('image_url');
        $table->boolean('is_active');
        $table->boolean('is_highlight');
        $table->integer('sort_order');
        $table->timestamps();
    });

    foreach (['anggota', 'instruktur', 'partners'] as $tableName) {
        Schema::create($tableName, function (Blueprint $table) use ($tableName): void {
            $table->increments('id');
            $table->string('name');
            $table->string($tableName === 'partners' ? 'logo_url' : 'photo_url');
            if ($tableName === 'anggota') {
                $table->string('role_label');
            }
            if ($tableName === 'partners') {
                $table->string('website_url')->nullable();
            }
            $table->boolean('is_active');
            $table->integer('sort_order');
            $table->timestamps();
        });
    }

    Schema::create('admin_users', function (Blueprint $table): void {
        $table->increments('id');
        $table->string('username')->unique();
        $table->string('name');
        $table->string('password_hash');
        $table->string('role');
        $table->boolean('is_active');
        $table->timestamp('last_login_at')->nullable();
        $table->timestamps();
    });
});

test('the homepage renders saved content and active schemes', function (): void {
    SiteSetting::create(['setting_key' => 'home_hero_title', 'setting_value' => 'Judul dari database']);
    Kegiatan::create(['title' => 'Skema aktif', 'slug' => 'aktif', 'category' => 'Instruktur', 'summary' => 'Ringkasan aktif', 'description' => 'Isi', 'image_url' => '/assets/example.png', 'is_active' => true, 'is_highlight' => true, 'sort_order' => 2]);
    Kegiatan::create(['title' => 'Skema nonaktif', 'slug' => 'nonaktif', 'category' => 'Instruktur', 'summary' => 'Ringkasan', 'description' => 'Isi', 'image_url' => '/assets/example.png', 'is_active' => false, 'is_highlight' => true, 'sort_order' => 3]);

    $this->get('/')->assertOk()->assertSee('Judul dari database')->assertSee('Skema aktif')->assertDontSee('Skema nonaktif');
});

test('inactive schemes cannot be opened and old scheme URLs redirect', function (): void {
    $scheme = Kegiatan::create(['title' => 'Skema nonaktif', 'slug' => 'nonaktif', 'category' => 'Instruktur', 'summary' => 'Ringkasan', 'description' => 'Isi', 'image_url' => '/assets/example.png', 'is_active' => false, 'is_highlight' => false, 'sort_order' => 1]);

    $this->get('/kegiatan-detail?id='.$scheme->id)->assertNotFound();
    $this->get('/kegiatan-detail.html?id='.$scheme->id)->assertRedirect('/kegiatan-detail?id='.$scheme->id);
    $this->get('/aboutus.html')->assertRedirect('/aboutus');

    $admin = AdminUser::create(['username' => 'editor', 'name' => 'Editor', 'password_hash' => Hash::make('rahasia'), 'role' => 'editor', 'is_active' => true]);
    $this->actingAs($admin)->get('/admin/content/kegiatan/'.$scheme->id.'/preview')->assertOk()->assertSee('Skema nonaktif');
});

test('only active legacy admin accounts can sign in', function (): void {
    AdminUser::create(['username' => 'aktif', 'name' => 'Admin Aktif', 'password_hash' => Hash::make('rahasia'), 'role' => 'admin_konten', 'is_active' => true]);
    AdminUser::create(['username' => 'nonaktif', 'name' => 'Admin Nonaktif', 'password_hash' => Hash::make('rahasia'), 'role' => 'viewer', 'is_active' => false]);

    $this->post('/admin/login', ['username' => 'nonaktif', 'password' => 'rahasia'])->assertSessionHasErrors('username');
    $this->post('/admin/login', ['username' => 'aktif', 'password' => 'rahasia'])->assertRedirect('/admin');
    $this->get('/admin')->assertOk()->assertSee('Admin Aktif');
});

test('content changes require an authenticated admin account', function (): void {
    $this->get('/admin/content/kegiatan')->assertRedirect('/admin/login');
    $this->post('/admin/content/kegiatan', ['title' => 'Tanpa login'])->assertRedirect('/admin/login');
});

test('an admin can create and update a scheme without changing the schema', function (): void {
    $admin = AdminUser::create(['username' => 'editor', 'name' => 'Editor', 'password_hash' => Hash::make('rahasia'), 'role' => 'editor', 'is_active' => true]);
    $this->actingAs($admin);

    $this->get('/admin/content/kegiatan/create')->assertOk()->assertSee('Informasi utama');

    $this->post('/admin/content/kegiatan', ['title' => 'Skema Baru', 'category' => 'Instruktur', 'summary' => 'Ringkasan', 'description' => 'Penjelasan', 'sort_order' => 4, 'is_active' => '1'])->assertRedirect('/admin/content/kegiatan');
    $scheme = Kegiatan::query()->where('slug', 'skema-baru')->firstOrFail();
    expect($scheme->is_active)->toBeTrue();

    $this->get('/admin/content/kegiatan/'.$scheme->id.'/edit')->assertOk()->assertSee('Skema Baru')->assertDontSee('name="slug"', false);

    $this->put('/admin/content/kegiatan/'.$scheme->id, ['title' => 'Skema Diubah', 'slug' => 'skema-diubah', 'category' => 'Instruktur', 'summary' => 'Ringkasan baru', 'description' => 'Penjelasan baru', 'sort_order' => 7])->assertRedirect('/admin/content/kegiatan');
    $this->assertDatabaseHas('kegiatan', ['id' => $scheme->id, 'title' => 'Skema Diubah', 'slug' => 'skema-baru', 'is_active' => 0, 'sort_order' => 7]);
});

test('scheme addresses are generated uniquely without an editable slug field', function (): void {
    $admin = AdminUser::create(['username' => 'editor', 'name' => 'Editor', 'password_hash' => Hash::make('rahasia'), 'role' => 'editor', 'is_active' => true]);
    $input = ['title' => 'Skema Sama', 'category' => 'Instruktur', 'summary' => 'Ringkasan', 'description' => 'Isi', 'sort_order' => 1];

    $this->actingAs($admin)->post('/admin/content/kegiatan', [...$input, 'slug' => 'alamat-palsu'])->assertRedirect();
    $this->post('/admin/content/kegiatan', $input)->assertRedirect();

    $this->assertDatabaseHas('kegiatan', ['title' => 'Skema Sama', 'slug' => 'skema-sama']);
    $this->assertDatabaseHas('kegiatan', ['title' => 'Skema Sama', 'slug' => 'skema-sama-2']);
});

test('legacy public API keeps its item fields and hides inactive records', function (): void {
    Kegiatan::create(['title' => 'Tampil', 'slug' => 'tampil', 'category' => 'Instruktur', 'summary' => 'Ringkasan', 'description' => 'Isi', 'image_url' => '/assets/example.png', 'is_active' => true, 'is_highlight' => true, 'sort_order' => 2]);
    Kegiatan::create(['title' => 'Tersembunyi', 'slug' => 'tersembunyi', 'category' => 'Instruktur', 'summary' => 'Ringkasan', 'description' => 'Isi', 'image_url' => '/assets/example.png', 'is_active' => false, 'is_highlight' => false, 'sort_order' => 3]);

    $this->getJson('/api/kegiatan.php')->assertOk()->assertJsonPath('items.0.title', 'Tampil')->assertJsonPath('items.0.isHighlight', true)->assertJsonCount(1, 'items');
});

test('homepage settings update the existing key without a created_at column', function (): void {
    $admin = AdminUser::create(['username' => 'editor', 'name' => 'Editor', 'password_hash' => Hash::make('rahasia'), 'role' => 'editor', 'is_active' => true]);
    SiteSetting::create(['setting_key' => 'home_hero_title', 'setting_value' => 'Judul lama']);

    $this->actingAs($admin)->get('/admin/settings/homepage')->assertOk()->assertSee('Tampilan utama');
    $this->put('/admin/settings/homepage', ['home_hero_title' => 'Judul baru'])->assertRedirect();

    $this->assertDatabaseHas('site_settings', ['setting_key' => 'home_hero_title', 'setting_value' => 'Judul baru']);
});

test('admin settings show media previews and ignore submitted media paths', function (): void {
    $admin = AdminUser::create(['username' => 'editor', 'name' => 'Editor', 'password_hash' => Hash::make('rahasia'), 'role' => 'editor', 'is_active' => true]);
    SiteSetting::create(['setting_key' => 'about_leader_image', 'setting_value' => '/storage/uploads/profile/current.jpg']);
    SiteSetting::create(['setting_key' => 'about_leader_name', 'setting_value' => 'Pimpinan Lama']);
    SiteSetting::create(['setting_key' => 'about_video_url', 'setting_value' => '/storage/uploads/profile/current.mp4']);
    SiteSetting::create(['setting_key' => 'home_hero_button_url', 'setting_value' => '/kegiatan']);
    SiteSetting::create(['setting_key' => 'home_section_order', 'setting_value' => 'profile,instructors']);

    $this->actingAs($admin)->get('/admin/settings/profil')->assertOk()
        ->assertSee('src="/storage/uploads/profile/current.jpg"', false)
        ->assertSee('src="/storage/uploads/profile/current.mp4"', false)
        ->assertDontSee('name="about_leader_image"', false)
        ->assertDontSee('name="about_video_url"', false);
    $this->get('/admin/settings/homepage')->assertOk()->assertDontSee('name="home_hero_button_url"', false)->assertDontSee('name="home_section_order"', false);

    $this->put('/admin/settings/profil', ['about_leader_name' => 'Pimpinan Baru', 'about_leader_image' => '/broken.jpg', 'about_video_url' => '/broken.mp4'])->assertRedirect();
    $this->put('/admin/settings/homepage', ['home_hero_button_url' => '/broken', 'home_section_order' => 'broken'])->assertRedirect();

    $this->assertDatabaseHas('site_settings', ['setting_key' => 'about_leader_name', 'setting_value' => 'Pimpinan Baru']);
    $this->assertDatabaseHas('site_settings', ['setting_key' => 'about_leader_image', 'setting_value' => '/storage/uploads/profile/current.jpg']);
    $this->assertDatabaseHas('site_settings', ['setting_key' => 'about_video_url', 'setting_value' => '/storage/uploads/profile/current.mp4']);
    $this->assertDatabaseHas('site_settings', ['setting_key' => 'home_hero_button_url', 'setting_value' => '/kegiatan']);
    $this->assertDatabaseHas('site_settings', ['setting_key' => 'home_section_order', 'setting_value' => 'profile,instructors']);
});

test('uploading a leader photo creates a missing setting and displays it publicly', function (): void {
    Storage::fake('public');
    $admin = AdminUser::create(['username' => 'editor', 'name' => 'Editor', 'password_hash' => Hash::make('rahasia'), 'role' => 'editor', 'is_active' => true]);
    SiteSetting::create(['setting_key' => 'about_leader_name', 'setting_value' => 'Pimpinan LSP FIT']);

    $this->actingAs($admin)->put('/admin/settings/profil', [
        'about_leader_image_file' => UploadedFile::fake()->image('pimpinan.jpg'),
    ])->assertRedirect();

    $imageUrl = SiteSetting::query()->whereKey('about_leader_image')->value('setting_value');

    expect($imageUrl)->toStartWith('/storage/uploads/profile/');
    $this->assertDatabaseHas('site_settings', ['setting_key' => 'about_leader_image', 'setting_value' => $imageUrl]);
    $this->get('/aboutus')->assertOk()->assertSee('src="'.$imageUrl.'"', false);
    Storage::disk('public')->assertExists(substr($imageUrl, strlen('/storage/')));
});

test('member photos use public storage and old uploads are removed after replacement', function (): void {
    Storage::fake('public');
    $admin = AdminUser::create(['username' => 'editor', 'name' => 'Editor', 'password_hash' => Hash::make('rahasia'), 'role' => 'editor', 'is_active' => true]);
    $this->actingAs($admin);

    $this->post('/admin/content/anggota', ['name' => 'Direktur Uji', 'role_label' => 'Direktur', 'sort_order' => 1, 'is_active' => '1', 'image' => UploadedFile::fake()->image('foto-lama.jpg')])->assertRedirect();

    $member = Anggota::query()->where('name', 'Direktur Uji')->firstOrFail();
    $oldPath = substr($member->photo_url, strlen('/storage/'));
    expect($member->photo_url)->toStartWith('/storage/uploads/anggota/');
    Storage::disk('public')->assertExists($oldPath);
    $this->get('/anggota')->assertOk()->assertSee($member->photo_url, false);

    $this->put('/admin/content/anggota/'.$member->id, ['name' => 'Direktur Uji', 'role_label' => 'Direktur', 'sort_order' => 1, 'is_active' => '1', 'image' => UploadedFile::fake()->image('foto-baru.jpg')])->assertRedirect();

    $member->refresh();
    Storage::disk('public')->assertMissing($oldPath);
    Storage::disk('public')->assertExists(substr($member->photo_url, strlen('/storage/')));
});

test('a profile video in public storage is embedded on the about page', function (): void {
    SiteSetting::create(['setting_key' => 'about_show_video', 'setting_value' => '1']);
    SiteSetting::create(['setting_key' => 'about_video_url', 'setting_value' => '/storage/uploads/profile/video.mp4']);

    $this->get('/aboutus')->assertOk()->assertSee('<video', false)->assertSee('src="/storage/uploads/profile/video.mp4"', false);
});

test('contact uses the saved phone number in international WhatsApp format', function (): void {
    SiteSetting::create(['setting_key' => 'contact_phone', 'setting_value' => '0811-1210-1007']);

    $this->get('/kontak')->assertOk()->assertSee('https://wa.me/6281112101007', false);
});

test('instructors and assessors share one public page', function (): void {
    Instruktur::create(['name' => 'Asesor Aktif', 'photo_url' => '/assets/asesor/foto.jpg', 'is_active' => true, 'sort_order' => 1]);
    Instruktur::create(['name' => 'Asesor Nonaktif', 'photo_url' => '/assets/asesor/foto.jpg', 'is_active' => false, 'sort_order' => 2]);

    $this->get('/instruktur')->assertOk()->assertSee('Instruktur &amp; Asesor LSP FIT', false)->assertSee('Asesor Aktif')->assertDontSee('Asesor Nonaktif');
    $this->get('/asesor')->assertRedirect('/instruktur');
    $this->get('/asesor.html')->assertRedirect('/instruktur');
});

test('the public structure displays active members in organization levels', function (): void {
    Anggota::create(['name' => 'Komisaris Aktif', 'role_label' => 'Dewan Komisaris', 'photo_url' => '/assets/asesor/foto.jpg', 'is_active' => true, 'sort_order' => 4]);
    Anggota::create(['name' => 'Direktur Aktif', 'role_label' => 'Direktur', 'photo_url' => '/assets/asesor/foto.jpg', 'is_active' => true, 'sort_order' => 3]);
    Anggota::create(['name' => 'Bidang Aktif', 'role_label' => 'Bidang Mutu', 'photo_url' => '/assets/asesor/foto.jpg', 'is_active' => true, 'sort_order' => 2]);
    Anggota::create(['name' => 'Komisaris Nonaktif', 'role_label' => 'Dewan Komisaris', 'photo_url' => '/assets/asesor/foto.jpg', 'is_active' => false, 'sort_order' => 1]);

    $this->get('/anggota')->assertOk()
        ->assertSee('Bagan struktur organisasi')
        ->assertSee('Komisaris Aktif')->assertSee('Direktur Aktif')->assertSee('Bidang Aktif')
        ->assertDontSee('Komisaris Nonaktif')
        ->assertViewHas('boardMembers', fn ($members): bool => $members->count() === 1)
        ->assertViewHas('directors', fn ($members): bool => $members->count() === 1)
        ->assertViewHas('divisionMembers', fn ($members): bool => $members->count() === 1);
});

test('instructors and assessors are paginated on their shared public page', function (): void {
    for ($number = 1; $number <= 9; $number++) {
        Instruktur::create(['name' => sprintf('Tenaga %02d', $number), 'photo_url' => '/assets/asesor/foto.jpg', 'is_active' => true, 'sort_order' => 10 - $number]);
    }
    Instruktur::create(['name' => 'Tenaga Nonaktif', 'photo_url' => '/assets/asesor/foto.jpg', 'is_active' => false, 'sort_order' => 99]);

    $this->get('/instruktur')->assertOk()
        ->assertViewHas('people', fn ($people): bool => $people->total() === 9 && $people->perPage() === 8)
        ->assertSee('Tenaga 01')->assertDontSee('Tenaga 09')->assertDontSee('Tenaga Nonaktif')
        ->assertSee('page=2');

    $this->get('/instruktur?page=2')->assertOk()->assertSee('Tenaga 09')->assertDontSee('Tenaga 01');
});

test('active schemes are paginated on the public listing', function (): void {
    for ($number = 1; $number <= 7; $number++) {
        Kegiatan::create(['title' => sprintf('Skema %02d', $number), 'slug' => 'skema-'.$number, 'category' => 'Instruktur', 'summary' => 'Ringkasan', 'description' => 'Isi', 'image_url' => '/assets/example.png', 'is_active' => true, 'is_highlight' => false, 'sort_order' => 10 - $number]);
    }
    Kegiatan::create(['title' => 'Skema Nonaktif', 'slug' => 'skema-nonaktif', 'category' => 'Instruktur', 'summary' => 'Ringkasan', 'description' => 'Isi', 'image_url' => '/assets/example.png', 'is_active' => false, 'is_highlight' => false, 'sort_order' => 99]);

    $this->get('/kegiatan')->assertOk()
        ->assertViewHas('schemes', fn ($schemes): bool => $schemes->total() === 7 && $schemes->perPage() === 6)
        ->assertSee('Skema 01')->assertDontSee('Skema 07')->assertDontSee('Skema Nonaktif')
        ->assertSee('page=2');

    $this->get('/kegiatan?page=2')->assertOk()->assertSee('Skema 07')->assertDontSee('Skema 01');
});

test('an admin can create :module content in its legacy table', function (string $module, array $input, string $table): void {
    $admin = AdminUser::create(['username' => 'editor', 'name' => 'Editor', 'password_hash' => Hash::make('rahasia'), 'role' => 'editor', 'is_active' => true]);

    $this->actingAs($admin)->post('/admin/content/'.$module, [...$input, 'sort_order' => 3, 'is_active' => '1'])->assertRedirect('/admin/content/'.$module);

    $this->assertDatabaseHas($table, ['name' => $input['name'], 'is_active' => 1, 'sort_order' => 3]);
})->with([
    'anggota' => ['anggota', ['name' => 'Nama Anggota', 'role_label' => 'Ketua'], 'anggota'],
    'instruktur' => ['instruktur', ['name' => 'Nama Instruktur'], 'instruktur'],
    'mitra' => ['mitra', ['name' => 'Nama Mitra', 'website_url' => 'https://example.org'], 'partners'],
]);

test('admin content lists have pagination and preserve search filters', function (): void {
    $admin = AdminUser::create(['username' => 'editor', 'name' => 'Editor', 'password_hash' => Hash::make('rahasia'), 'role' => 'editor', 'is_active' => true]);

    for ($number = 1; $number <= 12; $number++) {
        Instruktur::create(['name' => sprintf('Tenaga %02d', $number), 'photo_url' => '/assets/asesor/foto.jpg', 'is_active' => $number !== 12, 'sort_order' => 13 - $number]);
    }

    $this->actingAs($admin)->get('/admin/content/instruktur')->assertOk()
        ->assertViewHas('items', fn ($items): bool => $items->total() === 12 && $items->perPage() === 10)
        ->assertSee('Tenaga 01')->assertDontSee('Tenaga 12');

    $this->get('/admin/content/instruktur?page=2')->assertOk()->assertSee('Tenaga 12')->assertDontSee('Tenaga 01');

    $this->get('/admin/content/instruktur?q=Tenaga&status=active&per_page=10')->assertOk()
        ->assertViewHas('items', fn ($items): bool => $items->total() === 11 && str_contains($items->nextPageUrl(), 'status=active'))
        ->assertDontSee('Tenaga 12');
});

test('partner editing shows the logo and name without an editable website URL', function (): void {
    $admin = AdminUser::create(['username' => 'editor', 'name' => 'Editor', 'password_hash' => Hash::make('rahasia'), 'role' => 'editor', 'is_active' => true]);
    $partner = Partner::create(['name' => 'Mitra Uji', 'logo_url' => '/assets/partners/logo.jpg', 'website_url' => 'https://example.org', 'is_active' => true, 'sort_order' => 1]);

    $this->actingAs($admin)->get('/admin/content/mitra/'.$partner->id.'/edit')->assertOk()
        ->assertSee('Gambar konten saat ini')
        ->assertDontSee('name="website_url"', false);

    $this->put('/admin/content/mitra/'.$partner->id, ['name' => 'Mitra Baru', 'website_url' => 'https://wrong.example', 'sort_order' => 1, 'is_active' => '1'])->assertRedirect();

    $this->assertDatabaseHas('partners', ['id' => $partner->id, 'name' => 'Mitra Baru', 'website_url' => 'https://example.org']);
});
