<?php

namespace App\Enums;

enum ExpenseCategory: string
{
    case Pitch = 'pitch';
    case Ball = 'ball';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Pitch => 'Tiền sân',
            self::Ball => 'Mua bóng',
            self::Other => 'Khác',
        };
    }
}
