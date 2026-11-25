<?php

namespace Tests\Unit;

use App\Actions\BuildPostTableOfContents;
use PHPUnit\Framework\TestCase;

class BuildPostTableOfContentsTest extends TestCase
{
    public function test_generates_ids_with_muc_prefix_from_vietnamese_headings(): void
    {
        $result = (new BuildPostTableOfContents)->handle(
            '<h2>Cách chọn tủ lạnh</h2><p>nội dung</p><h2>Hai</h2><h2>Ba</h2>'
        );

        $this->assertSame('muc-cach-chon-tu-lanh', $result['items'][0]['id']);
        $this->assertStringContainsString('id="muc-cach-chon-tu-lanh"', $result['html']);
    }

    public function test_deduplicates_ids_for_repeated_headings(): void
    {
        $result = (new BuildPostTableOfContents)->handle(
            '<h2>Giới thiệu</h2><h2>Giới thiệu</h2><h2>Giới thiệu</h2>'
        );

        $ids = array_column($result['items'], 'id');

        $this->assertSame(['muc-gioi-thieu', 'muc-gioi-thieu-2', 'muc-gioi-thieu-3'], $ids);
        $this->assertSame(count($ids), count(array_unique($ids)));
    }

    public function test_items_empty_when_fewer_than_3_headings_but_ids_still_injected(): void
    {
        $result = (new BuildPostTableOfContents)->handle('<h2>Một</h2><h2>Hai</h2><p>chữ</p>');

        $this->assertSame([], $result['items']);
        $this->assertStringContainsString('<p>chữ</p>', $result['html']);
        $this->assertStringContainsString('id="muc-mot"', $result['html']);
    }

    public function test_items_populated_when_3_or_more_headings(): void
    {
        $result = (new BuildPostTableOfContents)->handle('<h2>Một</h2><h3>Hai</h3><h2>Ba</h2>');

        $this->assertCount(3, $result['items']);
        $this->assertSame(2, $result['items'][0]['level']);
        $this->assertSame(3, $result['items'][1]['level']);
    }

    public function test_handles_empty_content(): void
    {
        $result = (new BuildPostTableOfContents)->handle('');

        $this->assertSame([], $result['items']);
        $this->assertSame('', $result['html']);
    }

    public function test_preserves_non_heading_content_untouched(): void
    {
        $html = '<p>Giới thiệu</p><h2>Tiêu đề &amp; chi tiết</h2><h2>Hai</h2><h2>Ba</h2><p>Kết thúc</p>';

        $result = (new BuildPostTableOfContents)->handle($html);

        $this->assertStringContainsString('<p>Giới thiệu</p>', $result['html']);
        $this->assertStringContainsString('<p>Kết thúc</p>', $result['html']);
        $this->assertSame('Tiêu đề & chi tiết', $result['items'][0]['text']);
    }
}
