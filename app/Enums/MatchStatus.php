<?php

namespace App\Enums;

enum MatchStatus: string
{
    case Scheduled = 'scheduled';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Sắp diễn ra',
            self::Completed => 'Đã đá',
            self::Cancelled => 'Hủy',
        };
    }
}
