<?php

namespace App\Http\Controllers\Api\Auth;

use App\Domains\User\Actions\Auth\DeleteUserAction;
use App\Domains\User\Actions\Auth\ForgotPasswordAction;
use App\Domains\User\Actions\Auth\LoginUserAction;
use App\Domains\User\Actions\Auth\LogoutUserAction;
use App\Domains\User\Actions\Auth\RegisterUserAction;
use App\Domains\User\Actions\Auth\ResetPasswordAction;
use App\Domains\User\Actions\Auth\SendEmailVerificationAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\Auth\ConfirmDeleteUserRequest;
use App\Http\Requests\User\Auth\ForgotPasswordRequest;
use App\Http\Requests\User\Auth\LoginRequest;
use App\Http\Requests\User\Auth\RegisterRequest;
use App\Http\Requests\User\Auth\ResetPasswordRequest;
use App\Http\Resources\Auth\AuthResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\Request;
use Throwable;

class AuthController extends Controller
{
    /**
     * Register a new user in the system.
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
     */
    public function login(LoginRequest $request, LoginUserAction $action): ApiResponse
    {
        $result = $action->execute($request->toDTO());

        return $this->successResponse(
            data: AuthResource::make($result),
            message: 'Logged in successfully.',
        );
    }

    /**
     * Handle an incoming password reset link request.
     */
    public function forgotPassword(ForgotPasswordRequest $request, ForgotPasswordAction $action): ApiResponse
    {
        $result = $action->execute($request->validated('email'));

        if ($result['status'] === 'error') {
            return $this->errorResponse(message: $result['message'], status: 422);
        }

        return $this->successResponse(null, $result['message']);
    }

    /**
     * Handle an incoming new password reset request.
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
     */
    public function sendVerificationEmail(SendEmailVerificationAction $action): ApiResponse
    {
        $result = $action->execute(auth()->user());

        return $this->successResponse(message: $result['message']);
    }

    /**
     * Log the user out of the application and revoke their current token.
     */
    public function logout(Request $request, LogoutUserAction $action): ApiResponse
    {
        $action->execute($request->user());

        return $this->successResponse(message: 'Successfully logged out.');
    }

    /**
     * DELETE /api/user
     *
     * Permanently deletes the authenticated user's account and all associated
     * data. Requires the user's current password as confirmation.
     *
     * After a successful deletion the client should discard the token it holds
     * — it has already been revoked server-side.
     *
     * @throws Throwable
     */
    public function destroy(
        ConfirmDeleteUserRequest $request,
        DeleteUserAction $action,
    ): ApiResponse {
        $action->execute($request->user());

        return $this->successResponse(message: 'Account deleted successfully.');
    }
}
