<?php

namespace App\Http\Controllers\Api;

use App\Enums\LibraryTypeEnum;
use App\Http\Controllers\Controller;
use App\Models\Audiobook;
use App\Models\Book;
use App\Models\Serie;
use Illuminate\Http\Request;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Prefix;

#[Prefix('size')]
class SizeController extends Controller
{
    #[Get('/book/{book:id}', name: 'api.sizes.book')]
    public function book(Request $request, Book $book)
    {
        $book->loadMissing(['library', 'audiobooks']);

        if ($book->library?->type === LibraryTypeEnum::audiobook) {
            $size = $book->audiobooks
                ->map(fn (Audiobook $audiobook) => $audiobook->size)
                ->sum();
        } else {
            $size = $book->size;
        }

        return response()->json([
            'extension' => $book->library?->type === LibraryTypeEnum::audiobook ? 'zip' : $book->extension,
            'size' => $size,
        ]);
    }

    #[Get('/serie/{serie:id}', name: 'api.sizes.serie')]
    public function serie(Request $request, Serie $serie)
    {
        $size = 0;
        $serie->loadMissing(['books', 'books.library', 'books.audiobooks']);

        foreach ($serie->books as $book) {
            if ($book->library?->type === LibraryTypeEnum::audiobook) {
                $size += $book->audiobooks
                    ->map(fn (Audiobook $audiobook) => $audiobook->size)
                    ->sum();
            } else {
                $size += $book->size;
            }
        }

        return response()->json([
            'extension' => 'zip',
            'size' => $size,
        ]);
    }
}
