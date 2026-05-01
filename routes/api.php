<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BlockController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\FollowController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Auth Routes
Route::post('/register', [AuthController::class, 'register']); //User Registration
Route::post('/login', [AuthController::class, 'login']);       //User Login

Route::middleware('auth:sanctum')->group(function () {

    // 1. Shaxsiy profil va Auth amallari
    Route::get('/user', [AuthController::class, 'user']);
    Route::put('/update', [AuthController::class, 'update']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::delete('/delete', [AuthController::class, 'delete']);

    // 2. Mening postlarim (My Posts)
    Route::prefix('my/posts')->group(function () {
        Route::get('/', [PostController::class, 'index']);
        Route::post('/', [PostController::class, 'store']);
        Route::get('/{post}', [PostController::class, 'show']);
        Route::put('/{post}', [PostController::class, 'update']);
        Route::delete('/{post}', [PostController::class, 'destroy']);
    });

    // 3. Boshqa foydalanuvchilar (User Profile & Posts)
    Route::prefix('user')->middleware('profile_access')->group(function () {
        Route::get('/{user}', [UserController::class, 'userProfile']);
        Route::get('/{user}/posts', [UserController::class, 'postsAll']);
        Route::get('/{user}/post/{post}', [UserController::class, 'post']);
        Route::get('/{user}/friends', [UserController::class, 'friends']);
        Route::get('/{user}/following', [UserController::class, 'following']);
        Route::get('/{user}/followers', [UserController::class, 'followers']);
    });

    // 4. Postlarga comment yozish.
    Route::post('/posts/{post}/comment', [CommentController::class, 'store'])->middleware('profile_access');
    Route::put('comments/{comment}/', [CommentController::class, 'update']);
    Route::delete('comments/{comment}/', [CommentController::class, 'delete']);

    // 5. Like tizimi
    Route::post('/posts/{post}/like', [LikeController::class, 'togglePostLike'])->middleware('profile_access'); // like for post
    Route::post('/comments/{comment}/like', [LikeController::class, 'toggleCommentLike'])->middleware('profile_access'); // like for comment

    // 6. Ijtimoiy tizimlar (Follow & Block)
    Route::prefix('follow')->middleware('profile_access')->group(function () {
        Route::get('/{user}/status', [FollowController::class, 'status']);
        Route::post('/{user}', [FollowController::class, 'toggleFollow']);
        Route::post('/{user}/accept', [FollowController::class, 'acceptFollowRequest']);
        Route::delete('/{user}/reject', [FollowController::class, 'rejectFollowRequest']);
    });

    Route::post('/block/{user}', [BlockController::class, 'toggleBlock']);
    Route::get('/blocked-users', [BlockController::class, 'blockedList']);
});
