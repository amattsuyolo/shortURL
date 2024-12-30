<?php

namespace App\Services\ShortUrl\Generators;

use App\Models\ShortUrl;
use App\Services\ShortUrl\ShortUrlGeneratorInterface;

class HashGenerator implements ShortUrlGeneratorInterface
{
    public function generate(string $originalUrl, int $limit): string
    {
         // 檢查是否已存在該網址的記錄
        $existingShortUrl = ShortUrl::where('original_url', $originalUrl)->first();
        if ($existingShortUrl) {
            return $existingShortUrl->short_code;
        }

        $hash = md5($originalUrl);
        $shortCode = substr($hash, 0, 6);

        $counter = 0;
        while (ShortUrl::where('short_code', $shortCode)->exists()) {
            $counter++;
            $shortCode = substr($hash, 0, 5) . $counter;
        }

        ShortUrl::create([
            'short_code' => $shortCode,
            'original_url' => $originalUrl,
            'access_limit' => $limit,
            'access_count' => 0,
        ]);

        return $shortCode;
    }
}
