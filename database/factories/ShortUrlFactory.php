<?php

namespace Database\Factories;

use App\Models\ShortUrl;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShortUrlFactory extends Factory
{
    protected $model = ShortUrl::class;

    public function definition()
    {
        return [
            'short_code' => $this->faker->unique()->lexify('??????'), // 生成隨機 6 位碼
            'original_url' => $this->faker->url(),
            'access_limit' => $this->faker->numberBetween(0, 1000),
            'access_count' => $this->faker->numberBetween(0, 1000),
        ];
    }
}
