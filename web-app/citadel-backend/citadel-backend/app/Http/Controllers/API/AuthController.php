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
        $usernameExists = $user !== null;

        // Determine password correctness
        $passwordCorrect = false;
        if ($user) {
            $passwordCorrect = Hash::check($req->password, $user->password);
        } else {
            // User doesn't exist, check if password matches any user (for determining error message)
            // This is done to differentiate between "wrong username" and "both wrong"
            $passwordMatchesAnyUser = $this->checkPasswordAgainstAllUsers($req->password);
        }

        // Determine error message based on validation results
        if (!$usernameExists) {
            // Username doesn't exist
            if (isset($passwordMatchesAnyUser) && $passwordMatchesAnyUser) {
                // Password matches some user, but username is wrong
                \Log::info('Login failed: Invalid username (password correct)', ['identifier' => $req->email]);
                return response()->json(['message' => 'Invalid username.', 'error_type' => 'username'], 401);
            } else {
                // Both username and password are wrong
                \Log::info('Login failed: Invalid username and password', ['identifier' => $req->email]);
                return response()->json(['message' => 'Invalid username and password.', 'error_type' => 'both'], 401);
            }
        } else {
            // Username exists
            if (!$passwordCorrect) {
                // Username exists but password is wrong
                \Log::info('Login failed: Password mismatch', [
                    'identifier' => $req->email,
                    'user_id' => $user->id,
                    'user_email' => $user->email ?? 'N/A',
                    'user_username' => $user->username ?? 'N/A'
                ]);
                return response()->json(['message' => 'Incorrect password.', 'error_type' => 'password'], 401);
            }
        }

        // Both username and password are correct - proceed with login
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
                'has_facial_recognition' => $user->has_facial_recognition ?? false, // for students
            ],
        ]);
    }

    /**
     * 🧩 Helper: Find user by email or username across all tables
     */
    private function findUserByIdentifier($identifier)
    {
        // 1. Accounts table (super admin, dean, secretary)
        $user = Account::where(function($query) use ($identifier) {
            $query->where('email', $identifier)
                  ->orWhere('username', $identifier);
        })->first();
        if ($user) {
            $user->origin = 'accounts';
            return $user;
        }

        // 2. Users table (professors)
        $user = User::where(function($query) use ($identifier) {
            $query->where('email', $identifier)
                  ->orWhere('username', $identifier);
        })->first();
        if ($user) {
            $user->origin = 'users';
            return $user;
        }

        // 3. Students table
        $user = Student::where(function($query) use ($identifier) {
            $query->where('email', $identifier)
                  ->orWhere('username', $identifier);
        })->first();

        if ($user) {
            $user->origin = 'students';
            // assign default role manually (no 'role' column in DB)
            $user->role = 'student';
            return $user;
        }

        return null;
    }

    /**
     * 🔍 Helper: Check if password matches any user in the system
     * Used to differentiate between "wrong username" vs "both wrong"
     * Note: This checks a sample of users for performance (limit to first 100 per table)
     */
    private function checkPasswordAgainstAllUsers($password)
    {
        // Check a limited sample from each table for performance
        // This is a trade-off between accurate error messages and performance
        
        // Check Accounts table (limit to 100)
        $accounts = Account::limit(100)->get();
        foreach ($accounts as $account) {
            if (Hash::check($password, $account->password)) {
                return true;
            }
        }

        // Check Users table (limit to 100)
        $users = User::limit(100)->get();
        foreach ($users as $user) {
            if (Hash::check($password, $user->password)) {
                return true;
            }
        }

        // Check Students table (limit to 100)
        $students = Student::limit(100)->get();
        foreach ($students as $student) {
            if (Hash::check($password, $student->password)) {
                return true;
            }
        }

        return false;
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

    /**
     * 👤 GET CURRENT USER PROFILE
     */
    public function me(Request $req)
    {
        $user = $req->user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Determine origin based on the model type
        $origin = 'users'; // default
        if ($user instanceof \App\Models\Student) {
            $origin = 'students';
        } elseif ($user instanceof \App\Models\Account) {
            $origin = 'accounts';
        }

        // Return user data with all available fields
        return response()->json([
            'id' => $user->id,
            'fullname' => $user->fullname ?? null,
            'email' => $user->email ?? null,
            'username' => $user->username ?? null,
            'role' => $user->role ?? 'student',
            'origin' => $origin,
            'department' => $user->department ?? null,
            'college_id' => $user->college_id ?? null,
            'program' => $user->program ?? null,
            'contact' => $user->contact ?? null,
            'address' => $user->address ?? null,
            'gender' => $user->gender ?? null,
            'dob' => $user->dob ?? null,
            'has_facial_recognition' => $user->has_facial_recognition ?? false,
        ]);
    }

    /**
     * ✏️ UPDATE CURRENT USER PROFILE
     */
    public function updateProfile(Request $req)
    {
        $user = $req->user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Validate incoming data - allow nullable values
        $validated = $req->validate([
            'fullname' => 'sometimes|nullable|string|max:255',
            'email' => 'sometimes|nullable|email|max:255',
            'contact' => 'sometimes|nullable|string|max:20',
            'address' => 'sometimes|nullable|string|max:500',
            'department' => 'sometimes|nullable|string|max:255',
            'dob' => 'sometimes|nullable|date',
            'gender' => 'sometimes|nullable|in:Male,Female',
            'username' => 'sometimes|nullable|string|max:255',
        ]);

        // Filter out empty strings and convert to null
        $updateData = [];
        foreach ($validated as $key => $value) {
            if ($value !== '' && $value !== null) {
                $updateData[$key] = $value;
            } elseif ($value === '') {
                $updateData[$key] = null;
            }
        }

        // Update only the fields that were provided
        $user->update($updateData);

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => [
                'id' => $user->id,
                'fullname' => $user->fullname ?? null,
                'email' => $user->email ?? null,
                'username' => $user->username ?? null,
                'role' => $user->role ?? null,
                'department' => $user->department ?? null,
                'college_id' => $user->college_id ?? null,
                'program' => $user->program ?? null,
                'contact' => $user->contact ?? null,
                'address' => $user->address ?? null,
                'gender' => $user->gender ?? null,
                'dob' => $user->dob ?? null,
            ]
        ]);
    }
}
