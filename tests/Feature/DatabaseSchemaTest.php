<?php

it('has no migration that touches the legacy tables or schema', function (): void {
    $legacyTables = ['kegiatan', 'admin_users', 'anggota', 'instruktur', 'partners', 'site_settings'];

    foreach (glob(database_path('migrations/*.php')) as $file) {
        $contents = file_get_contents($file);

        foreach ($legacyTables as $table) {
            expect($contents)->not->toContain("'{$table}'");
        }
    }
});

it('configures file based sessions and Redis cache so no extra tables are needed', function (): void {
    $example = file_get_contents(base_path('.env.example'));

    expect($example)
        ->toContain('SESSION_DRIVER=file')
        ->toContain('CACHE_STORE=redis')
        ->toContain('REDIS_CLIENT=predis')
        ->toContain('REDIS_CACHE_DB=1')
        ->toContain('QUEUE_CONNECTION=sync');
});

it('refuses to run schema helpers against the live database', function (): void {
    config(['database.connections.mysql.database' => 'lsp_compro']);

    expect(fn () => createLegacySchema())->toThrow(RuntimeException::class);
});
