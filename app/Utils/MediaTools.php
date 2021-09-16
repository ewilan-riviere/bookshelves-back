<?php

namespace App\Utils;

use App\Providers\ImageProvider;
use Illuminate\Database\Eloquent\Model;

class MediaTools
{
    public function __construct(
        public Model $model,
        public string $name,
        public string $disk,
        public ?string $collection = null,
        public ?string $extension = null,
        public ?string $method = 'addMediaFromString',
    ) {
    }

    public function setMedia(string $data)
    {
        if (! $this->extension) {
            $extension = config('bookshelves.cover_extension');
        }
        if (! $this->collection) {
            $collection = $this->disk;
        }
        $method = $this->method;
        $this->model->$method($data)
            ->setName($this->name)
            ->setFileName($this->name . '.' . $extension)
            ->toMediaCollection($collection, $this->disk);
        $this->model->refresh();
    }

    public function setColor()
    {
        if (! $this->collection) {
            $collection = $this->disk;
        }
        $image = $this->model->getFirstMediaPath($collection);
        
        $color = ImageProvider::simple_color_thief($image);
        $media = $this->model->getFirstMedia($collection);
        $media->setCustomProperty('color', $color);
        $media->save();
    }
}
