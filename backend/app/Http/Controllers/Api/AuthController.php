<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * B2C Consumer Registration
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'phone' => 'nullable|string|max:30',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
            'role' => 'customer',
            'user_mode' => 'b2c',
            'status' => 'active',
            'avatar' => 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=200&h=200&q=80',
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Registration successful! Welcome to MotoCar Custom.',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,
                'user_mode' => $user->user_mode,
                'avatar_url' => $user->avatar,
                'is_admin' => in_array($user->role, ['admin', 'super_admin']),
                'is_b2b' => $user->user_mode === 'b2b',
                'b2b_profile' => null,
            ],
        ], 201);
    }

    /**
     * User Login (Customer, B2B Partner, Admin)
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials do not match our records.'],
            ]);
        }

        if ($user->status === 'banned') {
            return response()->json([
                'status' => 'error',
                'message' => 'This account has been suspended. Please contact customer support.',
            ], 403);
        }

        // Revoke old tokens if any
        $user->tokens()->delete();

        $token = $user->createToken('auth_token')->plainTextToken;

        // Check for garage partner profile
        $garage = DB::table('partner_garages')->where('owner_user_id', $user->id)->first();
        $b2bProfile = null;

        if ($garage || $user->role === 'b2b_client') {
            $b2bProfile = [
                'company_name' => $garage ? $garage->name : $user->name,
                'tier_level' => 'Tier 1 Wholesale',
                'status' => $garage ? $garage->status : 'active',
                'credit_limit' => 5000,
            ];
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Welcome back, ' . $user->name . '!',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,
                'user_mode' => $user->user_mode,
                'avatar_url' => $user->avatar ?: 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=200&h=200&q=80',
                'is_admin' => in_array($user->role, ['admin', 'super_admin']),
                'is_b2b' => $user->user_mode === 'b2b' || $user->role === 'b2b_client',
                'b2b_profile' => $b2bProfile,
            ],
        ]);
    }

    /**
     * B2B Partner / Garage Registration
     */
    public function b2bRegister(Request $request)
    {
        $validated = $request->validate([
            'contact_name' => 'required|string|max:255',
            'company_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'required|string|max:30',
            'password' => 'required|string|min:6',
            'address' => 'required|string|max:255',
            'city' => 'nullable|string|max:100',
            'tax_number' => 'nullable|string|max:100',
            'specialty' => 'nullable|string|max:255',
        ]);

        $user = User::create([
            'name' => $validated['contact_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => Hash::make($validated['password']),
            'role' => 'b2b_client',
            'user_mode' => 'b2b',
            'status' => 'active',
            'avatar' => 'https://images.unsplash.com/photo-1560250097-0b93528c311a?auto=format&fit=crop&w=200&h=200&q=80',
        ]);

        // Create partner garage entry
        DB::table('partner_garages')->insert([
            'owner_user_id' => $user->id,
            'name' => $validated['company_name'],
            'slug' => Str::slug($validated['company_name']) . '-' . Str::random(4),
            'description' => 'Certified MotoCar Custom B2B Partner Garage',
            'phone' => $validated['phone'],
            'email' => $validated['email'],
            'address' => $validated['address'],
            'city' => $validated['city'] ?? 'Phnom Penh',
            'business_tax_id' => $validated['tax_number'] ?? null,
            'is_b2b_partner' => true,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'B2B Wholesale Account registered successfully! You can now access wholesale tier pricing.',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => 'b2b_client',
                'user_mode' => 'b2b',
                'avatar_url' => $user->avatar,
                'is_admin' => false,
                'is_b2b' => true,
                'b2b_profile' => [
                    'company_name' => $validated['company_name'],
                    'tier_level' => 'Tier 1 Wholesale',
                    'status' => 'active',
                    'credit_limit' => 5000,
                ],
            ],
        ], 201);
    }

    /**
     * Get Current Authenticated User Profile
     */
    public function me(Request $request)
    {
        $user = $request->user();

        $garage = DB::table('partner_garages')->where('owner_user_id', $user->id)->first();
        $b2bProfile = null;

        if ($garage || $user->role === 'b2b_client') {
            $b2bProfile = [
                'company_name' => $garage ? $garage->name : $user->name,
                'tier_level' => 'Tier 1 Wholesale',
                'status' => $garage ? $garage->status : 'active',
                'credit_limit' => 5000,
            ];
        }

        return response()->json([
            'status' => 'success',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,
                'user_mode' => $user->user_mode,
                'avatar_url' => $user->avatar ?: 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=200&h=200&q=80',
                'is_admin' => in_array($user->role, ['admin', 'super_admin']),
                'is_b2b' => $user->user_mode === 'b2b' || $user->role === 'b2b_client',
                'b2b_profile' => $b2bProfile,
            ],
        ]);
    }

    /**
     * Switch User Mode between B2C and B2B
     */
    public function switchMode(Request $request)
    {
        $validated = $request->validate([
            'mode' => 'required|in:b2c,b2b',
        ]);

        $user = $request->user();
        $user->user_mode = $validated['mode'];
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Switched to ' . strtoupper($validated['mode']) . ' mode.',
            'user_mode' => $user->user_mode,
        ]);
    }

    /**
     * Update User Profile
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:30',
            'avatar' => 'nullable|string|max:500',
            'current_password' => 'nullable|string',
            'new_password' => 'nullable|string|min:6',
        ]);

        $user->name = $validated['name'];
        if (isset($validated['phone'])) {
            $user->phone = $validated['phone'];
        }
        if (isset($validated['avatar'])) {
            $user->avatar = $validated['avatar'];
        }

        if (!empty($validated['new_password'])) {
            if (empty($validated['current_password']) || !Hash::check($validated['current_password'], $user->password)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Current password does not match.',
                ], 422);
            }
            $user->password = Hash::make($validated['new_password']);
        }

        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Profile updated successfully!',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,
                'user_mode' => $user->user_mode,
                'avatar_url' => $user->avatar ?: 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=200&h=200&q=80',
                'is_admin' => in_array($user->role, ['admin', 'super_admin']),
                'is_b2b' => $user->user_mode === 'b2b' || $user->role === 'b2b_client',
            ],
        ]);
    }

    /**
     * User Logout
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Logged out successfully.',
        ]);
    }
}
