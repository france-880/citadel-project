<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Student;
use App\Models\Account;

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

        // Try to find user by email or username in users, accounts, and students tables
        $user = User::where('email', $req->email)
                   ->orWhere('username', $req->email)
                   ->first();
                   
        // If not found in users table, check accounts table (for super admin, dean, etc.)
        if (!$user) {
            $user = Account::where('email', $req->email)
                          ->orWhere('username', $req->email)
                          ->first();
        }
                   
        // If not found in accounts table, check students table
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
                'fullname' => $user->fullname ?? 'Unknown User',
                'email' => $user->email,
                'username' => $user->username,
                'role' => $user->role ?? 'user',
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
        
        // Ensure fullname is not null
        if (empty($user->fullname)) {
            $user->fullname = 'Unknown User';
        }
        
        return response()->json([
            'id' => $user->id,
            'fullname' => $user->fullname,
            'email' => $user->email,
            'username' => $user->username,
            'role' => $user->role,
            'contact' => $user->contact ?? '',
            'department' => $user->department ?? '',
            'dob' => $user->dob ?? '',
            'gender' => $user->gender ?? '',
            'address' => $user->address ?? '',
        ]); 
    }

    // Get current user endpoint (alias for /me)
    public function user(Request $req) { 
        $user = $req->user();
        
        // If user doesn't have a role (students), set it
        if (!isset($user->role) || empty($user->role)) {
            $user->role = 'student';
        }
        
        // Ensure fullname is not null
        if (empty($user->fullname)) {
            $user->fullname = 'Unknown User';
        }
        
        return response()->json([
            'id' => $user->id,
            'fullname' => $user->fullname,
            'email' => $user->email,
            'username' => $user->username,
            'role' => $user->role,
            'contact' => $user->contact ?? '',
            'department' => $user->department ?? '',
            'dob' => $user->dob ?? '',
            'gender' => $user->gender ?? '',
            'address' => $user->address ?? '',
        ]); 
    }

    /**
     * Update user profile (works for users from any table: users, accounts, students)
     */
    public function updateProfile(Request $req) {
        $user = $req->user();
        
        $validated = $req->validate([
            'fullname' => 'required|string|max:255',
            'department' => 'nullable|string',
            'dob' => 'nullable|date',
            'gender' => 'nullable|string',
            'address' => 'nullable|string',
            'contact' => 'nullable|string',
            'email' => 'required|email',
            'username' => 'required|string',
        ]);

        // Determine which table the user belongs to and update accordingly
        $payload = [
            'fullname' => $validated['fullname'],
            'email' => $validated['email'],
            'username' => $validated['username'],
        ];

        // Add optional fields if they exist in the model
        if (isset($validated['department'])) $payload['department'] = $validated['department'];
        if (isset($validated['dob'])) $payload['dob'] = $validated['dob'];
        if (isset($validated['gender'])) $payload['gender'] = $validated['gender'];
        if (isset($validated['address'])) $payload['address'] = $validated['address'];
        if (isset($validated['contact'])) $payload['contact'] = $validated['contact'];

        // Update password only if provided
        if ($req->filled('password')) {
            $req->validate(['password' => 'string|min:6']);
            $payload['password'] = Hash::make($req->password);
        }

        // Update the user
        $user->update($payload);

        return response()->json([
            'message' => 'Profile updated successfully!',
            'data' => [
                'id' => $user->id,
                'fullname' => $user->fullname,
                'email' => $user->email,
                'username' => $user->username,
                'role' => $user->role ?? 'user',
            ]
        ]);
    }

    /**
     * Change password for current user (works for users from any table)
     */
    public function changePassword(Request $req) {
        $user = $req->user();
        
        $validated = $req->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ]);

        // Check if current password is correct
        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'message' => 'Current password is incorrect.'
            ], 422);
        }

        // Update the password
        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return response()->json([
            'message' => 'Password updated successfully.'
        ]);
    }
}