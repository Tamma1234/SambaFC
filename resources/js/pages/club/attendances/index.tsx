import { Head, Link, usePage } from '@inertiajs/react';
import { ClipboardCheck } from 'lucide-react';
import ClubPage from '@/components/club-page';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatKickoff, matchStatusVariant } from '@/lib/club';
import { dashboard } from '@/routes';
import {
    edit as attendanceEdit,
    index as attendancesIndex,
} from '@/routes/club/attendances';
import type { ClubMatch } from '@/types';

type Props = {
    matches: ClubMatch[];
};

export default function AttendancesIndex({ matches }: Props) {
    const slug = usePage().props.currentTeam?.slug ?? '';

    return (
        <ClubPage>
            <Head title="Điểm danh" />

            <Heading
                variant="small"
                title="Điểm danh"
                description="Chọn trận đấu để ghi nhận ai ra sân."
            />

            <div className="space-y-3">
                {matches.map((match) => (
                    <div
                        key={match.id}
                        className="flex flex-col gap-4 rounded-xl border p-4 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div className="space-y-1">
                            <div className="flex flex-wrap items-center gap-2">
                                <span className="font-medium">
                                    {match.display_title}
                                </span>
                                <Badge
                                    variant={matchStatusVariant(match.status)}
                                >
                                    {match.status_label}
                                </Badge>
                            </div>
                            <p className="text-muted-foreground text-sm">
                                {formatKickoff(match.kickoff_at)}
                                {match.venue ? ` · ${match.venue}` : ''}
                            </p>
                            <p className="text-sm">
                                {match.attending_count} tham gia ·{' '}
                                {match.absent_count} vắng ·{' '}
                                {match.pending_count} chưa xác nhận
                            </p>
                        </div>
                        <Button asChild>
                            <Link href={attendanceEdit([slug, match.id])}>
                                <ClipboardCheck />
                                Điểm danh
                            </Link>
                        </Button>
                    </div>
                ))}

                {matches.length === 0 ? (
                    <p className="text-muted-foreground rounded-xl border py-10 text-center">
                        Chưa có trận để điểm danh. Hãy tạo trận đấu trước.
                    </p>
                ) : null}
            </div>
        </ClubPage>
    );
}

AttendancesIndex.layout = (props: {
    currentTeam?: { slug: string } | null;
}) => ({
    breadcrumbs: [
        {
            title: 'Bảng điều khiển',
            href: props.currentTeam ? dashboard(props.currentTeam.slug) : '/',
        },
        {
            title: 'Điểm danh',
            href: props.currentTeam
                ? attendancesIndex(props.currentTeam.slug)
                : '/',
        },
    ],
});
