<?php

namespace App\Services\ShortUrl\Generators;

use App\Models\ShortUrl;
use App\Services\ShortUrl\ShortUrlGeneratorInterface;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

class RandomGenerator implements ShortUrlGeneratorInterface
{
    /**
     * Calculate the optimal short code length based on the Birthday Paradox.
     */
    private function calculateOptimalLength(int $existingUrls, float $collisionProbability): int
    {
        $characterSetSize = 62; // 26 lowercase + 26 uppercase + 10 digits
        $maxLength = 10; // Maximum length to avoid excessive computation

        for ($length = 1; $length <= $maxLength; $length++) {
            $combinations = pow($characterSetSize, $length);
            $collisionChance = 1 - exp(-($existingUrls * ($existingUrls - 1)) / (2 * $combinations));

            if ($collisionChance <= $collisionProbability) {
                return $length;
            }
        }

        return $maxLength; // Fallback to max length if not within bounds
    }

    public function generate(string $originalUrl, int $limit): string
    {
        $existingUrls = Cache::remember('short_url_count', config('short_url.cache_ttl'), function () {
            return ShortUrl::count();
        });

        $optimalLength = Cache::remember('short_code_length', config('short_url.cache_ttl'), function () use ($existingUrls) {
            return $this->calculateOptimalLength($existingUrls, config('short_url.collision_probability'));
        });

        do {
            $shortCode = Str::random($optimalLength);
        } while (ShortUrl::where('short_code', $shortCode)->exists());

        ShortUrl::create([
            'short_code' => $shortCode,
            'original_url' => $originalUrl,
            'access_limit' => $limit,
            'access_count' => 0,
        ]);

        return $shortCode;
    }
}
