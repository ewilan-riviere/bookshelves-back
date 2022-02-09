<?php

namespace App\Http\Queries;

use App\Exports\PostExport;
use App\Http\Queries\Addon\QueryOption;
use App\Http\Resources\Admin\PostResource;
use App\Models\Post;
use App\Support\GlobalSearchFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class PostQuery extends BaseQuery
{
    public function make(?QueryOption $option = null): self
    {
        if (! $option) {
            $option = new QueryOption();
            $option->resource = PostResource::class;
            $option->with = ['category', 'media', 'tags', 'user'];
        }

        $this->option = $option;

        $this->query = QueryBuilder::for(Post::class)
            ->allowedFilters([
                AllowedFilter::custom('q', new GlobalSearchFilter(['title', 'summary', 'body'])),
                AllowedFilter::partial('title'),
                AllowedFilter::partial('summary'),
                AllowedFilter::partial('body'),
                AllowedFilter::exact('id'),
                AllowedFilter::exact('category', 'category_id'),
                AllowedFilter::exact('status'),
                AllowedFilter::exact('pin'),
                AllowedFilter::exact('promote'),
                AllowedFilter::scope('published_at', 'publishedBetween'),
                AllowedFilter::callback('user', function (Builder $query, $value) {
                    return $query->whereHas('user', function (Builder $query) use ($value) {
                        $query->where('name', 'like', "%{$value}%");
                    });
                }),
            ])
            ->allowedSorts(['id', 'title', 'published_at', 'created_at', 'updated_at'])
            ->with($option->with)
            ->orderByDesc($this->option->orderBy)
        ;

        $this->export = new PostExport($this->query);
        $this->resource = 'posts';

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
            'posts' => fn () => $this->collection(),
        ];
    }
}
