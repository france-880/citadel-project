<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AccountController extends Controller
{
    public function index(Request $request)
    {
        // Check if user is authorized (super_admin, dean, or college_secretary)
        $currentUser = auth()->user();
        if (!in_array($currentUser->role, ['super_admin', 'dean', 'college_secretary'])) {
            return response()->json([
                'message' => 'Unauthorized. Only super admins, deans, secretaries, and program heads can access account management.'
            ], 403);
        }

        $filters = $request->only(['search', 'role']);
        $perPage = $request->input('per_page', 10);

        $query = Account::query();

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
        $accounts = $query->latest()->paginate($perPage);

       // 🧾 Transform data for frontend - Super Admin's Account Management (All User Types)
        $accounts->getCollection()->transform(function ($u) {
            $roleNames = [
                'super_admin' => 'Super Admin',
                'dean' => 'Dean',
                'college_secretary' => 'College Secretary',
            ];

            return [
                'id' => $u->id,
                'fullname' => $u->fullname,
                'college_id' => $u->college_id,
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
        return response()->json($accounts);
    }

    public function store(Request $request)
    {
        // Check if user is authorized (super_admin only)
        $currentUser = auth()->user();
        if ($currentUser->role !== 'super_admin') {
            return response()->json([
                'message' => 'Unauthorized. Only super admins can create accounts.'
            ], 403);
        }

        // Validate incoming camelCase data from React
        $validated = $request->validate([
            'fullname' => 'required|string|max:255',
            'college_id' => 'required|string',
            'dob' => 'required|date',
            'role' => 'required|string',
            'gender' => 'required|string',
            'address' => 'required|string',
            'contact' => 'required|string',
            'email' => 'required|email|unique:accounts,email',
            'username' => 'required|string|unique:accounts,username',
            'password' => 'required|string|min:6',
        ]);

        // Map role values from frontend to database values
        $roleMapping = [
            'Super Admin' => 'super_admin',
            'Dean' => 'dean',  
            'College Secretary' => 'college_secretary'
        ];

        // Map camelCase → snake_case
        $payload = [
            'fullname' => $validated['fullname'],
            'college_id' => $validated['college_id'],
            'dob' => $validated['dob'],
            'role' => $roleMapping[$validated['role']] ?? strtolower(str_replace(' ', '_', $validated['role'])),
            'gender' => $validated['gender'],
            'address' => $validated['address'],
            'contact' => $validated['contact'],
            'email' => $validated['email'],
            'username' => $validated['username'],
            'password' => Hash::make($validated['password']),
        ];

        $account = Account::create($payload);

        return response()->json($account, 201);
    }

    public function show($id)
    {
        return response()->json(Account::findOrFail($id));
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
            'college_id' => 'required|string',
            'dob' => 'required|date',
            'gender' => 'required|string',
            'address' => 'required|string',
            'contact' => 'required|string',
            'email' => 'required|email|unique:accounts,email,' . $user->id,
            'username' => 'required|string|unique:accounts,username,' . $user->id,
        ]);

        // Users cannot update their own role - only admins can do that
        $payload = [
            'fullname' => $validated['fullname'],
            'college_id' => $validated['college_id'],
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

            // Check if user is super_admin
            $currentUser = auth()->user();
            if ($currentUser->role !== 'super_admin') {
                return response()->json([
                    'message' => 'Unauthorized. Only super admins can edit accounts.'
                ], 403);
            }

            $user = Account::findOrFail($id);

        $validated = $request->validate([
            'fullname' => 'required|string|max:255',
            'college_id' => 'required|string',
            'dob' => 'required|date',
            'role' => 'required|string',
            'gender' => 'required|string',
            'address' => 'required|string',
            'contact' => 'required|string',
            'email' => 'required|email|unique:accounts,email,' . $id,
            'username' => 'required|string|unique:accounts,username,' . $id,
            'password' => 'sometimes|string|min:6', // Only validate password if provided
        ]);

        // Map role values from frontend to database values
        $roleMapping = [
            'Super Admin' => 'super_admin',
            'Dean' => 'dean',
            'Secretary' => 'secretary',
            'Program Head' => 'program_head',
            'Professor' => 'prof'
        ];

        // Map camelCase → snake_case
        $payload = [
            'fullname' => $validated['fullname'],
            'college_id' => $validated['college_id'],
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
        // Check if user is super_admin
        $currentUser = auth()->user();
        if ($currentUser->role !== 'super_admin') {
            return response()->json([
                'message' => 'Unauthorized. Only super admins can delete accounts.'
            ], 403);
        }

        $account = Account::findOrFail($id);
        $account->delete();

        return response()->json(['message' => 'Account deleted successfully']);
    }

    public function deleteMultiple(Request $request)
    {
        // Check if user is super_admin
        $currentUser = auth()->user();
        if ($currentUser->role !== 'super_admin') {
            return response()->json([
                'message' => 'Unauthorized. Only super admins can delete accounts.'
            ], 403);
        }

        $ids = $request->input('ids');

        if (!$ids || !is_array($ids)) {
            return response()->json(['message' => 'No user IDs provided'], 400);
        }

        Account::whereIn('id', $ids)->delete();

        return response()->json(['message' => 'Accounts deleted successfully']);
    }

    public function changePassword(Request $request, $id)
    {
        $user = Account::findOrFail($id);
    
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