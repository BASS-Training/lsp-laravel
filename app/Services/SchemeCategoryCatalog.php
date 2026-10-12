<?php

namespace App\Services;

use App\Models\Kegiatan;
use App\Models\SiteSetting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SchemeCategoryCatalog
{
    public const SETTING_PREFIX = 'scheme_category_';

    /** @return Collection<int, string> */
    public function names(): Collection
    {
        $configured = SiteSetting::query()
            ->where('setting_key', 'like', self::SETTING_PREFIX.'%')
            ->pluck('setting_value');
        $used = Kegiatan::query()
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->pluck('category');

        return $configured
            ->merge($used)
            ->map(fn (?string $name): string => $this->normalize((string) $name))
            ->filter()
            ->unique(fn (string $name): string => mb_strtolower($name))
            ->sort(fn (string $first, string $second): int => strnatcasecmp($first, $second))
            ->values();
    }

    public function idFor(string $name): string
    {
        return sha1(mb_strtolower($this->normalize($name)));
    }

    public function find(string $id): ?string
    {
        return $this->names()->first(fn (string $name): bool => hash_equals($this->idFor($name), $id));
    }

    public function usageCount(string $name): int
    {
        return Kegiatan::query()->where('category', $name)->count();
    }

    /** @return Collection<string, int> */
    public function usageCounts(): Collection
    {
        return Kegiatan::query()
            ->selectRaw('category, COUNT(*) as aggregate')
            ->groupBy('category')
            ->pluck('aggregate', 'category')
            ->map(fn (mixed $count): int => (int) $count);
    }

    public function contains(string $name, ?string $except = null): bool
    {
        $normalizedName = mb_strtolower($this->normalize($name));
        $normalizedExcept = $except === null ? null : mb_strtolower($this->normalize($except));

        return $this->names()->contains(function (string $existing) use ($normalizedName, $normalizedExcept): bool {
            $normalizedExisting = mb_strtolower($existing);

            return $normalizedExisting === $normalizedName && $normalizedExisting !== $normalizedExcept;
        });
    }

    public function create(string $name): void
    {
        SiteSetting::query()->updateOrCreate(
            ['setting_key' => self::SETTING_PREFIX.$this->idFor($name)],
            ['setting_value' => $this->normalize($name)],
        );
    }

    public function rename(string $currentName, string $newName): void
    {
        DB::transaction(function () use ($currentName, $newName): void {
            Kegiatan::query()->where('category', $currentName)->update(['category' => $newName]);
            SiteSetting::query()->where('setting_key', self::SETTING_PREFIX.$this->idFor($currentName))->delete();
            $this->create($newName);
        });
    }

    public function delete(string $name): bool
    {
        if ($this->usageCount($name) > 0) {
            return false;
        }

        return SiteSetting::query()
            ->where('setting_key', self::SETTING_PREFIX.$this->idFor($name))
            ->delete() > 0;
    }

    public function normalize(string $name): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $name));
    }
}
