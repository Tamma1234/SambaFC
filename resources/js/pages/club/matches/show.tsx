import { Head, Link, router, usePage } from '@inertiajs/react';
import { CalendarDays, MapPin, Plus, Trash2 } from 'lucide-react';
import { useMemo, useState } from 'react';
import ClubPage from '@/components/club-page';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import MatchPitch from '@/components/match-pitch';
import NativeSelect from '@/components/native-select';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatKickoff, matchStatusVariant } from '@/lib/club';
import { dashboard } from '@/routes';
import { edit as attendanceEdit } from '@/routes/club/attendances';
import {
    index as matchesIndex,
    show as matchShow,
} from '@/routes/club/matches';
import { update as updateStats } from '@/routes/club/matches/stats';
import type {
    ClubMatch,
    ClubMatchGoal,
    ClubMatchLineup,
    ClubMember,
    ClubPitchSlot,
} from '@/types';

type LineupDraft = {
    member_id: string;
    rating: string;
};

type GoalDraft = {
    key: string;
    member_id: string;
    minute: string;
};

type Props = {
    match: ClubMatch;
    lineups: ClubMatchLineup[];
    goals: ClubMatchGoal[];
    members: ClubMember[];
    slots: ClubPitchSlot[];
};

function emptyLineups(slots: ClubPitchSlot[]): Record<string, LineupDraft> {
    return Object.fromEntries(
        slots.map((slot) => [slot.value, { member_id: '', rating: '' }]),
    );
}

function draftsFromLineups(
    slots: ClubPitchSlot[],
    lineups: ClubMatchLineup[],
): Record<string, LineupDraft> {
    const drafts = emptyLineups(slots);

    for (const row of lineups) {
        drafts[row.slot] = {
            member_id: String(row.member_id),
            rating: row.rating === null ? '' : String(row.rating),
        };
    }

    return drafts;
}

function draftsFromGoals(goals: ClubMatchGoal[]): GoalDraft[] {
    if (goals.length === 0) {
        return [];
    }

    return goals.map((goal, index) => ({
        key: `saved-${goal.id}-${index}`,
        member_id: String(goal.member_id),
        minute: goal.minute === null ? '' : String(goal.minute),
    }));
}

function memberById(
    members: ClubMember[],
    memberId: string,
): ClubMember | null {
    if (!memberId) {
        return null;
    }

    return members.find((member) => String(member.id) === memberId) ?? null;
}

function ratingValue(rating: string): number | null {
    if (rating === '') {
        return null;
    }

    const value = Number(rating);

    return Number.isNaN(value) ? null : value;
}

function SoccerBallIcon({ className }: { className?: string }) {
    return (
        <svg viewBox="0 0 24 24" fill="none" className={className} aria-hidden>
            <circle
                cx="12"
                cy="12"
                r="9.25"
                stroke="currentColor"
                strokeWidth="1.75"
            />
            <polygon
                points="12,8.1 14.7,10 13.65,13.15 10.35,13.15 9.3,10"
                fill="currentColor"
            />
            <path
                d="M12 8.1V3.6M14.7 10l4.1-1.9M13.65 13.15l2.7 4.7M10.35 13.15l-2.7 4.7M9.3 10 5.2 8.1"
                stroke="currentColor"
                strokeWidth="1.5"
                strokeLinecap="round"
            />
        </svg>
    );
}

function groupedScorers(goals: ClubMatchGoal[]) {
    const grouped = new Map<
        number,
        {
            member_id: number;
            name: string;
            minutes: Array<number | null>;
            count: number;
        }
    >();

    for (const goal of goals) {
        const current = grouped.get(goal.member_id) ?? {
            member_id: goal.member_id,
            name: goal.member_name ?? 'Cầu thủ',
            minutes: [],
            count: 0,
        };
        current.minutes.push(goal.minute);
        current.count += 1;
        grouped.set(goal.member_id, current);
    }

    return Array.from(grouped.values());
}

function GoalScorers({ goals }: { goals: ClubMatchGoal[] }) {
    if (goals.length === 0) {
        return (
            <p className="text-sm text-white/70">
                Chưa có cầu thủ được ghi nhận bàn thắng.
            </p>
        );
    }

    return (
        <div className="flex flex-wrap items-center justify-center gap-x-3 gap-y-1.5 text-sm text-white/90">
            {groupedScorers(goals).map((scorer) => {
                const minutes = scorer.minutes
                    .filter((minute): minute is number => minute !== null)
                    .map((minute) => `${minute}'`)
                    .join(', ');

                return (
                    <span
                        key={scorer.member_id}
                        className="inline-flex items-center gap-1"
                    >
                        {scorer.name}
                        {Array.from({ length: scorer.count }, (_, index) => (
                            <SoccerBallIcon
                                key={`${scorer.member_id}-ball-${index}`}
                                className="size-3.5 shrink-0 text-white/90"
                            />
                        ))}
                        {minutes ? (
                            <span className="text-white/60">{minutes}</span>
                        ) : null}
                    </span>
                );
            })}
        </div>
    );
}

