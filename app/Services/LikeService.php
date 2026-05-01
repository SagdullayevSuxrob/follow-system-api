<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\Post;
use Illuminate\Support\Facades\DB;

class LikeService
{
    /**
     * Postga like bosish
     */
    public function togglePostLike(Post $post)
    {
        $me = auth()->user();
        $user = $post->user;

        // post->user profili private
        if ($user->isPrivate($me) && $me->id != $user->id && !$me->isFollowing($user)) {
            $followStatus = DB::table('follows')
                ->where('follower_id', $me->id)
                ->where('following_id', $user->id)
                ->value('status');

            //follow bosilgan lekin qabul qilinmagan
            if ($followStatus == 'requested') {
                return response()->json([
                    "message" => "Bu hisob shaxsiy, obuna so'rovingiz tasdiqlanishi kutilmoqda..."
                ], 403);
            }

            //follow umuman bosilmagan
            if (!$followStatus) {
                return response()->json([
                    "message" => "Bu hisob shaxsiy, like bosish uchun obuna bo'lishingiz kerak."
                ], 403);
            }
        }

        $like = $post->likes()->where('user_id', $me->id)->first();

        // like bosilgan bo'lsa o'chadi
        if ($like) {
            $like->delete();
            return response()->json([
                'message' => 'Like removed',
                'likes_count' => $post->likes()->count()
            ]);
        }

        // like bosilmagan bolsa ishlaydi
        $post->likes()->create(['user_id' => $me->id]);

        return response()->json([
            "post" => $post->id,
            'message' => 'Postga like bosildi!',
            'likes_count' => $post->likes()->count()
        ]);
    }

    /**
     * Commentga like bosish
     */
    public function toggleCommentLike(Comment $comment)
    {
        $me = auth()->user();

        $postOwner = $comment->post->user;

        // post->user profili private
        if ($postOwner->isPrivate($me)) {
            $followStatus = DB::table('follows')
                ->where('follower_id', $me->id)
                ->where('following_id', $postOwner->id)
                ->value('status');

            //follow bosilgan lekin qabul qilinmagan
            if ($followStatus === 'requested') {
                return response()->json([
                    "message" => "Bu hisob shaxsiy, obuna so'rovingiz tasdiqlanishi kutilmoqda..."
                ], 403);
            }

            //follow umuman bosilmagan
            if (!$followStatus) {
                return response()->json([
                    "message" => "Bu hisob shaxsiy, like bosish uchun obuna bo'lishingiz kerak!"
                ], 403);
            }
        }

        $like = $comment->likes()->where('user_id', $me->id)->first();

        // like bosilgan bolsa o'chadi
        if ($like) {
            $like->delete();
            return response()->json([
                "message" => "Like o'chirib tashlandi",
                "likes_count" => $comment->likes->count()
            ]);
        }

        // like bosilmagan bo'lsa ishlaydi 
        $comment->likes()->create(['user_id' => $me->id]);

        return response()->json([
            "comment" => $comment->content,
            "message" => "Comment Liked",
            "likes_count" => $comment->likes()->count()
        ]);
    }
}
