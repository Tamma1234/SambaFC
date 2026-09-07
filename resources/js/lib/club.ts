import type { VariantProps } from 'class-variance-authority';
import type { badgeVariants } from '@/components/ui/badge';

type BadgeVariant = NonNullable<VariantProps<typeof badgeVariants>['variant']>;

export function formatKickoff(iso: string): string {
    return new Intl.DateTimeFormat('vi-VN', {
        weekday: 'short',
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(iso));
}

export function matchStatusVariant(status: string): BadgeVariant {
    switch (status) {
        case 'completed':
            return 'secondary';
        case 'cancelled':
            return 'destructive';
        default:
            return 'default';
    }
}

export function formatVnd(amount: number): string {
    return `${new Intl.NumberFormat('vi-VN').format(amount)} đ`;
}

export function formatRating(
    rating: number | string | null | undefined,
): string {
    if (rating === null || rating === undefined || rating === '') {
        return '';
    }

    const value = typeof rating === 'string' ? Number(rating) : rating;

    if (Number.isNaN(value)) {
        return '';
    }

    return Number.isInteger(value) ? String(value) : value.toFixed(1);
}

export function formatPitchName(name: string, max = 14): string {
    const upper = name.trim().toUpperCase();

    if (upper.length <= max) {
        return upper;
    }

    return `${upper.slice(0, max - 1)}…`;
}

export function defaultKickoffInput(): string {
    const date = new Date();
    date.setDate(date.getDate() + 1);
    date.setHours(19, 0, 0, 0);

    const pad = (value: number): string => String(value).padStart(2, '0');

    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
}
