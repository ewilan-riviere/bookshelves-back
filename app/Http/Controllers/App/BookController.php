<?php

namespace App\Http\Controllers\App;

use App\Enums\BookTypeEnum;
use App\Http\Controllers\Controller;
use App\Models\Book;
use Illuminate\Http\Request;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Prefix;

#[Prefix('books')]
class BookController extends Controller
{
    // #[Get('/audiobooks', name: 'books.audiobooks')]
    // public function audiobooks(Request $request)
    // {
    //     return $this->getQueryForBooks($request, Book::whereIsAudiobook(), 'Audiobooks', [
    //         ['label' => 'Audiobooks', 'route' => ['name' => 'books.audiobooks']],
    //     ]);
    // }

    #[Get('/', name: 'books.index')]
    public function index(Request $request)
    {
        return $this->getQueryForBooks($request, Book::whereIsBook(), 'Books', [
            ['label' => 'Books', 'route' => ['name' => 'books.index']],
        ]);
    }

    // #[Get('/comics', name: 'books.comics')]
    // public function comics(Request $request)
    // {
    //     return $this->getQueryForBooks($request, Book::whereIsComic(), 'Comics', [
    //         ['label' => 'Comics', 'route' => ['name' => 'books.comics']],
    //     ]);
    // }

    // #[Get('/mangas', name: 'books.mangas')]
    // public function mangas(Request $request)
    // {
    //     return $this->getQueryForBooks($request, Book::whereIsBook(), 'Mangas', [
    //         ['label' => 'Mangas', 'route' => ['name' => 'books.mangas']],
    //     ]);
    // }

    #[Get('/{book_slug}', name: 'books.show')]
    public function show(Book $book)
    {
        $book->load([
            'authors',
            'serie',
            'serie.books',
            'serie.books.media',
            'tags',
            'media',
            'publisher',
            'language',
        ]);

        return inertia('Books/Show', [
            'book' => $book,
            'square' => $book->type === BookTypeEnum::audiobook,
            'event' => [
                'title' => $book->title,
                'image' => $book->cover_social,
                'description' => $book->description,
            ],
        ]);
    }
}
