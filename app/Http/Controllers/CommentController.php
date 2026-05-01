<?php

namespace App\Http\Controllers;

use App\Http\Requests\Comments\StoreCommentRequest;
use App\Http\Requests\UpdateCommentRequest;
use App\Models\Comment;
use App\Models\Post;
use App\Services\CommentService;

class CommentController extends Controller
{
    public function __construct(private CommentService $commentService) {}

    // Postga Comment yozish
    public function store(StoreCommentRequest $request, Post $post)
    {
        $result = $this->commentService->storeComment($post, $request->validated());

        if ($result['status'] !== 201) {
            return response()->json(["message" => $result['message']], $result['status']);
        }
        return response()->json([
            "message" => $result['message'],
            "data" => $result['data']
        ], $result['status']);
    }

    // Commentni yangilash
    public function update(Comment $comment, UpdateCommentRequest $request)
    {
        $result = $this->commentService->updateComment($comment, $request);

        if ($result['status'] !== 201) {
            return response()->json([
                "message" => $result['message']
            ], $result['status']);
        }
        return response()->json([
            "message" => $result['message'],
            "data" => $result['data']
        ], $result['status']);
    }

    // Commentni o'chirish
    public function delete(Comment $comment)
    {
        $result = $this->commentService->deleteComment($comment);

        return response()->json([
            "message" => $result['message']
        ], $result['status']);
    }
}
