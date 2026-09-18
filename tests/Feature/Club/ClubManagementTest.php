<?php

namespace Tests\Feature\Club;

use App\Enums\AttendanceStatus;
use App\Enums\MatchStatus;
use App\Models\FundContribution;
use App\Models\FundExpense;
use App\Models\GameMatch;
use App\Models\KitPurchase;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ClubManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_dashboard_shows_club_management_stats(): void
    {
        $user = User::factory()->create();
        Member::factory()->count(2)->create();
        GameMatch::factory()->create([
            'opponent' => 'FC Test',
            'kickoff_at' => now()->addDays(2),
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('dashboard')
                ->where('stats.members_total', 2)
                ->where('stats.upcoming_matches', 1)
                ->has('upcomingMatches', 1)
                ->where('upcomingMatches.0.opponent', 'FC Test'));
    }

    public function test_members_can_be_listed_created_updated_and_deleted(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('club.members.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('club/members/index')
                ->has('members', 0));

        $this->actingAs($user)
            ->post(route('club.members.store'), [
                'name' => 'Nguyen Van A',
                'nickname' => 'A',
                'jersey_number' => 10,
                'preferred_position' => 'st',
                'status' => 'active',
                'role' => 'player',
            ])
            ->assertRedirect();

        $member = Member::query()->firstOrFail();

        $this->assertSame('Nguyen Van A', $member->name);

        $photo = UploadedFile::fake()->image('player.jpg', 120, 120);

        $this->actingAs($user)
            ->post(route('club.members.store'), [
                'name' => 'Cau Thu Anh',
                'status' => 'active',
                'role' => 'player',
                'photo' => $photo,
            ])
            ->assertRedirect();

        $memberWithPhoto = Member::query()->where('name', 'Cau Thu Anh')->firstOrFail();

        $this->assertNotNull($memberWithPhoto->photo_path);
        $this->assertStringStartsWith('images/member/', $memberWithPhoto->photo_path);
        $this->assertFileExists(public_path($memberWithPhoto->photo_path));

        @unlink(public_path($memberWithPhoto->photo_path));

        $this->actingAs($user)
            ->put(route('club.members.update', ['member' => $member]), [
                'name' => 'Nguyen Van B',
                'nickname' => 'B',
                'jersey_number' => 9,
                'preferred_position' => 'cb',
                'status' => 'inactive',
                'role' => 'manager',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('members', [
            'id' => $member->id,
            'name' => 'Nguyen Van B',
            'status' => 'inactive',
        ]);

        $this->actingAs($user)
            ->delete(route('club.members.destroy', ['member' => $member]))
            ->assertRedirect();

        $this->assertSoftDeleted($member);
    }

    public function test_matches_can_be_managed_and_seed_attendance(): void
    {
        $user = User::factory()->create();
        Member::factory()->create(['name' => 'Player One']);
        Member::factory()->inactive()->create(['name' => 'Inactive Player']);

        $this->actingAs($user)
            ->post(route('club.matches.store'), [
                'opponent' => 'Rival FC',
                'venue' => 'Sân bóng Samba',
                'is_home' => '1',
                'kickoff_at' => now()->addDays(4)->format('Y-m-d\TH:i'),
                'status' => MatchStatus::Scheduled->value,
            ])
            ->assertRedirect();

        $match = GameMatch::query()->firstOrFail();

        $this->assertSame('Rival FC', $match->opponent);
        $this->assertDatabaseCount('match_attendances', 1);
        $this->assertDatabaseHas('match_attendances', [
            'match_id' => $match->id,
            'status' => AttendanceStatus::Pending->value,
        ]);

        $this->actingAs($user)
            ->put(route('club.matches.update', ['gameMatch' => $match]), [
                'opponent' => 'Rival FC',
                'venue' => 'Sân khách',
                'is_home' => '0',
                'kickoff_at' => now()->subDay()->format('Y-m-d\TH:i'),
                'status' => MatchStatus::Completed->value,
                'score_for' => 5,
                'score_against' => 2,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('matches', [
            'id' => $match->id,
            'status' => MatchStatus::Completed->value,
            'score_for' => 5,
            'is_home' => 0,
        ]);

        $this->actingAs($user)
            ->delete(route('club.matches.destroy', ['gameMatch' => $match]))
            ->assertRedirect();

        $this->assertSoftDeleted($match);
    }

    public function test_match_stats_can_be_viewed_and_saved(): void
    {
        $user = User::factory()->create();
        $striker = Member::factory()->create(['name' => 'Tien Dao']);
        $keeper = Member::factory()->create(['name' => 'Thu Mon']);
        $match = GameMatch::factory()->completed()->create([
            'opponent' => 'Rival FC',
            'score_for' => 2,
            'score_against' => 1,
        ]);

        $this->actingAs($user)
            ->get(route('club.matches.show', ['gameMatch' => $match]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('club/matches/show')
                ->where('match.opponent', 'Rival FC')
                ->where('match.score_for', 2)
                ->has('slots', 22)
                ->has('lineups', 0)
                ->has('goals', 0));

        $this->actingAs($user)
            ->put(route('club.matches.stats.update', ['gameMatch' => $match]), [
                'lineups' => [
                    ['slot' => 'st', 'member_id' => $striker->id, 'rating' => 8.5],
                    ['slot' => 'gk', 'member_id' => $keeper->id, 'rating' => 7],
                    ['slot' => 'cb', 'member_id' => '', 'rating' => ''],
                ],
                'goals' => [
                    ['member_id' => $striker->id, 'minute' => 12],
                    ['member_id' => $striker->id, 'minute' => 70],
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('match_lineups', [
            'match_id' => $match->id,
            'member_id' => $striker->id,
            'slot' => 'st',
        ]);
        $this->assertDatabaseHas('match_lineups', [
            'match_id' => $match->id,
            'member_id' => $keeper->id,
            'slot' => 'gk',
        ]);
        $this->assertDatabaseCount('match_lineups', 2);
        $this->assertDatabaseCount('match_goals', 2);
        $this->assertDatabaseHas('match_goals', [
            'match_id' => $match->id,
            'member_id' => $striker->id,
            'minute' => 12,
        ]);

        $this->actingAs($user)
            ->get(route('club.matches.show', ['gameMatch' => $match]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('club/matches/show')
                ->has('lineups', 2)
                ->has('goals', 2)
                ->where('lineups.0.rating', 8.5)
                ->where('goals.0.member_id', $striker->id));
    }

    public function test_attendance_can_be_updated_for_a_match(): void
    {
        $user = User::factory()->create();
        $member = Member::factory()->create();
        $match = GameMatch::factory()->create();
        $match->syncRosterAttendances();

        $this->actingAs($user)
            ->get(route('club.attendances.edit', ['gameMatch' => $match]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('club/attendances/edit')
                ->has('attendances', 1)
                ->where('attendances.0.member_id', $member->id));

        $this->actingAs($user)
            ->put(route('club.attendances.update', ['gameMatch' => $match]), [
                'attendances' => [
                    [
                        'member_id' => $member->id,
                        'status' => AttendanceStatus::Attending->value,
                    ],
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('match_attendances', [
            'match_id' => $match->id,
            'member_id' => $member->id,
            'status' => AttendanceStatus::Attending->value,
        ]);
    }

    public function test_fund_contributions_can_be_managed(): void
    {
        $user = User::factory()->create();
        $first = Member::factory()->create(['name' => 'Player One']);
        $second = Member::factory()->create(['name' => 'Player Two']);
        Member::factory()->inactive()->create(['name' => 'Inactive']);

        $this->actingAs($user)
            ->get(route('club.funds.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('club/funds/index')
                ->has('groups', 0)
                ->has('amounts', 3)
                ->where('summary.paid_total', 0)
                ->where('summary.unpaid_total', 0));

        $this->actingAs($user)
            ->post(route('club.funds.store'), [
                'title' => 'Quỹ tháng 9',
                'amount' => 300000,
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('fund_contributions', 2);
        $this->assertDatabaseHas('fund_contributions', [
            'member_id' => $first->id,
            'title' => 'Quỹ tháng 9',
            'amount' => 300000,
            'status' => 'unpaid',
        ]);
        $this->assertDatabaseHas('fund_contributions', [
            'member_id' => $second->id,
            'title' => 'Quỹ tháng 9',
            'status' => 'unpaid',
        ]);
        $this->assertDatabaseMissing('fund_contributions', [
            'member_id' => Member::query()->where('name', 'Inactive')->value('id'),
        ]);

        $contribution = FundContribution::query()->where('member_id', $first->id)->firstOrFail();

        $this->actingAs($user)
            ->put(route('club.funds.update', ['fundContribution' => $contribution]), [
                'status' => 'paid',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('fund_contributions', [
            'id' => $contribution->id,
            'status' => 'paid',
        ]);

        $this->actingAs($user)
            ->put(route('club.funds.update', ['fundContribution' => $contribution]), [
                'amount' => 500000,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('fund_contributions', [
            'id' => $contribution->id,
            'amount' => 500000,
            'status' => 'paid',
        ]);

        $this->actingAs($user)
            ->delete(route('club.funds.destroy', ['fundContribution' => $contribution]))
            ->assertRedirect();

        $this->assertSoftDeleted($contribution);
    }

    public function test_new_active_members_are_added_to_existing_funds(): void
    {
        $user = User::factory()->create();
        Member::factory()->create(['name' => 'Player One']);
        Member::factory()->create(['name' => 'Player Two']);

        $this->actingAs($user)
            ->post(route('club.funds.store'), [
                'title' => 'Quỹ tháng 9',
                'amount' => 300000,
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('fund_contributions', 2);

        $this->actingAs($user)
            ->post(route('club.members.store'), [
                'name' => 'Player Three',
                'status' => 'active',
                'role' => 'player',
            ])
            ->assertRedirect();

        $third = Member::query()->where('name', 'Player Three')->firstOrFail();

        $this->assertDatabaseCount('fund_contributions', 3);
        $this->assertDatabaseHas('fund_contributions', [
            'member_id' => $third->id,
            'title' => 'Quỹ tháng 9',
            'amount' => 300000,
            'status' => 'unpaid',
        ]);

        $this->actingAs($user)
            ->get(route('club.funds.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('club/funds/index')
                ->has('groups', 1)
                ->has('groups.0.contributions', 3));

        Member::factory()->create(['name' => 'Player Four']);

        $this->actingAs($user)
            ->get(route('club.funds.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('groups.0.contributions', 4));

        $this->assertDatabaseHas('fund_contributions', [
            'member_id' => Member::query()->where('name', 'Player Four')->value('id'),
            'title' => 'Quỹ tháng 9',
            'status' => 'unpaid',
        ]);
    }

    public function test_kit_purchases_are_listed_and_can_be_updated(): void
    {
        $user = User::factory()->create();
        $first = Member::factory()->create(['name' => 'Player One']);
        Member::factory()->create(['name' => 'Player Two']);
        Member::factory()->inactive()->create(['name' => 'Inactive']);

        $this->actingAs($user)
            ->get(route('club.kits.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('club/kits/index')
                ->has('purchases', 2)
                ->where('amount', 200000)
                ->where('title', 'Quần áo bóng đá')
                ->where('summary.unpaid_count', 2)
                ->where('summary.paid_count', 0));

        $this->assertDatabaseMissing('kit_purchases', [
            'member_id' => Member::query()->where('name', 'Inactive')->value('id'),
        ]);

        $purchase = KitPurchase::query()->where('member_id', $first->id)->firstOrFail();

        $this->actingAs($user)
            ->put(route('club.kits.update', ['kitPurchase' => $purchase]), [
                'status' => 'paid',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('kit_purchases', [
            'id' => $purchase->id,
            'status' => 'paid',
            'amount' => 200000,
        ]);

        $this->actingAs($user)
            ->post(route('club.members.store'), [
                'name' => 'Player Three',
                'status' => 'active',
                'role' => 'player',
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('kit_purchases', 3);
        $this->assertDatabaseHas('kit_purchases', [
            'member_id' => Member::query()->where('name', 'Player Three')->value('id'),
            'amount' => 200000,
            'status' => 'unpaid',
        ]);
    }

    public function test_fund_expenses_can_be_managed(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('club.expenses.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('club/expenses/index')
                ->has('groups', 0)
                ->where('summary.expense_total', 0)
                ->where('summary.balance', 0));

        $this->actingAs($user)
            ->post(route('club.expenses.store'), [
                'title' => 'Tiền sân tháng 9',
                'category' => 'pitch',
                'amount' => 1500000,
                'spent_at' => now()->toDateString(),
            ])
            ->assertRedirect();

        $expense = FundExpense::query()->firstOrFail();

        $this->assertSame('Tiền sân tháng 9', $expense->title);
        $this->assertSame(1500000, $expense->amount);

        $this->actingAs($user)
            ->put(route('club.expenses.update', ['fundExpense' => $expense]), [
                'title' => 'Mua bóng',
                'category' => 'ball',
                'amount' => 400000,
                'spent_at' => now()->toDateString(),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('fund_expenses', [
            'id' => $expense->id,
            'title' => 'Mua bóng',
            'category' => 'ball',
            'amount' => 400000,
        ]);

        $this->actingAs($user)
            ->get(route('club.expenses.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('groups', 1)
                ->has('groups.0.expenses', 1)
                ->where('summary.expense_total', 400000)
                ->where('summary.expense_this_month', 400000));

        $this->actingAs($user)
            ->delete(route('club.expenses.destroy', ['fundExpense' => $expense]))
            ->assertRedirect();

        $this->assertSoftDeleted($expense);
    }
}
