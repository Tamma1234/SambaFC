<?php

namespace App\Enums;

enum ArticleCategory: string
{
    case News = 'news';
    case MatchPreview = 'match_preview';
    case Announcement = 'announcement';

    public function label(): string
    {
        return match ($this) {
            self::News => 'Tin tức',
            self::MatchPreview => 'Trận đấu tiếp theo',
            self::Announcement => 'Thông báo',
        };
    }
}
