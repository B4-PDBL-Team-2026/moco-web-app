<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class NotificationController extends Controller
{

    public function index()
    {
       /** @var \App\Models\User $user */
    $user = Auth::user();

    $notifications = $user->notifications()->paginate(20);

    return response()->json([
        'success' => true,
        'data' => $notifications,
        'message' => 'Notifications retrieved successfully.'
    ]);
    }

    public function markAsRead($id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Cari notifikasi spesifik milik user ini dan tandai sudah dibaca
        $notification = $user->notifications()->findOrFail($id);
        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read.'
        ]);
    }

    public function unreadCount()
    {
        return response()->json([
            'success' => true,
            'count' => Auth::user()->unreadNotifications->count()
        ]);
    }
}
