<?php

namespace App\Models;

use App\Enums\AttendanceStatus;
use App\Enums\FundStatus;
use App\Enums\MatchStatus;
use App\Enums\MemberRole;
use App\Enums\MemberStatus;
use Database\Factories\MemberFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string $name
 * @property string|null $nickname
 * @property int|null $jersey_number
 * @property string|null $preferred_position
 * @property string|null $photo_path
 * @property string|null $phone
 * @property Carbon|null $date_of_birth
 * @property Carbon|null $joined_at
 * @property MemberStatus $status
 * @property MemberRole $role
 * @property string|null $bio
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read User|null $user
 * @property-read Collection<int, MatchAttendance> $attendances
 * @property-read Collection<int, MatchLineup> $lineups
 * @property-read Collection<int, MatchGoal> $goals
 * @property-read Collection<int, GameMatch> $mvpMatches
 * @property-read Collection<int, FundContribution> $fundContributions
 * @property-read Collection<int, KitPurchase> $kitPurchases
 * @property-read int $matches_played
 * @property-read int $mvp_count
 * @property-read int $goals_count
 */
#[Fillable([
    'user_id',
    'name',
    'nickname',
    'jersey_number',
    'preferred_position',
    'photo_path',
    'phone',
    'date_of_birth',
    'joined_at',
    'status',
    'role',
    'bio',
])]
class Member extends Model
{
    /** @use HasFactory<MemberFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<MatchAttendance, $this>
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(MatchAttendance::class);
    }

    /**
     * @return HasMany<MatchLineup, $this>
     */
    public function lineups(): HasMany
    {
        return $this->hasMany(MatchLineup::class);
    }

    /**
     * @return HasMany<MatchGoal, $this>
     */
    public function goals(): HasMany
    {
        return $this->hasMany(MatchGoal::class);
    }

    /**
     * @return HasMany<GameMatch, $this>
     */
    public function mvpMatches(): HasMany
    {
        return $this->hasMany(GameMatch::class, 'mvp_member_id');
    }

    /**
     * @return HasMany<FundContribution, $this>
     */
    public function fundContributions(): HasMany
    {
        return $this->hasMany(FundContribution::class);
    }

    /**
     * @return HasMany<KitPurchase, $this>
     */
    public function kitPurchases(): HasMany
    {
        return $this->hasMany(KitPurchase::class);
    }

    public function displayName(): string
    {
        return $this->nickname ?: $this->name;
    }

    public function photoUrl(): ?string
    {
        if (! $this->photo_path) {
            return $this->user?->avatar;
        }

        if (str_starts_with($this->photo_path, 'http://') || str_starts_with($this->photo_path, 'https://')) {
            return $this->photo_path;
        }

        return asset($this->photo_path);
    }

    public function attachToUpcomingMatches(): void
    {
        if ($this->status !== MemberStatus::Active) {
            return;
        }

        GameMatch::query()
            ->where('status', MatchStatus::Scheduled)
            ->each(function (GameMatch $match): void {
                MatchAttendance::query()->firstOrCreate(
                    [
                        'match_id' => $match->id,
                        'member_id' => $this->id,
                    ],
                    ['status' => AttendanceStatus::Pending],
                );
            });
    }

    public function attachToOpenFunds(): void
    {
        if ($this->status !== MemberStatus::Active) {
            return;
        }

        $groups = FundContribution::query()
            ->whereNotNull('group_key')
            ->orderBy('id')
            ->get()
            ->unique('group_key');

        foreach ($groups as $group) {
            $alreadyListed = FundContribution::withTrashed()
                ->where('group_key', $group->group_key)
                ->where('member_id', $this->id)
                ->exists();

            if ($alreadyListed) {
                continue;
            }

            FundContribution::query()->create([
                'group_key' => $group->group_key,
                'member_id' => $this->id,
                'title' => $group->title,
                'amount' => $group->amount,
                'note' => null,
                'status' => FundStatus::Unpaid,
                'paid_at' => null,
            ]);
        }
    }

    public static function syncActiveMembersIntoFunds(): void
    {
        static::query()
            ->where('status', MemberStatus::Active)
            ->each(fn (Member $member) => $member->attachToOpenFunds());
    }

    public function attachToKitPurchases(): void
    {
        if ($this->status !== MemberStatus::Active) {
            return;
        }

        $alreadyListed = KitPurchase::withTrashed()
            ->where('member_id', $this->id)
            ->exists();

        if ($alreadyListed) {
            return;
        }

        KitPurchase::query()->create([
            'member_id' => $this->id,
            'amount' => KitPurchase::AMOUNT,
            'status' => FundStatus::Unpaid,
            'paid_at' => null,
        ]);
    }

    public static function syncActiveMembersIntoKits(): void
    {
        static::query()
            ->where('status', MemberStatus::Active)
            ->each(fn (Member $member) => $member->attachToKitPurchases());
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'joined_at' => 'date',
            'status' => MemberStatus::class,
            'role' => MemberRole::class,
            'jersey_number' => 'integer',
        ];
    }
}
