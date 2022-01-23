<?php

namespace App\Http\Controllers\Front\Opds;

use App\Enums\EntitiesEnum;
use App\Http\Controllers\Controller;
use App\Services\MarkdownService;
use App\Services\OpdsService;
use Artesaos\SEOTools\Facades\SEOTools;
use File;
use Illuminate\Http\Request;
use Route;

/**
 * @hideFromAPIDocumentation
 */
class OpdsController extends Controller
{
    public function index(Request $request)
    {
        $service = MarkdownService::generate('opds/index.md');
        $content = $service->convertToHtml();

        $feeds = [
            [
                'title' => 'Version 1.2',
                'param' => 'v1.2',
            ],
        ];
        $latest_feed = $feeds[sizeof($feeds) - 1];
        $latest_feed = route('front.opds.feed', ['version' => $latest_feed['param']]);

        SEOTools::setTitle('OPDS');

        return view('front::pages.opds.index', compact('content', 'feeds', 'latest_feed'));
    }

    public function feed(Request $request, string $version)
    {
        $feed = File::get(app_path('Services/opds-feed.json'));
        $feed = (array) json_decode($feed);
        foreach ($feed as $key => $value) {
            $model_name = 'App\Models\\'.ucfirst($value->model);
            $value->cover_thumbnail = config('app.url')."/assets/images/opds/{$value->key}.png";
            $value->route = route($value->route, ['version' => $version]);
            $value->content = $model_name::count().' '.$value->content;
        }
        $feed = collect($feed);

        $current_route = route(Route::currentRouteName(), ['version' => $version]);
        $opdsService = new OpdsService(
            version: $version,
            entity: EntitiesEnum::feed(),
            route: $current_route,
            data: $feed,
        );
        $result = $opdsService->template();

        return response($result)->withHeaders([
            'Content-Type' => 'text/xml',
        ]);
    }
}