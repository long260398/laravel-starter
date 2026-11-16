<?php

namespace App\Modules\FloatingContact\Filament\Pages;

use App\Actions\SaveSettings;
use App\Enums\NavigationGroup;
use App\Models\Setting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions as FormActions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class FloatingContactSettings extends Page
{
    /**
     * Tên field trong form KHÔNG chứa "." — Filament coi "." trong tên field
     * là dấu phân cấp state lồng nhau (nested array), không phải literal
     * trong key. Map sang key Setting thật (tiền tố `floating_contact.`) ở
     * mount()/save().
     *
     * @var array<int, string>
     */
    private const array FIELDS = ['enable_call', 'enable_zalo', 'enable_messenger', 'position'];

    private const string SETTING_PREFIX = 'floating_contact.';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::System;

    /**
     * @var array<string, mixed>
     */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function getNavigationLabel(): string
    {
        return 'Nút liên hệ nổi';
    }

    public function getTitle(): string
    {
        return 'Nút liên hệ nổi';
    }

    public function mount(): void
    {
        $defaults = [
            'enable_call' => false,
            'enable_zalo' => false,
            'enable_messenger' => false,
            'position' => 'right',
        ];

        $this->form->fill(
            collect(self::FIELDS)->mapWithKeys(
                fn (string $field): array => [$field => Setting::get(self::SETTING_PREFIX.$field, $defaults[$field])],
            )->all(),
        );
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Toggle::make('enable_call')
                    ->label('Hiện nút Gọi điện')
                    ->helperText('Lấy số từ Hotline ở trang Cài đặt website. Thiếu hotline sẽ tự ẩn dù bật ở đây.'),
                Toggle::make('enable_zalo')
                    ->label('Hiện nút Zalo')
                    ->helperText('Lấy từ Zalo ở trang Cài đặt website. Thiếu hoặc sai định dạng sẽ tự ẩn.'),
                Toggle::make('enable_messenger')
                    ->label('Hiện nút Messenger')
                    ->helperText('Lấy từ Messenger ở trang Cài đặt website. Thiếu hoặc sai định dạng sẽ tự ẩn.'),
                Select::make('position')
                    ->label('Vị trí')
                    ->options([
                        'right' => 'Phải',
                        'left' => 'Trái',
                    ])
                    ->default('right')
                    ->required(),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getFormContentComponent(),
            ]);
    }

    public function getFormContentComponent(): Component
    {
        return Form::make([EmbeddedSchema::make('form')])
            ->id('form')
            ->livewireSubmitHandler('save')
            ->footer([
                FormActions::make($this->getFormActions())
                    ->key('form-actions'),
            ]);
    }

    /**
     * @return array<Action>
     */
    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Lưu')
                ->submit('save'),
        ];
    }

    public function save(): void
    {
        $state = collect($this->form->getState())
            ->mapWithKeys(fn (mixed $value, string $field): array => [self::SETTING_PREFIX.$field => $value])
            ->all();

        app(SaveSettings::class)->handle($state);

        Notification::make()
            ->title('Đã lưu cài đặt.')
            ->success()
            ->send();
    }
}
