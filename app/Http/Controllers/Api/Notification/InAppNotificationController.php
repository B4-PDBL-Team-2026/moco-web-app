<?php

namespace App\Http\Controllers\Api\Notification;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class InAppNotificationController extends Controller
{
    public function index()
    {
        /** @var User $user */
        $user = Auth::user();

        $notifications = $user->notifications()->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $notifications,
            'message' => 'Notifications retrieved successfully.',
        ]);
    }

    public function markAsRead($id)
    {
        /** @var User $user */
        $user = Auth::user();

        $notification = $user->notifications()->findOrFail($id);
        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read.',
        ]);
    }

    public function unreadCount()
    {
        return response()->json([
            'success' => true,
            'count' => Auth::user()->unreadNotifications->count(),
        ]);
    }
}
