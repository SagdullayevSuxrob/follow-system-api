<?php

namespace App\Services;

use App\Models\Follow;
use App\Models\User;

class FollowService
{
    // follow / unfollow
    public function toggleFollow(User $user)
    {
        $me = auth()->user();
        if ($user->id === $me->id) {
            return ['message' => 'You cannot follow yourself.', 'status' => 400];
        }

        $follow = Follow::where('follower_id', $me->id)
            ->where('following_id', $user->id)
            ->first();

        if ($follow) {
            $status = $follow->status;
            $follow->delete();

            $msg = ($follow->status === 'requested')
                ? "Follow request cancelled for $user->username"
                : "You unfollowed $user->username";

            return ['message' => $msg, 'status' => 200];
        }

        //   Check if the user is private to Follow
        $status = ($user->type === 'private') ? 'requested' : 'accepted';

        Follow::create([
            'follower_id' => $me->id,
            'following_id' => $user->id,
            'status' => $status
        ]);

        $msg = ($status === 'requested')
            ? "Your Follow request sent to $user->username"
            : "You Followed $user->username";

        return ["message" => $msg, "status" => 201];
    }

    // accept follow request
    public function acceptRequest(User $user)
    {
        $me = auth()->user();
        $followRequest = Follow::where('follower_id', $user->id)
            ->where('following_id', $me->id)
            ->where('status', 'requested')
            ->first();

        if (!$followRequest) {
            return [
                'message' => 'Follow request not found',
                'status' => 404
            ];
        }

        $followRequest->update(['status' => 'accepted']);

        return [
            "message" => "You accepted the $user->username's follow request.",
            'status' => 200
        ];
    }

    // reject follow request
    public function rejectRequest(User $user)
    {
        $me = auth()->user();
        $followRequest = Follow::where('follower_id', $user->id)
            ->where('following_id', $me->id)
            ->where('status', 'requested')
            ->first();

        if (!$followRequest) {
            return [
                'message' => 'Follow request not found.',
                'status' => '404'
            ];
        }
        $followRequest->delete();

        return  [
            "message" => "You rejected the $user->username's follow request.",
            'status' => 200
        ];
    }
}
