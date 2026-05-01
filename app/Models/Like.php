<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Like extends Model
{
    protected $fillable = [
        'user_id',
        'likeable_id',
        'likeable_type'
    ];

    public function likeable()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected static function booted()
    {
        static::addGlobalScope('hide_blocked_likes', function ($builder) {
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
