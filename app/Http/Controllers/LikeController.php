<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Post;
use App\Services\LikeService;

class LikeController extends Controller
{
    public function __construct(private LikeService $likeService) {}
    /**
     *  Postga like bosish
     */
    public function togglePostLike(Post $post)
    {
        return $this->likeService->togglePostLike($post);
    }

    /**
     * Commentga like bosish
     */
    public function toggleCommentLike(Comment $comment)
    {
        return $this->likeService->toggleCommentLike($comment);
    }
}
