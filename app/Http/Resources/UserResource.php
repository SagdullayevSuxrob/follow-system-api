<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'User' => [
                'id' => $this->id,
                'name' => $this->name,
                'username' => $this->username,
                'email' => $this->when(isset($this->email), $this->email)
            ],
            'status' => [
                "Posts" => $this->posts_count,
                "Followers" => $this->followers_count,
                "Following" => $this->following_count,
                "Friends" => $this->friends_count ?? $this->friends()->count(),
            ]
        ];
    }
}
