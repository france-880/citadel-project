<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->only(['search', 'role']);
        $perPage = $request->input('per_page', 10);

        $query = User::query();

        // 🔍 Dynamic search (fullname, email, id)
        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $search = $filters['search'];
                $q->where('fullname', 'ILIKE', "%{$search}%")
                  ->orWhere('email', 'ILIKE', "%{$search}%")
                  ->orWhere('id', 'ILIKE', "%{$search}%")
                  ->orWhere('username', 'ILIKE', "%{$search}%");
            });
        }

        // 🧩 Role filter
        if (!empty($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        // 🕓 Paginate + sort by latest
        $users = $query->latest()->paginate($perPage);

       // 🧾 Transform data for frontend
        $users->getCollection()->transform(function ($u) {
            $roleNames = [
                'program_head' => 'Program Head',
                'dean' => 'Dean',
                'prof' => 'Professor',
                'guard' => 'Guard',
                'super_admin' => 'Super Admin',
            ];

            return [
                'id' => $u->id,
                'fullname' => $u->fullname,
                'department' => $u->department,
                'dob' => $u->dob,
                // ✅ Convert role slug to readable label
                'role' => $roleNames[$u->role] ?? ucfirst(str_replace('_', ' ', $u->role)),
                'gender' => $u->gender,
                'address' => $u->address,
                'contact' => $u->contact,
                'email' => $u->email,
                'username' => $u->username,
                'created_at' => $u->created_at,
            ];
        });
        return response()->json($users);
    }

    public function store(Request $request)
    {
        // Validate incoming camelCase data from React
        $validated = $request->validate([
            'fullname' => 'required|string|max:255',
            'department' => 'required|string',
            'dob' => 'required|date',
            'role' => 'required|string',
            'gender' => 'required|string',
            'address' => 'required|string',
            'contact' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'username' => 'required|string|unique:users,username',
            'password' => 'required|string|min:6',
        ]);

        // Map role values from frontend to database values
        $roleMapping = [
            'Program Head' => 'program_head',
            'Dean' => 'dean', 
            'Professor' => 'prof',
            'Guard' => 'guard',
            'Super Admin' => 'super_admin'
        ];

        // Map camelCase → snake_case
        $payload = [
            'fullname' => $validated['fullname'],
            'department' => $validated['department'],
            'dob' => $validated['dob'],
            'role' => $roleMapping[$validated['role']] ?? strtolower(str_replace(' ', '_', $validated['role'])),
            'gender' => $validated['gender'],
            'address' => $validated['address'],
            'contact' => $validated['contact'],
            'email' => $validated['email'],
            'username' => $validated['username'],
            'password' => Hash::make($validated['password']),
        ];

        $user = User::create($payload);

        return response()->json($user, 201);
    }

    public function show($id)
    {
        return response()->json(User::findOrFail($id));
    }

    /**
     * Update user profile (for users updating their own profile)
     * Users can only update their own profile and cannot change role
     */
    public function updateProfile(Request $request)
    {
        $user = auth()->user(); // Get authenticated user
        
        $validated = $request->validate([
            'fullname' => 'required|string|max:255',
            'department' => 'required|string',
            'dob' => 'required|date',
            'gender' => 'required|string',
            'address' => 'required|string',
            'contact' => 'required|string',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'username' => 'required|string|unique:users,username,' . $user->id,
        ]);

        // Users cannot update their own role - only admins can do that
        $payload = [
            'fullname' => $validated['fullname'],
            'department' => $validated['department'],
            'dob' => $validated['dob'],
            'gender' => $validated['gender'],
            'address' => $validated['address'],
            'contact' => $validated['contact'],
            'email' => $validated['email'],
            'username' => $validated['username'],
        ];

        // Update password only if provided
        if ($request->filled('password')) {
            $request->validate(['password' => 'string|min:6']);
            $payload['password'] = Hash::make($request->password);
        }
    
        $user->update($payload);
    
        return response()->json([
            'message' => 'Profile updated successfully!',
            'data' => $user
        ]);
    }

    /**
     * Update user (for admin editing any user)
     * Admins can update any user including their role
     */
    public function updateUser(Request $request, $id)
    {
        try {
            \Log::info('Update user request:', [
                'user_id' => $id,
                'request_data' => $request->all(),
                'auth_user' => auth()->user() ? auth()->user()->id : 'not_authenticated'
            ]);

            // Check if user is admin (dean, super_admin, or program_head)
            $currentUser = auth()->user();
            if (!in_array($currentUser->role, ['dean', 'super_admin', 'program_head'])) {
                return response()->json([
                    'message' => 'Unauthorized. Only administrators can edit users.'
                ], 403);
            }

            $user = User::findOrFail($id);

        $validated = $request->validate([
            'fullname' => 'required|string|max:255',
            'department' => 'required|string',
            'dob' => 'required|date',
            'role' => 'required|string',
            'gender' => 'required|string',
            'address' => 'required|string',
            'contact' => 'required|string',
            'email' => 'required|email|unique:users,email,' . $id,
            'username' => 'required|string|unique:users,username,' . $id,
            'password' => 'sometimes|string|min:6', // Only validate password if provided
        ]);

        // Map role values from frontend to database values
        $roleMapping = [
            'Program Head' => 'program_head',
            'Dean' => 'dean', 
            'Professor' => 'prof',
            'Guard' => 'guard',
            'Super Admin' => 'super_admin'
        ];

        // Map camelCase → snake_case
        $payload = [
            'fullname' => $validated['fullname'],
            'department' => $validated['department'],
            'dob' => $validated['dob'],
            'role' => $roleMapping[$validated['role']] ?? strtolower(str_replace(' ', '_', $validated['role'])),
            'gender' => $validated['gender'],
            'address' => $validated['address'],
            'contact' => $validated['contact'],
            'email' => $validated['email'],
            'username' => $validated['username'],
        ];

         // Update lang kapag may laman yung password
         if ($request->filled('password') && !empty($request->password)) {
            $payload['password'] = Hash::make($request->password);
        }
    
        $user->update($payload);
    
        return response()->json([
            'message' => 'User updated successfully!',
            'data' => $user
        ]);
        
        } catch (\Exception $e) {
            \Log::error('Error updating user:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'Error updating user: ' . $e->getMessage()
            ], 500);
        }
    }
    

    public function destroy($id)
    {
        // Check if user is admin (dean, super_admin, or program_head)
        $currentUser = auth()->user();
        if (!in_array($currentUser->role, ['dean', 'super_admin', 'program_head'])) {
            return response()->json([
                'message' => 'Unauthorized. Only administrators can delete users.'
            ], 403);
        }

        $user = User::findOrFail($id);
        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }

    public function deleteMultiple(Request $request)
    {
        // Check if user is admin (dean, super_admin, or program_head)
        $currentUser = auth()->user();
        if (!in_array($currentUser->role, ['dean', 'super_admin', 'program_head'])) {
            return response()->json([
                'message' => 'Unauthorized. Only administrators can delete users.'
            ], 403);
        }

        $ids = $request->input('ids');

        if (!$ids || !is_array($ids)) {
            return response()->json(['message' => 'No user IDs provided'], 400);
        }

        User::whereIn('id', $ids)->delete();

        return response()->json(['message' => 'Users deleted successfully']);
    }

    public function changePassword(Request $request, $id)
    {
        $user = User::findOrFail($id);
    
        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);
    
        // Check if current password is correct
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'Current password is incorrect.'
            ], 422);
        }
    
        // Update the password
        $user->update([
            'password' => Hash::make($request->password),
        ]);
    
        return response()->json([
            'message' => 'Password updated successfully.'
        ]);
    }
    
}