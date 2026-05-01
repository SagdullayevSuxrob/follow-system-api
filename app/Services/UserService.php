<?php

namespace App\Services;

use App\Models\Post;
use App\Models\PostView;
use App\Models\User;

class UserService
{
    // get follow suggestion
    private function getSuggestion(User $me, User $user)
    {
        // 1. Bloklanganlarni yig'amiz
        $blockedIds = $me->blockedUsers()->pluck('blocked_id')
            ->merge($me->blockers()->pluck('blocker_id'))
            ->unique();

        $excludeIds = $me->following()->pluck('following_id')
            ->merge($blockedIds)
            ->push($me->id)
            ->push($user->id)
            ->unique();

        // 2. Suggestion (Tavsiyalar) mantiqi
        if ($me->id === $user->id) {
            // O'z profilimda - Global tavsiyalar
            $suggestions = User::whereNotIn('id', $excludeIds)
                ->inRandomOrder()
                ->limit(5)
                ->get(['id', 'username', 'name']);
        } else {
            // Boshqa profilida - Avval uning do'stlari (mutual), keyin global
            $relatedIds = $user->following()->pluck('following_id')
                ->merge($user->followers()->pluck('follower_id'))
                ->unique();

            $suggestions = User::whereIn('id', $relatedIds)
                ->whereNotIn('id', $excludeIds)
                ->inRandomOrder()
                ->limit(5)
                ->get(['id', 'name', 'username']);

            if ($suggestions->count() < 5) {
                $globalSuggestions = User::whereNotIn('id', $excludeIds)
                    ->whereNotIn('id', $suggestions->pluck('id'))
                    ->inRandomOrder()
                    ->limit(5 - $suggestions->count())
                    ->get(['id', 'name', 'username']);

                $suggestions = $suggestions->merge($globalSuggestions);
            }
            return $suggestions;
        }
    }

    // show user profile
    public function userProfile(User $user)
    {
        $me = auth()->user();

        $user->loadCount(['posts', 'followers', 'following']);

        $friendsCount = $user->friends()->count();
        // Get user statistics
        $stats = [
            "Posts" => $user->posts_count ?? 0,
            "Followers" => $user->followers_count ?? 0,
            "Following" => $user->following_count ?? 0,
            "Friends" => $friendsCount
        ];

        // Bu foydalanuvchi akkaunti shaxsiymi?
        if ($user->isPrivate($me)) {
            return [
                'error' => [
                    "User" => $user->only(['username', 'name']),
                    "status" => $me->getFollowStatus($user, $me),
                    "statistics" => $stats,
                    'message' => 'Bu hisob shaxsiy.',
                ],
                'code' => 403,
            ];
        }
        return [
            'success' => [
                "User" => $user->only(['username', 'name', 'email']),
                'statistics' => $stats,
                "status" => $me->getFollowStatus($user, $me),
                "suggestions" => $this->getSuggestion($user, $me),
            ],
            'code' => 200
        ];
    }

    // get all posts of user 
    public function getAllPosts(User $user)
    {
        $me = auth()->user();

        $posts = $user->posts()
            ->with(['media'])
            ->latest()
            ->paginate(10);

        if ($user->isPrivate($me)) {
            return ['message' => 'Bu hisob shaxsiy'];
        }

        if ($posts->isEmpty()) {
            return [
                "user" => $user->only('id', 'name', 'username'),
                "message" => "Bu foydalanuvchida hali post mavjud emas",
            ];
        }

        return $posts;
    }

    // get a post of user
    public function getPostById(User $user, Post $post)
    {
        $me = auth()->user();
        if ($user->isPrivate($me)) {
            return ["error" => true, "status" => 403, 'message' => 'Bu hisob shaxsiy'];
        }

        if (!$post) {
            return ["error" => true, "status" => 404, "message" => "Post mavjud emas!"];
        }

        if ($post->user_id !== $user->id) {
            return ["error" => true, "status" => 403, "message" => "Post bu foydalanuvchiga tegishli emas."];
        }

        PostView::firstOrCreate([
            'post_id' => $post->id,
            'user_id' => $me->id
        ]);

        return $post->loadCount('likes', 'views', 'comments')->load('user', 'media');
    }

    // get followers
    public function getFollowers(User $user)
    {
        $me = auth()->user();
        // Bu foydalanuvchi profili shaxsiy
        if ($user->isPrivate($me)) {
            return [
                'error' => [
                    "username" => $user->username,
                    "Followers" => $user->followers_count,
                    'message' => 'Bu hisob shaxsiy.'
                ],
                'code' => 403
            ];
        }

        return [
            'data' => [
                'user' => $user->only('id', 'name'),
                'followers' => $user->followers()->paginate(10),
            ],
            'code' => 200
        ];
    }

    // get following
    public function getFollowing(User $user)
    {
        $me = auth()->user();

        // Bu foydalanuvchi profili shaxsiy
        if ($user->isPrivate($me)) {
            return [
                'error' => [
                    'username' => $user->username,
                    'following' => $user->following_count,
                    'message' => "Bu hisob shaxsiy"
                ],
                'code' => 403
            ];
        }

        return [
            'data' => [
                'user' => $user->only('id', 'name'),
                'following' => $user->following()->paginate(10),
            ],
            'code' => 200,
        ];
    }

    // get friends
    public function getFriends(User $user)
    {
        $me = auth()->user();

        // Bu foydalanuvchi profili shaxsiy
        if ($user->isPrivate($me)) {
            return [
                'error' => [
                    "username" => $user->username,
                    "Friends" => $user->friends_count,
                    'message' => 'Bu hisob shaxsiy.'
                ],
                'code' => 403
            ];
        }

        return [
            'data' => [
                'user' => $user->only('id', 'name'),
                "friends" => $user->friends()->paginate(10),
            ],
            'code' => 200
        ];
    }
}
