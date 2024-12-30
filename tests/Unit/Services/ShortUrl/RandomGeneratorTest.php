<?php

namespace Tests\Unit\Services\ShortUrl;

use App\Services\ShortUrl\Generators\RandomGenerator;
use App\Models\ShortUrl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class RandomGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        // 設定測試期間的配置
        Config::set('short_url.collision_probability', 0.01);
        Config::set('short_url.cache_ttl', 86400);
    }

    public function test_generate_returns_unique_short_code(): void
    {
        // 塞 100 筆資料到資料庫
        ShortUrl::factory()->count(100)->create();

        $generator = new RandomGenerator();
        $originalUrl = 'https://example.com';

        // 模擬資料庫目前短網址的數量
        $existingUrls = ShortUrl::count();
        $expectedLength = $this->getExpectedShortCodeLength($existingUrls);

        $shortCode1 = $generator->generate($originalUrl, 0);
        $shortCode2 = $generator->generate($originalUrl, 0);

        $this->assertNotEquals($shortCode1, $shortCode2);
        $this->assertEquals($expectedLength, strlen($shortCode1)); // 確保長度符合計算
    }

    public function test_generate_persists_to_database(): void
    {
        $generator = new RandomGenerator();
        $originalUrl = 'https://example.com';

        $shortCode = $generator->generate($originalUrl, 10);

        $this->assertDatabaseHas('short_urls', [
            'short_code' => $shortCode,
            'original_url' => $originalUrl,
            'access_limit' => 10,
        ]);
    }

    private function getExpectedShortCodeLength(int $existingUrls): int
    {
        $characterSetSize = 62; // 字符集大小
        $collisionProbability = Config::get('short_url.collision_probability', 0.01);
        $maxLength = 10;

        for ($length = 1; $length <= $maxLength; $length++) {
            $combinations = pow($characterSetSize, $length);
            $collisionChance = 1 - exp(-($existingUrls * ($existingUrls - 1)) / (2 * $combinations));

            if ($collisionChance <= $collisionProbability) {
                return $length;
            }
        }

        return $maxLength;
    }
}
