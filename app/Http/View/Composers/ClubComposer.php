<?php

namespace App\Http\View\Composers;

use App\Models\ClubSetting;
use Illuminate\View\View;

class ClubComposer
{
    public function compose(View $view): void
    {
        $view->with('club', ClubSetting::current());
    }
}
