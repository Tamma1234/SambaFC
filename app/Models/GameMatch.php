<?php

namespace App\Models;

use App\Enums\AttendanceStatus;
use App\Enums\MatchStatus;
use App\Enums\MemberStatus;
use Database\Factories\GameMatchFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string|null $title
 * @property string $opponent
 * @property string|null $venue
 * @property bool $is_home
 * @property Carbon $kickoff_at
 * @property int|null $score_for
 * @property int|null $score_against
 * @property MatchStatus $status
 * @property int|null $mvp_member_id
 * @property string|null $cover_image_path
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Member|null $mvp
 * @property-read Collection<int, MatchAttendance> $attendances
 * @property-read Collection<int, MatchLineup> $lineups
 * @property-read Collection<int, MatchGoal> $goals
 * @property-read Collection<int, Article> $articles
 */
#[Fillable([
    'title',
    'opponent',
    'venue',
    'is_home',
    'kickoff_at',
    'score_for',
    'score_against',
    'status',
    'mvp_member_id',
    'cover_image_path',
    'notes',
])]
class GameMatch extends Model
{
    /** @use HasFactory<GameMatchFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var string
     */
    protected $table = 'matches';

    /**
     * @return BelongsTo<Member, $this>
     */
    public function mvp(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'mvp_member_id');
    }

    /**
     * @return HasMany<MatchAttendance, $this>
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(MatchAttendance::class, 'match_id');
    }

    /**
     * @return HasMany<MatchLineup, $this>
     */
    public function lineups(): HasMany
    {
        return $this->hasMany(MatchLineup::class, 'match_id');
    }

    /**
     * @return HasMany<MatchGoal, $this>
     */
    public function goals(): HasMany
    {
        return $this->hasMany(MatchGoal::class, 'match_id')
            ->orderBy('sort_order')
            ->orderBy('minute');
    }

    /**
     * @return HasMany<Article, $this>
     */
    public function articles(): HasMany
    {
        return $this->hasMany(Article::class, 'match_id');
    }

    public function displayTitle(): string
    {
        if ($this->title) {
            return $this->title;
        }

        return $this->is_home
            ? 'Samba FC vs '.$this->opponent
            : $this->opponent.' vs Samba FC';
    }

    public function syncRosterAttendances(): void
    {
        $memberIds = Member::query()
            ->where('status', MemberStatus::Active)
            ->pluck('id');

        foreach ($memberIds as $memberId) {
            MatchAttendance::query()->firstOrCreate(
                [
                    'match_id' => $this->id,
                    'member_id' => $memberId,
                ],
                ['status' => AttendanceStatus::Pending],
            );
        }
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeWithAttendanceCounts(Builder $query): Builder
    {
        return $query->withCount([
            'attendances as attending_count' => fn (Builder $attendanceQuery) => $attendanceQuery->where('status', AttendanceStatus::Attending),
            'attendances as absent_count' => fn (Builder $attendanceQuery) => $attendanceQuery->where('status', AttendanceStatus::Absent),
            'attendances as pending_count' => fn (Builder $attendanceQuery) => $attendanceQuery->where('status', AttendanceStatus::Pending),
        ]);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_home' => 'boolean',
            'kickoff_at' => 'datetime',
            'score_for' => 'integer',
            'score_against' => 'integer',
            'status' => MatchStatus::class,
        ];
    }
}
