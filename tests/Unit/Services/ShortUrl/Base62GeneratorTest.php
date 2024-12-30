<?php

namespace Tests\Unit\Services\ShortUrl;

use App\Services\ShortUrl\Generators\Base62Generator;
use Illuminate\Support\Facades\Redis;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Base62GeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_generate_creates_unique_short_code(): void
    {
        Redis::shouldReceive('hincrby')
            ->once()
            ->with('short_url:unique_id', date('Ymd'), 1)
            ->andReturn(1);

        $generator = new Base62Generator();
        $originalUrl = 'https://example.com';

        $shortCode = $generator->generate($originalUrl, 0);

        $this->assertNotEmpty($shortCode);
        $this->assertDatabaseHas('short_urls', [
            'short_code' => $shortCode,
            'original_url' => $originalUrl,
        ]);
    }

    public function test_generate_handles_incrementing_ids_correctly(): void
    {
        Redis::shouldReceive('hincrby')
            ->twice()
            ->with('short_url:unique_id', date('Ymd'), 1)
            ->andReturn(1, 2);

        $generator = new Base62Generator();
        $originalUrl1 = 'https://example.com/1';
        $originalUrl2 = 'https://example.com/2';

        $shortCode1 = $generator->generate($originalUrl1, 0);
        $shortCode2 = $generator->generate($originalUrl2, 0);

        $this->assertNotEquals($shortCode1, $shortCode2);
    }
}
