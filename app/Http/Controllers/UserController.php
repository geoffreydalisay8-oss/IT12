<?php

namespace App\Http\Controllers;

// Import the User model.
// This allows the controller to work with the users table.
use App\Models\User;

// Request contains the data submitted from forms.
use Illuminate\Http\Request;

// Hash is used to securely encrypt/hash passwords
// before saving them to the database.
use Illuminate\Support\Facades\Hash;

// Rule allows us to handle the unique email rule
// when updating an existing user.
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    // ============================================================
    // DISPLAY ALL USERS
    // ============================================================
    //
    // Business purpose:
    // Show the administrator all users/staff accounts.
    //
    public function index()
    {
        // Get all users from the database.
        //
        // latest() shows the newest accounts first.
        $users = User::latest()->get();

        // Send the users to:
        // resources/views/users/index.blade.php
        //
        // Explicit array mapping instead of compact()
        return view('users.index', [
            'users' => $users
        ]);
    }


    // ============================================================
    // SHOW CREATE USER FORM
    // ============================================================
    //
    // Business purpose:
    // Display the form where an administrator can
    // create a new staff/user account.
    //
    public function create()
    {
        // Display the create user page.
        return view('users.create');
    }


    // ============================================================
    // SAVE NEW USER
    // ============================================================
    //
    // Business process:
    //
    // Admin enters user information
    //          ↓
    // Validate information
    //          ↓
    // Hash password
    //          ↓
    // Create user account
    //
    public function store(Request $request)
    {
        // Step 1: Validate the information submitted by the admin.
        $request->validate([
            // User's name is required.
            'name' => 'required|string|max:100',

            // Email is required and must be unique.
            'email' => 'required|email|max:255|unique:users,email',

            // Password is required when creating a new account.
            'password' => 'required|string|min:8|confirmed',

            'role' => 'required|in:admin,staff',

            'status' => 'required|in:active,inactive',
        ]);


        $user = new User();

        $user->name = $request->input('name');
        $user->email = $request->input('email');

        $user->password = Hash::make($request->input('password'));

        $user->role = $request->input('role');
        $user->status = $request->input('status');

        $user->save();

        return redirect()
            ->route('users.index')
            ->with('success', 'User created successfully.');
    }


    public function show($id)
    {
        $user = User::findOrFail($id);

        return view('users.show', [
            'user' => $user
        ]);
    }


    
    public function edit($id)
    {
        $user = User::findOrFail($id);

        return view('users.edit', [
            'user' => $user
        ]);
    }


    
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:100',

            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],

            'password' => 'nullable|string|min:8|confirmed',

            'role' => 'required|in:admin,staff',

            'status' => 'required|in:active,inactive',
        ]);

        $user->name = $request->input('name');
        $user->email = $request->input('email');
        $user->role = $request->input('role');
        $user->status = $request->input('status');

        if ($request->filled('password')) {
            $user->password = Hash::make($request->input('password'));
        }

        $user->save();

        return redirect()
            ->route('users.index')
            ->with('success', 'User updated successfully.');
    }


 
    public function destroy($id)
    {
        $user = User::findOrFail($id);

        $user->delete();

  
        return redirect()
            ->route('users.index')
            ->with('success', 'User deleted successfully.');
    }
}