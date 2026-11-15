<?php

namespace Tests\Unit\Enums;

use App\Features\Post\Enums\PostCategory;
use PHPUnit\Framework\TestCase;

class PostCategoryTest extends TestCase
{
    public function test_has_the_six_required_categories(): void
    {
        $this->assertSame(
            ['Teknologi', 'Pendidikan', 'Gaya Hidup', 'Bisnis', 'Opini', 'Umum'],
            PostCategory::values()
        );
    }

    public function test_labels_and_badge_classes(): void
    {
        $expected = [
            'Teknologi' => 'Teknologi & Koding',
            'Pendidikan' => 'Pendidikan & Tutorial',
            'Gaya Hidup' => 'Gaya Hidup & Produktivitas',
            'Bisnis' => 'Bisnis & Karir',
            'Opini' => 'Opini & Pemikiran',
            'Umum' => 'Umum',
        ];

        foreach (PostCategory::cases() as $case) {
            $this->assertSame($expected[$case->value], $case->label());
            $this->assertStringStartsWith('bg-', $case->badgeClass());
        }
    }

    public function test_options_for_frontend(): void
    {
        $options = PostCategory::options();

        $this->assertCount(6, $options);
        $this->assertSame(['value' => 'Teknologi', 'label' => 'Teknologi & Koding', 'badgeClass' => 'bg-blue-100 text-blue-800'], $options[0]);
    }

    public function test_from_loose_accepts_value_label_any_case_and_falls_back_to_umum(): void
    {
        $this->assertSame(PostCategory::TEKNOLOGI, PostCategory::fromLoose('teknologi'));
        $this->assertSame(PostCategory::GAYA_HIDUP, PostCategory::fromLoose('  Gaya Hidup & Produktivitas '));
        $this->assertSame(PostCategory::UMUM, PostCategory::fromLoose('Olahraga'));
        $this->assertSame(PostCategory::UMUM, PostCategory::fromLoose(null));
    }
}
