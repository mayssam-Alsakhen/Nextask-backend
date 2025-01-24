<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\AuthController;

Route::resource('users', UserController::class);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth.jwt')->group(function () {
    Route::post('/projects', [ProjectController::class,])->middleware('auth.jwt');;
    Route::get('/tasks', [TaskController::class])->middleware('auth.jwt');;
    Route::resource('comments', CommentController::class)->middleware('auth.jwt');;
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:api')->middleware('auth.jwt');;
    Route::get('/me', [AuthController::class, 'me'])->middleware('auth:api')->middleware('auth.jwt');;

});