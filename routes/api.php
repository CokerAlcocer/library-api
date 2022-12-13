<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BookController;
use App\Http\Controllers\AuthorController;
use App\Http\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('book')->group(function(){
    Route::get('index', [BookController::class, 'index']);
    Route::get('find/{id}', [BookController::class, 'find']);
    Route::post('save', [BookController::class, 'store']);
    Route::put('override/{id}', [BookController::class, 'override']);
    Route::delete('remove/{id}', [BookController::class, 'remove']);
});

Route::prefix('author')->group(function(){
    Route::get('index', [AuthorController::class, 'index']);
    Route::get('find/{id}', [AuthorController::class, 'find']);
    Route::post('save', [AuthorController::class, 'store']);
    Route::put('override/{id}', [AuthorController::class, 'override']);
    Route::delete('remove/{id}', [AuthorController::class, 'remove']);
});

//Authentication is not required for these endpoints
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

//Authentication is required for these endpoints (apply middleware auth:sanctum)
Route::group(['middleware' => ["auth:sanctum"]], function () {
    Route::get('userProfile', [AuthController::class, 'userProfile']);
    Route::get('logout', [AuthController::class, 'logout']);
    Route::put('changePassword/{id}', [AuthController::class, 'changePassword']);
    Route::post('addBookReview/{id}', [AuthController::class, 'addBookReview']);
    Route::put('updateBookReview/{id}', [AuthController::class, 'updateBookReview']);
});
