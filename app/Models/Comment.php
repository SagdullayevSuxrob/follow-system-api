<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    protected $fillable = [
        'user_id',
        'post_id',
        'parent_id',
        'content'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    public function likes()
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    public function replies()
    {
        return $this->hasMany(Comment::class, 'parent_id');
    }

    public function parent()
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    protected static function booted()
    {
        static::addGlobalScope('hide_blocked_comments', function ($builder) {
            if (auth()->check()) {
                $meId = auth()->id();
                $builder->whereDoesntHave('user', function ($q) use ($meId) {
                    $q->whereHas('blockers', function ($query) use ($meId) {
                        $query->where('blocker_id', $meId);
                    })->orWhereHas('blockedUsers', function ($query) use ($meId) {
                        $query->where('blocked_id', $meId);
                    });
                });
            }
        });
    }
}
