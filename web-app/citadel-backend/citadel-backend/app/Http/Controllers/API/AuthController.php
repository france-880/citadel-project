<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Student;

class AuthController extends Controller
{
    public function login(Request $req)
    {
        $req->validate([
            'email' => 'required|string', // Changed from email to string to support username
            'password' => 'required'
        ]);

        // Debug: Log the incoming request
        \Log::info('Login attempt:', [
            'email/username' => $req->email,
            'has_password' => !empty($req->password)
        ]);

        // Try to find user by email or username in both users and students tables
        $user = User::where('email', $req->email)
                   ->orWhere('username', $req->email)
                   ->first();
                   
        // If not found in users table, check students table
        if (!$user) {
            $user = Student::where('email', $req->email)
                          ->orWhere('username', $req->email)
                          ->first();
                          
            // Set role for students
            if ($user) {
                $user->role = 'student';
            }
        }
                   
        // Debug: Log user found
        if ($user) {
            \Log::info('User found:', [
                'id' => $user->id,
                'email' => $user->email,
                'username' => $user->username,
                'fullname' => $user->fullname,
                'role' => $user->role,
                'password_hash' => substr($user->password, 0, 10) . '...' // First 10 chars for debugging
            ]);
        } else {
            \Log::info('No user found with email/username:', ['email/username' => $req->email]);
        }
                   
        if (!$user || !Hash::check($req->password, $user->password)) {
            \Log::info('Authentication failed:', [
                'user_exists' => $user ? 'yes' : 'no',
                'password_check' => $user ? (Hash::check($req->password, $user->password) ? 'passed' : 'failed') : 'no_user'
            ]);
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        // create personal access token (now works for both users and students)
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
    public function me(Request $req) { 
        $user = $req->user();
        
        // If user doesn't have a role (students), set it
        if (!isset($user->role) || empty($user->role)) {
            $user->role = 'student';
        }
        
        return response()->json($user); 
    }
}
