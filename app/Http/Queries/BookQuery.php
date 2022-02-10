<?php

namespace App\Http\Queries;

use App\Exports\BookExport;
use App\Http\Queries\Addon\QueryOption;
use App\Http\Resources\Admin\BookResource;
use App\Models\Book;
use App\Support\GlobalSearchFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class BookQuery extends BaseQuery
{
    public function make(?QueryOption $option = null)
    {
        if (! $option) {
            $option = new QueryOption();
            $option->resource = BookResource::class;
        }

        $this->option = $option;
        $option->with = [] === $option->with ? ['serie', 'media', 'authors', 'language', 'publisher'] : $this->option->with;

        $this->query = QueryBuilder::for(Book::class)
            ->defaultSort($this->option->defaultSort)
            ->allowedFilters([
                AllowedFilter::custom('q', new GlobalSearchFilter(['title', 'serie'])),
                AllowedFilter::exact('id'),
                AllowedFilter::partial('title'),
                AllowedFilter::callback('serie', function (Builder $query, $value) {
                    return $query->whereHas('serie', function (Builder $query) use ($value) {
                        $query->where('title', 'like', "%{$value}%");
                    });
                }),
                AllowedFilter::partial('volume'),
                AllowedFilter::callback('authors', function (Builder $query, $value) {
                    return $query->whereHas('authors', function (Builder $query) use ($value) {
                        $query->where('name', 'like', "%{$value}%");
                    });
                }),
                AllowedFilter::exact('disabled'),
                AllowedFilter::exact('released_on'),
                AllowedFilter::exact('type'),
                AllowedFilter::callback('language', function (Builder $query, $value) {
                    return $query->whereHas('language', function (Builder $query) use ($value) {
                        $query->where('name', 'like', "%{$value}%");
                    });
                }),
                AllowedFilter::callback('publisher', function (Builder $query, $value) {
                    return $query->whereHas('publisher', function (Builder $query) use ($value) {
                        $query->where('name', 'like', "%{$value}%");
                    });
                }),
                AllowedFilter::scope('disallow_serie', 'whereDisallowSerie'),
                AllowedFilter::scope('languages', 'whereLanguagesIs'),
                AllowedFilter::scope('published', 'publishedBetween'),
                AllowedFilter::scope('author_like', 'whereAuthorIsLike'),
                AllowedFilter::scope('tags_all', 'whereTagsAllIs'),
                AllowedFilter::scope('tags', 'whereTagsIs'),
            ])
            ->allowedSorts(['id', 'title', 'slug_sort', 'type', 'serie', 'authors', 'volume', 'publisher',  'released_on', 'created_at', 'updated_at'])
            ->with($option->with)
            ->withCount('tags')
        ;

        if ($this->option->withExport) {
            $this->export = new BookExport($this->query);
        }
        $this->resource = 'books';

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
            'books' => fn () => $this->collection(),
        ];
    }
}
