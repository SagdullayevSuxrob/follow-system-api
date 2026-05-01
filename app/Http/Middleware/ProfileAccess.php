<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProfileAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        $me = auth()->user();
        $targetUsers = collect();

        // post route uchun tekshiruv 
        if ($post = $request->route('post')) {
            $targetUsers->push($post->user);
        }

        // user route uchun tekshiruv
        elseif ($user = $request->route('user')) {
            $targetUsers->push($user);
        }

        // comment route uchun tekshiruv
        elseif ($comment = $request->route('comment')) {
            // comment egasi
            if ($comment->user) {
                $targetUsers->push($comment->user);
            }
            // post egasi
            if ($comment->post->user) {
                $targetUsers->push($comment->post->user);
            }
        }

        foreach ($targetUsers as $targetUser) {

            if (!$targetUser || $me->id === $targetUser->id) {
                continue;
            }

            // Bu foydalanuvchi meni bloklagan yoki Men bu foydalanuvchini bloklaganman.
            if ($targetUser->isBlockedByMe($me) || $targetUser->hasBlockedMe($me)) {
                return response()->json([
                    "message" => "Bloklangansiz yoki foydalanuvchini bloklagansiz"
                ], 403);
            }
        }

        return $next($request);
    }
}