export default function MatchShow({
    match,
    lineups,
    goals,
    members,
    slots,
}: Props) {
    const page = usePage();
    const slug = page.props.currentTeam?.slug ?? '';
    const club = page.props.club;
    const errors = page.props.errors as Record<string, string>;
    const [saving, setSaving] = useState(false);
    const [lineupDrafts, setLineupDrafts] = useState<
        Record<string, LineupDraft>
    >(() => draftsFromLineups(slots, lineups));
    const [goalDrafts, setGoalDrafts] = useState<GoalDraft[]>(() =>
        draftsFromGoals(goals),
    );

    const starters = slots.filter((slot) => slot.is_starter);
    const substitutes = slots.filter((slot) => !slot.is_starter);
    const selectedMemberIds = useMemo(
        () =>
            new Set(
                Object.values(lineupDrafts)
                    .map((draft) => draft.member_id)
                    .filter(Boolean),
            ),
        [lineupDrafts],
    );

    const membersForSlot = (slotValue: string): ClubMember[] => {
        const currentId = lineupDrafts[slotValue]?.member_id ?? '';

        return members.filter((member) => {
            const id = String(member.id);

            return id === currentId || !selectedMemberIds.has(id);
        });
    };

    const pitchPlayers = useMemo(
        () =>
            starters.map((slot) => {
                const draft = lineupDrafts[slot.value] ?? {
                    member_id: '',
                    rating: '',
                };

                return {
                    slot,
                    member: memberById(members, draft.member_id),
                    rating: ratingValue(draft.rating),
                };
            }),
        [lineupDrafts, members, starters],
    );

    const pitchSubs = useMemo(
        () =>
            substitutes.map((slot) => {
                const draft = lineupDrafts[slot.value] ?? {
                    member_id: '',
                    rating: '',
                };

                return {
                    slot,
                    member: memberById(members, draft.member_id),
                    rating: ratingValue(draft.rating),
                };
            }),
        [lineupDrafts, members, substitutes],
    );

    const assignSlot = (
        slotValue: string,
        field: keyof LineupDraft,
        value: string,
    ) => {
        setLineupDrafts((current) => {
            const next = { ...current };

            if (field === 'member_id' && value) {
                for (const [key, draft] of Object.entries(next)) {
                    if (key !== slotValue && draft.member_id === value) {
                        next[key] = { ...draft, member_id: '' };
                    }
                }
            }

            next[slotValue] = {
                ...(next[slotValue] ?? { member_id: '', rating: '' }),
                [field]: value,
            };

            return next;
        });
    };

    const saveStats = () => {
        router.put(
            updateStats.url([slug, match.id]),
            {
                lineups: slots.map((slot) => ({
                    slot: slot.value,
                    member_id: lineupDrafts[slot.value]?.member_id || null,
                    rating: lineupDrafts[slot.value]?.rating || null,
                })),
                goals: goalDrafts
                    .filter((goal) => goal.member_id)
                    .map((goal) => ({
                        member_id: Number(goal.member_id),
                        minute: goal.minute || null,
                    })),
            },
            {
                preserveScroll: true,
                onStart: () => setSaving(true),
                onFinish: () => setSaving(false),
            },
        );
    };

    return (
        <ClubPage>
            <Head title={`Thống kê · ${match.display_title}`} />

            <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <Heading
                    variant="small"
                    title={match.display_title}
                    description={`${formatKickoff(match.kickoff_at)}${match.venue ? ` · ${match.venue}` : ''}`}
                />
                <div className="flex flex-wrap gap-2">
                    <Badge variant={matchStatusVariant(match.status)}>
                        {match.status_label}
                    </Badge>
                    <Badge variant="outline">
                        {match.is_home ? 'Sân nhà' : 'Sân khách'}
                    </Badge>
                    <Button variant="outline" size="sm" asChild>
                        <Link href={attendanceEdit([slug, match.id])}>
                            Điểm danh
                        </Link>
                    </Button>
                    <Button variant="secondary" size="sm" asChild>
                        <Link href={matchesIndex(slug)}>Quay lại</Link>
                    </Button>
                </div>
            </div>

            <div className="rounded-2xl bg-linear-to-r from-slate-900 to-slate-800 p-5 text-white shadow-sm">
                <div className="flex items-center justify-between gap-4">
                    <div className="flex min-w-0 flex-1 items-center justify-end gap-3 text-right">
                        <div className="min-w-0">
                            <p className="text-xs tracking-widest uppercase opacity-70">
                                Samba FC
                            </p>
                            <p className="truncate font-semibold">
                                {club.name}
                            </p>
                        </div>
                        <img
                            src={club.logo}
                            alt={club.name}
                            className="size-12 rounded-full bg-white object-contain p-1"
                        />
                    </div>
                    <div className="text-center">
                        <p className="text-[11px] tracking-[0.25em] uppercase opacity-70">
                            Tỷ số
                        </p>
                        <p className="text-4xl font-black tabular-nums sm:text-5xl">
                            {match.score_for ?? '–'}
                            <span className="mx-2 text-white/40">-</span>
                            {match.score_against ?? '–'}
                        </p>
                    </div>
                    <div className="flex min-w-0 flex-1 items-center gap-3">
                        <div className="flex size-12 items-center justify-center rounded-full bg-white/10 text-sm font-bold">
                            VS
                        </div>
                        <div className="min-w-0">
                            <p className="text-xs tracking-widest uppercase opacity-70">
                                Đối thủ
                            </p>
                            <p className="truncate font-semibold">
                                {match.opponent}
                            </p>
                        </div>
                    </div>
                </div>
                <div className="mt-4 flex flex-col items-center gap-2 text-center">
                    <p className="flex flex-wrap items-center justify-center gap-x-3 gap-y-1 text-xs text-white/70">
                        <span className="inline-flex items-center gap-1.5">
                            <CalendarDays className="size-3.5 shrink-0" />
                            {formatKickoff(match.kickoff_at)}
                        </span>
                        {match.venue ? (
                            <span className="inline-flex items-center gap-1.5">
                                <MapPin className="size-3.5 shrink-0" />
                                {match.venue}
                            </span>
                        ) : null}
                    </p>
                    <GoalScorers goals={goals} />
                    {match.mvp_name ? (
                        <p className="text-sm text-amber-300">
                            MVP: {match.mvp_name}
                        </p>
                    ) : null}
                </div>
            </div>

            <div className="grid gap-6 xl:grid-cols-[minmax(0,26rem)_minmax(0,1fr)]">
                <MatchPitch
                    clubName={club.name}
                    clubLogo={club.logo}
                    opponent={match.opponent}
                    players={pitchPlayers}
                    substitutes={pitchSubs}
                />

                <div className="space-y-6">
                    <section className="space-y-3 rounded-xl border p-4">
                        <div>
                            <h2 className="font-semibold">Đội hình & điểm</h2>
                            <p className="text-muted-foreground text-sm">
                                Chọn cầu thủ từng vị trí và chấm điểm. Sơ đồ sân
                                bên trái cập nhật ngay khi chọn.
                            </p>
                        </div>

                        <div className="space-y-2">
                            {starters.map((slot) => (
                                <SlotRow
                                    key={slot.value}
                                    slot={slot}
                                    draft={
                                        lineupDrafts[slot.value] ?? {
                                            member_id: '',
                                            rating: '',
                                        }
                                    }
                                    members={membersForSlot(slot.value)}
                                    onChange={assignSlot}
                                />
                            ))}
                        </div>

                        <div className="border-t pt-4">
                            <h2 className="font-semibold">Thay người & điểm</h2>
                            <p className="text-muted-foreground text-sm">
                                Chọn cầu thủ vào ghế thay người và chấm điểm. Có
                                15 vị trí thay người cho đội đông thành viên.
                            </p>
                        </div>
                        <div className="grid gap-3 sm:grid-cols-2">
                            {substitutes.map((slot) => (
                                <SlotRow
                                    key={slot.value}
                                    slot={slot}
                                    draft={
                                        lineupDrafts[slot.value] ?? {
                                            member_id: '',
                                            rating: '',
                                        }
                                    }
                                    members={membersForSlot(slot.value)}
                                    onChange={assignSlot}
                                    roomy
                                />
                            ))}
                        </div>
                        <InputError message={errors.lineups} />
                    </section>

                    <section className="space-y-3 rounded-xl border p-4">
                        <div className="flex items-center justify-between gap-3">
                            <div>
                                <h2 className="font-semibold">Người ghi bàn</h2>
                                <p className="text-muted-foreground text-sm">
                                    Chọn cầu thủ Samba FC ghi bàn trong trận
                                    này.
                                </p>
                            </div>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() =>
                                    setGoalDrafts((current) => [
                                        ...current,
                                        {
                                            key: `new-${Date.now()}`,
                                            member_id: '',
                                            minute: '',
                                        },
                                    ])
                                }
                            >
                                <Plus />
                                Thêm bàn
                            </Button>
                        </div>

                        {goalDrafts.length === 0 ? (
                            <p className="text-muted-foreground rounded-lg border border-dashed py-6 text-center text-sm">
                                Chưa có bàn thắng được ghi nhận.
                            </p>
                        ) : (
                            <div className="space-y-2">
                                {goalDrafts.map((goal, index) => (
                                    <div
                                        key={goal.key}
                                        className="grid grid-cols-[1fr_5.5rem_auto] items-end gap-2"
                                    >
                                        <div className="grid gap-1">
                                            <Label
                                                htmlFor={`goal-member-${goal.key}`}
                                            >
                                                Cầu thủ
                                            </Label>
                                            <NativeSelect
                                                id={`goal-member-${goal.key}`}
                                                value={goal.member_id}
                                                onChange={(event) =>
                                                    setGoalDrafts((current) =>
                                                        current.map((row) =>
                                                            row.key === goal.key
                                                                ? {
                                                                      ...row,
                                                                      member_id:
                                                                          event
                                                                              .target
                                                                              .value,
                                                                  }
                                                                : row,
                                                        ),
                                                    )
                                                }
                                            >
                                                <option value="">
                                                    Chọn cầu thủ
                                                </option>
                                                {members.map((member) => (
                                                    <option
                                                        key={member.id}
                                                        value={member.id}
                                                    >
                                                        {member.display_name}
                                                    </option>
                                                ))}
                                            </NativeSelect>
                                        </div>
                                        <div className="grid gap-1">
                                            <Label
                                                htmlFor={`goal-minute-${goal.key}`}
                                            >
                                                Phút
                                            </Label>
                                            <Input
                                                id={`goal-minute-${goal.key}`}
                                                type="number"
                                                min={1}
                                                max={120}
                                                value={goal.minute}
                                                onChange={(event) =>
                                                    setGoalDrafts((current) =>
                                                        current.map((row) =>
                                                            row.key === goal.key
                                                                ? {
                                                                      ...row,
                                                                      minute: event
                                                                          .target
                                                                          .value,
                                                                  }
                                                                : row,
                                                        ),
                                                    )
                                                }
                                            />
                                        </div>
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon"
                                            onClick={() =>
                                                setGoalDrafts((current) =>
                                                    current.filter(
                                                        (row) =>
                                                            row.key !==
                                                            goal.key,
                                                    ),
                                                )
                                            }
                                        >
                                            <Trash2 />
                                            <span className="sr-only">
                                                Xóa bàn {index + 1}
                                            </span>
                                        </Button>
                                    </div>
                                ))}
                            </div>
                        )}
                        <InputError message={errors.goals} />
                    </section>

                    <div className="flex justify-end">
                        <Button onClick={saveStats} disabled={saving}>
                            Lưu thống kê
                        </Button>
                    </div>
                </div>
            </div>
        </ClubPage>
    );
}

