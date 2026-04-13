<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // Register a new user
    public function register(Request $request)
    {
        $request->validate([
            "username" => "required|string|alpha_dash|max:16|unique:users,username",
            "name" => "nullable|string|max:255",
            "email" => "required|email|unique:users,email",
            'type' => 'in:public,private',
            "password" => "required|string|min:8|confirmed",
        ]);

        $user = User::create([
            "username" => $request->username,
            "name" => $request->name,
            "email" => $request->email,
            "type" => $request->type ?? 'public',
            "password" => bcrypt($request->password),
        ]);

        $token = $user->createToken("auth_token")->plainTextToken;

        return response()->json([
            "message" => "User registered successfully",
            "user" => $user,
            "token" => $token,
        ], 201);
    }

    // Login a user
    public function login(Request $request)
    {
        $request->validate([
            "username_or_email" => "required|string|alpha_dash|max:16",
            "password" => "required|string",
        ]);

        $user = User::where("username", $request->username_or_email)->orWhere("email", $request->username_or_email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                "username_or_email" => ["The provided credentials are incorrect."],
            ]);
        }

        $token = $user->createToken("auth_token")->plainTextToken;

        return response()->json([
            "message" => "User logged in successfully",
            "user" => $user,
            "token" => $token,
        ]);
    }

    // Get Current User
    public function user(Request $request)
    {
        return response()->json([
            "user" => $request->user(),
        ]);
    }

    // Update User Profile
    public function update(Request $request)
    {
        $request->validate([
            "username" => "sometimes|string|alpha_dash|max:16|unique:users,username," . $request->user()->id,
            "name" => "sometimes|string|max:255",
            "email" => "sometimes|email|unique:users,email," . $request->user()->id,
            "type" => "sometimes|string|in:public,private",
            "password" => "sometimes|string|min:8|confirmed",
        ]);

        $user = $request->user();
        $data = $request->only("username", "name", "email", "type");
        if ($request->filled("password")) {
            $data["password"] = Hash::make($request->password);
        }
        $user->update($data);

        return response()->json([
            "message" => "Your profile updated successfully",
            "user" => $user,
        ]);
    }

    // Logout a User
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            "message" => "You logged out successfully",
        ]);
    }

    // Delete account
    public function delete(Request $request)
    {
        $user = $request->user();
        $user->delete();

        return response()->json([
            "message" => "Your account has been deleted successfully",
        ]);
    }
}
