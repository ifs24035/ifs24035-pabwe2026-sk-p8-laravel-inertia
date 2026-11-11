<?php

namespace Tests\Unit\DTO;

use App\Features\Ai\DTO\GeneratedPostItem;
use PHPUnit\Framework\TestCase;

class GeneratedPostItemTest extends TestCase
{
    public function test_to_array_returns_all_fields(): void
    {
        $item = new GeneratedPostItem('Judul', 'Teknologi', 'Ringkasan', '## Isi');

        $this->assertSame([
            'title' => 'Judul',
            'category' => 'Teknologi',
            'excerpt' => 'Ringkasan',
            'content' => '## Isi',
        ], $item->toArray());
    }

    public function test_excerpt_can_be_null(): void
    {
        $item = new GeneratedPostItem('Judul', 'Umum', null, 'Isi');

        $this->assertNull($item->toArray()['excerpt']);
    }
}
