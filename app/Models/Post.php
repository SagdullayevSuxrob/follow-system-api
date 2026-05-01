<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory;
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

    public function likes()
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function views()
    {
        return $this->hasMany(PostView::class);
    }

    // blok payti postlarni yashirish
    protected static function booteed()
    {
        static::addGlobalScope('hide_blocked_posts', function ($builder) {
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
