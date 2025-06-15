<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Get authenticated user's profile
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function profile(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'username' => $user->username ?? null,
            'profile_photo_url' => $user->profile_photo_path 
                ? Storage::url($user->profile_photo_path) 
                : null,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ]);
    }

    /**
     * Update user profile
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'username' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('users')->ignore($user->id),
            ],
        ]);

        $user->update($validated);

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'username' => $user->username,
                'profile_photo_url' => $user->profile_photo_path 
                    ? Storage::url($user->profile_photo_path) 
                    : null,
            ]
        ]);
    }

    /**
     * Update user profile photo
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProfilePhoto(Request $request)
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048' // 2MB max
        ]);

        $user = $request->user();

        // Delete old photo if exists
        if ($user->profile_photo_path) {
            Storage::delete($user->profile_photo_path);
        }

        // Store new photo
        $path = $request->file('photo')->store('profile-photos', 'public');
        
        $user->update([
            'profile_photo_path' => $path
        ]);

        return response()->json([
            'message' => 'Profile photo updated successfully',
            'profile_photo_url' => Storage::url($path)
        ]);
    }

    /**
     * Delete user profile photo
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteProfilePhoto(Request $request)
    {
        $user = $request->user();

        if ($user->profile_photo_path) {
            Storage::delete($user->profile_photo_path);
            $user->update([
                'profile_photo_path' => null
            ]);
        }

        return response()->json([
            'message' => 'Profile photo deleted successfully'
        ]);
    }

    /**
     * Get user's flares
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function userFlares(Request $request)
    {
        $user = $request->user();
        
        // Check if user exists
        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated'
            ], 401);
        }

        try {
            // Get flares without the images relationship for now
            $flares = $user->flares()
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'data' => $flares,
                'total' => $flares->count()
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching user flares: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error fetching flares',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user statistics
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function userStats(Request $request)
    {
        $user = $request->user();
        
        $totalFlares = $user->flares()->count();
        
        // Since participants_count column doesn't exist, use total flares as participants
        $totalParticipants = $totalFlares; // Or set to 0 if you prefer
        
        $flaresThisMonth = $user->flares()
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        // Get flare category breakdown
        $categoryBreakdown = $user->flares()
            ->selectRaw('category, count(*) as count')
            ->groupBy('category')
            ->get()
            ->pluck('count', 'category')
            ->toArray();

        $lastFlare = $user->flares()->latest()->first();

        return response()->json([
            'total_flares' => $totalFlares,
            'total_participants' => $totalParticipants,
            'flares_this_month' => $flaresThisMonth,
            'category_breakdown' => $categoryBreakdown,
            'member_since' => $user->created_at->format('F Y'),
            'last_flare' => $lastFlare ? $lastFlare->created_at->toISOString() : null,
        ]);
    }
}