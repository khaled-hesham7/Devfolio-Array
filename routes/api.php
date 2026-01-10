<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ArticleController;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


// 1. مسارات الزوار (Public) - مش محتاجة تسجيل دخول
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/articles', [ArticleController::class, 'index']);
Route::get('/articles/{id}', [ArticleController::class, 'show']);
Route::post('/articles/{id}/like', [ArticleController::class, 'toggleLike']);

// 2. مسارات المحمية (Private) - لازم توكن (Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('/logout', [AuthController::class, 'logout']); // دخلناه جوه الجروب

    // إدارة المقالات
    Route::post('/articles', [ArticleController::class, 'store']);
    
    Route::post('/articles/{id}', [ArticleController::class, 'update']); 
    
    Route::delete('/articles/{id}', [ArticleController::class, 'destroy']);
});