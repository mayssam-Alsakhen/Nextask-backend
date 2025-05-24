<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserActivityController;
use App\Http\Controllers\TaskAnalyticsController;
use App\Http\Controllers\Api\ProjectReportController;


Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware(['jwt.auth'])->group(function () {
    //projects routes 
    Route::post('/projects', [ProjectController::class, 'store']);
    Route::get('/projects', [ProjectController::class, 'index']);
    Route::get('/projects/user/{id}', [ProjectController::class, 'getProjectsByUserId']);
    Route::get('/projects/{id}', [ProjectController::class, 'show']);
    Route::get('/search-project', [ProjectController::class, 'search']);
    Route::put('/projects/{id}', [ProjectController::class, 'update']);
    Route::delete('/projects/{id}', [ProjectController::class, 'destroy']);
    Route::get('/projects/{id}/report', [ProjectReportController::class, 'show']);
          // add a user to the project
    Route::post('/projects/{projectId}/add-user', [ProjectController::class, 'addUserToProject']);
    Route::put('/projects/{projectId}/users/{userId}/admin', [ProjectController::class, 'setUserAsAdmin']);
    Route::put('projects/{projectId}/remove-admin/{userId}', [ProjectController::class, 'removeAdminPrivilege']);
    Route::delete('/projects/{projectId}/users/{userId}', [ProjectController::class, 'removeUserFromProject']);

    //tasks routes
    Route::prefix('tasks')->group(function () {
        Route::post('/', [TaskController::class, 'store']);
        Route::get('/', [TaskController::class, 'index']);
        Route::get('/{id}', [TaskController::class, 'show']);
        Route::put('/{id}', [TaskController::class, 'update']);
        Route::patch('/{id}/progress', [TaskController::class, 'updateProgress']);
        Route::delete('/{id}', [TaskController::class, 'destroy']);
    });
      
    // user routes
    Route::get('/user/{id}', [UserController::class, 'show']);
    Route::put('/user/{id}', [UserController::class, 'update']);
    Route::delete('/user/{id}', [UserController::class, 'destroy']);   
    Route::post('/user/search', [UserController::class, 'searchByEmail']);
    
    Route::resource('comments', CommentController::class);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/users/{user_id}/activities', [UserActivityController::class, 'getUserActivities']);
    Route::get('/dashboard/summary', [DashboardController::class, 'summary']);
    Route::post('/dashboard/tasks/filter', [DashboardController::class, 'filteredTasks']);
    Route::get('/analytics/completed-tasks', [TaskAnalyticsController::class, 'completedByMonth']);
});
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('jwt.auth');