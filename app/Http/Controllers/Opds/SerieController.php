<?php

namespace App\Http\Controllers\Opds;

use App\Engines\OpdsApp;
use App\Http\Controllers\Controller;
use App\Models\Serie;
use Kiwilan\Opds\Entries\OpdsEntryNavigation;
use Kiwilan\Opds\Opds;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Prefix;

/**
 * @hideFromAPIDocumentation
 */
#[Prefix('series')]
class SerieController extends Controller
{
    #[Get('/', name: 'opds.series.index')]
    public function index()
    {
        $feeds = OpdsApp::cache('opds.series.index', function () {
            $alphabet = range('A', 'Z');
            $feeds = [];

            foreach ($alphabet as $char) {
                $id = strtolower($char);
                $count = Serie::query()
                    ->orderBy('title')
                    ->whereFirstCharacterIs($char)
                    ->count();
                $feeds[] = new OpdsEntryNavigation(
                    id: $id,
                    title: "{$char} ({$count} entries)",
                    route: route('opds.series.character', ['character' => $id]),
                    summary: "Series beginning with {$char}",
                    media: asset('vendor/images/no-cover.jpg'),
                );
            }

            return $feeds;
        });

        Opds::make(OpdsApp::options())
            ->title('Series')
            ->feeds($feeds)
            ->send();
    }

    #[Get('/{character}', name: 'opds.series.character')]
    public function character(string $character)
    {
        $lower = strtolower($character);
        $feeds = OpdsApp::cache("opds.series.character.{$lower}", function () use ($character) {
            $series = Serie::query()
                ->orderBy('title')
                ->whereFirstCharacterIs($character)
                ->get();

            $feeds = [];

            foreach ($series as $serie) {
                $description = $serie->description;
                $count = $serie->books_count;

                $feeds[] = new OpdsEntryNavigation(
                    id: $serie->slug,
                    title: "{$serie->title} ({$serie->type->name})",
                    route: route('opds.series.show', ['character' => $character, 'serie' => $serie->slug]),
                    summary: "{$count} books, {$description}",
                    media: $serie->cover_og,
                    updated: $serie->updated_at,
                );
            }

            return $feeds;
        });

        Opds::make(OpdsApp::options())
            ->title("Series with {$character}")
            ->feeds($feeds)
            ->send();
    }

    #[Get('/{character}/{serie}', name: 'opds.series.show')]
    public function show(string $character, string $serie_slug)
    {
        $serie = Serie::whereSlug($serie_slug)->firstOrFail();
        $feeds = [];

        foreach ($serie->books as $book) {
            $feeds[] = OpdsApp::bookToEntry($book);
        }

        Opds::make(OpdsApp::options())
            ->title("Serie {$serie->title}")
            ->feeds($feeds)
            ->send();
    }
}