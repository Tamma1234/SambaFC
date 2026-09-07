import { Head, Link, usePage } from '@inertiajs/react';
import { CalendarDays, ClipboardCheck, Trophy, Users } from 'lucide-react';
import { useState } from 'react';
import ClubPage from '@/components/club-page';
import PendingInvitationsModal from '@/components/pending-invitations-modal';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { formatKickoff, matchStatusVariant } from '@/lib/club';
import { dashboard } from '@/routes';
import {
    edit as attendanceEdit,
    index as attendancesIndex,
} from '@/routes/club/attendances';
import {
    index as matchesIndex,
    show as matchShow,
} from '@/routes/club/matches';
import { index as membersIndex } from '@/routes/club/members';
import type {
    ClubDashboardStats,
    ClubMatch,
    DashboardInvitation,
} from '@/types';

type Props = {
    pendingInvitations?: DashboardInvitation[];
    stats?: ClubDashboardStats;
    upcomingMatches?: ClubMatch[];
    recentMatches?: ClubMatch[];
};

const emptyStats: ClubDashboardStats = {
    members_total: 0,
    members_active: 0,
    upcoming_matches: 0,
    matches_this_month: 0,
    pending_attendance: 0,
};

export default function Dashboard({
    pendingInvitations = [],
    stats = emptyStats,
    upcomingMatches = [],
    recentMatches = [],
}: Props) {
    const slug = usePage().props.currentTeam?.slug ?? '';
    const [showInvitations, setShowInvitations] = useState(
        pendingInvitations.length > 0,
    );

    const cards = [
        {
            title: 'Thành viên',
            value: stats.members_active,
            hint: `${stats.members_total} trong danh sách`,
            icon: Users,
            href: slug ? membersIndex(slug) : '/',
        },
        {
            title: 'Trận sắp tới',
            value: stats.upcoming_matches,
            hint: 'Lịch đã lên',
            icon: CalendarDays,
            href: slug ? matchesIndex(slug) : '/',
        },
        {
            title: 'Đã đá tháng này',
            value: stats.matches_this_month,
            hint: 'Trận hoàn thành',
            icon: Trophy,
            href: slug ? matchesIndex(slug) : '/',
        },
        {
            title: 'Chưa điểm danh',
            value: stats.pending_attendance,
            hint: 'Cần xác nhận ra sân',
            icon: ClipboardCheck,
            href: slug ? attendancesIndex(slug) : '/',
        },
    ];

    return (
        <ClubPage>
            <Head title="Bảng điều khiển" />
            <PendingInvitationsModal
                invitations={pendingInvitations}
                open={pendingInvitations.length > 0 && showInvitations}
                onOpenChange={setShowInvitations}
            />

            <div>
                <h1 className="text-xl font-semibold tracking-tight">
                    Quản lý đội bóng
                </h1>
                <p className="text-muted-foreground text-sm">
                    Theo dõi thành viên, lịch thi đấu và điểm danh của Samba FC.
                </p>
            </div>

            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                {cards.map((card) => (
                    <Link key={card.title} href={card.href} prefetch>
                        <Card className="hover:bg-muted/40 h-full transition-colors">
                            <CardHeader className="flex flex-row items-center justify-between space-y-0">
                                <CardTitle className="text-sm font-medium">
                                    {card.title}
                                </CardTitle>
                                <card.icon className="text-muted-foreground size-4" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-3xl font-semibold">
                                    {card.value}
                                </div>
                                <p className="text-muted-foreground mt-1 text-xs">
                                    {card.hint}
                                </p>
                            </CardContent>
                        </Card>
                    </Link>
                ))}
            </div>

            <div className="grid gap-4 lg:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle>Trận sắp diễn ra</CardTitle>
                        <CardDescription>
                            Lịch thi đấu gần nhất của đội.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        {upcomingMatches.map((match) => (
                            <div
                                key={match.id}
                                className="flex items-start justify-between gap-3 rounded-lg border p-3"
                            >
                                <div>
                                    <div className="flex flex-wrap items-center gap-2">
                                        <Link
                                            href={
                                                slug
                                                    ? matchShow([
                                                          slug,
                                                          match.id,
                                                      ])
                                                    : '/'
                                            }
                                            className="font-medium hover:underline"
                                        >
                                            {match.display_title}
                                        </Link>
                                        <Badge
                                            variant={matchStatusVariant(
                                                match.status,
                                            )}
                                        >
                                            {match.status_label}
                                        </Badge>
                                    </div>
                                    <p className="text-muted-foreground mt-1 text-sm">
                                        {formatKickoff(match.kickoff_at)}
                                        {match.venue ? ` · ${match.venue}` : ''}
                                    </p>
                                </div>
                                {slug ? (
                                    <Button variant="outline" size="sm" asChild>
                                        <Link
                                            href={attendanceEdit([
                                                slug,
                                                match.id,
                                            ])}
                                        >
                                            Điểm danh
                                        </Link>
                                    </Button>
                                ) : null}
                            </div>
                        ))}
                        {upcomingMatches.length === 0 ? (
                            <p className="text-muted-foreground text-sm">
                                Chưa có trận nào được lên lịch.
                            </p>
                        ) : null}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Kết quả gần đây</CardTitle>
                        <CardDescription>
                            Những trận đã đá gần nhất.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        {recentMatches.map((match) => (
                            <div
                                key={match.id}
                                className="rounded-lg border p-3"
                            >
                                <div className="flex flex-wrap items-center justify-between gap-2">
                                    <Link
                                        href={
                                            slug
                                                ? matchShow([slug, match.id])
                                                : '/'
                                        }
                                        className="font-medium hover:underline"
                                    >
                                        {match.display_title}
                                    </Link>
                                    <span className="text-lg font-semibold">
                                        {match.score_for ?? 0} -{' '}
                                        {match.score_against ?? 0}
                                    </span>
                                </div>
                                <p className="text-muted-foreground mt-1 text-sm">
                                    {formatKickoff(match.kickoff_at)}
                                    {match.mvp_name
                                        ? ` · MVP: ${match.mvp_name}`
                                        : ''}
                                </p>
                            </div>
                        ))}
                        {recentMatches.length === 0 ? (
                            <p className="text-muted-foreground text-sm">
                                Chưa có kết quả trận đấu.
                            </p>
                        ) : null}
                    </CardContent>
                </Card>
            </div>
        </ClubPage>
    );
}

Dashboard.layout = (props: { currentTeam?: { slug: string } | null }) => ({
    breadcrumbs: [
        {
            title: 'Bảng điều khiển',
            href: props.currentTeam ? dashboard(props.currentTeam.slug) : '/',
        },
    ],
});
