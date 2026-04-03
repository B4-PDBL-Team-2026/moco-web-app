<?php

namespace App\Domains\Auth\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Permanently deletes a user account and all associated data.
 *
 * Deletion strategy per table:
 *
 * Handled automatically by DB cascadeOnDelete:
 *   - transactions
 *   - fixed_cost_templates  (which cascades to fixed_cost_occurrences)
 *   - fixed_cost_occurrences
 *   - user_budget_settings
 *   - user_budget_snapshots
 *
 * Handled explicitly here (no DB-level cascade):
 *   - personal_access_tokens  — Sanctum tokens, revoked via the ORM so
 *                               any token-based middleware immediately
 *                               rejects further requests.
 *   - sessions                — no FK constraint, cleaned by email.
 *   - password_reset_tokens   — keyed by email, not user_id.
 *
 *
 * @throws Throwable If the transaction fails.
 */
class DeleteUserAction
{
    /**
     * @param  User  $user  The authenticated user requesting account deletion.
     *
     * @throws Throwable
     */
    public function execute(User $user): void
    {
        DB::transaction(function () use ($user): void {
            // Revoke all Sanctum tokens first so the client token becomes
            // invalid immediately — even before the user row is gone.
            $user->tokens()->delete();

            // Clean up non-cascading tables keyed by email.
            DB::table('password_reset_tokens')
                ->where('email', $user->email)
                ->delete();

            // Sessions are indexed by user_id but have no FK constraint,
            // so the DB will not cascade them automatically.
            DB::table('sessions')
                ->where('user_id', $user->id)
                ->delete();

            // Hard-delete all user related data
            $user->forceDelete();
        });
    }
}
