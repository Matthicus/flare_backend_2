<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Flare;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminController extends Controller
{
    public function dashboard()
    {
        $stats = [
            'total_users' => User::count(),
            'total_flares' => Flare::count(),
            'admin_users' => User::where('is_admin', true)->count(),
            'recent_users_count' => User::whereDate('created_at', '>=', now()->subDays(7))->count(),
            'recent_flares_count' => Flare::whereDate('created_at', '>=', now()->subDays(7))->count(),
        ];

        $recentUsers = User::latest()->take(5)->get(['id', 'name', 'email', 'username', 'created_at']);
        $recentFlares = Flare::with('user:id,name,username')->latest()->take(5)->get();

        return view('admin.dashboard', compact('stats', 'recentUsers', 'recentFlares'));
    }

    public function users()
    {
        $users = User::withCount('flares')->orderBy('created_at', 'desc')->paginate(20);
        return view('admin.users', compact('users'));
    }

    public function flares()
    {
        $flares = Flare::with('user:id,name,username,email')->orderBy('created_at', 'desc')->paginate(20);
        return view('admin.flares', compact('flares'));
    }

    public function deleteUser(User $user)
    {
        // Don't allow deleting yourself
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        // Delete user's flares first
        $user->flares()->delete();
        
        // Delete the user
        $user->delete();

        return back()->with('success', 'User and all their flares have been deleted successfully.');
    }

    public function deleteFlare(Flare $flare)
    {
        // Delete the photo file if it exists
        if ($flare->photo_path && Storage::disk('public')->exists($flare->photo_path)) {
            Storage::disk('public')->delete($flare->photo_path);
        }

        $flare->delete();

        return back()->with('success', 'Flare has been deleted successfully.');
    }

    public function toggleAdmin(User $user)
    {
        // Don't allow removing your own admin status
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot modify your own admin status.');
        }

        $user->is_admin = !$user->is_admin;
        $user->save();

        $message = $user->is_admin ? 'User granted admin access.' : 'User admin access revoked.';
        return back()->with('success', $message);
    }
}