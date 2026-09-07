<?php

namespace App\Models;

use App\Enums\PitchSlot;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $match_id
 * @property int $member_id
 * @property PitchSlot $slot
 * @property bool $is_starter
 * @property string|null $rating
 * @property int|null $stars
 * @property int $sort_order
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read GameMatch $match
 * @property-read Member $member
 */
#[Fillable([
    'match_id',
    'member_id',
    'slot',
    'is_starter',
    'rating',
    'stars',
    'sort_order',
    'notes',
])]
class MatchLineup extends Model
{
    /**
     * @return BelongsTo<GameMatch, $this>
     */
    public function match(): BelongsTo
    {
        return $this->belongsTo(GameMatch::class, 'match_id');
    }

    /**
     * @return BelongsTo<Member, $this>
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'slot' => PitchSlot::class,
            'is_starter' => 'boolean',
            'rating' => 'decimal:1',
            'stars' => 'integer',
            'sort_order' => 'integer',
        ];
    }
}
