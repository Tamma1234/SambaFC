<?php

namespace App\Enums;

enum PitchSlot: string
{
    case Gk = 'gk';
    case Lb = 'lb';
    case Cb = 'cb';
    case Rb = 'rb';
    case Lm = 'lm';
    case Rm = 'rm';
    case St = 'st';
    case Sub1 = 'sub_1';
    case Sub2 = 'sub_2';
    case Sub3 = 'sub_3';
    case Sub4 = 'sub_4';
    case Sub5 = 'sub_5';
    case Sub6 = 'sub_6';
    case Sub7 = 'sub_7';
    case Sub8 = 'sub_8';
    case Sub9 = 'sub_9';
    case Sub10 = 'sub_10';
    case Sub11 = 'sub_11';
    case Sub12 = 'sub_12';
    case Sub13 = 'sub_13';
    case Sub14 = 'sub_14';
    case Sub15 = 'sub_15';

    public function label(): string
    {
        return match ($this) {
            self::Gk => 'Thủ môn',
            self::Lb => 'Hậu vệ trái',
            self::Cb => 'Trung vệ',
            self::Rb => 'Hậu vệ phải',
            self::Lm => 'Tiền vệ trái',
            self::Rm => 'Tiền vệ phải',
            self::St => 'Tiền đạo',
            default => 'Thay '.str_replace('sub_', '', $this->value),
        };
    }

    public function groupLabel(): string
    {
        return match ($this) {
            self::Gk => 'Thủ Môn',
            self::Lb, self::Cb, self::Rb => 'Hậu Vệ',
            self::Lm, self::Rm => 'Tiền Vệ',
            self::St => 'Tiền Đạo',
            default => 'Thay người',
        };
    }

    public function isStarter(): bool
    {
        return ! str_starts_with($this->value, 'sub_');
    }

    public function pitchTop(): ?string
    {
        return match ($this) {
            self::St => '13%',
            self::Lm, self::Rm => '36%',
            self::Lb, self::Cb, self::Rb => '61%',
            self::Gk => '85%',
            default => null,
        };
    }

    public function pitchLeft(): ?string
    {
        return match ($this) {
            self::Lm, self::Lb => '20%',
            self::St, self::Cb, self::Gk => '50%',
            self::Rm, self::Rb => '80%',
            default => null,
        };
    }

    /**
     * @return array<int, self>
     */
    public static function starters(): array
    {
        return [
            self::Gk,
            self::Lb,
            self::Cb,
            self::Rb,
            self::Lm,
            self::Rm,
            self::St,
        ];
    }

    /**
     * @return array<int, self>
     */
    public static function substitutes(): array
    {
        return array_values(array_filter(
            self::cases(),
            static fn (self $slot): bool => ! $slot->isStarter(),
        ));
    }
}
