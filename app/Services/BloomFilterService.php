<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;

class BloomFilterService
{
    private $bloomKey;
    private $hashFunctions;
    private $bitVectorSize;

    public function __construct($bloomKey = 'bloom_filter', $bitVectorSize = 8 * 1024 * 1024)
    {
        $this->bloomKey = $bloomKey;
        $this->bitVectorSize = $bitVectorSize;
        $this->hashFunctions = [
            fn($id) => crc32($id),
            fn($id) => hexdec(substr(md5($id), 0, 8)),
            fn($id) => $this->fnv1aHash($id),
        ];
    }

    // 更新布隆過濾器
    public function update($urlId)
    {
        foreach ($this->hashFunctions as $hashFunction) {
            $hash = $hashFunction($urlId) % $this->bitVectorSize;
            Redis::setbit($this->bloomKey, $hash, 1);
        }
    }

    // 檢查短網址是否存在
    public function check($urlId)
    {
        foreach ($this->hashFunctions as $hashFunction) {
            $hash = $hashFunction($urlId) % $this->bitVectorSize;
            if (!Redis::getbit($this->bloomKey, $hash)) {
                return false; // 一定不存在
            }
        }
        return true; // 可能存在
    }

    // 初始化布隆過濾器
    public function initialize()
    {
        $shortUrls = DB::table('short_urls')->pluck('short_code');
        foreach ($shortUrls as $code) {
            foreach ($this->hashFunctions as $hashFunction) {
                $hash = $hashFunction($code) % $this->bitVectorSize;
                Redis::setbit($this->bloomKey, $hash, 1);
            }
        }
    }

    // 自定義 FNV-1a 哈希函數
    private function fnv1aHash($input)
    {
        $prime = 16777619;
        $offsetBasis = 2166136261;
        $hash = $offsetBasis;
        foreach (str_split($input) as $char) {
            $hash ^= ord($char);
            $hash *= $prime;
        }
        return $hash & 0xffffffff; // 保持 32 位
    }
    public function reset()
    {
        // 清空布隆過濾器
        Redis::del($this->bloomKey);

        // 重新初始化布隆過濾器
        $this->initialize();
    }
}
