<?php

namespace App\Services;

use App\Models\RefreshToken;
use App\Models\User;
use Illuminate\Support\Str;

class TokenService
{
    // How long refresh tokens live in days
    private const REFRESH_TOKEN_TTL_DAYS = 7;

    /**
     * Issue a Sanctum access token and a hashed refresh token.
     * Returns ['access_token' => string, 'refresh_token' => string].
     *
     * DSA — Hash Map insertion:
     * We generate a random 64-char token, hash it with SHA-256,
     * and store the hash in the DB. The plain token goes to the client.
     * On refresh, the client sends the plain token, we hash it again,
     * and do a O(1) indexed lookup against token_hash — same as
     * computing a hash key and checking the map.
     */
    public function issueTokens(User $user): array
    {
        // Create Sanctum access token — expires in 15 minutes
        // (configured in config/sanctum.php)
        $accessToken = $user->createToken('access')->plainTextToken;

        // Generate a cryptographically secure random refresh token
        $plainRefreshToken = Str::random(64);

        // DSA — Hash Map: store SHA-256 hash, never the plain text
        $user->refreshTokens()->create([
            'token_hash' => hash('sha256', $plainRefreshToken),
            'expires_at' => now()->addDays(self::REFRESH_TOKEN_TTL_DAYS),
            'revoked'    => false,
        ]);

        return [
            'access_token'  => $accessToken,
            'refresh_token' => $plainRefreshToken,
        ];
    }

    /**
     * Find and validate a refresh token by its plain text value.
     * Returns the RefreshToken model or null if invalid.
     *
     * DSA — Hash Map O(1) lookup:
     * Hash the incoming token → look up by token_hash index.
     * The unique index on token_hash makes this a single-row lookup.
     */
    public function findValidRefreshToken(string $plainToken): ?RefreshToken
    {
        $hash = hash('sha256', $plainToken);

        $token = RefreshToken::where('token_hash', $hash)->first();

        if (!$token || !$token->isValid()) {
            return null;
        }

        return $token;
    }

    /**
     * Revoke all refresh tokens for a user and delete their Sanctum tokens.
     */
    public function revokeAllForUser(User $user): void
    {
        // Mark all refresh tokens as revoked
        $user->refreshTokens()
             ->where('revoked', false)
             ->update(['revoked' => true]);

        // Delete Sanctum access tokens
        $user->tokens()->delete();
    }

    /**
     * Rotate a refresh token: revoke the old one, issue a new pair.
     * This prevents refresh token reuse after a successful refresh.
     */
    public function rotateRefreshToken(RefreshToken $oldToken): array
    {
        // Mark old refresh token as revoked
        $oldToken->update(['revoked' => true]);

        // Delete the current Sanctum access token so a fresh one is issued
        $oldToken->user->currentAccessToken()?->delete();

        // Issue a fresh pair
        return $this->issueTokens($oldToken->user);
    }

    /**
     * Clean up expired and revoked tokens for a user.
     * Call this on login or refresh to keep the table lean.
     */
    public function pruneExpiredTokens(User $user): void
    {
        $user->refreshTokens()
             ->where(function ($query) {
                 $query->where('expires_at', '<', now())
                       ->orWhere('revoked', true);
             })
             ->delete();
    }
}