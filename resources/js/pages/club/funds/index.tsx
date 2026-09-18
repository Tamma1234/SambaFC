import { Form, Head, Link, router, usePage } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import ClubPage from '@/components/club-page';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import NativeSelect from '@/components/native-select';
import PaymentStatusFilter, {
    type PaymentStatusFilter as StatusFilter,
} from '@/components/payment-status-filter';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
import { formatVnd } from '@/lib/club';
import { dashboard } from '@/routes';
import {
    destroy,
    index as fundsIndex,
    store,
    update,
} from '@/routes/club/funds';
import { destroy as destroyGroup } from '@/routes/club/funds/groups';
import { index as expensesIndex } from '@/routes/club/expenses';
import type {
    ClubFundContribution,
    ClubFundGroup,
    ClubFundSummary,
    ClubOption,
} from '@/types';

type Props = {
    groups: ClubFundGroup[];
    statuses: ClubOption[];
    amounts: ClubOption[];
    summary: ClubFundSummary;
};

function groupAmountLabel(group: ClubFundGroup): string {
    if (group.amount_min === group.amount_max) {
        return `${formatVnd(group.amount_min)} / người`;
    }

    return `${formatVnd(group.amount_min)} – ${formatVnd(group.amount_max)} / người`;
}

function amountChoices(amounts: ClubOption[], current: number): ClubOption[] {
    const value = String(current);

    if (amounts.some((option) => option.value === value)) {
        return amounts;
    }

    return [...amounts, { value, label: formatVnd(current) }].sort(
        (first, second) => Number(first.value) - Number(second.value),
    );
}

