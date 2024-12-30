<?php

namespace Tests\Unit\Services\ShortUrl;

use App\Services\ShortUrl\Generators\HashGenerator;
use App\Models\ShortUrl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HashGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_generate_creates_consistent_short_code(): void
    {
        // 清理資料庫
        ShortUrl::truncate();

        $generator = new HashGenerator();
        $originalUrl = 'https://example.com';

        $shortCode1 = $generator->generate($originalUrl, 0);
        $shortCode2 = $generator->generate($originalUrl, 0);

        $this->assertEquals($shortCode1, $shortCode2); // 相同的網址應生成相同的短碼
    }

    public function test_generate_handles_hash_collisions(): void
    {
         // 清空資料庫，確保唯一性檢查生效
        ShortUrl::truncate();
        
        $generator = new HashGenerator();
        $originalUrl1 = 'https://example.com';
        $originalUrl2 = 'https://example.com/different';

        ShortUrl::create([
            'short_code' => substr(md5($originalUrl1), 0, 6),
            'original_url' => $originalUrl1,
        ]);
        $shortCode = $generator->generate($originalUrl2, 0);

        $this->assertNotEquals(substr(md5($originalUrl1), 0, 6), $shortCode);
        $this->assertDatabaseHas('short_urls', [
            'short_code' => $shortCode,
            'original_url' => $originalUrl2,
        ]);
    }
}
