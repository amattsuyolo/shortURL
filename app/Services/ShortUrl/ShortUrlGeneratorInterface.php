<?php

namespace App\Services\ShortUrl;

interface ShortUrlGeneratorInterface
{
    public function generate(string $originalUrl, int $limit): string;
}
