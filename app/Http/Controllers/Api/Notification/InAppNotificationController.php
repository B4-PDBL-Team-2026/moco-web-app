<?php

namespace App\Http\Controllers\Api\Notification;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;

class InAppNotificationController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $user = auth()->user();

        $notifications = $user->notifications()->paginate(20);

        return $this->success($notifications, 'Notification list retrieved successfully.');
    }

    public function markAsRead($id)
    {
        $user = auth()->user();

        $notification = $user->notifications()->findOrFail($id);
        $notification->markAsRead();

        return $this->success(message: 'Notification marked as read.');
    }

    public function getUnreadTotal()
    {
        return $this->success([
            'total' => auth()->user()->unreadNotifications->count(),
        ]);
    }
}
