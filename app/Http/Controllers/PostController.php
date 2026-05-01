<?php

namespace App\Http\Controllers;

use App\Http\Requests\Posts\PostStoreRequest;
use App\Http\Requests\Posts\PostUpdateRequest;
use App\Http\Resources\PostResource;
use App\Models\Post;
use App\Services\PostService;

class PostController extends Controller
{
    public function __construct(private PostService $postService) {}
    // Post Create
    public function store(PostStoreRequest $request)
    {
        $post = $this->postService->postStore($request);

        return response()->json([
            "message" => "Post created successfully",
            "post" => new PostResource($post),
        ], 201);
    }

    // All Posts of me
    public function index()
    {
        $result = $this->postService->getMyPosts();

        if ($result['message']) {
            return response()->json($result, 403);
        }

        return PostResource::collection($result);
    }

    // Show Single Post
    public function show(int $id)
    {
        $post = $this->postService->myPost($id);

        if (!$post) {
            return response()->json([
                "message" => "Post topilmadi yoki sizga tegishli emas."
            ], 403);
        }

        return new PostResource($post);
    }

    // Update Post
    public function update(PostUpdateRequest $request, Post $post)
    {
        $updatedPost = $this->postService->postUpdate($request, $post);

        return response()->json([
            "message"   => "Post muvaffaqiyatli yangilandi",
            "post"      => new PostResource($updatedPost)
        ]);
    }

    // Delete Post
    public function destroy(int $post_id)
    {
        $result = $this->postService->postDelete($post_id);

        return response()->json([
            "message" => $result['message'],
        ], $result['status']);
    }
}
