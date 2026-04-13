<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PostMedia extends Model
{
    protected $fillable = ['post_id', 'media_path'];

    public function getMediaUrlAttribute()
    {
        return asset('storage/' . $this->media_path);
    }

    public function post()
    {
        return $this->belongsTo(Post::class);
    }
}
