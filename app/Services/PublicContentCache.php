<?php

namespace App\Services;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class PublicContentCache
{
    private const TTL_SECONDS = 3600;

    /**
     * @template TValue
     *
     * @param  Closure(): TValue  $resolver
     * @return TValue
     */
    public function remember(string $tag, string $key, Closure $resolver): mixed
    {
        return Cache::tags(['public-content', $tag])->remember($key, self::TTL_SECONDS, $resolver);
    }

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @return Collection<int, TModel>
     */
    public function collection(string $tag, string $key, Builder $query): Collection
    {
        $model = $query->getModel();
        $rows = $this->remember($tag, $key, fn (): array => $query->get()
            ->map(fn (Model $item): array => $item->getAttributes())
            ->all());

        return $model->newCollection(array_map(
            fn (array $attributes): Model => $model->newFromBuilder($attributes),
            $rows,
        ));
    }

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @return TModel
     */
    public function model(string $tag, string $key, Builder $query): Model
    {
        $model = $query->getModel();
        $attributes = $this->remember($tag, $key, fn (): array => $query->firstOrFail()->getAttributes());

        return $model->newFromBuilder($attributes);
    }

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @param  array<string, mixed>  $options
     * @return LengthAwarePaginator<int, TModel>
     */
    public function paginate(string $tag, string $key, Builder $query, int $perPage, array $options = []): LengthAwarePaginator
    {
        $pagination = $this->remember($tag, $key, function () use ($query, $perPage): array {
            $paginator = $query->paginate($perPage);

            return [
                'items' => $paginator->getCollection()
                    ->map(fn (Model $item): array => $item->getAttributes())
                    ->all(),
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
            ];
        });
        $model = $query->getModel();
        $items = $model->newCollection(array_map(
            fn (array $attributes): Model => $model->newFromBuilder($attributes),
            $pagination['items'],
        ));

        return new LengthAwarePaginator(
            $items,
            $pagination['total'],
            $pagination['per_page'],
            $pagination['current_page'],
            $options,
        );
    }

    public function flush(string $tag): void
    {
        Cache::tags($tag)->flush();
    }
}
