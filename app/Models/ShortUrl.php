<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShortUrl extends Model
{
    protected $fillable = ['short_code', 'original_url', 'access_limit', 'access_count'];
}
