<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Translatable\HasTranslations;

/**
 * @property \Illuminate\Database\Eloquent\Collection<mixed, \App\Models\Cms\CmsHomePageStatistic> $statistics
 * @property \Illuminate\Database\Eloquent\Collection<mixed, \App\Models\Cms\CmsHomePageLogo>      $logos
 * @property \Illuminate\Database\Eloquent\Collection<mixed, \App\Models\Cms\CmsHomePageFeature>   $features
 * @property \Illuminate\Database\Eloquent\Collection<mixed, \App\Models\Cms\CmsHomePageHighlight> $highlights
 */
class CmsHomePage extends Model implements HasMedia
{
    use InteractsWithMedia;
    use HasTranslations;

    public $translatable = [
        'hero_title',
        'hero_text',
        'statistics_eyebrow',
        'statistics_title',
        'statistics_text',
        'logos_title',
        'features_title',
        'features_text',
    ];
    protected $fillable = [
        'hero_title',
        'hero_text',
        'statistics_eyebrow',
        'statistics_title',
        'statistics_text',
        'logos_title',
        'features_title',
        'features_text',
        'display_statistics',
        'display_logos',
        'display_features',
        'display_latest',
        'display_selection',
        'display_highlights',
    ];

    protected $casts = [
        'display_statistics' => 'boolean',
        'display_logos' => 'boolean',
        'display_features' => 'boolean',
        'display_latest' => 'boolean',
        'display_selection' => 'boolean',
        'display_highlights' => 'boolean',
    ];

    protected $with = [
        'statistics',
        'logos',
        'features',
    ];

    public function getHeroPictureAttribute(): string|null
    {
        return $this->getFirstMediaUrl('cms_hero');
    }

    public function statistics()
    {
        return $this->hasMany(CmsHomePageStatistic::class, 'cms_home_page_id');
    }

    public function logos()
    {
        return $this->hasMany(CmsHomePageLogo::class, 'cms_home_page_id');
    }

    public function features()
    {
        return $this->hasMany(CmsHomePageFeature::class, 'cms_home_page_id');
    }

    public function highlights()
    {
        return $this->hasMany(CmsHomePageHighlight::class, 'cms_home_page_id');
    }
}
