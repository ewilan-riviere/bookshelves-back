<?php

namespace App\Http\Queries;

use App\Models\Stub;
use App\Exports\StubExport;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use App\Http\Queries\Addon\QueryOption;
use App\Http\Resources\Admin\StubResource;
use App\Http\Queries\Filter\GlobalSearchFilter;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class StubQuery extends BaseQuery
{
    public function make(?QueryOption $option = null): self
    {
        if (! $option) {
            $option = new QueryOption();
            $option->resource = StubResource::class;
        }

        $this->option = $option;
        $option->with = [] === $option->with ? [] : $this->option->with;

        $this->query = QueryBuilder::for(Stub::class)
            ->allowedFilters([
                AllowedFilter::custom('q', new GlobalSearchFilter(['stubAttr'])),
                AllowedFilter::exact('id'),
                AllowedFilter::partial('stubAttr'),
            ])
            ->allowedSorts(['id', 'stubAttr', 'created_at', 'updated_at'])
            ->with($option->with)
            ->orderByDesc($this->option->orderBy)
        ;

        if ($this->option->withExport) {
            $this->export = new StubExport($this->query);
        }
        $this->resource = 'stubsKebab';

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
            'stubsKebab' => fn () => $this->collection(),
        ];
    }
}
