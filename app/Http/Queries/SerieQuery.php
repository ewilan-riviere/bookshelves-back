<?php

namespace App\Http\Queries;

use App\Exports\SerieExport;
use App\Http\Queries\Addon\QueryOption;
use App\Http\Resources\Admin\SerieResource;
use App\Models\Serie;
use App\Support\GlobalSearchFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class SerieQuery extends BaseQuery
{
    public function make(?QueryOption $option = null): self
    {
        if (! $option) {
            $option = new QueryOption();
            $option->resource = SerieResource::class;
        }

        $this->option = $option;
        $option->with = [] === $option->with ? ['books', 'media', 'authors', 'language'] : $this->option->with;

        $this->query = QueryBuilder::for(Serie::class)
            ->defaultSort($this->option->defaultSort)
            ->allowedFilters([
                AllowedFilter::custom('q', new GlobalSearchFilter(['title'])),
                AllowedFilter::partial('title'),
                AllowedFilter::partial('authors'),
                AllowedFilter::callback('language', function (Builder $query, $value) {
                    return $query->whereHas('language', function (Builder $query) use ($value) {
                        $query->where('name', 'like', "%{$value}%");
                    });
                }),
            ])
            ->allowedSorts(['id', 'title', 'authors', 'books_count', 'created_at', 'updated_at', 'language'])
            ->with($option->with)
            ->withCount('books', 'tags')
        ;

        if ($this->option->withExport) {
            $this->export = new SerieExport($this->query);
        }
        $this->resource = 'series';

        return $this;
    }

    public function collection(): AnonymousResourceCollection
    {
        /** @var JsonResource $resource */
        $resource = $this->option->resource;

        return $resource::collection($this->paginate());
    }

    public function get(): array
    {
        return [
            'sort' => request()->get('sort', $this->option->defaultSort),
            'filter' => request()->get('filter'),
            'series' => fn () => $this->collection(),
        ];
    }
}