<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $fillable = ['caption', 'user_id'];

    public function getMediaUrlAttribute()
    {
        return $this->media_path ? asset('storage/' . $this->media_path) : null;
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function media()
    {
        return $this->hasMany(PostMedia::class);
    }
}
