<?php

namespace App\Http\Controllers\Api\Auth;

use App\Domains\Auth\Actions\ForgotPasswordAction;
use App\Domains\Auth\Actions\LoginUserAction;
use App\Domains\Auth\Actions\LogoutUserAction;
use App\Domains\Auth\Actions\RegisterUserAction;
use App\Domains\Auth\Actions\RequestEmailVerificationAction;
use App\Domains\Auth\Actions\ResetPasswordAction;
use App\Domains\Auth\Actions\VerifyEmailAction;
use App\Domains\Auth\DTOs\LoginUserDTO;
use App\Domains\Auth\DTOs\RegisterUserDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    use ApiResponse;

    /**
     * Register a new user in the system.
     *
     * @param  RegisterRequest  $request
     * @param  RegisterUserAction  $action
     * @return JsonResponse
     */
    public function register(RegisterRequest $request, RegisterUserAction $action): JsonResponse
    {
        $dto = RegisterUserDTO::fromRequest($request);
        $result = $action->execute($dto);

        return $this->success([
            'user' => $result['user'],
            'token' => $result['token'],
            'requires_onboarding' => $result['requires_onboarding'],
        ], 'Registered successfully.', 201);
    }

    /**
     * Authenticate a user and return an access token.
     *
     * @param  LoginRequest  $request
     * @param  LoginUserAction  $action
     * @return JsonResponse
     */
    public function login(LoginRequest $request, LoginUserAction $action): JsonResponse
    {
        $dto = LoginUserDTO::fromRequest($request);
        $result = $action->execute($dto);

        return $this->success([
            'user' => $result['user'],
            'token' => $result['token'],
            'requires_onboarding' => $result['requires_onboarding'],
        ], 'Logged in successfully.');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @param  ForgotPasswordRequest  $request
     * @param  ForgotPasswordAction  $action
     * @return JsonResponse
     */
    public function forgotPassword(ForgotPasswordRequest $request, ForgotPasswordAction $action): JsonResponse
    {
        $result = $action->execute($request->validated('email'));

        if ($result['status'] === 'error') {
            return $this->error($result['message'], 422);
        }

        return $this->success(null, $result['message']);
    }

    /**
     * Handle an incoming new password reset request.
     *
     * @param  ResetPasswordRequest  $request
     * @param  ResetPasswordAction  $action
     * @return JsonResponse
     */
    public function resetPassword(ResetPasswordRequest $request, ResetPasswordAction $action): JsonResponse
    {
        $result = $action->execute(
            email: $request->validated('email'),
            password: $request->validated('password'),
            token: $request->validated('token'),
        );

        if ($result['status'] === 'error') {
            return $this->error($result['message'], 422);
        }

        return $this->success(null, $result['message']);
    }

    /**
     * Send a new email verification notification.
     *
     * @param  RequestEmailVerificationAction  $action
     * @return JsonResponse
     */
    public function sendVerificationEmail(RequestEmailVerificationAction $action): JsonResponse
    {
        $result = $action->execute(auth()->user());

        return $this->success($result, $result['message']);
    }

    /**
     * Verify the user's email address using the signed route hash.
     *
     * @param  Request  $request
     * @param  VerifyEmailAction  $action
     * @return JsonResponse
     */
    public function verifyEmail(Request $request, VerifyEmailAction $action): JsonResponse
    {
        /** @var User $user */
        $user = User::query()->findOrFail($request->route('id'));

        if (! hash_equals(sha1($user->getEmailForVerification()), (string) $request->route('hash'))) {
            return $this->error('Verification link is invalid.', 403);
        }

        $result = $action->execute($user);

        return $this->success(null, $result['message'], 200);
    }

    /**
     * Log the user out of the application and revoke their current token.
     *
     * @param  Request  $request
     * @param  LogoutUserAction  $action
     * @return JsonResponse
     */
    public function logout(Request $request, LogoutUserAction $action): JsonResponse
    {
        $action->execute($request->user());

        return $this->success(message: 'Successfully logged out.');
    }
}
