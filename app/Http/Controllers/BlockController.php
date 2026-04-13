<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class BlockController extends Controller
{
    // Blocklash
    public function block(Request $request, User $user)
    {
        $me = $request->user();

        if ($user->isBlocked($request->user())) {
            return response()->json(['message' => 'You have already blocked this user.'], 400);
        }

        $userToBlock = User::findOrFail($user->id);

        if ($me->id == $userToBlock->id) {
            return response()->json(['message' => 'You cannot block yourself.'], 400);
        }
        // bloklash
        $me->blockedUsers()->syncWithoutDetaching([$userToBlock->id]);

        // foydalanuvchini kuzatishni(follow->unfollow) to'xtatish
        $me->following()->detach($userToBlock->id);

        // foydalanuvchini kuzatishini to'xtatish
        $me->followers()->detach($userToBlock->id);

        return response()->json([
            'message' => "You blocked the $userToBlock->name [@$userToBlock->username] successfully."
        ]);
    }

    // Blockdan chiqarish
    public function unblock(Request $request, User $user)
    {
        $me = $request->user();

        if (!$user->isBlocked($me)) {
            return response()->json(['message' => 'This user is not blocked.'], 400);
        }
        // blokdan chiqarish
        $me->blockedUsers()->detach($user->id);

        return response()->json([
            'message' => "You unblocked the $user->name [@$user->username] successfully."
        ]);
    }

    // Blocklanganlar ro'yxati
    public function blockedList(Request $request)
    {
        $me = $request->user();
        $blockedUsers = $me->blockedUsers()
            ->select(['users.id', 'users.name', 'users.username'])
            ->get();

        if ($blockedUsers->isEmpty()) {
            return response()->json([
                'message' => 'You have no blocked users.'
            ]);
        }
        return response()->json([
            'blocked users list' => $blockedUsers
        ]);
    }
}
