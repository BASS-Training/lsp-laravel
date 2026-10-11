<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\ContentController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Public\LegacyApiController;
use App\Http\Controllers\Public\SiteController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', [SiteController::class, 'home'])->name('home');
Route::get('/aboutus', [SiteController::class, 'about'])->name('about');
Route::get('/kegiatan', [SiteController::class, 'schemes'])->name('schemes');
Route::get('/kegiatan-detail', [SiteController::class, 'scheme'])->name('schemes.show');
Route::get('/kegiatan-detail.html', fn (Request $request): RedirectResponse => redirect()->route('schemes.show', ['id' => $request->query('id')], 301));
Route::get('/anggota', [SiteController::class, 'members'])->name('members');
Route::get('/instruktur', [SiteController::class, 'instructors'])->name('instructors');
Route::redirect('/asesor', '/instruktur', 301);
Route::redirect('/gallery', '/kegiatan', 301)->name('gallery');
Route::get('/kontak', [SiteController::class, 'contact'])->name('contact');

Route::get('/api/auth.php', [LegacyApiController::class, 'authentication']);
Route::get('/api/kegiatan.php', [LegacyApiController::class, 'kegiatan']);
Route::get('/api/anggota.php', [LegacyApiController::class, 'anggota']);
Route::get('/api/instruktur.php', [LegacyApiController::class, 'instruktur']);
Route::get('/api/partners.php', [LegacyApiController::class, 'partners']);
Route::get('/api/homepage.php', [LegacyApiController::class, 'homepage']);
Route::get('/api/profile.php', [LegacyApiController::class, 'profile']);

Route::get('/admin/login', [AuthController::class, 'create'])->name('login');
Route::post('/admin/login', [AuthController::class, 'store'])->middleware('throttle:5,1')->name('admin.login');
Route::post('/admin/logout', [AuthController::class, 'destroy'])->middleware('auth')->name('admin.logout');
Route::middleware(['auth', 'active.admin'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/settings/{group}', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::put('/settings/{group}', [SettingsController::class, 'update'])->name('settings.update');
    Route::get('/content/{module}', [ContentController::class, 'index'])->name('content.index');
    Route::get('/content/{module}/create', [ContentController::class, 'create'])->name('content.create');
    Route::get('/content/kegiatan/{id}/preview', [ContentController::class, 'preview'])->whereNumber('id')->name('content.preview');
    Route::post('/content/{module}', [ContentController::class, 'store'])->name('content.store');
    Route::get('/content/{module}/{id}/edit', [ContentController::class, 'edit'])->whereNumber('id')->name('content.edit');
    Route::put('/content/{module}/{id}', [ContentController::class, 'update'])->whereNumber('id')->name('content.update');
    Route::delete('/content/{module}/{id}', [ContentController::class, 'destroy'])->whereNumber('id')->name('content.destroy');
});

Route::redirect('/admin.html', '/admin/login', 301);
Route::redirect('/admin-dashboard.html', '/admin', 301);
Route::redirect('/admin-homepage.html', '/admin/settings/homepage', 301);
Route::redirect('/admin-profil.html', '/admin/settings/profil', 301);
Route::redirect('/admin-kegiatan.html', '/admin/content/kegiatan', 301);
Route::redirect('/admin-anggota.html', '/admin/content/anggota', 301);
Route::redirect('/admin-instruktur.html', '/admin/content/instruktur', 301);
Route::redirect('/admin-mitra.html', '/admin/content/mitra', 301);

foreach ([
    'index.html' => 'home',
    'aboutus.html' => 'about',
    'skema.html' => 'schemes',
    'kegiatan.html' => 'schemes',
    'anggota.html' => 'members',
    'instruktur.html' => 'instructors',
    'asesor.html' => 'instructors',
    'gallery.html' => 'schemes',
    'kontak.html' => 'contact',
] as $legacyPath => $routeName) {
    Route::get('/'.$legacyPath, fn (): RedirectResponse => redirect()->route($routeName, status: 301));
}
