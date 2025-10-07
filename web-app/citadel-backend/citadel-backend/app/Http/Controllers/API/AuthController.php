<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    public function login(Request $req)
    {
        $req->validate([
            'email' => 'required|string', // Changed from email to string to support username
            'password' => 'required'
        ]);

        // Try to find user by email or username
        $user = User::where('email', $req->email)
                   ->orWhere('username', $req->email)
                   ->first();
                   
        if (!$user || !Hash::check($req->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        // create personal access token
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
           'token' => $token,
                'user' => [
                'id' => $user->id,
                'fullname' => $user->fullname,
                'email' => $user->email,
                'username' => $user->username,
                'role' => $user->role,
            ]

        ]);
    }

    public function logout(Request $req)
    {
        // revoke the current access token
        $req->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out']);
    }

    // optional: get current user
    public function me(Request $req) { return response()->json($req->user()); }
}
