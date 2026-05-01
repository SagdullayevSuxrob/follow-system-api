<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\FollowService;

class FollowController extends Controller
{

    public function __construct(private FollowService $followService) {}

    // Follow/Unfollow a user
    public function toggleFollow(User $user)
    {
        $result = $this->followService->toggleFollow($user);
        return response()->json(
            ['message' => $result['message']],
            $result['status']
        );
    }

    // Accept a follow request
    public function acceptFollowRequest(User $user)
    {
        $result = $this->followService->acceptRequest($user);

        if (!$result) {
            return response()->json([
                "message" => $result['message'],
            ], $result['status']);
        }
        return response()->json([
            "message" => $result['message']
        ], $result['status']);
    }

    // Reject a follow request
    public function rejectFollowRequest(User $user)
    {
        $result = $this->followService->rejectRequest($user);

        if (!$result) {
            return  response()->json([
                "message" => $result['message'],
            ], $result['status']);
        }
        return response()->json([
            "message" => $result['message']
        ], $result['status']);
    }

    // Get follows status
    public function status(User $user)
    {
        $me = auth()->user();
        return response()->json([
            'me' => $me->only(['name', 'username']),
            'user' => $user->only(['name', 'username']),
            'status' => $me->getFollowStatus($user, $me)
        ]);
    }
}
