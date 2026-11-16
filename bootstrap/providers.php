<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\ModulesServiceProvider;

return [
    AppServiceProvider::class,
    AdminPanelProvider::class,
    ModulesServiceProvider::class,
];
