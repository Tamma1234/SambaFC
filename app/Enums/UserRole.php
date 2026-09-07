<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Member = 'member';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Quản trị',
            self::Member => 'Thành viên',
        };
    }

    public function isAdmin(): bool
    {
        return $this === self::Admin;
    }
}
