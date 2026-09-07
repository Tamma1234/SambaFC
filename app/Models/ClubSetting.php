<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string|null $logo_path
 * @property string|null $cover_image_path
 * @property string|null $description
 * @property string|null $founded_year
 * @property string|null $home_venue
 * @property string|null $facebook_url
 * @property string|null $contact_email
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'name',
    'logo_path',
    'cover_image_path',
    'description',
    'founded_year',
    'home_venue',
    'facebook_url',
    'contact_email',
])]
class ClubSetting extends Model
{
    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'name' => 'Samba FC',
            'logo_path' => 'images/logo.png',
            'description' => 'Câu lạc bộ bóng đá Samba FC',
        ]);
    }

    public function logoUrl(): string
    {
        return $this->publicUrl($this->logo_path) ?? asset('images/logo.png');
    }

    public function coverUrl(): ?string
    {
        return $this->publicUrl($this->cover_image_path);
    }

    private function publicUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return asset($path);
    }
}