export default function FundsIndex({
    groups,
    statuses,
    amounts,
    summary,
}: Props) {
    const slug = usePage().props.currentTeam?.slug ?? '';
    const [creating, setCreating] = useState(false);
    const [savingId, setSavingId] = useState<number | null>(null);
    const [pendingStatuses, setPendingStatuses] = useState<
        Record<number, string>
    >({});
    const [pendingAmounts, setPendingAmounts] = useState<
        Record<number, number>
    >({});
    const [deleting, setDeleting] = useState<ClubFundContribution | null>(null);
    const [deletingGroup, setDeletingGroup] = useState<ClubFundGroup | null>(
        null,
    );
    const [statusFilter, setStatusFilter] = useState<StatusFilter>('all');

    const saveStatus = (row: ClubFundContribution, status: string) => {
        if (status === (pendingStatuses[row.id] ?? row.status)) {
            return;
        }

        setPendingStatuses((current) => ({
            ...current,
            [row.id]: status,
        }));

        router.visit(update([slug, row.id]), {
            data: { status },
            preserveScroll: true,
            onStart: () => setSavingId(row.id),
            onFinish: () => {
                setSavingId(null);
                setPendingStatuses((current) => {
                    const next = { ...current };
                    delete next[row.id];

                    return next;
                });
            },
        });
    };

    const saveAmount = (row: ClubFundContribution, amount: number) => {
        if (amount === (pendingAmounts[row.id] ?? row.amount)) {
            return;
        }

        setPendingAmounts((current) => ({
            ...current,
            [row.id]: amount,
        }));

        router.visit(update([slug, row.id]), {
            data: { amount },
            preserveScroll: true,
            onStart: () => setSavingId(row.id),
            onFinish: () => {
                setSavingId(null);
                setPendingAmounts((current) => {
                    const next = { ...current };
                    delete next[row.id];

                    return next;
                });
            },
        });
    };

    const visibleGroups = groups
        .map((group) => ({
            ...group,
            contributions: group.contributions.filter((row) => {
                if (statusFilter === 'all') {
                    return true;
                }

                return (pendingStatuses[row.id] ?? row.status) === statusFilter;
            }),
        }))
        .filter((group) => group.contributions.length > 0);

    return (
        <ClubPage>
            <Head title="Quỹ đội" />

            <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <Heading
                    variant="small"
                    title="Quỹ đội"
                    description="Mỗi khoản quỹ áp dụng cho toàn đội. Đánh dấu từng người đã đóng hoặc chưa đóng."
                />
                <div className="flex flex-wrap items-center gap-2">
                    <PaymentStatusFilter
                        value={statusFilter}
                        onChange={setStatusFilter}
                    />
                    <Button onClick={() => setCreating(true)}>
                        <Plus />
                        Thêm quỹ
                    </Button>
                </div>
            </div>

            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <Card>
                    <CardHeader>
                        <CardTitle className="text-sm font-medium">
                            Đã đóng
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="text-2xl font-semibold">
                            {formatVnd(summary.paid_total)}
                        </div>
                        <p className="text-muted-foreground mt-1 text-xs">
                            {summary.paid_count} khoản
                        </p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <CardTitle className="text-sm font-medium">
                            Chưa đóng
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="text-2xl font-semibold">
                            {formatVnd(summary.unpaid_total)}
                        </div>
                        <p className="text-muted-foreground mt-1 text-xs">
                            {summary.unpaid_count} khoản
                        </p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <CardTitle className="text-sm font-medium">
                            Đã chi
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="text-2xl font-semibold">
                            {formatVnd(summary.expense_total)}
                        </div>
                        <p className="text-muted-foreground mt-1 text-xs">
                            {summary.expense_count} khoản · tháng này{' '}
                            {formatVnd(summary.expense_this_month)}
                        </p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <CardTitle className="text-sm font-medium">
                            Còn lại
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="text-2xl font-semibold">
                            {formatVnd(summary.balance)}
                        </div>
                        <p className="text-muted-foreground mt-1 text-xs">
                            <Link
                                href={expensesIndex(slug)}
                                className="hover:underline"
                            >
                                Xem chi tiêu
                            </Link>
                        </p>
                    </CardContent>
                </Card>
            </div>

            {groups.length === 0 ? (
                <p className="text-muted-foreground rounded-xl border py-10 text-center">
                    Chưa có quỹ. Thêm quỹ để toàn đội cùng đóng.
                </p>
            ) : visibleGroups.length === 0 ? (
                <p className="text-muted-foreground rounded-xl border py-10 text-center">
                    Không có thành viên với trạng thái này.
                </p>
            ) : (
                <div className="space-y-6">
                    {visibleGroups.map((group) => (
                        <section
                            key={group.group_key ?? group.title}
                            className="overflow-hidden rounded-xl border"
                        >
                            <div className="flex flex-col gap-3 border-b p-4 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <h2 className="font-semibold">
                                        {group.title}
                                    </h2>
                                    <p className="text-muted-foreground text-sm">
                                        {groupAmountLabel(group)} ·{' '}
                                        {group.paid_count} đã đóng ·{' '}
                                        {group.unpaid_count} chưa đóng
                                    </p>
                                </div>
                                {group.group_key ? (
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() => setDeletingGroup(group)}
                                    >
                                        <Trash2 />
                                        Xóa quỹ
                                    </Button>
                                ) : null}
                            </div>
                            <table className="w-full text-left text-sm">
                                <thead className="bg-muted/50 text-muted-foreground">
                                    <tr>
                                        <th className="px-4 py-3 font-medium">
                                            STT
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Thành viên
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Số tiền
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Trạng thái
                                        </th>
                                        <th className="px-4 py-3 font-medium" />
                                    </tr>
                                </thead>
                                <tbody>
                                    {group.contributions.map((row, index) => {
                                        const currentStatus =
                                            pendingStatuses[row.id] ??
                                            row.status;
                                        const currentAmount =
                                            pendingAmounts[row.id] ??
                                            row.amount;

                                        return (
                                            <tr
                                                key={row.id}
                                                className="border-t"
                                            >
                                                <td className="text-muted-foreground px-4 py-3">
                                                    {index + 1}
                                                </td>
                                                <td className="px-4 py-3 font-medium">
                                                    {row.member_name ?? '—'}
                                                </td>
                                                <td className="px-4 py-3">
                                                    <NativeSelect
                                                        aria-label={`Số tiền của ${row.member_name ?? 'thành viên'}`}
                                                        value={String(
                                                            currentAmount,
                                                        )}
                                                        disabled={
                                                            savingId === row.id
                                                        }
                                                        onChange={(event) =>
                                                            saveAmount(
                                                                row,
                                                                Number(
                                                                    event.target
                                                                        .value,
                                                                ),
                                                            )
                                                        }
                                                        className="w-auto min-w-32"
                                                    >
                                                        {amountChoices(
                                                            amounts,
                                                            currentAmount,
                                                        ).map((option) => (
                                                            <option
                                                                key={
                                                                    option.value
                                                                }
                                                                value={
                                                                    option.value
                                                                }
                                                            >
                                                                {option.label}
                                                            </option>
                                                        ))}
                                                    </NativeSelect>
                                                </td>
                                                <td className="px-4 py-3">
                                                    <NativeSelect
                                                        aria-label={`Trạng thái của ${row.member_name ?? 'thành viên'}`}
                                                        value={currentStatus}
                                                        disabled={
                                                            savingId === row.id
                                                        }
                                                        onChange={(event) =>
                                                            saveStatus(
                                                                row,
                                                                event.target
                                                                    .value,
                                                            )
                                                        }
                                                        className={
                                                            currentStatus ===
                                                            'paid'
                                                                ? 'w-auto min-w-36 border-emerald-500/40 bg-emerald-500/10'
                                                                : 'w-auto min-w-36 border-amber-500/40 bg-amber-500/10'
                                                        }
                                                    >
                                                        {statuses.map(
                                                            (status) => (
                                                                <option
                                                                    key={
                                                                        status.value
                                                                    }
                                                                    value={
                                                                        status.value
                                                                    }
                                                                >
                                                                    {
                                                                        status.label
                                                                    }
                                                                </option>
                                                            ),
                                                        )}
                                                    </NativeSelect>
                                                </td>
                                                <td className="px-4 py-3">
                                                    <div className="flex justify-end gap-1">
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            onClick={() =>
                                                                setDeleting(row)
                                                            }
                                                        >
                                                            <Trash2 />
                                                            <span className="sr-only">
                                                                Xóa
                                                            </span>
                                                        </Button>
                                                    </div>
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </section>
                    ))}
                </div>
            )}

            <CreateFundDialog
                open={creating}
                onOpenChange={setCreating}
                slug={slug}
                amounts={amounts}
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
                                        <DialogTitle>
                                            Xóa khoản đóng?
                                        </DialogTitle>
                                        <DialogDescription>
                                            Xóa khoản của{' '}
                                            {deleting.member_name ??
                                                'thành viên'}{' '}
                                            ({formatVnd(deleting.amount)}).
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

            <Dialog
                open={deletingGroup !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setDeletingGroup(null);
                    }
                }}
            >
                <DialogContent>
                    {deletingGroup?.group_key ? (
                        <Form
                            key={deletingGroup.group_key}
                            {...destroyGroup.form([
                                slug,
                                deletingGroup.group_key,
                            ])}
                            onSuccess={() => setDeletingGroup(null)}
                        >
                            {({ processing }) => (
                                <>
                                    <DialogHeader>
                                        <DialogTitle>Xóa quỹ?</DialogTitle>
                                        <DialogDescription>
                                            Xóa {deletingGroup.title} của toàn
                                            đội.
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
                                            Xóa quỹ
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

function CreateFundDialog({
    open,
    onOpenChange,
    slug,
    amounts,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    slug: string;
    amounts: ClubOption[];
}) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-lg">
                <Form
                    key={String(open)}
                    {...store.form(slug)}
                    className="space-y-4"
                    onSuccess={() => onOpenChange(false)}
                >
                    {({ errors, processing }) => (
                        <>
                            <DialogHeader>
                                <DialogTitle>Thêm quỹ</DialogTitle>
                                <DialogDescription>
                                    Tất cả thành viên đang hoạt động được thêm
                                    vào quỹ với số tiền mặc định. Sau đó có thể
                                    đổi từng người: 300.000, 400.000 hoặc
                                    500.000 đ.
                                </DialogDescription>
                            </DialogHeader>

                            <div className="grid gap-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="title">Tên quỹ</Label>
                                    <Input
                                        id="title"
                                        name="title"
                                        required
                                        placeholder="Ví dụ: Quỹ tháng 9"
                                    />
                                    <InputError message={errors.title} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="amount">
                                        Số tiền mặc định mỗi người
                                    </Label>
                                    <NativeSelect
                                        id="amount"
                                        name="amount"
                                        required
                                        defaultValue={
                                            amounts[0]?.value ?? '300000'
                                        }
                                    >
                                        {amounts.map((option) => (
                                            <option
                                                key={option.value}
                                                value={option.value}
                                            >
                                                {option.label}
                                            </option>
                                        ))}
                                    </NativeSelect>
                                    <InputError message={errors.amount} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="note">Ghi chú</Label>
                                    <Textarea id="note" name="note" />
                                    <InputError message={errors.note} />
                                </div>
                            </div>

                            <DialogFooter>
                                <DialogClose asChild>
                                    <Button variant="secondary">Hủy</Button>
                                </DialogClose>
                                <Button type="submit" disabled={processing}>
                                    Tạo quỹ
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

FundsIndex.layout = (props: { currentTeam?: { slug: string } | null }) => ({
    breadcrumbs: [
        {
            title: 'Bảng điều khiển',
            href: props.currentTeam ? dashboard(props.currentTeam.slug) : '/',
        },
        {
            title: 'Quỹ đội',
            href: props.currentTeam ? fundsIndex(props.currentTeam.slug) : '/',
        },
    ],
});
