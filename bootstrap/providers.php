<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\ModulesServiceProvider;
use App\Providers\ThemeServiceProvider;

return [
    AppServiceProvider::class,
    AdminPanelProvider::class,
    ModulesServiceProvider::class,
    ThemeServiceProvider::class,
];
