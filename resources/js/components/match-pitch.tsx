import { Star, User } from 'lucide-react';
import { formatPitchName, formatRating } from '@/lib/club';
import { cn } from '@/lib/utils';
import type { ClubMember, ClubPitchSlot } from '@/types';

export type PitchAssignment = {
    slot: ClubPitchSlot;
    member: ClubMember | null;
    rating: number | null;
};

function PitchMarkings() {
    return (
        <svg
            className="pointer-events-none absolute inset-0 h-full w-full"
            viewBox="0 0 100 140"
            preserveAspectRatio="none"
            aria-hidden
        >
            <rect
                x="3.5"
                y="3.5"
                width="93"
                height="133"
                fill="none"
                stroke="rgba(255,255,255,0.88)"
                strokeWidth="0.7"
            />
            <line
                x1="3.5"
                y1="70"
                x2="96.5"
                y2="70"
                stroke="rgba(255,255,255,0.78)"
                strokeWidth="0.5"
            />
            <circle
                cx="50"
                cy="70"
                r="11.5"
                fill="none"
                stroke="rgba(255,255,255,0.78)"
                strokeWidth="0.5"
            />
            <circle cx="50" cy="70" r="0.9" fill="rgba(255,255,255,0.9)" />
            <rect
                x="24"
                y="3.5"
                width="52"
                height="20"
                fill="none"
                stroke="rgba(255,255,255,0.78)"
                strokeWidth="0.5"
            />
            <rect
                x="36"
                y="3.5"
                width="28"
                height="8"
                fill="none"
                stroke="rgba(255,255,255,0.78)"
                strokeWidth="0.5"
            />
            <rect
                x="24"
                y="116.5"
                width="52"
                height="20"
                fill="none"
                stroke="rgba(255,255,255,0.78)"
                strokeWidth="0.5"
            />
            <rect
                x="36"
                y="128.5"
                width="28"
                height="8"
                fill="none"
                stroke="rgba(255,255,255,0.78)"
                strokeWidth="0.5"
            />
        </svg>
    );
}

function highestRating(assignments: PitchAssignment[]): number | null {
    const ratings = assignments
        .map((row) => row.rating)
        .filter((rating): rating is number => rating !== null);

    if (ratings.length === 0) {
        return null;
    }

    return Math.max(...ratings);
}

function PitchPlayerCard({
    assignment,
    compact = false,
    isTop = false,
}: {
    assignment: PitchAssignment;
    compact?: boolean;
    isTop?: boolean;
}) {
    const { slot, member, rating } = assignment;
    const name = member?.display_name ?? slot.label;

    return (
        <div
            className={cn(
                'flex flex-col items-center',
                compact ? 'w-[3.75rem] sm:w-16' : 'w-16 sm:w-[4.25rem]',
            )}
        >
            <div className="relative">
                {isTop ? (
                    <Star
                        className="absolute -top-2 left-1/2 z-10 size-3.5 -translate-x-1/2 fill-amber-400 text-amber-300 drop-shadow sm:size-4"
                        aria-label="Điểm cao nhất"
                    />
                ) : null}
                <div
                    className={cn(
                        'overflow-hidden rounded-full bg-emerald-950 shadow-[0_4px_10px_rgba(0,0,0,0.35)]',
                        compact
                            ? 'size-10 sm:size-11'
                            : 'size-11 sm:size-[3.15rem]',
                        isTop
                            ? 'ring-2 ring-amber-400 ring-offset-1 ring-offset-[#166534]'
                            : 'ring-2 ring-white/90',
                    )}
                >
                    {member?.photo ? (
                        <img
                            src={member.photo}
                            alt={name}
                            className="h-full w-full object-cover object-top"
                        />
                    ) : (
                        <div className="flex h-full w-full items-center justify-center bg-linear-to-b from-emerald-600 to-emerald-950">
                            <User className="size-5 text-white/80 sm:size-6" />
                        </div>
                    )}
                </div>
            </div>

            {rating !== null ? (
                <p
                    className={cn(
                        'mt-1 leading-none font-black tabular-nums',
                        compact ? 'text-xs sm:text-sm' : 'text-sm sm:text-base',
                        isTop ? 'text-amber-300' : 'text-white',
                    )}
                >
                    {formatRating(rating)}
                </p>
            ) : (
                <span className="mt-1 h-3 sm:h-4" />
            )}

            <div className="mt-1 w-full rounded-sm bg-[#c8102e] px-0.5 py-0.5 text-center text-[8px] leading-tight font-extrabold tracking-wide text-white uppercase shadow-md sm:text-[10px]">
                {formatPitchName(name, compact ? 8 : 11)}
            </div>
        </div>
    );
}

