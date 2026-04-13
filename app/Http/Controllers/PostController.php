<?php

namespace App\Http\Controllers;

use App\Http\Resources\PostResource;
use App\Models\Post;
use App\Models\PostMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PostController extends Controller
{
    // Post Create
    public function store(Request $request)
    {
        $request->validate([
            'caption' => 'nullable|string',
            'media' => 'required|array|min:1|max:10',
            'media.*' => 'required|file|mimetypes:image/jpg,image/png,image/jpeg,application/pdf,video/mp4|max:102400',
        ]);

        $post = $request->user()->posts()->create([
            'caption' => $request->caption,
        ]);

        if ($request->file('media')) {
            $folderPath = "posts/user_" . $request->user()->id . "/post_" . $post->id;

            $files = $request->file('media');

            if (!is_array($files)) {
                $files = [$files];
            }

            foreach ($files as $file) {
                $path = $file->store($folderPath, 'public');

                $extension = strtolower($file->getClientOriginalExtension());

                $type = match ($extension) {
                    'jpg', 'jpeg', 'png' => 'image',
                    'mp4' => 'video',
                    'pdf' => 'pdf',
                    default => 'file'
                };

                $post->media()->create([
                    'media_path' => $path,
                    'type' => $type
                ]);
            }
        }

        return response()->json([
            "message" => "Post created successfully",
            "post" => new PostResource($post),
        ], 201);
    }

    // All Posts of User
    public function index(Request $request)
    {
        $posts = $request->user()->posts()
            ->latest()
            ->paginate(10);

        return response()->json([
            "user" => $request->user()->only('id', 'name', 'username'),
            "posts" => PostResource::collection($posts)->response()->getData(true)
        ]);
    }

    // Show Single Post
    public function show(Request $request, $id)
    {
        $post = $request->user()->posts()
            ->with(['user', 'media'])
            ->find($id);

        if (!$post) {
            return response()->json([
                "message" => "Post topilmadi yoki sizga tegishli emas."
            ], 404);
        }

        return new PostResource($post);
    }

    // Update my Post
    public function update(Request $request, Post $post)
    {
        if ($request->user()->id !== $post->user_id) {
            return response()->json([
                "message" => "This post is not yours!"
            ], 403);
        }

        $validated = $request->validate([
            'caption' => 'nullable|string',

            'media' => 'nullable|array|max:10',
            'media.*' => 'required|file|mimetypes:image/jpg,image/jpeg,image/png,application/pdf,video/mp4|max:102400',

            'delete_media_ids' => 'nullable',
        ]);

        $post->update([
            'caption' => $validated['caption'] ?? $post->caption,
        ]);

        if ($request->filled('delete_media_ids')) {

            $ids = $request->delete_media_ids;

            if (is_string($ids)) {
                $ids = json_decode($ids, true) ?? explode(',', $ids);
            }

            if (!is_array($ids)) {
                $ids = [$ids];
            }

            $ids = array_map('intval', $ids);

            $mediaDelete = PostMedia::where('post_id', $post->id)
                ->whereIn('id', $ids)
                ->get();

            if ($mediaDelete->isEmpty()) {
                return response()->json([
                    "message" => "No media found to delete",
                    "debug_ids" => $ids
                ]);
            }

            foreach ($mediaDelete as $media) {

                if (Storage::disk('public')->exists($media->media_path)) {
                    Storage::disk('public')->delete($media->media_path);
                }

                $media->delete();
            }
        }

        if ($request->file('media')) {

            $folderPath = "posts/user_" . $request->user()->id . "/post_" . $post->id;

            $files = $request->file('media');

            if (!is_array($files)) {
                $files = [$files];
            }

            foreach ($files as $file) {

                $path = $file->store($folderPath, 'public');

                $type = match ($file->getClientOriginalExtension()) {
                    'jpg', 'jpeg', 'png' => 'image',
                    'mp4' => 'video',
                    'pdf' => 'pdf',
                    default => 'unknown'
                };

                $post->media()->create([
                    'media_path' => $path,
                    'type' => $type,
                    'file_name' => $file->getClientOriginalName()
                ]);
            }
        }

        return response()->json([
            "message" => "Post muvaffaqiyatli yangilandi",
            "post" => new PostResource($post->load('media'))
        ]);
    }

    // Delete my Post
    public function destroy(Request $request, Post $post)
    {
        if ($request->user()->id !== $post->user_id) {
            return response()->json([
                "message" => "this post is not yours!"
            ], 403);
        }

        $folderPath = "posts/user_" . $post->user_id . "/post_" . $post->id;

        if (Storage::disk('public')->exists($folderPath)) {
            Storage::disk('public')->deleteDirectory($folderPath);
        }

        $post->delete();

        return response()->json([
            "message" => "Post deleted successfully",
        ]);
    }
}
