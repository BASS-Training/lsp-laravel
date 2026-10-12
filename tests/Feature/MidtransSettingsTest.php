<?php

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Crypt;

beforeEach(function (): void {
    $this->actingAs(createAdmin());
});

it('stores Midtrans keys encrypted and masks them in the admin form', function (): void {
    $this->put('/admin/midtrans', [
        'enabled' => '1',
        'environment' => 'sandbox',
        'merchant_id' => 'G123456789',
        'server_key' => 'SB-Mid-server-secret',
        'client_key' => 'SB-Mid-client-secret',
    ])->assertRedirect();

    $encrypted = SiteSetting::findOrFail('midtrans_server_key')->setting_value;
    expect($encrypted)->not->toContain('SB-Mid-server-secret')
        ->and(Crypt::decryptString($encrypted))->toBe('SB-Mid-server-secret');

    $this->get('/admin/midtrans')
        ->assertOk()
        ->assertDontSee('SB-Mid-server-secret')
        ->assertSee('cret');
});

it('requires a server key before Midtrans can be enabled', function (): void {
    $this->put('/admin/midtrans', [
        'enabled' => '1',
        'environment' => 'sandbox',
        'merchant_id' => 'G123456789',
    ])->assertSessionHasErrors('server_key');
});

it('does not expose Midtrans settings through the legacy profile API', function (): void {
    SiteSetting::create(['setting_key' => 'midtrans_server_key', 'setting_value' => 'secret']);
    SiteSetting::create(['setting_key' => 'vision', 'setting_value' => 'Visi']);

    $settings = $this->getJson('/api/profile.php')->assertOk()->json('settings');

    expect($settings)->toHaveKey('vision')->not->toHaveKey('midtrans_server_key');
});
