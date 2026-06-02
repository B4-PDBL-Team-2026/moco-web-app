<?php

namespace App\Http\Controllers\Api\User;

use App\Domains\User\Actions\Auth\DeleteUserAction;
use App\Domains\User\Actions\Auth\ForgotPasswordAction;
use App\Domains\User\Actions\Auth\HandleGoogleLoginUserAction;
use App\Domains\User\Actions\Auth\LoginUserAction;
use App\Domains\User\Actions\Auth\LogoutUserAction;
use App\Domains\User\Actions\Auth\RegisterUserAction;
use App\Domains\User\Actions\Auth\ResetPasswordAction;
use App\Domains\User\Actions\Auth\SendEmailVerificationAction;
use App\Domains\User\Exceptions\UserBannedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\Auth\ConfirmDeleteUserRequest;
use App\Http\Requests\User\Auth\ForgotPasswordRequest;
use App\Http\Requests\User\Auth\LoginRequest;
use App\Http\Requests\User\Auth\LoginWithGoogleRequest;
use App\Http\Requests\User\Auth\RegisterRequest;
use App\Http\Requests\User\Auth\ResetPasswordRequest;
use App\Http\Resources\Auth\AuthResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

/**
 * Handles HTTP requests for User Authentication and Account Management.
 */
class AuthController extends Controller
{
    /**
     * Register a new user in the system.
     *
     *
     * @response array{success: bool, message: string, data: AuthResource}
     */
    public function register(RegisterRequest $request, RegisterUserAction $action): ApiResponse
    {
        $result = $action->execute($request->toDTO());

        return $this->successResponse(
            data: AuthResource::make($result),
            message: 'Registered successfully.',
            status: 201,
        );
    }

    /**
     * Authenticate a user and return an access token.
     *
     *
     * @response array{success: bool, message: string, data: AuthResource}
     */
    public function login(LoginRequest $request, LoginUserAction $action): ApiResponse
    {
        try {
            $result = $action->execute($request->toDTO());
        } catch (UserBannedException $e) {
            return $this->errorResponse(
                message: 'Your account has been banned.',
                status: 403,
            );
        }

        return $this->successResponse(
            data: AuthResource::make($result),
            message: 'Logged in successfully.',
        );
    }

    /**
     * @throws Throwable
     */
    public function loginWithGoogle(LoginWithGoogleRequest $request, HandleGoogleLoginUserAction $action): ApiResponse
    {
        $providerUser = Socialite::driver('google')
            ->stateless()
            ->userFromToken($request->google_token);

        try {
            $user = $action->execute($providerUser);
        } catch (UserBannedException $e) {
            return $this->errorResponse(
                message: 'Your account has been banned.',
                status: 403,
            );
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->successResponse(
            data: AuthResource::make([
                'user' => $user,
                'token' => $token,
                'requiresOnboarding' => ! $user->has_onboarded,
            ]),
        );
    }

    /**
     * Handle an incoming password reset link request.
     *
     *
     * @response array{success: bool, message: string}
     * @response 422 array{success: bool, message: string}
     */
    public function forgotPassword(ForgotPasswordRequest $request, ForgotPasswordAction $action): ApiResponse
    {
        $result = $action->execute($request->validated('email'));

        if ($result['status'] === 'error') {
            return $this->errorResponse(message: $result['message'], status: 422);
        }

        return $this->successResponse(message: $result['message']);
    }

    /**
     * Handle an incoming new password reset request.
     *
     *
     * @response array{success: bool, message: string}
     * @response 422 array{success: bool, message: string}
     */
    public function resetPassword(ResetPasswordRequest $request, ResetPasswordAction $action): ApiResponse
    {
        $result = $action->execute($request->toDTO());

        if ($result['status'] === 'error') {
            return $this->errorResponse(
                message: $result['message'],
                status: 422,
            );
        }

        return $this->successResponse(null, $result['message']);
    }

    /**
     * Send a new email verification notification.
     *
     *
     * @response array{success: bool, message: string}
     */
    public function sendVerificationEmail(SendEmailVerificationAction $action): ApiResponse
    {
        $result = $action->execute(auth()->user());

        return $this->successResponse(message: $result['message']);
    }

    /**
     * Log the user out of the application and revoke their current token.
     *
     *
     * @response array{success: bool, message: string}
     */
    public function logout(Request $request, LogoutUserAction $action): ApiResponse
    {
        $action->execute($request->user());

        return $this->successResponse(message: 'Successfully logged out.');
    }

    /**
     * Permanently deletes the authenticated user's account and all associated data.
     *
     * Requires the user's current password as confirmation.
     * After a successful deletion the client should discard the token it holds
     * — it has already been revoked server-side.
     *
     *
     * @throws Throwable
     *
     * @response array{success: bool, message: string}
     */
    public function destroy(ConfirmDeleteUserRequest $request, DeleteUserAction $action): ApiResponse
    {
        $action->execute($request->user());

        return $this->successResponse(message: 'Account deleted successfully.');
    }
}
