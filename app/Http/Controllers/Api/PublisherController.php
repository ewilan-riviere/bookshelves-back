<?php

namespace App\Http\Controllers\Api;

use App\Models\Publisher;
use App\Http\Controllers\Controller;
use App\Http\Resources\Publisher\PublisherResource;
use App\Http\Resources\Publisher\PublisherLightResource;

class PublisherController extends Controller
{
    public function index()
    {
        $pubs = Publisher::orderBy('name')->get();

        return PublisherLightResource::collection($pubs);
    }

    public function show(string $publisher_slug)
    {
        $pub = Publisher::whereSlug($publisher_slug)->first();

        return PublisherResource::make($pub);
    }
}