import { Form, Head, Link, usePage } from '@inertiajs/react';
import { ClipboardCheck, Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import ClubPage from '@/components/club-page';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import NativeSelect from '@/components/native-select';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
    defaultKickoffInput,
    formatKickoff,
    matchStatusVariant,
} from '@/lib/club';
import { dashboard } from '@/routes';
import { edit as attendanceEdit } from '@/routes/club/attendances';
import {
    destroy,
    index as matchesIndex,
    show as matchShow,
    store,
    update,
} from '@/routes/club/matches';
import type { ClubMatch, ClubMember, ClubOption } from '@/types';

type Props = {
    matches: ClubMatch[];
    members: ClubMember[];
    statuses: ClubOption[];
};

export default function MatchesIndex({ matches, members, statuses }: Props) {
    const slug = usePage().props.currentTeam?.slug ?? '';
    const [creating, setCreating] = useState(false);
    const [editing, setEditing] = useState<ClubMatch | null>(null);
    const [deleting, setDeleting] = useState<ClubMatch | null>(null);

    return (
        <ClubPage>
            <Head title="Trận đấu" />

            <div className="flex items-center justify-between gap-4">
                <Heading
                    variant="small"
                    title="Trận đấu"
                    description="Lịch thi đấu, kết quả và thông tin đối thủ."
                />
                <Button onClick={() => setCreating(true)}>
                    <Plus />
                    Thêm trận
                </Button>
            </div>

            <div className="space-y-3">
                {matches.map((match) => (
                    <div
                        key={match.id}
                        className="flex flex-col gap-4 rounded-xl border p-4 sm:flex-row sm:items-center sm:justify-between"
                        data-test="match-row"
                    >
                        <div className="space-y-1">
                            <div className="flex flex-wrap items-center gap-2">
                                <Link
                                    href={matchShow([slug, match.id])}
                                    className="font-medium hover:underline"
                                >
                                    {match.display_title}
                                </Link>
                                <Badge
                                    variant={matchStatusVariant(match.status)}
                                >
                                    {match.status_label}
                                </Badge>
                                <Badge variant="outline">
                                    {match.is_home ? 'Sân nhà' : 'Sân khách'}
                                </Badge>
                            </div>
                            <p className="text-muted-foreground text-sm">
                                {formatKickoff(match.kickoff_at)}
                                {match.venue ? ` · ${match.venue}` : ''}
                            </p>
                            {match.status === 'completed' ? (
                                <p className="text-sm font-medium">
                                    Tỷ số {match.score_for ?? 0} -{' '}
                                    {match.score_against ?? 0}
                                    {match.mvp_name
                                        ? ` · MVP: ${match.mvp_name}`
                                        : ''}
                                </p>
                            ) : (
                                <p className="text-muted-foreground text-sm">
                                    Điểm danh: {match.attending_count} tham gia
                                    · {match.pending_count} chờ
                                </p>
                            )}
                        </div>
                        <div className="flex items-center gap-1">
                            <Button variant="outline" size="sm" asChild>
                                <Link href={attendanceEdit([slug, match.id])}>
                                    <ClipboardCheck />
                                    Điểm danh
                                </Link>
                            </Button>
                            <Button
                                variant="ghost"
                                size="icon"
                                onClick={() => setEditing(match)}
                            >
                                <Pencil />
                                <span className="sr-only">Sửa</span>
                            </Button>
                            <Button
                                variant="ghost"
                                size="icon"
                                onClick={() => setDeleting(match)}
                            >
                                <Trash2 />
                                <span className="sr-only">Xóa</span>
                            </Button>
                        </div>
                    </div>
                ))}

                {matches.length === 0 ? (
                    <p className="text-muted-foreground rounded-xl border py-10 text-center">
                        Chưa có trận đấu nào.
                    </p>
                ) : null}
            </div>

            <MatchFormDialog
                open={creating}
                onOpenChange={setCreating}
                slug={slug}
                members={members}
                statuses={statuses}
            />

            <MatchFormDialog
                open={editing !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setEditing(null);
                    }
                }}
                slug={slug}
                match={editing}
                members={members}
                statuses={statuses}
            />

            <Dialog
                open={deleting !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setDeleting(null);
                    }
                }}
            >
                <DialogContent>
                    {deleting ? (
                        <Form
                            key={deleting.id}
                            {...destroy.form([slug, deleting.id])}
                            onSuccess={() => setDeleting(null)}
                        >
                            {({ processing }) => (
                                <>
                                    <DialogHeader>
                                        <DialogTitle>Xóa trận đấu?</DialogTitle>
                                        <DialogDescription>
                                            {deleting.display_title} sẽ bị xóa
                                            khỏi lịch thi đấu.
                                        </DialogDescription>
                                    </DialogHeader>
                                    <DialogFooter>
                                        <DialogClose asChild>
                                            <Button variant="secondary">
                                                Hủy
                                            </Button>
                                        </DialogClose>
                                        <Button
                                            type="submit"
                                            variant="destructive"
                                            disabled={processing}
                                        >
                                            Xóa
                                        </Button>
                                    </DialogFooter>
                                </>
                            )}
                        </Form>
                    ) : null}
                </DialogContent>
            </Dialog>
        </ClubPage>
    );
}

