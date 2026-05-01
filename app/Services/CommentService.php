<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentService
{
    // Comment yozish 
    public function storeComment(Post $post, array $data)
    {
        $me = Auth::user();
        $user = $post->user;

        // profil yopiq emasmi
        if ($user->isPrivate($me)) {
            return [
                "message" => "Izoh yozish uchun obuna bo'ling.",
                "status" => 403
            ];
        }

        // create qilish
        $comment = $post->comments()->create([
            'user_id' => $me->id,
            'content' => $data['content'],
            'parent_id' => $data['parent_id'] ?? null,
        ]);

        // response qaytarish
        return [
            "message" => "Comment added!",
            "data" => $comment->load('user:id,name,username'),
            "status" => 201
        ];
    }

    // Comment edit qilish comment_user uchun
    public function updateComment(Comment $comment, Request $request)
    {
        $user = $comment->user;
        $me = $request->user();

        // faqat comment egasi bolsin
        if ($me->id !== $user->id) {
            return [
                "message" => "Bu amalni bajarishga ruxsat yo'q!",
                "status" => 403
            ];
        }
        $comment->update([
            'content' => $request->content,
        ]);

        return [
            "message" => "Comment updated!",
            "data" => $comment->load('user:id,name,username'),
            "status" => 200
        ];
    }

    // Commentni o'chirish comment_user & post_user uchun
    public function deleteComment(Comment $comment)
    {
        $me = auth()->user();

        // comment egasi va post egasi bu commentni o'chira oladi
        if ($me->id === $comment->user_id || $me->id === $comment->post->user_id) {
            $comment->delete();

            return ["message" => "Izoh o'chirildi.", "status" => 200];
        }
        return ["message" => "Bu amalni bajarishga ruxsat yo'q!", "status" => 403];
    }
}
