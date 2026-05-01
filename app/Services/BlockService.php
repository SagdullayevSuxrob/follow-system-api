<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class BlockService
{
    /**
     * Toggle Block - block and unblock 
     */
    public function toggleBlock(User $user)
    {
        $me = auth()->user();

        if ($me->id === $user->id) {
            return response()->json(["message" => "O'zingizni blok qila olmaysiz"], 400);
        }

        $block = DB::table('blocks')
            ->where('blocker_id', $me->id)
            ->where('blocked_id', $user->id);

        if ($block->exists()) {
            // blok bosa blokdan chiqadi
            $block->delete();
            return response()->json(["message" => "Foydalanuvchi blokdan chiqarildi"]);
        }

        DB::table('blocks')->insert([
            'blocker_id' => $me->id,
            'blocked_id' => $user->id
        ]);

        DB::table('follows')
            ->where(function ($q) use ($me, $user) {
                $q->where('follower_id', $me->id)->where('following_id', $user->id);
            })
            ->orWhere(function ($q) use ($me, $user) {
                $q->where('follower_id', $user->id)->where('following_id', $me->id);
            })
            ->delete();

        return response()->json([
            'message' => "Foydalanuvchi bloklandi va barcha aloqalar berkitildi"
        ]);
    }

    /**
     * Blocklanganlar ro'yhat
     */
    public function blockedList()
    {
        $me = auth()->user();
        $blockedUsers = $me->blockedUsers()
            ->select(['users.id', 'users.name', 'users.username'])
            ->paginate(10);

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
