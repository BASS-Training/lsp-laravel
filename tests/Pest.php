<?php

use App\Models\AdminUser;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->beforeEach(fn () => createLegacySchema())
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Meniru tabel lama (database/schema.sql) di database khusus tes (`lsp_compro_test`).
 * Database aktif tidak pernah dimigrasi: helper ini menolak berjalan di database lain.
 */
function createLegacySchema(): void
{
    $database = (string) config('database.connections.'.config('database.default').'.database');

    if (! str_ends_with($database, '_test')) {
        throw new RuntimeException("Pengujian menolak berjalan di database [{$database}]; gunakan database berakhiran _test.");
    }

    Schema::disableForeignKeyConstraints();
    foreach (['kegiatan', 'admin_users', 'anggota', 'instruktur', 'partners', 'site_settings'] as $table) {
        Schema::dropIfExists($table);
    }
    Schema::enableForeignKeyConstraints();

    Schema::create('kegiatan', function (Blueprint $table): void {
        $table->id();
        $table->string('title', 180);
        $table->string('slug', 220)->unique();
        $table->string('category', 80)->default('Kegiatan');
        $table->text('summary');
        $table->text('description');
        $table->string('image_url');
        $table->boolean('is_active')->default(true);
        $table->boolean('is_highlight')->default(false);
        $table->integer('sort_order')->default(0);
        $table->timestamps();
    });

    Schema::create('admin_users', function (Blueprint $table): void {
        $table->id();
        $table->string('username')->unique();
        $table->string('name');
        $table->string('password_hash');
        $table->string('role')->default('admin_konten');
        $table->boolean('is_active')->default(true);
        $table->timestamp('last_login_at')->nullable();
        $table->timestamps();
    });

    foreach (['anggota' => 'photo_url', 'instruktur' => 'photo_url', 'partners' => 'logo_url'] as $name => $imageColumn) {
        Schema::create($name, function (Blueprint $table) use ($name, $imageColumn): void {
            $table->id();
            $table->string('name', 140);
            if ($name === 'anggota') {
                $table->string('role_label', 120);
            }
            $table->string($imageColumn);
            if ($name === 'partners') {
                $table->string('website_url')->nullable();
            }
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    Schema::create('site_settings', function (Blueprint $table): void {
        $table->string('setting_key', 80)->primary();
        $table->text('setting_value')->nullable();
        $table->timestamp('updated_at')->nullable();
    });
}

function createAdmin(array $overrides = []): AdminUser
{
    return AdminUser::create([
        'username' => 'admin',
        'name' => 'Admin Uji',
        'password_hash' => Hash::make('rahasia-123'),
        'role' => 'super_admin',
        'is_active' => true,
        ...$overrides,
    ]);
}
