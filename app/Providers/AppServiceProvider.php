<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\ShortUrl\ShortUrlGeneratorInterface;
use App\Services\ShortUrl\Generators\RandomGenerator;
use App\Services\ShortUrl\Generators\HashGenerator;
use App\Services\ShortUrl\Generators\Base62Generator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // 綁定預設策略
        $this->app->singleton(ShortUrlGeneratorInterface::class, function () {
            $generatorType = config('short_url.default_generator', 'random');

            return match ($generatorType) {
                'hash' => new HashGenerator(),
                'base62' => new Base62Generator(),
                default => new RandomGenerator(),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
