<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name'                  => 'required|string|max:100',
            'email'                 => 'required|email|unique:users,email',
            'password'              => 'required|string|min:8|confirmed',
            'currency'              => 'nullable|string|max:10',
        ]);

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
            'currency' => $validated['currency'] ?? 'USD',
        ]);

        // Seed default categories for new user
        $defaults = [
            ['name' => 'Salary',        'type' => 'income',  'icon' => '💼', 'color' => '#10b981'],
            ['name' => 'Freelance',     'type' => 'income',  'icon' => '💻', 'color' => '#6366f1'],
            ['name' => 'Investments',   'type' => 'income',  'icon' => '📈', 'color' => '#f59e0b'],
            ['name' => 'Food',          'type' => 'expense', 'icon' => '🍔', 'color' => '#ef4444'],
            ['name' => 'Transport',     'type' => 'expense', 'icon' => '🚗', 'color' => '#f97316'],
            ['name' => 'Utilities',     'type' => 'expense', 'icon' => '💡', 'color' => '#eab308'],
            ['name' => 'Rent',          'type' => 'expense', 'icon' => '🏠', 'color' => '#8b5cf6'],
            ['name' => 'Entertainment', 'type' => 'expense', 'icon' => '🎬', 'color' => '#ec4899'],
            ['name' => 'Healthcare',    'type' => 'expense', 'icon' => '🏥', 'color' => '#14b8a6'],
            ['name' => 'Shopping',      'type' => 'expense', 'icon' => '🛍️', 'color' => '#06b6d4'],
        ];

        foreach ($defaults as $cat) {
            Category::create(array_merge($cat, ['user_id' => $user->id]));
        }

        Auth::login($user);

        return response()->json([
            'message' => 'Registration successful.',
            'user'    => $user,
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if (! Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $request->session()->regenerate();

        return response()->json([
            'message' => 'Login successful.',
            'user'    => Auth::user(),
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Logged out.']);
    }

    public function me(Request $request)
    {
        return response()->json($request->user());
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name'     => 'sometimes|string|max:100',
            'currency' => 'sometimes|string|max:10',
            'password' => 'sometimes|string|min:8|confirmed',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        return response()->json($user);
    }
}
