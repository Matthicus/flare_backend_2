<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Get current user profile with additional data
     */
    public function profile(Request $request)
    {
        $user = $request->user();
        
        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'profile_photo_url' => $user->profile_photo_url,
            'profile_photo_path' => $user->profile_photo_path,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ]);
    }

    /**
     * Update user profile (name and username)
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
                'min:3',
                'max:30',
                'regex:/^[a-zA-Z0-9_]+$/', // Only letters, numbers, and underscores
                Rule::unique('users')->ignore($user->id)
            ],
        ]);

        $user->update($validated);

        return response()->json([
            'user' => $user->fresh(),
            'message' => 'Profile updated successfully'
        ]);
    }

    /**
     * Update profile photo
     */
    public function updateProfilePhoto(Request $request)
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048', // 2MB max
        ]);

        $user = $request->user();

        // Delete old profile photo if it exists
        if ($user->profile_photo_path) {
            Storage::disk('public')->delete($user->profile_photo_path);
        }

        // Store new photo
        $path = $request->file('photo')->store('profile-photos', 'public');
        
        $user->update(['profile_photo_path' => $path]);

        return response()->json([
            'user' => $user->fresh(),
            'profile_photo_url' => $user->profile_photo_url,
            'message' => 'Profile photo updated successfully'
        ]);
    }

    /**
     * Delete profile photo
     */
    public function deleteProfilePhoto(Request $request)
    {
        $user = $request->user();

        if ($user->profile_photo_path) {
            Storage::disk('public')->delete($user->profile_photo_path);
            $user->update(['profile_photo_path' => null]);
        }

        return response()->json([
            'user' => $user->fresh(),
            'message' => 'Profile photo deleted successfully'
        ]);
    }

    /**
     * Get user's flares
     */
    public function userFlares(Request $request)
    {
        $user = $request->user();
        
        $flares = $user->flares()
            ->with(['place', 'knownPlace'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'flares' => $flares,
            'total_flares' => $flares->count()
        ]);
    }

    /**
     * Get user statistics
     */
    public function userStats(Request $request)
    {
        $user = $request->user();
        
        $totalFlares = $user->flares()->count();
        $totalParticipants = $user->flares()->sum('participants_count') ?? 0;
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

        return response()->json([
            'total_flares' => $totalFlares,
            'total_participants' => $totalParticipants,
            'flares_this_month' => $flaresThisMonth,
            'category_breakdown' => $categoryBreakdown,
            'member_since' => $user->created_at->format('F Y'),
            'last_flare' => $user->flares()->latest()->first()?->created_at,
        ]);
    }
}