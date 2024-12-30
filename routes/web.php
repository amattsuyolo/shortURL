<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ShortUrlController;

// 渲染短網址填寫頁面
Route::get('/', [ShortUrlController::class, 'index']);

// 處理短網址生成請求
Route::post('/short-url', [ShortUrlController::class, 'create']);

// 用戶訪問短網址
Route::get('/{shortUrl}', [ShortUrlController::class, 'redirect']);

