<?php

namespace App\Jobs;

use App\Engines\Book\BookFileItem;
use App\Engines\BookEngine;
use App\Facades\Bookshelves;
use Error;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Kiwilan\LaravelNotifier\Facades\Journal;

class BookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected BookFileItem $file,
        protected string $number,
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $engine = BookEngine::make($this->file);
        $title = $engine->ebook()->getTitle();
        if (! $title) {
            $title = $this->file->path();
        }

        Journal::debug("BookJob: {$this->number} {$title}");
    }

    private function log(string $message): void
    {
        $path = Bookshelves::exceptionParserLog();
        $json = json_decode(file_get_contents($path), true);
        $content = [
            'path' => $this->file->path(),
            'message' => $message,
            'status' => 'failed',
        ];
        $json[] = $content;
        file_put_contents($path, json_encode($json));

        Journal::error('BookJob', $content);
    }

    /**
     * Handle a job failure.
     *
     * @return void
     */
    public function failed(Exception|Error $exception)
    {
        $this->log($exception->getMessage());
    }
}
