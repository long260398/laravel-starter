<?php

namespace Tests\Feature;

use App\Actions\DeleteMedia;
use App\Exceptions\MediaInUseException;
use App\Filament\Pages\Settings;
use App\Filament\Resources\Banners\Pages\EditBanner;
use App\Models\Banner;
use App\Models\Media;
use App\Models\MediaUsage;
use App\Models\Post;
use App\Models\Setting;
use App\Models\User;
use App\Modules\Services\Filament\Resources\Services\Pages\EditService;
use App\Modules\Services\Models\Service;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class MediaPickerTest extends TestCase
{
    use RefreshDatabase;

    public function test_choosing_existing_media_for_settings_logo_registers_usage_without_duplicating_the_file(): void
    {
        Storage::fake(config('media.disk'));
        $this->actingAs(User::factory()->admin()->create());

        $media = Media::factory()->create(['mime_type' => 'image/jpeg']);
        Storage::disk(config('media.disk'))->put($media->path, 'fake-image-content');
        $filesBefore = count(Storage::disk(config('media.disk'))->allFiles());

        Livewire::test(Settings::class)
            ->fillForm(['site_name' => 'Spa ABC'])
            ->callAction(TestAction::make('chooseFromLibrary')->schemaComponent('logo'), data: ['path' => $media->path])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame($media->path, Setting::get('logo'));
        $this->assertTrue(
            MediaUsage::query()->where('media_id', $media->id)->where('field', 'logo')->exists(),
        );
        $this->assertSame($filesBefore, count(Storage::disk(config('media.disk'))->allFiles()));
    }

    public function test_same_media_used_for_logo_and_a_post_creates_two_usages_and_blocks_deletion(): void
    {
        $media = Media::factory()->create(['mime_type' => 'image/jpeg']);

        Setting::set('logo', $media->path);
        Post::factory()->create(['featured_image' => $media->path]);

        $this->assertSame(2, $media->usages()->count());
        $this->assertTrue($media->isUsed());

        $this->expectException(MediaInUseException::class);

        app(DeleteMedia::class)->handle($media);
    }

    public function test_saving_settings_with_a_path_not_in_the_media_library_is_rejected(): void
    {
        Storage::fake(config('media.disk'));
        Storage::disk(config('media.disk'))->put('settings/not-in-library.jpg', 'fake-image-content');

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(Settings::class)
            ->fillForm(['site_name' => 'Spa ABC'])
            ->set('data.logo', ['x' => 'settings/not-in-library.jpg'])
            ->call('save')
            ->assertHasFormErrors(['logo']);

        $this->assertNull(Setting::get('logo'));
    }

    public function test_saving_settings_keeps_an_untracked_existing_value_unchanged(): void
    {
        Storage::fake(config('media.disk'));
        Storage::disk(config('media.disk'))->put('settings/legacy.png', 'fake-image-content');

        // Dữ liệu giả lập "đã có từ trước", chưa từng qua RegisterMediaUsage
        // (vd dữ liệu cũ trước khi có Media Library) — tạo thẳng qua
        // Setting::query()->create(), không qua Setting::set() để không tự
        // đăng ký vào kho media.
        Setting::query()->create(['key' => 'logo', 'value' => 'settings/legacy.png']);

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(Settings::class)
            ->fillForm(['site_name' => 'Spa ABC'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('settings/legacy.png', Setting::get('logo'));
    }

    public function test_saving_settings_with_a_different_untracked_path_is_rejected(): void
    {
        Storage::fake(config('media.disk'));
        Storage::disk(config('media.disk'))->put('settings/legacy.png', 'fake-image-content');
        Storage::disk(config('media.disk'))->put('settings/other-legacy.png', 'fake-image-content');

        Setting::query()->create(['key' => 'logo', 'value' => 'settings/legacy.png']);

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(Settings::class)
            ->fillForm(['site_name' => 'Spa ABC'])
            ->set('data.logo', ['x' => 'settings/other-legacy.png'])
            ->call('save')
            ->assertHasFormErrors(['logo']);
    }

    public function test_services_module_can_choose_existing_media_via_the_core_media_picker(): void
    {
        Storage::fake(config('media.disk'));
        $this->actingAs(User::factory()->create());

        $service = Service::factory()->create();
        $media = Media::factory()->create(['mime_type' => 'image/jpeg']);
        Storage::disk(config('media.disk'))->put($media->path, 'fake-image-content');

        Livewire::test(EditService::class, ['record' => $service->getRouteKey()])
            ->callAction(TestAction::make('chooseFromLibrary')->schemaComponent('image'), data: ['path' => $media->path])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame($media->path, $service->refresh()->image);
    }

    public function test_banner_page_handles_two_separate_media_pickers_independently(): void
    {
        Storage::fake(config('media.disk'));
        $this->actingAs(User::factory()->create());

        $banner = Banner::factory()->create();
        $image = Media::factory()->create(['mime_type' => 'image/jpeg']);
        $imageMobile = Media::factory()->create(['mime_type' => 'image/jpeg']);
        Storage::disk(config('media.disk'))->put($image->path, 'fake-image-content');
        Storage::disk(config('media.disk'))->put($imageMobile->path, 'fake-image-content');

        Livewire::test(EditBanner::class, ['record' => $banner->getRouteKey()])
            ->callAction(TestAction::make('chooseFromLibrary')->schemaComponent('image'), data: ['path' => $image->path])
            ->callAction(TestAction::make('chooseFromLibrary')->schemaComponent('image_mobile'), data: ['path' => $imageMobile->path])
            ->call('save')
            ->assertHasNoFormErrors();

        $banner->refresh();
        $this->assertSame($image->path, $banner->image);
        $this->assertSame($imageMobile->path, $banner->image_mobile);
    }
}
