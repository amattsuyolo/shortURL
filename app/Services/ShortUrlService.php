<?php

namespace App\Services;

use App\Models\ShortUrl;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ShortUrlService
{
    private $bloomFilterService;

    public function __construct(BloomFilterService $bloomFilterService)
    {
        $this->bloomFilterService = $bloomFilterService;
    }

    /**
     * 處理短網址重定向邏輯
     */
    public function handleRedirect($shortCode)
    {
        // 使用布隆過濾器檢查是否可能存在
        if (!$this->bloomFilterService->check($shortCode)) {
            return ['error' => 'Short URL not found', 'status' => 404];
        }

        // 快取鍵
        $cacheKey = "short_url:{$shortCode}";

        // 快取命中
        $originalUrl = Redis::get($cacheKey);
        if ($originalUrl) {
            return ['url' => $originalUrl, 'cacheHit' => true];
        }

        // 快取未命中 - 加鎖防快取擊穿
        $lockKey = "lock:short_url:{$shortCode}";
        if (!Redis::set($lockKey, 1, 'NX', 'EX', 10)) {
            // 等待其他請求更新快取
            usleep(500000); // 等待 0.5 秒
            $originalUrl = Redis::get($cacheKey);
            if ($originalUrl) {
                return ['url' => $originalUrl, 'cacheHit' => true];
            }
        }

        // 查詢數據庫
        $shortUrl = DB::table('short_urls')->where('short_code', $shortCode)->first();
        if (!$shortUrl) {
            Redis::del($lockKey); // 釋放鎖
            return ['error' => 'Short URL not found', 'status' => 404];
        }

        // 寫入快取，設置隨機過期時間防止快取雪崩
        $ttl = random_int(60, 3600); // 隨機過期時間 1 到 60 分鐘
        Redis::setex($cacheKey, $ttl, $shortUrl->original_url);
        Redis::del($lockKey); // 釋放鎖

        return ['url' => $shortUrl->original_url, 'cacheHit' => false];
    }

    /**
     * 創建短網址
     *
     * @param string $originalUrl 原始網址
     * @param int $limit 訪問限制
     * @return string 生成的短網址
     */
    public function createShortUrl(string $originalUrl, int $limit = 0): string
    {
        do {
            $shortCode = Str::random(6);
        } while (ShortUrl::where('short_code', $shortCode)->exists()); // 確保短碼唯一

        ShortUrl::create([
            'short_code' => $shortCode,
            'original_url' => $originalUrl,
            'access_limit' => $limit,
            'access_count' => 0,
        ]);

        // 更新布隆過濾器
        $this->bloomFilterService->update($shortCode);

        return $shortCode;
    }
}
