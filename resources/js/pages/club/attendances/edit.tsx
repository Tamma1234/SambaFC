import { Form, Head, Link, usePage } from '@inertiajs/react';
import ClubPage from '@/components/club-page';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatKickoff } from '@/lib/club';
import { dashboard } from '@/routes';
import {
    edit as attendanceEdit,
    index as attendancesIndex,
    update,
} from '@/routes/club/attendances';
import type { ClubAttendanceRow, ClubMatch, ClubOption } from '@/types';

type Props = {
    match: ClubMatch;
    attendances: ClubAttendanceRow[];
    statuses: ClubOption[];
};

export default function AttendanceEdit({
    match,
    attendances,
    statuses,
}: Props) {
    const slug = usePage().props.currentTeam?.slug ?? '';

    return (
        <ClubPage>
            <Head title={`Điểm danh · ${match.display_title}`} />

            <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <Heading
                    variant="small"
                    title={match.display_title}
                    description={`${formatKickoff(match.kickoff_at)}${match.venue ? ` · ${match.venue}` : ''}`}
                />
                <div className="flex flex-wrap gap-2 text-sm">
                    <Badge>{match.attending_count} tham gia</Badge>
                    <Badge variant="outline">{match.absent_count} vắng</Badge>
                    <Badge variant="secondary">{match.pending_count} chờ</Badge>
                </div>
            </div>

            {attendances.length === 0 ? (
                <p className="text-muted-foreground rounded-xl border py-10 text-center">
                    Chưa có thành viên hoạt động để điểm danh.
                </p>
            ) : (
                <Form {...update.form([slug, match.id])} className="space-y-4">
                    {({ processing }) => (
                        <>
                            <div className="overflow-hidden rounded-xl border">
                                <table className="w-full text-left text-sm">
                                    <thead className="bg-muted/50 text-muted-foreground">
                                        <tr>
                                            <th className="px-4 py-3 font-medium">
                                                Thành viên
                                            </th>
                                            <th className="px-4 py-3 font-medium">
                                                Trạng thái
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {attendances.map((row, index) => (
                                            <tr
                                                key={row.member_id}
                                                className="border-t"
                                            >
                                                <td className="px-4 py-3">
                                                    <input
                                                        type="hidden"
                                                        name={`attendances[${index}][member_id]`}
                                                        value={row.member_id}
                                                    />
                                                    <div className="font-medium">
                                                        {row.jersey_number
                                                            ? `#${row.jersey_number} `
                                                            : ''}
                                                        {row.member_name}
                                                    </div>
                                                </td>
                                                <td className="px-4 py-3">
                                                    <div className="flex flex-wrap gap-3">
                                                        {statuses.map(
                                                            (status) => (
                                                                <label
                                                                    key={
                                                                        status.value
                                                                    }
                                                                    className="flex items-center gap-2"
                                                                >
                                                                    <input
                                                                        type="radio"
                                                                        name={`attendances[${index}][status]`}
                                                                        value={
                                                                            status.value
                                                                        }
                                                                        defaultChecked={
                                                                            row.status ===
                                                                            status.value
                                                                        }
                                                                        className="size-4"
                                                                    />
                                                                    {
                                                                        status.label
                                                                    }
                                                                </label>
                                                            ),
                                                        )}
                                                    </div>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>

                            <div className="flex justify-end gap-2">
                                <Button variant="secondary" asChild>
                                    <Link href={attendancesIndex(slug)}>
                                        Quay lại
                                    </Link>
                                </Button>
                                <Button type="submit" disabled={processing}>
                                    Lưu điểm danh
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            )}
        </ClubPage>
    );
}

AttendanceEdit.layout = (props: {
    currentTeam?: { slug: string } | null;
    match?: ClubMatch;
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
        {
            title: props.match?.display_title ?? 'Trận đấu',
            href:
                props.currentTeam && props.match
                    ? attendanceEdit([props.currentTeam.slug, props.match.id])
                    : '/',
        },
    ],
});
