<?php

namespace App\Enums;

enum FundStatus: string
{
    case Unpaid = 'unpaid';
    case Paid = 'paid';

    public function label(): string
    {
        return match ($this) {
            self::Unpaid => 'Chưa đóng',
            self::Paid => 'Đã đóng',
        };
    }

    public function isPaid(): bool
    {
        return $this === self::Paid;
    }
}
