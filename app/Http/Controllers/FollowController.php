<?php

namespace App\Http\Controllers;

use App\Models\Follow;
use App\Models\User;
use Illuminate\Http\Request;

class FollowController extends Controller
{
    // Follow a user
    public function follow(User $user, Request $request)
    {
        $me = $request->user();

        if ($user->id === $me->id) {
            return response()->json(['message' => 'You cannot follow yourself.']);
        }

        $follow = Follow::where('follower_id', $me->id)->where('following_id', $user->id)->first();

        if ($user->Blocked($me)) {
            return response()->json(['message' => "You have been blocked by $user->name[ @$user->username ], you cannot follow, until @$user->username unblock you."], 403);
        }

        if ($user->isBlocked($me)) {
            return response()->json([
                "message" => "You have blocked $user->name[ @$user->username ], you can follow by unblocking @$user->username"
            ], 403);
        }

        if ($follow) {
            return response()->json(['message' => "You are already following $user->name[ @$user->username ]"]);
        }

        //   Check if the user is private to Follow
        if ($user->type === 'private') {
            $status = 'requested';
            Follow::create([
                'follower_id' => $me->id,
                'following_id' => $user->id,
                'status' => $status
            ]);
            return response()->json(['message' => "Follow request sent to $user->name[ @$user->username ]"], 201);
        }
        $status = 'accepted';
        Follow::create([
            'follower_id' => $me->id,
            'following_id' => $user->id,
            'status' => $status
        ]);

        return  response()->json(['message' => "You Followed $user->name[ @$user->username ]"], 201);
    }

    // Accept a follow request
    public function acceptFollowRequest(User $user, Request $request)
    {
        $me = $request->user();
        $followRequest = Follow::where('follower_id', $user->id)
            ->where('following_id', $me->id)
            ->where('status', 'requested')
            ->first();

        if (!$followRequest) {
            return response()->json(['message' => 'Follow request not found.'], 404);
        }

        $followRequest->status = 'accepted';
        $followRequest->save();

        return response()->json(['message' => "You accepted the $user->name [@$user->username]'s follow request."]);
    }

    // Reject a follow request
    public function rejectFollowRequest(User $user, Request $request)
    {
        $me = $request->user();
        $followRequest = Follow::where('follower_id', $user->id)
            ->where('following_id', $me->id)
            ->where('status', 'requested')
            ->first();

        if (!$followRequest) {
            return response()->json(['message' => 'Follow request not found.'], 404);
        }
        $followRequest->delete();

        return response()->json(['message' => "You rejected the $user->name [@$user->username]'s follow request."]);
    }

    // Unfollow a user
    public function unfollow(User $user, Request $request)
    {
        $me = $request->user();
        $isFollowing = $me->following()
            ->where('following_id', $user->id)
            ->exists();

        $me->following()->detach($user);

        return response()->json([
            'message' => $isFollowing
                ? "You Unfollowed $user->name"
                : "Already unfollowed",
            'is_following' => false
        ]);
    }

    // Get follows status
    public function status(User $user, Request $request)
    {
        $me = $request->user();
        return response()->json([
            'me' => $me->only(['name', 'username']),
            'user' => $user->only(['name', 'username']),
            'status' => $me->getFollowStatus($user)
        ]);
    }
}
