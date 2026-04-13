<?php

namespace App\Http\Controllers;

use App\Http\Resources\PostResource;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    // get User Profile
    public function userProfile(Request $request, User $user)
    {
        $me = $request->user();

        // Get user statistics
        $stats = [
            "Posts" => $user->posts()->count(),
            "Followers" => $user->followers()->count(),
            "Following" => $user->following()->count(),
            "Friends" => $user->friends()->count()
        ];

        // Get user suggestions
        if ($me->id === $user->id) {
            $suggestions = User::where('id', '!=', $me->id)
                ->where('id', '!=', $user->id)
                ->whereNotIn('id', $user->following()->pluck('following_id'))
                ->inRandomOrder()
                ->limit(5)
                ->get(['username', 'name', 'email']);
        } else {
            // 1. Avval profil egasining do'stlari orasidan (men kuzatmayotganlar)
            $relatedIds = $user->following()->pluck('following_id')
                ->merge($user->followers()->pluck('follower_id'))
                ->unique();

            $suggestions = User::whereIn('id', $relatedIds)
                ->where('id', '!=', $me->id)
                ->where('id', '!=', $user->id)
                ->whereNotIn('id', $me->following()->pluck('following_id'))
                ->inRandomOrder()
                ->limit(5)
                ->get(['id', 'name', 'username']);

            // 2. Agar 5 tadan kam bo'lsa, qolganini globaldan to'ldiramiz

            $globalSuggestions = User::where('id', '!=', $me->id)
                ->where('id', '!=', $user->id)
                ->whereNotIn('id', $me->following()->pluck('following_id'))
                ->whereNotIn('id', $suggestions->pluck('id')) // Allaqachon tanlanganlarni takrorlamaslik uchun
                ->inRandomOrder()
                ->limit(5 - $suggestions->count())
                ->get(['id', 'name', 'username']);

            $suggestions = $suggestions->merge($globalSuggestions);
        }

        // Bu foydalanuvchi meni bloklagan
        if ($user->Blocked($me)) {
            return response()->json([
                "message" => "Siz bu foydalanuvchi tomonidan bloklangansiz."
            ], 403);
        }

        // Men bu foydalanuvchini bloklaganman
        if ($user->isBlocked($me)) {
            return response()->json([
                "message" => "Siz bu foydalanuvchini bloklagansiz, uning profilini ko'rish uchun avval blokdan chiqaring"
            ], 403);
        }

        // Bu foydalanuvchi akkaunti shaxsiymi?
        if ($user->isPrivate($me)) {
            return response()->json([
                "User" => $user->only(['username', 'name']),
                "statistics" => $stats,
                'message' => 'This account is private',
                "suggestions" => $suggestions,
            ], 403);
        }
        return response()->json([
            "User" => $user->only(['username', 'name', 'email']),
            'statistics' => $stats,
            "status" => $me->getFollowStatus($user),
            "suggestions" => $suggestions
        ]);
    }

    // get Users Posts
    public function postsAll(Request $request, User $user, Post $post)
    {
        $me = $request->user();

        //  Bloklash tekshiruvi
        if ($user->Blocked($me)) {
            return response()->json([
                "message" => "Siz bu hisob tomonidan bloklangansiz."
            ], 403);
        }

        // Men uni blok qiganmanmi?
        if ($user->isBlocked($me)) {
            return response()->json([
                "message" => "Siz bu foydalanuvchini bloklagansiz uning profilini ko'rish uchun blokdan chiqaring."
            ], 403);
        }

        // Private profil tekshiruvi
        if ($user->type === 'private' && $me->id !== $user->id && !$me->isFollowing($user)) {
            return response()->json([
                "user" => $user->username,
                "message" => "Bu hisob shaxsiy, obuna bo'lishingiz kerak."
            ], 403);
        }

        // 3. Postlarni yuklash
        $posts = $user->posts()
            ->with(['user', 'media'])
            ->latest()
            ->paginate(10);
        if ($posts->isEmpty()) {
            return response()->json([
                "user" => $user->username,
                "message" => "Bu foydalanuvchida hali post mavjud emas",
            ]);
        }

        return PostResource::collection($posts);
    }

    // get a post of User
    public function post(Request $request, User $user, Post $post)
    {
        $me = $request->user();

        //  Bloklash tekshiruvi
        if ($user->Blocked($me)) {
            return response()->json([
                "message" => "Siz bu hisob tomonidan bloklangansiz."
            ], 403);
        }

        if ($user->isBlocked($me)) {
            return response()->json([
                "message" => "Siz bu foydalanuvchini bloklagansiz, uning profilini ko'rish uchun blokdan chiqaring"
            ], 403);
        }

        // Post bor yoki yo'qligi tekshiruvi
        if ($post->user_id !== $user->id) {
            return response()->json(["message" => "Post topilmadi yoki bu foydalanuvchiga tegishli emas."], 404);
        }

        // Account type private bo'lish tekshiruvi
        if ($user->type === 'private' && $request->user()->id !== $user->id && !$request->user()->isFollowing($user)) {
            return response()->json([
                "User" => $user->username,
                "message" => "Bu hisob shaxsiy, postlarini ko'rish uchun avval obuna bo'ling."
            ], 403);
        }

        return new PostResource($post->load('user', 'media'));
    }

    // get Followers - list of users following the given user
    public function followers(Request $request, User $user)
    {
        $me = $request->user();

        // Bu foydalanuvchi meni bloklagan
        if ($user->Blocked($me)) {
            return response()->json([
                "message" => "Siz bu foydalanuvchi tomonidan bloklangansiz."
            ], 403);
        }

        // Men bu foydalanuvchini bloklaganman. 
        if ($user->isBlocked($me)) {
            return response()->json([
                "message" => "Siz bu foydalanuvchini bloklagansiz, uning obunachilarini ko'rish uchun avval blokdan chiqaring"
            ], 403);
        }

        // Bu foydalanuvchi profili shaxsiy
        if ($user->isPrivate($me)) {
            return response()->json([
                "username" => $user->username,
                "Followers" => $user->followers()->count(),
                'message' => 'This account is private'
            ], 403);
        }
        return response()->json([
            "User" => $user->only(['id', 'name']),
            'followers' => $user->followers->map->only(['id', 'name', 'email']),
        ]);
    }

    // get Following - list of users followed by the given user
    public function following(Request $request, User $user)
    {
        $me = $request->user();

        // Bu foydalanuvchi meni bloklagan
        if ($user->Blocked($me)) {
            return response()->json([
                "message" => "Siz bu foydalanuvchi tomonidan bloklangansiz."
            ], 403);
        }

        // Men bu foydalanuvchini bloklaganman. 
        if ($user->isBlocked($me)) {
            return response()->json([
                "message" => "Siz bu foydalanuvchini bloklagansiz, uning obunalarini ko'rish uchun avval blokdan chiqaring"
            ], 403);
        }

        // Bu foydalanuvchi profili shaxsiy
        if ($user->isPrivate($me)) {
            return response()->json([
                "User" => $user->username,
                "Following" => $user->following()->count(),
                'message' => 'This account is private'
            ], 403);
        }
        return response()->json([
            "User" => $user->only(['id', 'name']),
            'following' => $user->following->map->only(['id', 'name', 'email'])
        ]);
    }

    // get Friends - list of users who are both following and followed by the given user
    public function friends(Request $request, User $user)
    {
        $me = $request->user();

        // Bu foydalanuvchi meni bloklagan
        if ($user->Blocked($me)) {
            return response()->json([
                "message" => "Siz bu foydalanuvchi tomonidan bloklangansiz, shu sabab uning malumotlarini ko'ra olmaysiz"
            ], 403);
        }

        // Men bu foydalanuvchini bloklaganman. 
        if ($user->isBlocked($me)) {
            return response()->json([
                "message" => "Siz bu foydalanuvchini bloklagansiz, uning do'stlarini ko'rish uchun avval blokdan chiqaring"
            ], 403);
        }

        // Bu foydalanuvchi profili shaxsiy
        if ($user->isPrivate($me)) {
            return response()->json([
                "User" => $user->username,
                "Friends" => $user->friends()->count(),
                'message' => 'This account is private'
            ], 403);
        }
        $friends = $user->friends;
        return response()->json([
            "User" => $user->only(['id', 'name']),
            'friends' => $friends->map->only(['id', 'name', 'email'])
        ]);
    }
}
