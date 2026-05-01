<?php

namespace App\Http\Controllers;

use App\Http\Resources\PostResource;
use App\Http\Resources\UserResource;
use App\Models\Post;
use App\Models\User;
use App\Services\UserService;

class UserController extends Controller
{
    public function __construct(private UserService $userService) {}

    // get User Profile
    public function userProfile(User $user)
    {
        $result = $this->userService->userProfile($user);

        if ($result['code'] !== 200) {
            return response()->json(["message" => $result['error']], $result['code']);
        }
        return response()->json([$result['success']], $result['code']);
    }

    // get Users Posts
    public function postsAll(User $user)
    {
        $result =  $this->userService->getAllPosts($user);

        if ($result['message']) {
            return response()->json($result, 403);
        }

        return PostResource::collection($result);
    }

    // Bitta postni olish
    public function post(User $user, Post $post)
    {
        $result = $this->userService->getPostById($user, $post);
        if (is_array($result) && isset($result['error'])) {
            return response()->json([
                "message" => $result['message']
            ], $result['status']);
        }

        return new PostResource($post->load('user', 'media'));
    }

    // get Followers - list of users following the given user
    public function followers(User $user)
    {
        $result = $this->userService->getFollowers($user);

        if (isset($result['error'])) {
            return response()->json($result['error'], $result['code']);
        }

        return response()->json([
            "User" => $result['data']['user'],
            'followers' => UserResource::collection($result['data']['followers'])->response()->getData(true),
        ], 200);
    }

    // get Following - list of users followed by the given user
    public function following(User $user)
    {
        $result = $this->userService->getFollowing($user);

        if (isset($result['error'])) {
            return response()->json($result['error'], $result['code']);
        }

        return response()->json([
            "User" => $result['data']['user'],
            'following' => UserResource::collection($result['data']['following'])->response()->getData(true),
        ], 200);
    }

    // get Friends - list of users who are both following and followed by the given user
    public function friends(User $user)
    {
        $result = $this->userService->getFriends($user);

        if (isset($result['error'])) {
            return response()->json($result['error'], $result['code']);
        }

        return response()->json([
            "User" => $result['data']['user'],
            'friends' => UserResource::collection($result['data']['friends'])->response()->getData(true),
        ], 200);
    }
}
