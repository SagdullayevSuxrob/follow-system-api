<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'user' => [
                'name' => $this->user->name ?? "Noma'lum foydalanuvchi",
                'username' => $this->user ? $this->user->username : null,
            ],

            "post" => [
                'id' => $this->id,
                'media' => $this->media->map(function ($item) {
                    return [
                        'id'  => $item->id,
                        'url' => $item->media_url,
                    ];
                }),
                'caption' => $this->caption,
                'time_ago' => $this->created_at->diffForHumans(),
                'posted_at' => $this->created_at->format('d.m.Y'),
                'is_updated' => $this->updated_at->gt($this->created_at),
            ],

            'stats' => [
                "likes" => $this->likes()->count(),
                'is_liked' => $request->user() ? $this->likes()->where('user_id', $request->user()->id)->exists() : false,
                'comment_count' => $this->comments_count ?? 0,
                'views_count' => $this->views_count ?? 0,
            ],
        ];
    }
}
