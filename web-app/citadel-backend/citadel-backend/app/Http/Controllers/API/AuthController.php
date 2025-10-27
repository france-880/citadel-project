<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Student;
use App\Models\Account;

class AuthController extends Controller
{
    /**
     * 🔐 LOGIN (for accounts, users, and students)
     */
    public function login(Request $req)
    {
        $req->validate([
            'email' => 'required|string', // supports email or username
            'password' => 'required|string',
        ]);

        // Try to find the user in all 3 tables
        $user = $this->findUserByIdentifier($req->email);

        if (!$user || !Hash::check($req->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        // Create a token for the authenticated user
        $token = $user->createToken('api-token')->plainTextToken;

        // Return unified response
        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'fullname' => $user->fullname ?? 'Unknown User',
                'email' => $user->email ?? null,
                'username' => $user->username ?? null,
                'role' => $user->role ?? 'user',
                'origin' => $user->origin, // added field to indicate table source
                'department' => $user->department ?? null,
                'college_id' => $user->college_id ?? null,
                'program' => $user->program ?? null,
                'contact' => $user->contact ?? null,
                'address' => $user->address ?? null,
                'gender' => $user->gender ?? null,
                'dob' => $user->dob ?? null,
            ],
        ]);
    }

    /**
     * 🧩 Helper: Find user by email or username across all tables
     */
    private function findUserByIdentifier($identifier)
    {
        // 1. Accounts table (super admin, dean, secretary)
        $user = Account::where('email', $identifier)
            ->orWhere('username', $identifier)
            ->first();
        if ($user) {
            $user->origin = 'accounts';
            return $user;
        }

        // 2. Users table (professors)
        $user = User::where('email', $identifier)
            ->orWhere('username', $identifier)
            ->first();
        if ($user) {
            $user->origin = 'users';
            return $user;
        }

        // 3. Students table
        $user = Student::where('email', $identifier)
            ->orWhere('username', $identifier)
            ->first();

        if ($user) {
            $user->origin = 'students';
            // assign default role manually (no 'role' column in DB)
            $user->role = 'student';
            return $user;
        }

        return null;
    }

    /**
     * 🚪 LOGOUT - revoke current token
     */
    public function logout(Request $req)
    {
        $req->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully']);
    }

    /**
     * 🔑 CHANGE PASSWORD
     */
    public function changePassword(Request $req)
    {
        $user = $req->user();

        $validated = $req->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json(['message' => 'Current password is incorrect.'], 422);
        }

        $user->update(['password' => Hash::make($validated['password'])]);

        return response()->json(['message' => 'Password updated successfully.']);
    }
}
