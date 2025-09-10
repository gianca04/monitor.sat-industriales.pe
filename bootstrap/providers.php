<?php

use App\Models\Photo;
use App\Observers\PhotoObserver;

Photo::observe(PhotoObserver::class);

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\Filament\DashboardPanelProvider::class,
];
