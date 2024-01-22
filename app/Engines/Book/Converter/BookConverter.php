<?php

namespace App\Engines\Book\Converter;

use App\Engines\Book\Converter\Modules\AuthorModule;
use App\Engines\Book\Converter\Modules\CoverModule;
use App\Engines\Book\Converter\Modules\IdentifierModule;
use App\Engines\Book\Converter\Modules\LanguageModule;
use App\Engines\Book\Converter\Modules\PublisherModule;
use App\Engines\Book\Converter\Modules\SerieModule;
use App\Engines\Book\Converter\Modules\TagModule;
use App\Enums\BookTypeEnum;
use App\Models\Book;
use Illuminate\Support\Carbon;
use Kiwilan\Ebook\Ebook;
use Kiwilan\Steward\Utils\Process;

/**
 * Create or improve a `Book` and relations.
 */
class BookConverter
{
    protected function __construct(
        protected Ebook $ebook,
        protected ?Book $book = null,
    ) {
    }

    /**
     * Set Book from Ebook.
     */
    public static function make(Ebook $ebook, BookTypeEnum $type, ?Book $book = null): self
    {
        $self = new self($ebook);
        $self->parse($type, $book);

        return $self;
    }

    public function book(): ?Book
    {
        return $this->book;
    }

    private function parse(BookTypeEnum $type, ?Book $book): self
    {
        if ($book) {
            $this->checkBook($type);
        }

        $identifiers = IdentifierModule::toCollection($this->ebook);

        if (! $book) {
            $this->book = new Book([
                'title' => $this->ebook->getTitle(),
                'uuid' => uniqid(),
                'slug' => $this->ebook->getMetaTitle()->getSlugLang(),
                'slug_sort' => $this->ebook->getMetaTitle()->getSlugSortWithSerie(),
                'contributor' => $this->ebook->getExtra('contributor'),
                'released_on' => $this->ebook->getPublishDate()?->format('Y-m-d'),
                'description' => $this->ebook->getDescription(2000),
                'rights' => $this->ebook->getCopyright(255),
                'volume' => $this->ebook->getVolume(),
                'type' => $type,
                'page_count' => $this->ebook->getPagesCount(),
                'physical_path' => $this->ebook->getPath(),
                'isbn10' => $identifiers->get('isbn10') ?? null,
                'isbn13' => $identifiers->get('isbn13') ?? null,
                'identifiers' => json_encode($identifiers),
            ]);

            Book::withoutSyncingToSearch(function () {
                $this->book->save();
            });
        }

        if (empty($this->book?->title)) {
            $this->book = null;

            return $this;
        }

        $this->syncAuthors();
        $this->syncTags();
        $this->syncPublisher();
        $this->syncLanguage();
        $this->syncSerie($type);
        $this->syncIdentifiers();
        $this->syncCover();

        return $this;
    }

    private function syncAuthors(): self
    {
        $authors = AuthorModule::toCollection($this->ebook);
        if ($authors->isEmpty()) {
            return $this;
        }

        Book::withoutSyncingToSearch(function () use ($authors) {
            $this->book->authorMain()->associate($authors->first());
            $this->book?->authors()->sync($authors->pluck('id'));
        });

        return $this;
    }

    private function syncTags(): self
    {
        $tags = TagModule::toCollection($this->ebook);
        if ($tags->isEmpty()) {
            return $this;
        }

        Book::withoutSyncingToSearch(function () use ($tags) {
            $this->book?->tags()->sync($tags->pluck('id'));
        });

        return $this;
    }

    private function syncPublisher(): self
    {
        $publisher = PublisherModule::toModel($this->ebook);
        if (! $publisher) {
            return $this;
        }

        Book::withoutSyncingToSearch(function () use ($publisher) {
            $this->book?->publisher()->associate($publisher);
            $this->book?->save();
        });

        return $this;
    }

    private function syncLanguage(): self
    {
        $language = LanguageModule::toModel($this->ebook);

        Book::withoutSyncingToSearch(function () use ($language) {
            $this->book?->language()->associate($language);
            $this->book?->save();
        });

        return $this;
    }

    private function syncSerie(BookTypeEnum $type): self
    {
        $serie = SerieModule::toModel($this->ebook, $type)->associate($this->book);
        if (! $serie) {
            return $this;
        }

        Book::withoutSyncingToSearch(function () use ($serie) {
            $this->book?->serie()->associate($serie);
            $this->book?->save();
        });

        return $this;
    }

    private function syncIdentifiers(): self
    {
        $identifiers = IdentifierModule::toCollection($this->ebook);
        if ($identifiers->isEmpty()) {
            return $this;
        }

        Book::withoutSyncingToSearch(function () use ($identifiers) {
            $this->book->isbn10 = $identifiers->get('isbn10') ?? null;
            $this->book->isbn13 = $identifiers->get('isbn13') ?? null;
            $this->book->identifiers = json_encode($identifiers);
            $this->book->save();
        });

        return $this;
    }

    private function syncCover(): void
    {
        Process::memoryPeek(function () {
            CoverModule::make($this->ebook, $this->book);
        }, maxMemory: 3);
    }

    private function checkBook(BookTypeEnum $type): self
    {
        if (! $this->book) {
            return $this;
        }

        if (! $this->book->slug_sort && $this->ebook->getSeries() && ! $this->book->serie) {
            $this->book->slug_sort = $this->ebook->getMetaTitle()->getSerieSlugSort();
        }

        if (! $this->book->contributor) {
            $this->book->contributor = $this->ebook->getExtra('contributor') ?? null;
        }

        if (! $this->book->released_on) {
            $this->book->released_on = Carbon::parse($this->ebook->getPublishDate());
        }

        if (! $this->book->rights) {
            $this->book->rights = $this->ebook->getCopyright();
        }

        if (! $this->book->description) {
            $this->book->description = $this->ebook->getDescription();
        }

        if (! $this->book->volume) {
            $this->book->volume = $this->ebook->getVolume();
        }

        if ($this->book->type === null) {
            $this->book->type = $type;
        }

        return $this;
    }
}
