<?php

namespace App\Models;

use App\Enums\FundStatus;
use Database\Factories\FundContributionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string|null $group_key
 * @property int $member_id
 * @property string|null $title
 * @property int $amount
 * @property FundStatus $status
 * @property Carbon|null $paid_at
 * @property string|null $note
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Member $member
 */
#[Fillable(['group_key', 'member_id', 'title', 'amount', 'status', 'paid_at', 'note'])]
class FundContribution extends Model
{
    /** @use HasFactory<FundContributionFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var array<int, int>
     */
    public const AMOUNTS = [300000, 400000, 500000];

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
            'amount' => 'integer',
            'status' => FundStatus::class,
            'paid_at' => 'datetime',
        ];
    }
}
