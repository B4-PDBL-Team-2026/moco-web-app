<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Notification;

use App\Http\Controllers\Controller;
use App\Http\Resources\Notification\NotificationResource;
use App\Http\Responses\ApiResponse;

class InAppNotificationController extends Controller
{
    /**
     * Get list of notifications.
     *
     * @response array{
     *     success: bool,
     *     message: string,
     *     data: array<NotificationResource>,
     *     meta?: array{
     *         currentPage: int,
     *         lastPage: int,
     *         perPage: int,
     *         total: int,
     *         hasMore: bool
     *     }
     * }
     */
    public function index(): ApiResponse
    {
        $user = auth()->user();

        $notifications = $user->notifications()->paginate(20);

        return $this->successResponse(
            data: NotificationResource::collection($notifications),
            message: 'Notifications retrieved successfully.',
        );
    }

    /**
     * Mark a notification item has been read
     *
     * @param  string  $id  ID of notification
     *
     * @response array{success: bool, message: string}
     */
    public function markAsRead(string $id): ApiResponse
    {
        $user = auth()->user();

        $notification = $user->notifications()->findOrFail($id);
        $notification->markAsRead();

        return $this->successResponse(
            message: 'Notification marked as read.',
        );
    }

    /**
     * Get total of unread notifications
     *
     * @response array{success: bool, message: string, data: array{total: int}}
     */
    public function getUnreadTotal()
    {
        return $this->successResponse(
            data: [
                'total' => auth()->user()->unreadNotifications->count(),
            ],
            message: 'Unread notification total retrieved successfully.',
        );
    }
}
