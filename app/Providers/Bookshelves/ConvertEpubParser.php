<?php

namespace App\Providers\Bookshelves;

use App\Models\Tag;
use App\Models\Book;
use App\Models\Serie;
use App\Models\Author;
use App\Models\Language;
use App\Models\Publisher;
use App\Models\Identifier;
use Illuminate\Support\Str;
use App\Providers\EpubParser\EpubParser;

class ConvertEpubParser
{
    public static function run(EpubParser $epubParser, bool $is_debug): Book
    {
        $bookIfExist = Book::whereSlug(Str::slug($epubParser->title, '-'))->first();
        $book = null;
        if (! $bookIfExist) {
            $book = Book::firstOrCreate([
                'title'        => $epubParser->title,
                'slug'         => Str::slug($epubParser->title, '-'),
                'title_sort'   => $epubParser->title_sort,
                'contributor'  => $epubParser->contributor,
                'description'  => $epubParser->description,
                'date'         => $epubParser->date,
                'rights'       => $epubParser->rights,
                'serie_number' => $epubParser->serie_number,
            ]);
            $authors = [];
            foreach ($epubParser->creators as $key => $creator) {
                $author_data = explode(' ', $creator);
                $lastname = $author_data[sizeof($author_data) - 1];
                array_pop($author_data);
                $firstname = implode(' ', $author_data);
                $author = Author::firstOrCreate([
                    'lastname'  => $lastname,
                    'firstname' => $firstname,
                    'name'      => "$firstname $lastname",
                    'slug'      => Str::slug("$lastname $firstname", '-'),
                ]);
                array_push($authors, $author);
            }
            $book->authors()->saveMany($authors);
            foreach ($epubParser->subjects as $key => $subject) {
                $tagIfExist = Tag::whereSlug(Str::slug($subject))->first();
                $tag = null;
                if (! $tagIfExist) {
                    $tag = Tag::firstOrCreate([
                        'name' => $subject,
                        'slug' => Str::slug($subject),
                    ]);
                }
                if (! $tag) {
                    $tag = $tagIfExist;
                }

                $book->tags()->save($tag);
            }
            $publisher = Publisher::firstOrCreate([
                'name' => $epubParser->publisher,
                'slug' => Str::slug($epubParser->publisher),
            ]);
            $book->publisher()->associate($publisher);
            if ($epubParser->serie) {
                $serieIfExist = Serie::whereSlug(Str::slug($epubParser->serie))->first();
                $serie = null;
                if (! $serieIfExist) {
                    $serie = Serie::firstOrCreate([
                        'title'      => $epubParser->serie,
                        'title_sort' => $epubParser->serie_sort,
                        'slug'       => Str::slug($epubParser->serie),
                    ]);
                } else {
                    $serie = $serieIfExist;
                }

                $book->serie()->associate($serie);
            }
            $language = Language::firstOrCreate([
                'slug' => $epubParser->language,
            ]);
            $language = self::generateLanguageFlag($language);
            $book->language()->associate($language->slug);
            $identifiers = Identifier::firstOrCreate([
                'isbn'   => $epubParser->identifiers->isbn,
                'isbn13' => $epubParser->identifiers->isbn13,
                'doi'    => $epubParser->identifiers->doi,
                'amazon' => $epubParser->identifiers->amazon,
                'google' => $epubParser->identifiers->google,
            ]);
            $book->identifier()->associate($identifiers);
            $book->save();
        }
        if (! $book) {
            $book = $bookIfExist;
        }

        return $book;
    }

    public static function generateLanguageFlag(Language $language): Language
    {
        $languages_display = [
            'en' => 'English',
            'gb' => 'English',
            'fr' => 'French',
        ];
        $languages_id = [
            'en' => 'gb',
            'gb' => 'gb',
            'fr' => 'fr',
        ];
        $lang_id = $language->slug;
        $lang_flag = array_key_exists($lang_id, $languages_id) ? $languages_id[$lang_id] : $lang_id;

        $language->flag = "https://www.countryflags.io/$lang_flag/flat/32.png";
        $language->display = array_key_exists($lang_id, $languages_display) ? $languages_display[$lang_id] : $lang_id;
        $language->save();

        return $language;
    }
}
