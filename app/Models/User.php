<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'username',
        'name',
        'email',
        'type',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Get the users that this user is following
    public function following()
    {
        return $this->belongsToMany(User::class, 'follows', 'follower_id', 'following_id');
    }

    // Get the users that are following this user
    public function followers()
    {
        return $this->belongsToMany(User::class, 'follows', 'following_id', 'follower_id');
    }

    // Get the users that are friends with this user
    public function friends()
    {
        return $this->following()->whereHas('following', function ($query) {
            $query->where('following_id', $this->id);
        });
    }

    // Check if the user is following another user
    public function isFollowing(User $user)
    {
        return $this->following()
            ->wherePivot('status', 'accepted')
            ->where('following_id', $user->id)
            ->exists();
    }

    // Get follow status.
    public function getFollowStatus(User $User, Request $request)
    {
        $me = $request->user();

        if (!$me) return 'You are not logged in'; // Agar foydalanuvchi tizimga kirmagan bo'lsa
        if ($User->id === $me->id) return 'self';

        $isFollowing = $me->following()->where('following_id', $User->id)->exists();
        $isFollower = $me->followers()->where('follower_id', $User->id)->exists();
        $isBlocked = $me->blockedUsers()->where('blocked_id', $User->id)->exists();
        $isBlockedBy = $me->blockedBy()->where('blocker_id', $User->id)->exists();

        if ($isFollowing && $isFollower) return 'friend';
        if ($isFollowing) return 'following';
        if ($isFollower) return 'follow back';
        if ($isBlocked) return 'unblock';
        if ($isBlockedBy) return 'blocked by user';

        return 'follow';
    }

    // The account is private
    public function isPrivate(User $me)
    {
        if ($this->id === $me->id) {
            return false;
        }

        if ($this->type === 'private' && !$me->isFollowing($this)) {
            return true;
        }
        return false;
    }

    public function Blocked(User $me)
    {
        if ($this->blockedUsers()->where('blocked_id', $me->id)->exists()) {
            return true;
        }
        return false;
    }

    public function isBlocked(User $me)
    {
        if ($this->blockedBy()->where('blocker_id', $me->id)->exists()) {
            return true;
        }
        return false;
    }

    // Check if the user is blocked
    public function blockedUsers()
    {
        return $this->belongsToMany(User::class, 'blocks', 'blocker_id', 'blocked_id');
    }

    public function blockedBy()
    {
        return $this->belongsToMany(User::class, 'blocks', 'blocked_id', 'blocker_id');
    }
    public function block($userId)
    {
        return $this->blockedUsers()->attach([$userId]);
    }

    public function unblock(User $user)
    {
        return $this->blockedUsers()->detach($user->id);
    }

    public function posts()
    {
        return $this->hasMany(Post::class);
    }
}
