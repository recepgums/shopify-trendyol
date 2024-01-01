<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers;

Route::group(['prefix' => 'trendyol-review', 'middleware' => 'shopify.auth'], function () {
    Route::post('download', [Controllers\TrendyolReviewController::class, 'download']);
});
