<?php

namespace App\Http\Resources\Search;

use Illuminate\Http\Resources\Json\JsonResource;

class SearchSerieResource extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function toArray($request)
    {
        return [
            'meta' => [
                'entity'   => 'serie',
                'author'   => $this->author->slug,
                'slug'     => $this->slug,
            ],
            'title'      => $this->title,
            'author'     => $this->books[0]->author->name,
            'picture'    => $this->image_thumbnail,
        ];
    }
}