<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum NavigationGroup: string implements HasLabel
{
    case Content = 'content';
    case Customers = 'customers';
    case System = 'system';

    public function getLabel(): string
    {
        return match ($this) {
            self::Content => 'Nội dung',
            self::Customers => 'Khách hàng',
            self::System => 'Hệ thống',
        };
    }
}
