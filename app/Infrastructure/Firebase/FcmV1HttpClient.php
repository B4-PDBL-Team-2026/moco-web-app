<?php

namespace App\Infrastructure\Firebase;

use App\Domains\Notification\Contracts\PushNotification;
use App\Domains\Notification\DTOs\PushMessage;
use App\Domains\User\Models\UserDevice;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FcmV1HttpClient implements PushNotification
{
    private string $projectId;

    private string $credentialsPath;

    public function __construct()
    {
        $this->projectId = config('services.firebase.project_id', '');
        $this->credentialsPath = storage_path('app/private/firebase_key.json');
    }

    /**
     * @throws ConnectionException
     */
    public function send(PushMessage $message): bool
    {
        $accessToken = $this->getAccessToken();

        $endpoint = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";

        $payload = [
            'message' => [
                'token' => $message->deviceToken,
                'notification' => [
                    'title' => $message->title,
                    'body' => $message->body,
                ],
                'data' => array_map('strval', $message->data),
            ],
        ];

        if ($message->image) {
            $payload['message']['notification']['image'] = $message->image;
        }

        $response = Http::withToken($accessToken)->post($endpoint, $payload);

        if ($response->successful()) {
            return true;
        }

        return $this->handleError($response, $message->deviceToken);
    }

    /**
     * Generate OAuth Token and store it in cache for 55 minutes
     * since real token expiry is 60 minutes
     */
    private function getAccessToken(): string
    {
        return Cache::remember('firebase_access_token', 55 * 60, function () {
            $credentials = new ServiceAccountCredentials(
                'https://www.googleapis.com/auth/firebase.messaging',
                $this->credentialsPath,
            );

            $token = $credentials->fetchAuthToken();

            return $token['access_token'];
        });
    }

    /**
     * Catch FCM failed push and delete device token if dead
     */
    private function handleError($response, string $deviceToken): bool
    {
        $status = $response->status();

        $errorCode = $response->json('error.details.0.errorCode') ?? 0;

        if ($status === 404 && in_array($errorCode, ['UNREGISTERED', 'INVALID_ARGUMENT'])) {
            Log::info('[Notification] Dead FCM device token detected: '.$deviceToken);
            UserDevice::query()
                ->where('fcm_token', $deviceToken)
                ->delete();
        } else {
            Log::error('[Notification] FCM error: '.$response->body());
        }

        return false;
    }
}
