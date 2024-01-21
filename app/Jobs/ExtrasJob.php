<?php

namespace App\Jobs;

use App\Console\Commands\Bookshelves\AuthorsCommand;
use App\Console\Commands\Bookshelves\GoogleBooksCommand;
use App\Console\Commands\Bookshelves\SeriesCommand;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;

class ExtrasJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Artisan::call(AuthorsCommand::class);
        Artisan::call(SeriesCommand::class);
        Artisan::call(GoogleBooksCommand::class);
    }
}