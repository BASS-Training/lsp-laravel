<?php

it('logs in with a legacy password_hash and records last_login_at', function (): void {
    $admin = createAdmin();

    $this->post('/admin/login', ['username' => 'admin', 'password' => 'rahasia-123'])->assertRedirect('/admin');

    $this->assertAuthenticatedAs($admin);
    expect($admin->refresh()->last_login_at)->not->toBeNull();
});

it('rejects a wrong password', function (): void {
    createAdmin();

    $this->from('/admin/login')->post('/admin/login', ['username' => 'admin', 'password' => 'salah'])
        ->assertRedirect('/admin/login')->assertSessionHasErrors('username');

    $this->assertGuest();
});

it('rejects an inactive admin even with the right password', function (): void {
    createAdmin(['is_active' => false]);

    $this->post('/admin/login', ['username' => 'admin', 'password' => 'rahasia-123'])->assertSessionHasErrors('username');

    $this->assertGuest();
});

it('redirects guests away from admin pages', function (string $path): void {
    $this->get($path)->assertRedirect('/admin/login');
})->with(['/admin', '/admin/content/kegiatan', '/admin/settings/homepage']);

it('logs an admin out', function (): void {
    $this->actingAs(createAdmin())->post('/admin/logout')->assertRedirect('/admin/login');

    $this->assertGuest();
});

it('kicks out an admin that became inactive during the session', function (): void {
    $admin = createAdmin();
    $this->actingAs($admin);
    $admin->update(['is_active' => false]);

    $this->get('/admin')->assertRedirect('/admin/login');
});
