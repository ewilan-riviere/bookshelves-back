<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * App\Models\Language.
 *
 * @property string|null                                                 $slug
 * @property string|null                                                 $flag
 * @property string|null                                                 $display
 * @property \Illuminate\Database\Eloquent\Collection|\App\Models\Book[] $books
 * @property int|null                                                    $books_count
 * @method static \Illuminate\Database\Eloquent\Builder|Language newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Language newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Language query()
 * @method static \Illuminate\Database\Eloquent\Builder|Language whereDisplay($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Language whereFlag($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Language whereSlug($value)
 * @mixin \Eloquent
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Serie[] $series
 * @property-read int|null $series_count
 */
class Language extends Model
{
    use HasFactory;

    protected $primaryKey = 'slug';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
    protected $fillable = [
        'slug',
        'flag',
        'display',
    ];

    public function books(): HasMany
    {
        return $this->hasMany(Book::class);
    }

    public function series(): HasMany
    {
        return $this->hasMany(Serie::class);
    }
}
