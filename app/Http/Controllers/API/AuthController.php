<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\WorkshopResource;
use App\Models\User;
use App\Models\Workshop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a new user and workshop
     */
    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'login' => 'required|string|max:255|unique:users',
            'phone' => 'required|string|max:20|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'workshop_name' => 'nullable|string|max:255',
        ]);

        // User yaratish
        $user = User::create([
            'name' => $request->name,
            'login' => $request->login,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
        ]);

        // Workshop yaratish
        $workshop = Workshop::create([
            'user_id' => $user->id,
            'name' => $request->workshop_name ?? $request->name . ' Ustaxonasi',
            'owner_name' => $request->name,
            'phone' => $request->phone ?? '',
            'subscription_plan' => 'free',
            'subscription_expires_at' => now()->addDays(30), // 30 kunlik bepul trial
            'is_active' => true,
        ]);

        // API token yaratish
        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'message' => 'Registration successful',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'login' => $user->login,
                'email' => $user->email,
                'phone' => $user->phone,
            ],
            'workshop' => new WorkshopResource($workshop),
            'token' => $token,
        ], 201);
    }

    /**
     * Login user
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required',
        ]);

        $user = User::where('login', $request->login)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'login' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Workshop yuklash
        $workshop = $user->workshop;

        // Token yaratish
        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'login' => $user->login,
                'email' => $user->email,
                'phone' => $user->phone,
            ],
            'workshop' => new WorkshopResource($workshop),
            'token' => $token,
        ]);
    }

    /**
     * Logout user (revoke token)
     */
    public function logout(Request $request): JsonResponse
    {
        // Hozirgi tokenni o'chirish
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }

    /**
     * Get authenticated user
     */
    public function user(Request $request): JsonResponse
    {
        $user = $request->user();
        $workshop = $user->workshop;

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'login' => $user->login,
                'email' => $user->email,
                'phone' => $user->phone,
            ],
            'workshop' => new WorkshopResource($workshop),
        ]);
    }
}
