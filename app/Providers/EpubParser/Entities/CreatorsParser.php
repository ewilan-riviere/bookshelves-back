<?php

namespace App\Providers\EpubParser\Entities;

use App\Models\Author;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Image\Image;
use Spatie\Image\Manipulations;
use Spatie\ImageOptimizer\OptimizerChainFactory;

/**
 * Manage Book Authors
 * 
 * @package App\Providers\EpubParser\Book
 */
class CreatorsParser
{
    public function __construct(
        public ?array $creators = [],
    ) {}

    /**
     * Generate author from XML dc:creator string.
     *
     * @param string $creator_raw_data
     *
     * @return CreatorsParser
     */
    public static function run(iterable|string $creators, bool $is_debug = false): CreatorsParser
    {
        $creators_entities = [];

        if ($creators) {
            if (!is_array($creators)) {
                $creator_string = $creators;
                $creators = [];
                $creators[] = $creator_string;
            }
            foreach ($creators as $creator) {
                array_push($creators_entities, $creator);
            }
        }
        $creators_entities = array_unique($creators_entities);
        
        return new CreatorsParser(
            creators: $creators_entities
        );
    }
}