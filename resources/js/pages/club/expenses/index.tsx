import { Form, Head, usePage } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { useMemo, useState } from 'react';
import ClubPage from '@/components/club-page';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import NativeSelect from '@/components/native-select';
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
import { formatDate, formatVnd, todayInput } from '@/lib/club';
import { dashboard } from '@/routes';
import {
    destroy,
    index as expensesIndex,
    store,
    update,
} from '@/routes/club/expenses';
import { index as fundsIndex } from '@/routes/club/funds';
import type {
    ClubExpense,
    ClubExpenseGroup,
    ClubFundSummary,
    ClubOption,
} from '@/types';

type Props = {
    groups: ClubExpenseGroup[];
    categories: ClubOption[];
    summary: ClubFundSummary;
};

export default function ExpensesIndex({ groups, categories, summary }: Props) {
    const slug = usePage().props.currentTeam?.slug ?? '';
    const [creating, setCreating] = useState(false);
    const [editing, setEditing] = useState<ClubExpense | null>(null);
    const [deleting, setDeleting] = useState<ClubExpense | null>(null);
    const [categoryFilter, setCategoryFilter] = useState('all');

    const visibleGroups = useMemo(
        () =>
            groups
                .map((group) => ({
                    ...group,
                    expenses:
                        categoryFilter === 'all'
                            ? group.expenses
                            : group.expenses.filter(
                                  (row) => row.category === categoryFilter,
                              ),
                }))
                .map((group) => ({
                    ...group,
                    total: group.expenses.reduce(
                        (sum, row) => sum + row.amount,
                        0,
                    ),
                }))
                .filter((group) => group.expenses.length > 0),
        [categoryFilter, groups],
    );

    return (
        <ClubPage>
            <Head title="Chi tiêu" />

            <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <Heading
                    variant="small"
                    title="Chi tiêu"
                    description="Các khoản chi từ quỹ đội: tiền sân, mua bóng và chi phí khác."
                />
                <div className="flex flex-wrap items-center gap-2">
                    <div className="flex flex-wrap gap-2">
                        <Button
                            type="button"
                            size="sm"
                            variant={
                                categoryFilter === 'all' ? 'default' : 'outline'
                            }
                            onClick={() => setCategoryFilter('all')}
                        >
                            Tất cả
                        </Button>
                        {categories.map((category) => (
                            <Button
                                key={category.value}
                                type="button"
                                size="sm"
                                variant={
                                    categoryFilter === category.value
                                        ? 'default'
                                        : 'outline'
                                }
                                onClick={() =>
                                    setCategoryFilter(category.value)
                                }
                            >
                                {category.label}
                            </Button>
                        ))}
                    </div>
                    <Button onClick={() => setCreating(true)}>
                        <Plus />
                        Thêm khoản chi
                    </Button>
                </div>
            </div>

            <div className="grid gap-4 sm:grid-cols-3">
                <Card>
                    <CardHeader>
                        <CardTitle className="text-sm font-medium">
                            Chi tháng này
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="text-2xl font-semibold">
                            {formatVnd(summary.expense_this_month)}
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <CardTitle className="text-sm font-medium">
                            Tổng đã chi
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="text-2xl font-semibold">
                            {formatVnd(summary.expense_total)}
                        </div>
                        <p className="text-muted-foreground mt-1 text-xs">
                            {summary.expense_count} khoản
                        </p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <CardTitle className="text-sm font-medium">
                            Quỹ còn lại
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="text-2xl font-semibold">
                            {formatVnd(summary.balance)}
                        </div>
                        <p className="text-muted-foreground mt-1 text-xs">
                            Từ {formatVnd(summary.paid_total)} đã đóng
                        </p>
                    </CardContent>
                </Card>
            </div>

            {groups.length === 0 ? (
                <p className="text-muted-foreground rounded-xl border py-10 text-center">
                    Chưa có khoản chi. Thêm tiền sân, mua bóng hoặc chi phí
                    khác.
                </p>
            ) : visibleGroups.length === 0 ? (
                <p className="text-muted-foreground rounded-xl border py-10 text-center">
                    Không có khoản chi với loại này.
                </p>
            ) : (
                <div className="space-y-6">
                    {visibleGroups.map((group) => (
                        <section
                            key={group.month_key}
                            className="overflow-hidden rounded-xl border"
                        >
                            <div className="flex items-center justify-between gap-3 border-b p-4">
                                <h2 className="font-semibold">
                                    {group.month_label}
                                </h2>
                                <p className="text-sm font-medium">
                                    {formatVnd(group.total)}
                                </p>
                            </div>
                            <div className="divide-y">
                                {group.expenses.map((row) => (
                                    <div
                                        key={row.id}
                                        className="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between"
                                    >
                                        <div>
                                            <p className="font-medium">
                                                {row.title}
                                            </p>
                                            <p className="text-muted-foreground text-sm">
                                                {row.category_label} ·{' '}
                                                {formatDate(row.spent_at)}
                                                {row.note
                                                    ? ` · ${row.note}`
                                                    : ''}
                                            </p>
                                        </div>
                                        <div className="flex items-center justify-between gap-3">
                                            <p className="font-semibold">
                                                {formatVnd(row.amount)}
                                            </p>
                                            <div className="flex gap-1">
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    onClick={() =>
                                                        setEditing(row)
                                                    }
                                                >
                                                    <Pencil />
                                                    <span className="sr-only">
                                                        Sửa
                                                    </span>
                                                </Button>
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
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </section>
                    ))}
                </div>
            )}

            <ExpenseFormDialog
                open={creating}
                onOpenChange={setCreating}
                slug={slug}
                categories={categories}
            />

            <ExpenseFormDialog
                open={editing !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setEditing(null);
                    }
                }}
                slug={slug}
                expense={editing}
                categories={categories}
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
                                            Xóa khoản chi?
                                        </DialogTitle>
                                        <DialogDescription>
                                            {deleting.title} (
                                            {formatVnd(deleting.amount)}) sẽ bị
                                            xóa khỏi sổ chi tiêu.
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

function ExpenseFormDialog({
    open,
    onOpenChange,
    slug,
    expense,
    categories,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    slug: string;
    expense?: ClubExpense | null;
    categories: ClubOption[];
}) {
    const formProps = expense
        ? update.form([slug, expense.id])
        : store.form(slug);

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-lg">
                <Form
                    key={`${expense?.id ?? 'create'}-${String(open)}`}
                    {...formProps}
                    className="space-y-4"
                    onSuccess={() => onOpenChange(false)}
                >
                    {({ errors, processing }) => (
                        <>
                            <DialogHeader>
                                <DialogTitle>
                                    {expense
                                        ? 'Sửa khoản chi'
                                        : 'Thêm khoản chi'}
                                </DialogTitle>
                                <DialogDescription>
                                    Ghi lại khoản chi từ quỹ đội, ví dụ tiền sân
                                    hoặc mua bóng.
                                </DialogDescription>
                            </DialogHeader>

                            <div className="grid gap-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="title">Khoản chi</Label>
                                    <Input
                                        id="title"
                                        name="title"
                                        required
                                        placeholder="Ví dụ: Trả tiền sân tháng 9"
                                        defaultValue={expense?.title ?? ''}
                                    />
                                    <InputError message={errors.title} />
                                </div>
                                <div className="grid gap-2 sm:grid-cols-2 sm:gap-4">
                                    <div className="grid gap-2">
                                        <Label htmlFor="category">Loại</Label>
                                        <NativeSelect
                                            id="category"
                                            name="category"
                                            defaultValue={
                                                expense?.category ?? 'pitch'
                                            }
                                        >
                                            {categories.map((category) => (
                                                <option
                                                    key={category.value}
                                                    value={category.value}
                                                >
                                                    {category.label}
                                                </option>
                                            ))}
                                        </NativeSelect>
                                        <InputError message={errors.category} />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="spent_at">
                                            Ngày chi
                                        </Label>
                                        <Input
                                            id="spent_at"
                                            name="spent_at"
                                            type="date"
                                            required
                                            defaultValue={
                                                expense?.spent_at ??
                                                todayInput()
                                            }
                                        />
                                        <InputError message={errors.spent_at} />
                                    </div>
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="amount">Số tiền (đ)</Label>
                                    <Input
                                        id="amount"
                                        name="amount"
                                        type="number"
                                        min={0}
                                        required
                                        defaultValue={expense?.amount ?? ''}
                                    />
                                    <InputError message={errors.amount} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="note">Ghi chú</Label>
                                    <Textarea
                                        id="note"
                                        name="note"
                                        defaultValue={expense?.note ?? ''}
                                    />
                                    <InputError message={errors.note} />
                                </div>
                            </div>

                            <DialogFooter>
                                <DialogClose asChild>
                                    <Button variant="secondary">Hủy</Button>
                                </DialogClose>
                                <Button type="submit" disabled={processing}>
                                    {expense ? 'Lưu' : 'Thêm khoản chi'}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

ExpensesIndex.layout = (props: { currentTeam?: { slug: string } | null }) => ({
    breadcrumbs: [
        {
            title: 'Bảng điều khiển',
            href: props.currentTeam ? dashboard(props.currentTeam.slug) : '/',
        },
        {
            title: 'Quỹ đội',
            href: props.currentTeam ? fundsIndex(props.currentTeam.slug) : '/',
        },
        {
            title: 'Chi tiêu',
            href: props.currentTeam
                ? expensesIndex(props.currentTeam.slug)
                : '/',
        },
    ],
});
