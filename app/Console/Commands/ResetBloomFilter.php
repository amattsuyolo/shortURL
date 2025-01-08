<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\BloomFilterService;

class ResetBloomFilter extends Command
{
    /**
     * 命令名稱
     *
     * @var string
     */
    protected $signature = 'bloom:reset';

    /**
     * 命令描述
     *
     * @var string
     */
    protected $description = 'Reset and reinitialize the Bloom Filter for short URLs.';

    /**
     * @var BloomFilterService
     */
    private $bloomFilterService;

    /**
     * 建構函式注入 BloomFilterService
     */
    public function __construct(BloomFilterService $bloomFilterService)
    {
        parent::__construct();
        $this->bloomFilterService = $bloomFilterService;
    }

    /**
     * 執行命令的邏輯
     */
    public function handle()
    {
        $this->info('Resetting the Bloom Filter...');
        $this->bloomFilterService->reset();
        $this->info('Bloom Filter has been successfully reset.');
    }
}
