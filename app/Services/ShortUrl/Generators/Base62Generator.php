<?php

namespace App\Services\ShortUrl\Generators;

use App\Models\ShortUrl;
use App\Services\ShortUrl\ShortUrlGeneratorInterface;
use Illuminate\Support\Facades\Redis;

class Base62Generator implements ShortUrlGeneratorInterface
{
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

    public function generate(string $originalUrl, int $limit): string
    {
        $today = date('Ymd');
        $uniqueId = Redis::hincrby('short_url:unique_id', $today, 1);
        $shortCode = $this->encodeBase62("{$today}{$uniqueId}");

        ShortUrl::create([
            'short_code' => $shortCode,
            'original_url' => $originalUrl,
            'access_limit' => $limit,
            'access_count' => 0,
        ]);

        return $shortCode;
    }
}
