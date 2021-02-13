<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SearchSerieCollection extends JsonResource
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
                'entity' => 'serie',
                'slug'   => $this->slug,
            ],
            'title'    => $this->title,
            'author' => $this->books[0]->author->name,
            'image'    => $this->getMedia('series')->first()?->getUrl(),
        ];
    }
}
