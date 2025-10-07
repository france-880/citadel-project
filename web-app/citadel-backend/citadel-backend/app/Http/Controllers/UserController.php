<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        $users = User::all()->map(function($s) {
            return [
                'id' => $s->id,
                'fullname' => $s->fullname,
                'department' => $s->department,
                'dob' => $s->dob,
                'role' => $s->role,
                'gender' => $s->gender,
                'address' => $s->address,
                'contact' => $s->contact,
                'email' => $s->email,
                'username' => $s->username,
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

    public function update(Request $request, $id)
    {
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
            'username' => 'required|string',
        ]);

        // Map camelCase → snake_case
        $payload = [
            'fullname' => $validated['fullname'],
            'department' => $validated['department'],
            'dob' => $validated['dob'],
            'role' => $validated['role'],
            'gender' => $validated['gender'],
            'address' => $validated['address'],
            'contact' => $validated['contact'],
            'email' => $validated['email'],
            'username' => $validated['username'],
        ];

         // Update lang kapag may laman yung password
         if ($request->filled('password')) {
            $payload['password'] = bcrypt($validated['password']);
        }
    
        $user->update($payload);
    
        return response()->json([
            'message' => 'User updated successfully!',
            'data' => $user
        ]);
    }
    

    public function deleteMultiple(Request $request)
    {
        $ids = $request->input('ids');

        if (!$ids || !is_array($ids)) {
            return response()->json(['message' => 'No user IDs provided'], 400);
        }

        User::whereIn('id', $ids)->delete();

        return response()->json(['message' => 'Users deleted successfully']);
    }
}