function MatchFormDialog({
    open,
    onOpenChange,
    slug,
    match,
    members,
    statuses,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    slug: string;
    match?: ClubMatch | null;
    members: ClubMember[];
    statuses: ClubOption[];
}) {
    const formProps = match ? update.form([slug, match.id]) : store.form(slug);

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-lg">
                <Form
                    key={`${match?.id ?? 'create'}-${String(open)}`}
                    {...formProps}
                    className="space-y-4"
                    onSuccess={() => onOpenChange(false)}
                >
                    {({ errors, processing }) => (
                        <>
                            <DialogHeader>
                                <DialogTitle>
                                    {match ? 'Sửa trận đấu' : 'Thêm trận đấu'}
                                </DialogTitle>
                                <DialogDescription>
                                    Tạo lịch thi đấu và cập nhật kết quả sau
                                    trận.
                                </DialogDescription>
                            </DialogHeader>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2 sm:col-span-2">
                                    <Label htmlFor="opponent">Đối thủ</Label>
                                    <Input
                                        id="opponent"
                                        name="opponent"
                                        required
                                        defaultValue={match?.opponent ?? ''}
                                    />
                                    <InputError message={errors.opponent} />
                                </div>
                                <div className="grid gap-2 sm:col-span-2">
                                    <Label htmlFor="title">
                                        Tiêu đề (tuỳ chọn)
                                    </Label>
                                    <Input
                                        id="title"
                                        name="title"
                                        defaultValue={match?.title ?? ''}
                                    />
                                    <InputError message={errors.title} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="venue">Sân</Label>
                                    <Input
                                        id="venue"
                                        name="venue"
                                        defaultValue={
                                            match?.venue ?? 'Sân bóng Samba'
                                        }
                                    />
                                    <InputError message={errors.venue} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="kickoff_at">
                                        Giờ bóng lăn
                                    </Label>
                                    <Input
                                        id="kickoff_at"
                                        name="kickoff_at"
                                        type="datetime-local"
                                        required
                                        defaultValue={
                                            match?.kickoff_input ??
                                            defaultKickoffInput()
                                        }
                                    />
                                    <InputError message={errors.kickoff_at} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="status">Trạng thái</Label>
                                    <NativeSelect
                                        id="status"
                                        name="status"
                                        defaultValue={
                                            match?.status ?? 'scheduled'
                                        }
                                    >
                                        {statuses.map((status) => (
                                            <option
                                                key={status.value}
                                                value={status.value}
                                            >
                                                {status.label}
                                            </option>
                                        ))}
                                    </NativeSelect>
                                    <InputError message={errors.status} />
                                </div>
                                <label className="flex items-center gap-2 self-end pb-2 text-sm">
                                    <input
                                        type="checkbox"
                                        name="is_home"
                                        value="1"
                                        defaultChecked={match?.is_home ?? true}
                                        className="size-4 rounded border"
                                    />
                                    Sân nhà
                                </label>
                                <div className="grid gap-2">
                                    <Label htmlFor="score_for">Bàn thắng</Label>
                                    <Input
                                        id="score_for"
                                        name="score_for"
                                        type="number"
                                        min={0}
                                        max={99}
                                        defaultValue={match?.score_for ?? ''}
                                    />
                                    <InputError message={errors.score_for} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="score_against">
                                        Bàn thua
                                    </Label>
                                    <Input
                                        id="score_against"
                                        name="score_against"
                                        type="number"
                                        min={0}
                                        max={99}
                                        defaultValue={
                                            match?.score_against ?? ''
                                        }
                                    />
                                    <InputError
                                        message={errors.score_against}
                                    />
                                </div>
                                <div className="grid gap-2 sm:col-span-2">
                                    <Label htmlFor="mvp_member_id">MVP</Label>
                                    <NativeSelect
                                        id="mvp_member_id"
                                        name="mvp_member_id"
                                        defaultValue={
                                            match?.mvp_member_id ?? ''
                                        }
                                    >
                                        <option value="">Chưa chọn</option>
                                        {members.map((member) => (
                                            <option
                                                key={member.id}
                                                value={member.id}
                                            >
                                                {member.display_name}
                                            </option>
                                        ))}
                                    </NativeSelect>
                                    <InputError
                                        message={errors.mvp_member_id}
                                    />
                                </div>
                                <div className="grid gap-2 sm:col-span-2">
                                    <Label htmlFor="notes">Ghi chú</Label>
                                    <Textarea
                                        id="notes"
                                        name="notes"
                                        defaultValue={match?.notes ?? ''}
                                    />
                                    <InputError message={errors.notes} />
                                </div>
                            </div>

                            <DialogFooter>
                                <DialogClose asChild>
                                    <Button variant="secondary">Hủy</Button>
                                </DialogClose>
                                <Button type="submit" disabled={processing}>
                                    {match ? 'Lưu' : 'Tạo trận'}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

MatchesIndex.layout = (props: { currentTeam?: { slug: string } | null }) => ({
    breadcrumbs: [
        {
            title: 'Bảng điều khiển',
            href: props.currentTeam ? dashboard(props.currentTeam.slug) : '/',
        },
        {
            title: 'Trận đấu',
            href: props.currentTeam
                ? matchesIndex(props.currentTeam.slug)
                : '/',
        },
    ],
});
