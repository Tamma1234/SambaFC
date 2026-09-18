import { Head, router, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import ClubPage from '@/components/club-page';
import Heading from '@/components/heading';
import NativeSelect from '@/components/native-select';
import PaymentStatusFilter, {
    type PaymentStatusFilter as StatusFilter,
} from '@/components/payment-status-filter';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatVnd } from '@/lib/club';
import { dashboard } from '@/routes';
import { index as kitsIndex, update } from '@/routes/club/kits';
import type { ClubFundSummary, ClubKitPurchase, ClubOption } from '@/types';

type Props = {
    purchases: ClubKitPurchase[];
    statuses: ClubOption[];
    summary: ClubFundSummary;
    amount: number;
    title: string;
};

export default function KitsIndex({
    purchases,
    statuses,
    summary,
    amount,
    title,
}: Props) {
    const slug = usePage().props.currentTeam?.slug ?? '';
    const [savingId, setSavingId] = useState<number | null>(null);
    const [pendingStatuses, setPendingStatuses] = useState<
        Record<number, string>
    >({});
    const [statusFilter, setStatusFilter] = useState<StatusFilter>('all');

    const saveStatus = (row: ClubKitPurchase, status: string) => {
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

    const visiblePurchases = useMemo(
        () =>
            purchases.filter((row) => {
                if (statusFilter === 'all') {
                    return true;
                }

                return (pendingStatuses[row.id] ?? row.status) === statusFilter;
            }),
        [pendingStatuses, purchases, statusFilter],
    );

    return (
        <ClubPage>
            <Head title="Mua đồ" />

            <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <Heading
                    variant="small"
                    title="Mua đồ"
                    description={`${title} · ${formatVnd(amount)} / người. Đánh dấu từng thành viên đã đóng hoặc chưa đóng.`}
                />
                <PaymentStatusFilter
                    value={statusFilter}
                    onChange={setStatusFilter}
                />
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
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
                            {summary.paid_count} thành viên
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
                            {summary.unpaid_count} thành viên
                        </p>
                    </CardContent>
                </Card>
            </div>

            {purchases.length === 0 ? (
                <p className="text-muted-foreground rounded-xl border py-10 text-center">
                    Chưa có thành viên đang hoạt động để theo dõi mua đồ.
                </p>
            ) : visiblePurchases.length === 0 ? (
                <p className="text-muted-foreground rounded-xl border py-10 text-center">
                    Không có thành viên với trạng thái này.
                </p>
            ) : (
                <div className="overflow-hidden rounded-xl border">
                    <table className="w-full text-left text-sm">
                        <thead className="bg-muted/50 text-muted-foreground">
                            <tr>
                                <th className="px-4 py-3 font-medium">STT</th>
                                <th className="px-4 py-3 font-medium">
                                    Thành viên
                                </th>
                                <th className="px-4 py-3 font-medium">
                                    Số tiền
                                </th>
                                <th className="px-4 py-3 font-medium">
                                    Trạng thái
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {visiblePurchases.map((row, index) => {
                                const currentStatus =
                                    pendingStatuses[row.id] ?? row.status;

                                return (
                                    <tr key={row.id} className="border-t">
                                        <td className="text-muted-foreground px-4 py-3">
                                            {index + 1}
                                        </td>
                                        <td className="px-4 py-3 font-medium">
                                            {row.member_name ?? '—'}
                                        </td>
                                        <td className="px-4 py-3 font-medium">
                                            {formatVnd(row.amount)}
                                        </td>
                                        <td className="px-4 py-3">
                                            <NativeSelect
                                                aria-label={`Trạng thái của ${row.member_name ?? 'thành viên'}`}
                                                value={currentStatus}
                                                disabled={savingId === row.id}
                                                onChange={(event) =>
                                                    saveStatus(
                                                        row,
                                                        event.target.value,
                                                    )
                                                }
                                                className={
                                                    currentStatus === 'paid'
                                                        ? 'w-auto min-w-36 border-emerald-500/40 bg-emerald-500/10'
                                                        : 'w-auto min-w-36 border-amber-500/40 bg-amber-500/10'
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
                                        </td>
                                    </tr>
                                );
                            })}
                        </tbody>
                    </table>
                </div>
            )}
        </ClubPage>
    );
}

KitsIndex.layout = (props: { currentTeam?: { slug: string } | null }) => ({
    breadcrumbs: [
        {
            title: 'Bảng điều khiển',
            href: props.currentTeam ? dashboard(props.currentTeam.slug) : '/',
        },
        {
            title: 'Mua đồ',
            href: props.currentTeam ? kitsIndex(props.currentTeam.slug) : '/',
        },
    ],
});
