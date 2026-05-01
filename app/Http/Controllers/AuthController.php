<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\UserLoginRequest;
use App\Http\Requests\Auth\UserRegisterRequest;
use App\Http\Requests\Auth\UserUpdateRequest;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function __construct(private AuthService $authService) {}

    // Register a new user
    public function register(UserRegisterRequest $request)
    {
        $user = $this->authService->userRegister($request);

        $token = $user->createToken("auth_token")->plainTextToken;

        return response()->json([
            "message" => "Muvaffaqiyatli ro'yhatdan o'tdingiz..",
            "user" => $user,
            "token" => $token,
        ], 201);
    }

    // Login a user
    public function login(UserLoginRequest $request)
    {
        $user = $this->authService->userLogin($request);

        $token = $user->createToken("auth_token")->plainTextToken;

        return response()->json([
            "message" => "Hisobingizga muvaffaqiyatli kirdingiz",
            "user" => $user,
            "token" => $token,
        ]);
    }

    // Get Current User
    public function user()
    {
        return response()->json([
            "user" => Auth::user(),
        ]);
    }

    // Update User Profile
    public function update(UserUpdateRequest $request)
    {
        $user = $this->authService->userUpdate($request);

        return response()->json([
            "message" => "Sizning profil malumotlaringiz yangilandi.",
            "user" => $user,
        ]);
    }

    // Logout a User
    public function logout(Request $request)
    {
        $this->authService->userLogout($request);

        return response()->json([
            "message" => "Siz profilingizdan muvaffaqiyatli chiqdingiz",
        ]);
    }

    // Delete account
    public function delete(Request $request)
    {
        $this->authService->userDelete($request);

        return response()->json([
            "message" => "Sizning hisobingiz o'chirildi",
        ]);
    }
}
