<?php

namespace App\Enums;

enum MemberRole: string
{
    case Admin = 'admin';
    case Manager = 'manager';
    case Player = 'player';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Ban quản lý',
            self::Manager => 'HLV / Quản lý',
            self::Player => 'Cầu thủ',
        };
    }
}