function SlotRow({
    slot,
    draft,
    members,
    onChange,
    roomy = false,
}: {
    slot: ClubPitchSlot;
    draft: LineupDraft;
    members: ClubMember[];
    onChange: (
        slotValue: string,
        field: keyof LineupDraft,
        value: string,
    ) => void;
    roomy?: boolean;
}) {
    return (
        <div
            className={
                roomy
                    ? 'bg-muted/30 grid grid-cols-[4.25rem_minmax(0,1fr)_5.5rem] items-center gap-2 rounded-lg border p-2 sm:grid-cols-[4.5rem_minmax(0,1fr)_6rem] sm:gap-3 sm:p-3'
                    : 'grid grid-cols-[7.5rem_minmax(0,1fr)_4.5rem] items-center gap-2'
            }
        >
            <p className="text-muted-foreground text-sm font-medium">
                {slot.label}
            </p>
            <NativeSelect
                aria-label={slot.label}
                className={roomy ? 'h-10 min-h-10' : undefined}
                value={draft.member_id}
                onChange={(event) =>
                    onChange(slot.value, 'member_id', event.target.value)
                }
            >
                <option value="">Chưa chọn</option>
                {members.map((member) => (
                    <option key={member.id} value={member.id}>
                        {member.display_name}
                    </option>
                ))}
            </NativeSelect>
            <Input
                aria-label={`Điểm ${slot.label}`}
                type="number"
                min={0}
                max={10}
                step={0.5}
                placeholder="Điểm"
                className={
                    roomy ? 'h-10 min-h-10 text-base font-semibold' : undefined
                }
                value={draft.rating}
                onChange={(event) =>
                    onChange(slot.value, 'rating', event.target.value)
                }
            />
        </div>
    );
}

MatchShow.layout = (props: {
    currentTeam?: { slug: string } | null;
    match?: ClubMatch;
}) => ({
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
        {
            title: props.match?.display_title ?? 'Thống kê',
            href:
                props.currentTeam && props.match
                    ? matchShow([props.currentTeam.slug, props.match.id])
                    : '/',
        },
    ],
});
