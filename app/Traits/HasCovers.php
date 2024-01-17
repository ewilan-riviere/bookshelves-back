<?php

namespace App\Traits;

use App\Enums\MediaDiskEnum;
use App\Models\MediaExtended;
use App\Services\EntityService;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Manage cover with conversions with `spatie/laravel-medialibrary`.
 *
 * @property string $cover_book
 * @property string $cover_thumbnail
 * @property string $cover_social
 * @property string $cover_standard
 * @property string $cover_original
 * @property string $cover_color
 * @property string $cover
 */
trait HasCovers
{
    use InteractsWithMedia;

    // protected bool $cover_available = false;

    public function registerMediaConversions(?\Spatie\MediaLibrary\MediaCollections\Models\Media $media = null): void
    {
        $formatThumbnail = config('bookshelves.image.cover.thumbnail');
        $formatStandard = config('bookshelves.image.cover.standard');
        $formatSocial = config('bookshelves.image.cover.social');

        if (config('bookshelves.image.conversion')) {
            $this->addMediaConversion('thumbnail')
                ->performOnCollections('covers')
                ->fit(Fit::Crop, $formatThumbnail['width'], $formatThumbnail['height'])
                ->sharpen(10)
                ->optimize()
                ->format(config('bookshelves.image.format'));

            $this->addMediaConversion('standard')
                ->performOnCollections('covers')
                ->fit(Fit::Crop, $formatStandard['width'], $formatStandard['height'])
                ->sharpen(10)
                ->optimize()
                ->format(config('bookshelves.image.format'));

            $this->addMediaConversion('social')
                ->performOnCollections('covers')
                ->fit(Fit::Crop, $formatSocial['width'], $formatSocial['height'])
                ->sharpen(10)
                ->optimize()
                ->format('jpg');
        }
    }

    // /**
    //  * Manage EPUB files with spatie/laravel-medialibrary.
    //  *
    //  * @return null|Media|MediaExtended
    //  */
    // public function getCoverBookAttribute()
    // {
    //     return $this->getMedia(MediaDiskEnum::cover->value)->first() ?? null;
    // }

    // public function getCoverFilamentAttribute(): ?string
    // {
    //     return $this->getCover('thumbnail');
    // }

    // /**
    //  * Get cover thumbnail with `spatie/laravel-medialibrary`
    //  * With config/bookshelves define format.
    //  */
    // public function getCoverThumbnailAttribute(): ?string
    // {
    //     return $this->getCover('thumbnail');
    // }

    // /**
    //  * Get cover OpenGraph with `spatie/laravel-medialibrary`
    //  * With JPG format for social.
    //  */
    // public function getCoverOgAttribute(): ?string
    // {
    //     return $this->getCover('og');
    // }

    // /**
    //  * Get cover standard with `spatie/laravel-medialibrary`
    //  * With JPG format for Catalog.
    //  */
    // public function getCoverStandardAttribute(): ?string
    // {
    //     return $this->getCover('standard');
    // }

    // /**
    //  * Get cover original with `spatie/laravel-medialibrary`
    //  * With config/bookshelves define format.
    //  */
    // public function getCoverOriginalAttribute(): ?string
    // {
    //     return $this->getCover();
    // }

    // /**
    //  * Get cover main color with `spatie/laravel-medialibrary`
    //  * Use for placeholder during cover loading.
    //  */
    // public function getCoverColorAttribute(): ?string
    // {
    //     /** @var Media $media */
    //     $media = $this->getFirstMedia(MediaDiskEnum::cover->value);

    //     // @phpstan-ignore-next-line
    //     if ($color = $media?->getCustomProperty('color')) {
    //         return "#{$color}";
    //     }

    //     return '#ffffff';
    // }

    // public function getCoverMediaAttribute(): array
    // {
    //     return [
    //         'url' => $this->cover_thumbnail,
    //         'color' => $this->cover_color,
    //         'available' => $this->cover_available,
    //     ];
    // }

    // public function getCoverAttribute(): array
    // {
    //     return [
    //         'thumbnail' => $this->cover_thumbnail,
    //         'color' => $this->cover_color,
    //     ];
    // }

    // public function getCoverFullAttribute(): array
    // {
    //     return [
    //         'cover' => $this->cover,
    //         'social' => $this->cover_social,
    //         'standard' => $this->cover_standard,
    //         'original' => $this->cover_original,
    //     ];
    // }

    // private function getCover(string $collection = '', string $extension = ''): string
    // {
    //     if (! $extension) {
    //         $extension = config('bookshelves.image.format');
    //     }

    //     $that = EntityService::entityOutput($this);
    //     // fix crash if conversion not exist in spatie/laravel-medialibrary
    //     $cover = null;

    //     $medias = $this->getMedia(MediaDiskEnum::cover->value);
    //     $coverMedia = $medias->first();

    //     if ($coverMedia instanceof MediaExtended || $coverMedia instanceof Media) {
    //         $this->cover_available = true;
    //         // $cover = $coverMedia->getFirstMediaUrl(MediaDiskEnum::cover->value, $collection);
    //         $cover = $coverMedia->getUrl();
    //     }

    //     if (! $this->cover_available) {
    //         $baseURL = config('app.url');
    //         $image = $that->meta_class_snake_plural === 'authors' ? 'no-author' : 'no-cover';

    //         return "{$baseURL}/vendor/images/{$image}.webp";
    //     }

    //     return $cover;
    // }
}
