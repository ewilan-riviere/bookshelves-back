<?php

namespace App\Providers\MetadataExtractor;

use File;
use Storage;
use Generator;
use ZipArchive;
use Illuminate\Support\Str;
use App\Utils\BookshelvesTools;
use App\Providers\MetadataExtractor\Parsers\CreatorParser;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

class MetadataExtractorTools
{
    /**
     * Parse OPF file as PHP XML file.
     */
    public static function parseXMLFile(string $filepath, bool $debug = false): array
    {
        // $filepath = storage_path("app/public/$filepath");
        $metadata = [];

        $zip = new ZipArchive();
        $zip->open($filepath);
        $xml_string = '';

        // extract .opf file
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $file = $zip->statIndex($i);
            if (strpos($file['name'], '.opf')) {
                $xml_string = $zip->getFromName($file['name']);
            }
        }

        // Transform XML to Array
        try {
            $metadata = self::convertXML(xml: $xml_string, filepath: $filepath, debug: $debug);
        } catch (\Throwable $th) {
            //throw $th;
            MetadataExtractorTools::error('convert XML file', basename($filepath));
        }

        if ($debug) {
            Storage::disk('public')->put('/debug/' . pathinfo($filepath)['basename'] . '.opf', $xml_string);
        }

        $cover = null;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $file = $zip->statIndex($i);
            try {
                if (array_key_exists('cover_file', $metadata) && $metadata['cover_file']) {
                    $cover = $zip->getFromName($metadata['cover_file']);
                }
            } catch (\Throwable $th) {
                throw $th;
                echo "No cover for " . basename($filepath) . "\n";
            }
        }
        $metadata['cover_file'] = $cover;

        $zip->close();

        return $metadata;
    }

    /**
     * Get all EPUB files from storage/raw/books
     * Return false if raw/books not exist.
     *
     * @return false|array
     */
    public static function getAllEpubFiles(int $limit = null): array | false
    {
        try {
            // Get all files in raw/books/
            // $files = Storage::disk('public')->allFiles('raw/books');
            $epubsFiles = [];
            $path = 'public/storage/raw/books';
            foreach (self::getDirectoryFiles($path) as $file) {
                if (array_key_exists('extension', pathinfo($file)) && 'epub' === pathinfo($file)['extension']) {
                    $file = str_replace('public/storage/', '', $file);
                    array_push($epubsFiles, $file);
                }
            }
        } catch (\Throwable $th) {
            echo "storage/raw/books not found\n";
            echo $th;

            return false;
        }

        if ($limit) {
            return array_slice($epubsFiles, 0, $limit);
        }

        return $epubsFiles;
    }

    public static function error(string $type, string $filepath)
    {
        $book = pathinfo($filepath)['filename'];
        $output = new \Symfony\Component\Console\Output\ConsoleOutput();
        $outputStyle = new OutputFormatterStyle('red', '', ['bold']);
        $output->getFormatter()->setStyle('fire', $outputStyle);
        
        $output->writeln("<fire>Error about $type:</> $book");
    }

    public static function getSortString(string $title)
    {
        $title_sort = $title;
        $articles = [
            'the ',
            'les ',
            "l'",
            'le ',
            'la ',
            // 'a ',
            "d'un",
            "d'",
            'une ',
        ];
        foreach ($articles as $key => $value) {
            $title_sort = preg_replace('/^' . preg_quote($value, '/') . '/i', '', $title_sort);
        }
        // $title_sort = str_replace($articles, '', $title_sort);
        $title_sort = BookshelvesTools::cleanString($title_sort);

        return utf8_encode($title_sort);
    }

    public static function cleanText(string $text, string $type = 'html', int $limit = null): string
    {
        $isUTF8 = mb_check_encoding($text, 'UTF-8');

        try {
            $text = iconv('UTF-8', 'UTF-8//IGNORE', $text);

            if ('html' === $type) {
                $text = filter_var($text, FILTER_SANITIZE_STRING);
            } elseif ('markdown' === $type) {
                $text = Str::markdown($text);
            }

            $text = preg_replace('#<a.*?>.*?</a>#i', '', $text);
            $text = preg_replace('#<img.*?>.*?/>#i', '', $text);
            $text = strip_tags($text, '<br>');
            $text = Str::markdown($text);

            if ($limit && strlen($text) > $limit) {
                $text = substr($text, 0, $limit);
            }
        } catch (\Throwable $th) {
            // TODO Log
            $text = '';
        }

        return $text;
    }

    /**
     * Parse directory (recursive)
     * @param mixed $dir
     * @return Generator<mixed, mixed, mixed, void>
     */
    public static function getDirectoryFiles($dir)
    {
        $files = scandir($dir);
        foreach ($files as $key => $value) {
            $path = realpath($dir . DIRECTORY_SEPARATOR . $value);
            if (! is_dir($path)) {
                yield $path;
            } elseif ($value != "." && $value != "..") {
                yield from self::getDirectoryFiles($path);
                yield $path;
            }
        }
    }
    

    

    /**
     * Transform OPF file as array.
     */
    public static function convertXML(string $xml, string $filepath, bool $debug = false): array
    {
        $xml = self::XMLtoArray($xml);
        $xml = $xml['PACKAGE'];
        $cover = null;
        $manifest = $xml['MANIFEST']['ITEM'];
        $i = 0;
        foreach ($manifest as $key => $value) {
            if ($value['MEDIA-TYPE'] === 'image/jpeg' || $value['MEDIA-TYPE'] === 'image/png') {
                $i++;
                if ('cover' === $value['ID']) {
                    $cover = $value;
                } elseif ($i === 1) {
                    $cover = $value;
                }
            }
        }
        unset($xml['MANIFEST'], $xml['SPINE']);

        $xml['COVER'] = $cover;
        $title = pathinfo($filepath)['basename'];

        if ($debug) {
            try {
                $xmlToJson = json_encode($xml, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
                Storage::disk('public')->put("/debug/$title.json", $xmlToJson);
            } catch (\Throwable $th) {
                dump($th);
            }
        }

        $metadata = [];

        try {
            $meta = $xml['METADATA'];
            // dump($meta);
            
            $creators = $meta['DC:CREATOR'] ?? null;
            $creators_arr = [];
            if (array_key_exists('content', $creators)) {
                if (count($creators) == count($creators, COUNT_RECURSIVE)) {
                    array_push($creators_arr, new CreatorParser(name: $creators['content'], role: $creators['OPF:ROLE']));
                } else {
                    foreach ($creators as $key => $value) {
                        array_push($creators_arr, new CreatorParser(name: $value['content'], role: $value['OPF:ROLE']));
                    }
                }
            }

            $contributors = $meta['DC:CONTRIBUTOR'] ?? null;
            $contributors_arr = [];
            foreach ($contributors as $key => $value) {
                // only one contributor
                if ('content' === $key) {
                    array_push($contributors_arr, $value);
                // More than one contributor
                } elseif (is_numeric($key)) {
                    array_push($contributors_arr, $value['content']);
                }
            }
            $contributors = implode(',', $contributors_arr);

            $identifiers = $meta['DC:IDENTIFIER'] ?? null;
            $identifiers_arr = [];
            foreach ($identifiers as $key => $value) {
                // More than one identifier
                if (is_numeric($key)) {
                    try {
                        array_push($identifiers_arr, [
                            'id'    => $value['OPF:SCHEME'],
                            'value' => $value['content'],
                        ]);
                    } catch (\Throwable $th) {
                        //throw $th;
                        // echo $th->getMessage();
                    }
                } else {
                    $identifiers_arr = [];
                }
            }
            // only one identifier
            if (! sizeof($identifiers_arr) && array_key_exists('content', $identifiers)) {
                $identifiers_arr[0]['id'] = $identifiers['OPF:SCHEME'];
                $identifiers_arr[0]['content'] = $identifiers['content'];
            }

            $subjects_arr = [];

            try {
                $subjects = (array) $meta['DC:SUBJECT'] ?? null;
                foreach ($subjects as $key => $value) {
                    array_push($subjects_arr, $value);
                }
            } catch (\Throwable $th) {
                //throw $th;
                // echo $th->getMessage();
            }

            $serie = null;
            $volume = null;
            $meta_serie = $meta['META'] ?? null;
            foreach ($meta_serie as $key => $value) {
                if ('calibre:series' === $value['NAME']) {
                    $serie = $value['CONTENT'];
                }
                if ('calibre:series_index' === $value['NAME']) {
                    $volume = $value['CONTENT'];
                }
            }

            if ($cover) {
                $cover_file = $cover['HREF'] ?? null;
                $cover_extension = pathinfo($cover['HREF'], PATHINFO_EXTENSION) ?? null;
            } else {
                echo 'NO COVER';
            }

            $date = null;
            if (array_key_exists('DC:DATE', $meta)) {
                $date = is_string($meta['DC:DATE']) ? $meta['DC:DATE'] : null;
            }

            $metadata = [
                'title'                 => $meta['DC:TITLE'] ?? null,
                'creators'              => $creators_arr,
                'contributor'           => $contributors,
                'description'           => $meta['DC:DESCRIPTION'] ?? null,
                'date'                  => $date,
                'identifiers'           => $identifiers_arr,
                'publisher'             => $meta['DC:PUBLISHER'] ?? null,
                'subjects'              => $subjects_arr,
                'language'              => $meta['DC:LANGUAGE'] ?? 'unknown',
                'rights'                => $meta['DC:RIGHTS'] ?? null,
                'serie'                 => $serie,
                'volume'                => $volume,
                'cover_file'            => $cover_file ?? null,
                'cover_extension'       => $cover_extension ?? null,
            ];
        } catch (\Throwable $th) {
            BookshelvesTools::console(__METHOD__, $th);
        }

        return $metadata;
    }

    /**
     * Convert XML to an Array.
     */
    public static function XMLtoArray(string $XML): array
    {
        $xml_parser = xml_parser_create();
        xml_parse_into_struct($xml_parser, $XML, $vals);
        xml_parser_free($xml_parser);
        // wyznaczamy tablice z powtarzajacymi sie tagami na tym samym poziomie
        $_tmp = '';
        foreach ($vals as $xml_elem) {
            $x_tag = $xml_elem['tag'];
            $x_level = $xml_elem['level'];
            $x_type = $xml_elem['type'];
            if (1 != $x_level && 'close' == $x_type) {
                if (isset($multi_key[$x_tag][$x_level])) {
                    $multi_key[$x_tag][$x_level] = 1;
                } else {
                    $multi_key[$x_tag][$x_level] = 0;
                }
            }
            if (1 != $x_level && 'complete' == $x_type) {
                if ($_tmp == $x_tag) {
                    $multi_key[$x_tag][$x_level] = 1;
                }
                $_tmp = $x_tag;
            }
        }
        // jedziemy po tablicy
        $xml_array = [];
        foreach ($vals as $xml_elem) {
            $x_tag = $xml_elem['tag'];
            $x_level = $xml_elem['level'];
            $x_type = $xml_elem['type'];
            if ('open' == $x_type) {
                $level[$x_level] = $x_tag;
            }
            $start_level = 1;
            $php_stmt = '$xml_array';
            if ('close' == $x_type && 1 != $x_level) {
                $multi_key[$x_tag][$x_level]++;
            }
            while ($start_level < $x_level) {
                $php_stmt .= '[$level[' . $start_level . ']]';
                if (isset($multi_key[$level[$start_level]][$start_level]) && $multi_key[$level[$start_level]][$start_level]) {
                    $php_stmt .= '[' . ($multi_key[$level[$start_level]][$start_level] - 1) . ']';
                }
                $start_level++;
            }
            $add = '';
            if (isset($multi_key[$x_tag][$x_level]) && $multi_key[$x_tag][$x_level] && ('open' == $x_type || 'complete' == $x_type)) {
                if (! isset($multi_key2[$x_tag][$x_level])) {
                    $multi_key2[$x_tag][$x_level] = 0;
                }
                $multi_key2[$x_tag][$x_level]++;

                $add = '[' . $multi_key2[$x_tag][$x_level] . ']';
            }
            if (isset($xml_elem['value']) && '' != trim($xml_elem['value']) && ! array_key_exists('attributes', $xml_elem)) {
                if ('open' == $x_type) {
                    $php_stmt_main = $php_stmt . '[$x_type]' . $add . '[\'content\'] = $xml_elem[\'value\'];';
                } else {
                    $php_stmt_main = $php_stmt . '[$x_tag]' . $add . ' = $xml_elem[\'value\'];';
                }
                eval($php_stmt_main);
            }
            if (array_key_exists('attributes', $xml_elem)) {
                if (isset($xml_elem['value'])) {
                    $php_stmt_main = $php_stmt . '[$x_tag]' . $add . '[\'content\'] = $xml_elem[\'value\'];';
                    eval($php_stmt_main);
                }
                foreach ($xml_elem['attributes'] as $key => $value) {
                    $php_stmt_att = $php_stmt . '[$x_tag]' . $add . '[$key] = $value;';
                    eval($php_stmt_att);
                }
            }
        }

        return $xml_array;
    }
}
