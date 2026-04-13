<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BlockController;
use App\Http\Controllers\FollowController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Auth Routes
Route::post('/register', [AuthController::class, 'register']); // User Registration
Route::post('/login', [AuthController::class, 'login']); // User Login
//---------- Authenticated Routes ----------\\
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']); // Get Current User
    Route::post('/logout', [AuthController::class, 'logout']); // logout
    Route::delete('/delete', [AuthController::class, 'delete']); // Delete account
    Route::put('/update', [AuthController::class, 'update']); // Update User Profile

    // User Profile
    Route::prefix('user')->group(function () {
        //get user profile
        Route::get('/{user}', [UserController::class, 'userProfile']); // Get User Profile
        Route::get('/{user}/followers', [UserController::class, 'followers']); // User's Followers
        Route::get('/{user}/following', [UserController::class, 'following']); // User's Following
        Route::get('/{user}/friends', [UserController::class, 'friends']); // User's Friends

        // get user posts
        Route::get('/{user}/posts', [UserController::class, 'postsAll']); // User's Posts
        Route::get('/{user}/post/{post}', [UserController::class, 'post']); // User's Single Post
    });

    // Follow System Routes
    Route::post('/follow/{user}', [FollowController::class, 'follow']); // Follow a User
    Route::delete('/unfollow/{user}', [FollowController::class, 'unfollow']); // Unfollow a User
    Route::get('/status/{user}', [FollowController::class, 'status']); // get Follow Status
    Route::post('/follow/{user}/accept', [FollowController::class, 'acceptFollowRequest']); // Accept Follow Request
    Route::delete('/follow/{user}/reject', [FollowController::class, 'rejectFollowRequest']); // Reject Follow Request

    // Block System Routes
    Route::post('/block/{user}', [BlockController::class, 'block']); // Block a User
    Route::delete('/unblock/{user}', [BlockController::class, 'unblock']); // Unblock a User
    Route::get('/blocked-users', [BlockController::class, 'blockedList']); // Get Blocked Users List
});

// Post Routes
Route::prefix('my')->middleware('auth:sanctum')->group(function () {
    Route::post('/posts', [PostController::class, 'store']); // Create a Post
    Route::get('/posts', [PostController::class, 'index']); // Get my all Posts
    Route::get('/posts/{post}', [PostController::class, 'show']); // Get my Single Post
    Route::put('/posts/{post}', [PostController::class, 'update']); // Update my post
    Route::delete('/posts/{post}', [PostController::class, 'destroy']); // Delete my post
});
