<?php

namespace App\Services;

use App\Models\ShortUrl;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;
use App\Services\ShortUrl\ShortUrlGeneratorInterface;

class ShortUrlService
{
    private $bloomFilterService;
    private $generator;

    public function __construct(
        BloomFilterService $bloomFilterService,
        ShortUrlGeneratorInterface $generator
    )
    {
        $this->bloomFilterService = $bloomFilterService;
        $this->generator = $generator;
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
        // 使用生成器創建短碼
        $shortCode = $this->generator->generate($originalUrl, $limit);

        // 更新布隆過濾器
        $this->bloomFilterService->update($shortCode);

        return $shortCode;
    }
    /**
     * 創建短網址
     *
     * @param string $originalUrl 原始網址
     * @param int $limit 訪問限制
     * @return string 生成的短網址
     */
    public function createShortUrlV2(string $originalUrl, int $limit = 0): string
    {
        // 使用哈希函數生成摘要
        $hash = md5($originalUrl); // 或者使用其他哈希函數，例如 sha256

        // 裁剪哈希摘要生成短碼
        $shortCode = substr($hash, 0, 6); // 使用前 6 個字符作為短碼

        // 確保短碼唯一（解決哈希碰撞）
        $counter = 0;
        while (ShortUrl::where('short_code', $shortCode)->exists()) {
            $counter++;
            // 如果短碼已存在，附加遞增數字來解決衝突
            $shortCode = substr($hash, 0, 5) . $counter;
        }

        // 存入數據庫
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
    /**
     * 創建短網址
     *
     * @param string $originalUrl 原始網址
     * @param int $limit 訪問限制
     * @return string 生成的短網址
     */
    public function createShortUrlV3(string $originalUrl, int $limit = 0): string
    {
        // 獲取當天的日期 (格式: YYYYMMDD)
        $today = date('Ymd');

        // 使用 Redis 的 HASH 結構記錄每天的遞增 ID
        $uniqueId = Redis::hincrby('short_url:unique_id', $today, 1);

        // 將日期與遞增 ID 拼接後進行 Base62 編碼
        $shortCode = $this->encodeBase62("{$today}{$uniqueId}");

        // 將短網址數據存入資料庫
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

    /**
     * 將整數編碼為 Base62 字符串
     *
     * @param string $number 整數或數字字符串
     * @return string Base62 編碼字符串
     */
    private function encodeBase62(string $number): string
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $base = strlen($characters);
        $result = '';

        $number = intval($number);
        do {
            $result = $characters[$number % $base] . $result;
            $number = intdiv($number, $base);
        } while ($number > 0);

        return $result;
    }
}
