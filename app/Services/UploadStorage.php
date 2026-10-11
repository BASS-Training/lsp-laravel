<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Menyimpan unggahan ke public/assets/uploads/<folder>, path yang sama dengan situs PHP lama.
 */
class UploadStorage
{
    private const DISK = 'uploads';

    private const URL_PREFIX = '/assets/uploads/';

    public function store(UploadedFile $file, string $folder): string
    {
        $fileName = Str::random(24).'.'.$file->extension();
        $path = $file->storeAs($folder, $fileName, self::DISK);

        abort_if($path === false, 500, 'Berkas gagal disimpan.');

        return self::URL_PREFIX.$path;
    }

    /**
     * Hanya menghapus berkas milik aplikasi: nama acak 24 karakter (baru) atau 24/32 heksadesimal (lama).
     */
    public function deleteOwned(?string $url, string $folder): void
    {
        if (! is_string($url)) {
            return;
        }

        $pattern = '~^'.preg_quote(self::URL_PREFIX.$folder, '~').'/(?:[A-Za-z0-9]{24}|[a-f0-9]{32})\.(?:jpe?g|png|webp|mp4)$~';

        if (preg_match($pattern, $url) === 1) {
            Storage::disk(self::DISK)->delete(substr($url, strlen(self::URL_PREFIX)));
        }
    }
}
