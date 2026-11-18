<?php

namespace Tests\Feature;

use App\Enums\PublishStatus;
use App\Models\User;
use App\Modules\Faq\Filament\Resources\Faqs\Pages\CreateFaq;
use App\Modules\Faq\Filament\Resources\Faqs\Pages\EditFaq;
use App\Modules\Faq\Filament\Resources\Faqs\Pages\ListFaqs;
use App\Modules\Faq\Models\Faq;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FaqResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_faqs(): void
    {
        $response = $this->get('/admin/faqs');

        $response->assertRedirect('/admin/login');
    }

    public function test_can_list_faqs(): void
    {
        $this->actingAs(User::factory()->create());

        $faqs = Faq::factory()->count(3)->create();

        Livewire::test(ListFaqs::class)
            ->assertOk()
            ->assertCanSeeTableRecords($faqs);
    }

    public function test_can_create_faq(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(CreateFaq::class)
            ->fillForm([
                'question' => 'Giờ mở cửa là khi nào?',
                'answer' => 'Chúng tôi mở cửa từ 8h đến 20h.',
                'status' => PublishStatus::Draft,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('faqs', [
            'question' => 'Giờ mở cửa là khi nào?',
        ]);
    }

    public function test_can_update_faq(): void
    {
        $this->actingAs(User::factory()->create());

        $faq = Faq::factory()->create();

        Livewire::test(EditFaq::class, ['record' => $faq->getRouteKey()])
            ->fillForm([
                'question' => 'Câu hỏi đã sửa?',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('faqs', [
            'id' => $faq->id,
            'question' => 'Câu hỏi đã sửa?',
        ]);
    }

    public function test_can_soft_delete_restore_and_force_delete_faq(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $faq = Faq::factory()->create();

        Livewire::test(EditFaq::class, ['record' => $faq->getRouteKey()])
            ->callAction(DeleteAction::class);

        $this->assertSoftDeleted('faqs', ['id' => $faq->id]);

        Livewire::test(EditFaq::class, ['record' => $faq->getRouteKey()])
            ->callAction(RestoreAction::class);

        $this->assertDatabaseHas('faqs', ['id' => $faq->id, 'deleted_at' => null]);

        Livewire::test(EditFaq::class, ['record' => $faq->getRouteKey()])
            ->callAction(DeleteAction::class);

        Livewire::test(EditFaq::class, ['record' => $faq->getRouteKey()])
            ->callAction(ForceDeleteAction::class);

        $this->assertDatabaseMissing('faqs', ['id' => $faq->id]);
    }

    public function test_editor_has_full_crud_but_cannot_force_delete_faq(): void
    {
        $editor = User::factory()->editor()->create();
        $faq = Faq::factory()->create();

        $this->assertTrue($editor->can('viewAny', Faq::class));
        $this->assertTrue($editor->can('update', $faq));
        $this->assertTrue($editor->can('delete', $faq));
        $this->assertFalse($editor->can('forceDelete', $faq));
    }

    public function test_admin_can_force_delete_faq(): void
    {
        $admin = User::factory()->admin()->create();
        $faq = Faq::factory()->create();

        $this->assertTrue($admin->can('forceDelete', $faq));
    }
}
