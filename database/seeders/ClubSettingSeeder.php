<?php

namespace Database\Seeders;

use App\Models\ClubSetting;
use Illuminate\Database\Seeder;

class ClubSettingSeeder extends Seeder
{
    public function run(): void
    {
        ClubSetting::query()->updateOrCreate(
            ['id' => 1],
            [
                'name' => 'Samba FC',
                'logo_path' => 'images/logo.png',
                'description' => 'Câu lạc bộ bóng đá Samba FC — cùng nhau ra sân, gắn kết và phát triển.',
                'founded_year' => '2020',
                'home_venue' => 'Sân bóng Samba',
                'contact_email' => 'hello@sambafc.local',
            ],
        );
    }
}
