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
            'user' => $this->user->name . ' [@' . $this->user->username . ']',
            "post" => [
                'id' => $this->id,
                'media' => $this->media->map(function ($item) {
                    return [
                        'id'  => $item->id,
                        'url' => $item->media_url,
                    ];
                }),
                'caption' => $this->caption
                    ? $this->caption . '  updated ' . $this->updated_at->diffForHumans()
                    : $this->updated_at->diffForHumans(),
                'posted_at' => $this->created_at->format('d. m. Y'),
            ]
        ];
    }
}
