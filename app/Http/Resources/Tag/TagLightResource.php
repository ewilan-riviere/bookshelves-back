<?php

namespace App\Http\Resources\Tag;

use App\Models\Book;
use Spatie\Tags\Tag;
use App\Enums\CountSizeEnum;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Tag $resource
 */
class TagLightResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function toArray($request)
    {
        // $total = Book::count();
        $count = Book::withAllTags([$this->resource])->count();
        // $percentage = intval($count * 100 / $total);

        // switch ($percentage) {
        //     case $percentage <= 5:
        //         $size = CountSizeEnum::XS();
        //         break;

        //     case $percentage <= 10:
        //         $size = CountSizeEnum::SM();
        //         break;

        //     case $percentage <= 20:
        //         $size = CountSizeEnum::MD();
        //         break;

        //     case $percentage <= 30:
        //         $size = CountSizeEnum::LG();
        //         break;

        //     case $percentage <= 50:
        //         $size = CountSizeEnum::XL();
        //         break;

        //     default:
        //         $size = CountSizeEnum::XS();
        //         break;
        // }
        $type = $this->resource->type;

        return [
            'name'  => $this->resource->name,
            'slug'  => $this->resource->slug,
            'type'  => $this->resource->type,
            'count' => $count,
            // 'size' => $size,
            'meta' => [
                'show' => route('api.'.$type.'s.show', [
                    $type => $this->resource->slug,
                ]),
            ],
        ];
    }
}