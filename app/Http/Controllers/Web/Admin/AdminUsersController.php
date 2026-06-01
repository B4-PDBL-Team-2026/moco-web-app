<?php

namespace App\Http\Controllers\Web\Admin;

use App\Domains\User\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AdminUsersController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->input('search');

        $users = User::query()
            ->where('role', '!=', 'admin')
            ->when($search, function ($query, $search) {
                $query->where('name', 'ilike', "{$search}%")
                    ->orWhere('email', 'ilike', "{$search}%");
            })
            ->orderBy('created_at', 'desc')
            ->paginate(5)
            ->withQueryString()
            ->through(function ($user) {
                $hasSession = DB::table('sessions')->where('user_id', $user->id)->exists();
                $hasToken = DB::table('personal_access_tokens')
                    ->where('tokenable_id', $user->id)
                    ->where('tokenable_type', User::class)
                    ->exists();

                $words = explode(' ', $user->name);
                $initials = strtoupper(substr($words[0], 0, 1).(isset($words[1]) ? substr($words[1], 0, 1) : ''));

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'joinedAt' => $user->created_at->translatedFormat('d M Y'),
                    'status' => $user->status ?? 'active',
                    'isLoggedIn' => $hasSession || $hasToken,
                    'avatarInitials' => $initials ?: 'U',
                    'emailVerified' => $user->email_verified_at !== null,
                    'banDuration' => $user->ban_duration,
                ];
            });

        return Inertia::render('Admin/Users', [
            'users' => $users,
            'filters' => $request->only(['search']),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', 'in:active,banned'],
            'banDuration' => ['nullable', 'string', 'max:255'],
        ]);

        $user->update([
            'name' => $validated['name'],
            'status' => $validated['status'],
            'ban_duration' => $validated['status'] === 'banned' ? $validated['banDuration'] : null,
        ]);

        if ($validated['status'] === 'banned') {
            $this->revokeUserAccess($user->id);
        }

        return back();
    }

    public function forceLogout(User $user)
    {
        $this->revokeUserAccess($user->id);

        return back();
    }

    public function destroy(User $user)
    {
        $user->delete();

        return back();
    }

    private function revokeUserAccess($userId)
    {
        DB::table('sessions')->where('user_id', $userId)->delete();
        DB::table('personal_access_tokens')
            ->where('tokenable_id', $userId)
            ->where('tokenable_type', User::class)
            ->delete();
    }
}
