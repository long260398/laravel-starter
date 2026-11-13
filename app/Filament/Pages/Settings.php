<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use BackedEnum;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions as FormActions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class Settings extends Page
{
    /**
     * @var array<int, string>
     */
    private const array KEYS = [
        'site_name', 'slogan', 'logo', 'favicon',
        'hotline', 'contact_email', 'address', 'working_hours', 'google_maps_url',
        'facebook_url', 'zalo_url', 'tiktok_url', 'youtube_url', 'messenger_url',
        'seo_title', 'seo_description', 'og_image',
        'ga_measurement_id', 'fb_pixel_id',
    ];

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

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
        return 'Cài đặt website';
    }

    public function getTitle(): string
    {
        return 'Cài đặt website';
    }

    public function mount(): void
    {
        $this->form->fill(
            collect(self::KEYS)->mapWithKeys(fn (string $key): array => [$key => Setting::get($key)])->all(),
        );
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Tabs::make('Cài đặt')
                    ->tabs([
                        Tab::make('Thông tin chung')
                            ->schema([
                                TextInput::make('site_name')
                                    ->label('Tên website')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('slogan')
                                    ->label('Slogan')
                                    ->maxLength(255),
                                FileUpload::make('logo')
                                    ->label('Logo')
                                    ->image()
                                    ->disk('public')
                                    ->directory('settings')
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                    ->maxSize(2048),
                                FileUpload::make('favicon')
                                    ->label('Favicon')
                                    ->disk('public')
                                    ->directory('settings')
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/vnd.microsoft.icon'])
                                    ->mimeTypeMap(['ico' => 'image/vnd.microsoft.icon'])
                                    ->maxSize(512),
                            ]),
                        Tab::make('Liên hệ')
                            ->schema([
                                TextInput::make('hotline')
                                    ->label('Hotline')
                                    ->tel()
                                    ->maxLength(32),
                                TextInput::make('contact_email')
                                    ->label('Email liên hệ')
                                    ->email()
                                    ->maxLength(255),
                                Textarea::make('address')
                                    ->label('Địa chỉ')
                                    ->maxLength(500),
                                TextInput::make('working_hours')
                                    ->label('Giờ làm việc')
                                    ->maxLength(255),
                                TextInput::make('google_maps_url')
                                    ->label('Link Google Maps')
                                    ->url()
                                    ->rule(self::googleMapsUrlRule())
                                    ->maxLength(2048),
                            ]),
                        Tab::make('Mạng xã hội')
                            ->schema([
                                TextInput::make('facebook_url')
                                    ->label('Facebook')
                                    ->url()
                                    ->startsWith('https://')
                                    ->maxLength(255),
                                TextInput::make('zalo_url')
                                    ->label('Zalo')
                                    ->url()
                                    ->startsWith('https://')
                                    ->maxLength(255),
                                TextInput::make('tiktok_url')
                                    ->label('TikTok')
                                    ->url()
                                    ->startsWith('https://')
                                    ->maxLength(255),
                                TextInput::make('youtube_url')
                                    ->label('YouTube')
                                    ->url()
                                    ->startsWith('https://')
                                    ->maxLength(255),
                                TextInput::make('messenger_url')
                                    ->label('Messenger')
                                    ->url()
                                    ->startsWith('https://')
                                    ->maxLength(255),
                            ]),
                        Tab::make('SEO mặc định')
                            ->schema([
                                TextInput::make('seo_title')
                                    ->label('Meta title')
                                    ->maxLength(255),
                                Textarea::make('seo_description')
                                    ->label('Meta description')
                                    ->maxLength(500),
                                FileUpload::make('og_image')
                                    ->label('Ảnh chia sẻ (OG image)')
                                    ->image()
                                    ->disk('public')
                                    ->directory('settings')
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                    ->maxSize(2048),
                            ]),
                        Tab::make('Tracking')
                            ->schema([
                                TextInput::make('ga_measurement_id')
                                    ->label('Google Analytics Measurement ID')
                                    ->regex('/^G-[A-Z0-9]+$/')
                                    ->maxLength(32)
                                    ->helperText('Định dạng: G-XXXXXXXXXX'),
                                TextInput::make('fb_pixel_id')
                                    ->label('Facebook Pixel ID')
                                    ->regex('/^\d+$/')
                                    ->maxLength(32)
                                    ->helperText('Chỉ gồm số.'),
                            ]),
                    ]),
            ]);
    }

    private static function googleMapsUrlRule(): Closure
    {
        return function () {
            return function (string $attribute, mixed $value, Closure $fail): void {
                if (blank($value)) {
                    return;
                }

                $scheme = parse_url($value, PHP_URL_SCHEME);
                $host = parse_url($value, PHP_URL_HOST);
                $path = parse_url($value, PHP_URL_PATH) ?? '';

                if ($scheme !== 'https') {
                    $fail('Link Google Maps phải dùng https.');

                    return;
                }

                $exactHosts = ['maps.app.goo.gl', 'maps.google.com'];
                $pathPrefixedHosts = ['google.com', 'www.google.com'];

                $isAllowed = in_array($host, $exactHosts, true)
                    || (in_array($host, $pathPrefixedHosts, true) && str_starts_with($path, '/maps'));

                if (! $isAllowed) {
                    $fail('Link Google Maps không hợp lệ.');
                }
            };
        };
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
        $data = $this->form->getState();

        foreach ($data as $key => $value) {
            Setting::set($key, $value);
        }

        Notification::make()
            ->title('Đã lưu cài đặt.')
            ->success()
            ->send();
    }
}
