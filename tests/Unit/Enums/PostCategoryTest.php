<?php

namespace Tests\Unit\Enums;

use App\Features\Post\Enums\PostCategory;
use PHPUnit\Framework\TestCase;

class PostCategoryTest extends TestCase
{
    public function test_enum_has_the_six_expected_categories(): void
    {
        $this->assertSame(
            ['Teknologi', 'Pendidikan', 'Gaya Hidup', 'Bisnis', 'Opini', 'Umum'],
            PostCategory::values()
        );
    }

    public function test_every_case_has_label_and_badge_class(): void
    {
        $expectedLabels = [
            'Teknologi' => 'Teknologi & Koding',
            'Pendidikan' => 'Pendidikan & Tutorial',
            'Gaya Hidup' => 'Gaya Hidup & Produktivitas',
            'Bisnis' => 'Bisnis & Karir',
            'Opini' => 'Opini & Pemikiran',
            'Umum' => 'Umum',
        ];

        foreach (PostCategory::cases() as $case) {
            $this->assertSame($expectedLabels[$case->value], $case->label());
            $this->assertStringContainsString('bg-', $case->badgeClass());
            $this->assertStringContainsString('text-', $case->badgeClass());
        }
    }

    public function test_options_are_shaped_for_the_frontend(): void
    {
        $options = PostCategory::options();

        $this->assertCount(6, $options);
        $this->assertSame('Teknologi', $options[0]['value']);
        $this->assertSame('Teknologi & Koding', $options[0]['label']);
        $this->assertArrayHasKey('badgeClass', $options[0]);
    }

    public function test_enum_can_be_created_from_value(): void
    {
        $this->assertSame(PostCategory::GAYA_HIDUP, PostCategory::from('Gaya Hidup'));
        $this->assertNull(PostCategory::tryFrom('Tidak Ada'));
    }
}
