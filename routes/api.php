<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ArticleController;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');



Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
//////////////////////////////////////////////////////////////////////////
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/articles', [ArticleController::class, 'store']);     // إنشاء جديد
    Route::put('/articles/{id}', [ArticleController::class, 'update']);   // تعديل
    Route::delete('/articles/{id}', [ArticleController::class, 'destroy']);   // حذف
});
///////////////////////////////////////////////////////////////////////
Route::get('/articles/{id}', [ArticleController::class, 'show']);  // عرض واحد
Route::get('/articles', [ArticleController::class, 'index']);      // عرض الكل
Route::post('/articles/{id}/like', [ArticleController::class, 'toggleLike']);

//////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////

