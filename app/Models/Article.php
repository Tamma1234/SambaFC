<?php

namespace App\Models;

use App\Enums\ArticleCategory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int|null $author_id
 * @property int|null $match_id
 * @property string $title
 * @property string $slug
 * @property ArticleCategory $category
 * @property string|null $excerpt
 * @property string $content
 * @property string|null $cover_image_path
 * @property bool $is_featured
 * @property Carbon|null $published_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read User|null $author
 * @property-read GameMatch|null $match
 */
#[Fillable([
    'author_id',
    'match_id',
    'title',
    'slug',
    'category',
    'excerpt',
    'content',
    'cover_image_path',
    'is_featured',
    'published_at',
])]
class Article extends Model
{
    use SoftDeletes;

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Article $article): void {
            if (empty($article->slug)) {
                $article->slug = static::uniqueSlug($article->title);
            }
        });
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * @return BelongsTo<GameMatch, $this>
     */
    public function match(): BelongsTo
    {
        return $this->belongsTo(GameMatch::class, 'match_id');
    }

    /**
     * @param  Builder<Article>  $query
     * @return Builder<Article>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    public static function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'bai-viet';
        $slug = $base;
        $i = 1;

        while (static::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => ArticleCategory::class,
            'is_featured' => 'boolean',
            'published_at' => 'datetime',
        ];
    }
}
