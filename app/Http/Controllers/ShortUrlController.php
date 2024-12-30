<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use App\Services\ShortUrlService;

class ShortUrlController extends Controller
{
    private $shortUrlService;

    public function __construct(ShortUrlService $shortUrlService)
    {
        $this->shortUrlService = $shortUrlService;
    }

    public function index()
    {
        return view('short-url-form');
    }

    public function create(Request $request)
    {
        $request->validate([
            'url' => 'required|url',
            'limit' => 'nullable|integer|min:1',
        ]);

        $originalUrl = $request->input('url');
        $limit = $request->input('limit', 0);

        // 調用 Service 處理邏輯
        $shortCode = $this->shortUrlService->createShortUrl($originalUrl, $limit);

        return response()->json([
            'short_url' => url($shortCode),
        ]);
    }

    public function redirect($shortCode)
    {
        $result = $this->shortUrlService->handleRedirect($shortCode);

        // 處理錯誤情況
        if (isset($result['error'])) {
            return response()->json(['message' => $result['error']], $result['status']);
        }

        // 更新訪問計數
        $this->incrementVisitCount($shortCode);

        // 重定向
        return redirect()->away($result['url']);
    }

    private function incrementVisitCount($shortCode)
    {
        // 限流邏輯：每分鐘最多 10 次
        $rateLimitKey = "rate_limit:{$shortCode}";
        $rateLimitTTL = 60; // 限制時間為 60 秒
        $rateLimitMax = 10; // 每分鐘最大訪問次數

        // 使用 Lua 腳本處理限流
        $luaScript = <<<LUA
        local count = redis.call('incr', KEYS[1])
        if count == 1 then
            redis.call('expire', KEYS[1], ARGV[1])
        end
        return count
        LUA;

        // 執行腳本並獲取當前計數
        $currentCount = Redis::eval($luaScript, 1, $rateLimitKey, $rateLimitTTL);

        if ($currentCount > $rateLimitMax) {
            throw new \Exception('Rate limit exceeded.');
        }

        // 更新訪問計數到資料庫
        dispatch(new \App\Jobs\UpdateVisitCount($shortCode));
    }
}
