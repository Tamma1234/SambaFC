<?php

namespace Tests\Feature;

use App\Enums\MemberStatus;
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
            ->assertSee('Trận đấu tiếp theo')
            ->assertSee('Top 5 đá nhiều nhất tháng')
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
}
