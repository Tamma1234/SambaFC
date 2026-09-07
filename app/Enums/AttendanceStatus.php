<?php

namespace App\Enums;

enum AttendanceStatus: string
{
    case Pending = 'pending';
    case Attending = 'attending';
    case Absent = 'absent';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Chưa xác nhận',
            self::Attending => 'Tham gia',
            self::Absent => 'Không tham gia',
        };
    }

    public function isAttending(): bool
    {
        return $this === self::Attending;
    }
}
