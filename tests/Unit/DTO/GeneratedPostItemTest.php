<?php

namespace Tests\Unit\DTO;

use App\Features\Ai\DTO\GeneratedPostItem;
use PHPUnit\Framework\TestCase;

class GeneratedPostItemTest extends TestCase
{
    public function test_to_array(): void
    {
        $item = new GeneratedPostItem('Judul', 'Teknologi', 'Ringkas', '## Isi');

        $this->assertSame([
            'title' => 'Judul',
            'category' => 'Teknologi',
            'excerpt' => 'Ringkas',
            'content' => '## Isi',
        ], $item->toArray());
    }

    public function test_defaults(): void
    {
        $item = new GeneratedPostItem('Judul');

        $this->assertSame('Umum', $item->category);
        $this->assertNull($item->excerpt);
        $this->assertSame('', $item->content);
    }
}
