<?php

namespace App\Console\Commands;

use App\Models\Author;
use Illuminate\Console\Command;
use App\Providers\Bookshelves\ExtraDataGenerator;

class BooksAuthorsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'books:authors';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $authors = Author::orderBy('lastname')->get();
        $authors->each(function ($query) {
            $query->clearMediaCollection('authors');
        });
        foreach ($authors as $key => $author) {
            $author->description = null;
            $author->wikipedia_link = null;
            $author->save();
        }

        // $authors = Author::limit(10)->get();
        $this->alert('Bookshelves: regenerate authors extra data');
        $this->info('- Erase description, wikipedia link and picture');
        $this->info('- Regenerate description, wikipedia link and picture: HTTP requests');
        $this->newLine();

        $bar = $this->output->createProgressBar(count($authors));
        $bar->start();
        foreach ($authors as $key => $author) {
            ExtraDataGenerator::generateAuthorData($author);
            $bar->advance();
        }
        $bar->finish();
        $this->newLine(2);
        $this->info('Extradata regenerated!');

        return 0;
    }
}