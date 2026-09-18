import { Button } from '@/components/ui/button';

export type PaymentStatusFilter = 'all' | 'paid' | 'unpaid';

const options: Array<{ value: PaymentStatusFilter; label: string }> = [
    { value: 'all', label: 'Tất cả' },
    { value: 'unpaid', label: 'Chưa đóng' },
    { value: 'paid', label: 'Đã đóng' },
];

export default function PaymentStatusFilter({
    value,
    onChange,
}: {
    value: PaymentStatusFilter;
    onChange: (value: PaymentStatusFilter) => void;
}) {
    return (
        <div className="flex flex-wrap gap-2">
            {options.map((option) => (
                <Button
                    key={option.value}
                    type="button"
                    size="sm"
                    variant={value === option.value ? 'default' : 'outline'}
                    onClick={() => onChange(option.value)}
                >
                    {option.label}
                </Button>
            ))}
        </div>
    );
}
