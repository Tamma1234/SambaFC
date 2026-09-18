<?php

namespace App\Models;

use App\Enums\FundStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $member_id
 * @property int $amount
 * @property FundStatus $status
 * @property Carbon|null $paid_at
 * @property string|null $note
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Member $member
 */
#[Fillable(['member_id', 'amount', 'status', 'paid_at', 'note'])]
class KitPurchase extends Model
{
    use SoftDeletes;

    public const AMOUNT = 200000;

    public const TITLE = 'Quần áo bóng đá';

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