export default function MatchPitch({
    clubName,
    clubLogo,
    opponent,
    players,
    substitutes,
}: {
    clubName: string;
    clubLogo: string;
    opponent: string;
    players: PitchAssignment[];
    substitutes: PitchAssignment[];
}) {
    const topRating = highestRating([...players, ...substitutes]);
    const filledSubs = substitutes.filter((row) => row.member);

    return (
        <div className="overflow-hidden rounded-2xl bg-[#0b3d1c] text-white shadow-xl">
            <div className="flex items-center justify-between px-4 pt-3 pb-1">
                <p className="text-lg font-black tracking-[0.2em] uppercase">
                    Đội hình
                </p>
                <p className="text-[11px] font-semibold tracking-widest uppercase opacity-80">
                    7 người
                </p>
            </div>

            <div
                className="relative mx-3 mb-3 aspect-[3/4] overflow-hidden rounded-xl border-2 border-white/70"
                style={{
                    backgroundImage: `
                        repeating-linear-gradient(
                            90deg,
                            rgba(255,255,255,0.05) 0 12.5%,
                            transparent 12.5% 25%
                        ),
                        linear-gradient(180deg, #2d9a4a 0%, #1f7a38 48%, #14632c 100%)
                    `,
                }}
            >
                <PitchMarkings />

                {players.map((assignment) => {
                    if (!assignment.slot.top || !assignment.slot.left) {
                        return null;
                    }

                    return (
                        <div
                            key={assignment.slot.value}
                            className="absolute -translate-x-1/2 -translate-y-1/2"
                            style={{
                                top: assignment.slot.top,
                                left: assignment.slot.left,
                            }}
                        >
                            <PitchPlayerCard
                                assignment={assignment}
                                isTop={
                                    topRating !== null &&
                                    assignment.rating === topRating
                                }
                            />
                        </div>
                    );
                })}
            </div>

            {filledSubs.length > 0 ? (
                <div className="border-t border-white/15 px-3 py-3">
                    <p className="mb-2 text-[11px] font-semibold tracking-widest uppercase opacity-80">
                        Dự bị
                    </p>
                    <div className="flex flex-wrap justify-center gap-x-3 gap-y-3">
                        {filledSubs.map((assignment) => (
                            <PitchPlayerCard
                                key={assignment.slot.value}
                                assignment={assignment}
                                compact
                                isTop={
                                    topRating !== null &&
                                    assignment.rating === topRating
                                }
                            />
                        ))}
                    </div>
                </div>
            ) : null}

            <div className="flex items-center justify-between gap-3 border-t border-white/15 px-4 py-3">
                <div className="flex min-w-0 flex-1 items-center gap-2">
                    <img
                        src={clubLogo}
                        alt={clubName}
                        className="size-9 rounded-full bg-white object-contain p-0.5"
                    />
                    <span className="truncate text-xs font-bold uppercase">
                        {clubName}
                    </span>
                </div>
                <span className="text-[11px] font-semibold tracking-widest uppercase opacity-80">
                    vs
                </span>
                <div className="min-w-0 flex-1 text-right">
                    <span className="truncate text-xs font-bold uppercase">
                        {opponent}
                    </span>
                </div>
            </div>
        </div>
    );
}
