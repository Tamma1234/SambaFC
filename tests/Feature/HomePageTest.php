<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Enums\MemberStatus;
use App\Models\GameMatch;
use App\Models\MatchAttendance;
use App\Models\MatchGoal;
use App\Models\MatchLineup;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_renders_club_sections(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertViewIs('club.home')
            ->assertSee('Samba FC')
            ->assertSee('Trang Chủ')
            ->assertSee('Tin Tức')
            ->assertSee('Thành Viên')
            ->assertSee('Thống Kê')
            ->assertSee('banner-sambafc.jpg', false)
            ->assertSee('Trận đấu')
            ->assertSee('Top 5 đá nhiều nhất tháng')
            ->assertSee('Top 5 ghi bàn tháng')
            ->assertSee('Top 5 MVP');
    }

    public function test_club_menu_pages_are_available(): void
    {
        $this->get(route('news.index'))->assertOk()->assertViewIs('club.news');
        $this->get(route('members.index'))->assertOk()->assertViewIs('club.members');
        $this->get(route('stats.index'))->assertOk()->assertViewIs('club.stats');
    }

    public function test_members_page_shows_active_players_from_the_database(): void
    {
        Member::factory()->create([
            'name' => 'Nguyen Van A',
            'nickname' => 'Samba',
            'preferred_position' => 'lm',
            'status' => MemberStatus::Active,
        ]);
        Member::factory()->inactive()->create([
            'nickname' => 'Hidden',
            'preferred_position' => 'st',
        ]);

        $this->get(route('members.index'))
            ->assertOk()
            ->assertViewIs('club.members')
            ->assertSee('Samba')
            ->assertSee('Tiền Vệ')
            ->assertDontSee('Hidden');
    }

    public function test_home_page_shows_match_result_and_mvp_from_the_database(): void
    {
        $mvp = Member::factory()->create([
            'name' => 'Sang',
            'nickname' => 'Sáng Cầy',
        ]);
        $teammate = Member::factory()->create([
            'name' => 'Xuyen',
            'nickname' => 'Xuyên Mõm',
        ]);

        $match = GameMatch::factory()->completed()->create([
            'title' => 'Thắng Dễ FC Lai Xá',
            'opponent' => 'FC Lai Xá',
            'venue' => 'Sân bóng Samba',
            'is_home' => true,
            'score_for' => 4,
            'score_against' => 0,
            'mvp_member_id' => $mvp->id,
        ]);

        MatchGoal::query()->create([
            'match_id' => $match->id,
            'member_id' => $mvp->id,
            'minute' => 20,
            'sort_order' => 1,
        ]);
        MatchGoal::query()->create([
            'match_id' => $match->id,
            'member_id' => $teammate->id,
            'minute' => 55,
            'sort_order' => 2,
        ]);
        MatchLineup::query()->create([
            'match_id' => $match->id,
            'member_id' => $mvp->id,
            'slot' => 'rm',
            'is_starter' => true,
            'rating' => 8.5,
            'sort_order' => 1,
        ]);
        MatchLineup::query()->create([
            'match_id' => $match->id,
            'member_id' => $teammate->id,
            'slot' => 'st',
            'is_starter' => true,
            'rating' => 7.5,
            'sort_order' => 2,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Kết quả gần nhất')
            ->assertSee('Thắng Dễ FC Lai Xá')
            ->assertSee('4 - 0')
            ->assertSee('Bàn thắng: Sáng Cầy, Xuyên Mõm')
            ->assertSee('MVP: Sáng Cầy')
            ->assertSee('Sáng Cầy')
            ->assertSee('8.5 điểm')
            ->assertSee('7.5 điểm')
            ->assertSee('Top 5 ghi bàn tháng')
            ->assertSee('1 bàn');
    }

    public function test_home_page_ranks_mvp_from_match_ratings_when_not_selected(): void
    {
        $best = Member::factory()->create(['nickname' => 'Sáng Cầy']);
        $other = Member::factory()->create(['nickname' => 'Nga Hói']);
        $match = GameMatch::factory()->completed()->create([
            'opponent' => 'FC Lai Xá',
            'score_for' => 4,
            'score_against' => 0,
        ]);

        MatchLineup::query()->create([
            'match_id' => $match->id,
            'member_id' => $other->id,
            'slot' => 'cb',
            'is_starter' => true,
            'rating' => 8.0,
            'sort_order' => 1,
        ]);
        MatchLineup::query()->create([
            'match_id' => $match->id,
            'member_id' => $best->id,
            'slot' => 'rm',
            'is_starter' => true,
            'rating' => 8.0,
            'sort_order' => 2,
        ]);
        MatchGoal::query()->create([
            'match_id' => $match->id,
            'member_id' => $best->id,
            'sort_order' => 1,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('MVP: Sáng Cầy')
            ->assertSee('Sáng Cầy')
            ->assertSee('8.0 điểm')
            ->assertSee('Nga Hói');
    }

    public function test_home_page_shows_next_match_weekday_and_attendance(): void
    {
        $attending = Member::factory()->create(['nickname' => 'Nga Hói']);
        $absent = Member::factory()->create(['nickname' => 'Tuyến Cua']);
        $pending = Member::factory()->create(['nickname' => 'Tamma']);
        $kickoff = now()->next('Monday')->setTime(19, 15);

        $match = GameMatch::factory()->create([
            'title' => 'Giao hữu FC Demo',
            'opponent' => 'FC Demo',
            'kickoff_at' => $kickoff,
        ]);

        MatchAttendance::query()->create([
            'match_id' => $match->id,
            'member_id' => $attending->id,
            'status' => AttendanceStatus::Attending,
        ]);
        MatchAttendance::query()->create([
            'match_id' => $match->id,
            'member_id' => $absent->id,
            'status' => AttendanceStatus::Absent,
        ]);
        MatchAttendance::query()->create([
            'match_id' => $match->id,
            'member_id' => $pending->id,
            'status' => AttendanceStatus::Pending,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Giao hữu FC Demo')
            ->assertSee('Thứ Hai')
            ->assertSee('1 tham gia')
            ->assertSee('1 không tham gia')
            ->assertSee('1 chưa xác nhận');
    }
}
