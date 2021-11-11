<?php

namespace App\Services\GoogleBookService;

use App\Services\HttpService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class GoogleBookService
{
    public function __construct(
        public ?string $class = null,
        // /** @var Model[] */
        public ?Collection $models = null,
        // /** @var WikipediaQuery[] */
        public ?Collection $google_book_queries = null,
        // /** @var WikipediaQuery[] */
        public ?Collection $google_book_queries_failed = null,
        public ?bool $debug = false,
    ) {
        $this->models = collect([]);
        $this->google_book_queries = collect([]);
        $this->google_book_queries_failed = collect([]);
    }

    /**
     * Get data from Google Books API with ISBN from meta
     * Example: https://www.googleapis.com/books/v1/volumes?q=isbn:9782700239904.
     *
     * Get all useful data to improve Book, Identifier, Publisher and Tag
     * If data exist, create GoogleBook associate with Book with useful data to purchase eBook
     */
    public static function create(string $class, ?bool $debug = false): GoogleBookService
    {
        $service = new GoogleBookService();
        $service->class = $class;
        $service->models = $class::all();
        $service->debug = $debug;

        $service->getQueries()
            ->search()
        ;

        return $service;
    }

    public function getQueries(): GoogleBookService
    {
        /** @var Model $model */
        foreach ($this->models as $model) {
            $query = GoogleBookQuery::create($model, $this);

            $this->google_book_queries->add($query);
        }

        return $this;
    }

    /**
     * Make GET request from Wikipedia API and parse it.
     */
    public function search(): GoogleBookService
    {
        /**
         * Make GET request from $url_attribute of GoogleBookQuery[].
         */
        $responses = HttpService::getCollection($this->google_book_queries, 'url', 'model_id');

        $queries = collect([]);
        $failed = collect([]);
        /** Parse Reponse[] with $method */
        foreach ($responses as $id => $response) {
            /** @var null|GoogleBookQuery $query */
            $query = $this->google_book_queries->first(fn (GoogleBookQuery $query) => $query->model_id === $id);
            if (null !== $query) {
                $query = $query->parseResponse($response);
                $queries->add($query);
            } else {
                $failed->add($id);
            }
        }

        $this->google_book_queries->replace($queries);
        $this->google_book_queries_failed->replace($failed);

        return $this;
    }
}
