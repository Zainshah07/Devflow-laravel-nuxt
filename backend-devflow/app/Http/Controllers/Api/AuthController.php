<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\TokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function __construct( private readonly TokenService $tokenService){
       
    }

    /**
     * Register a new user and issue tokens.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name'     => $request->validated('name'),
            'email'    => $request->validated('email'),
            'password' => Hash::make($request->validated('password')),
        ]);

        $tokens = $this->tokenService->issueTokens($user);

        return $this->tokenResponse($user, $tokens, 201);
    }

    /**
     * Authenticate an existing user and issue tokens.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->validated('email'))->first();

        if (!$user || !Hash::check($request->validated('password'), $user->password)) {
            return response()->json([
                'message' => 'The provided credentials are incorrect.',
                'errors'  => [
                    'email' => ['Invalid email or password.'],
                ],
            ], 401);
        }

        // Clean up expired/revoked tokens on login
        // DSA: this prune keeps the refresh_tokens table lean —
        // expired rows are garbage collected so the hash map stays small
        $this->tokenService->pruneExpiredTokens($user);

        $tokens = $this->tokenService->issueTokens($user);

        return $this->tokenResponse($user, $tokens, 200);
    }

    /**
     * Issue a new access token using a valid refresh token.
     *
     * DSA — Sliding Window:
     * The client sends the refresh token. We hash it → look up in the
     * DB → verify validity → rotate (revoke old, issue new pair).
     * The new access token's 15-minute window slides forward from now.
     */
    public function refresh(Request $request): JsonResponse
    {
        $plainToken = $request->cookie('refresh_token');

        if (!$plainToken) {
            return response()->json([
                'message' => 'Refresh token not found.',
            ], 401);
        }

        // DSA — Hash Map O(1) lookup:
        // Hash the incoming token and find the matching row
        $refreshToken = $this->tokenService->findValidRefreshToken($plainToken);

        if (!$refreshToken) {
            return response()->json([
                'message' => 'Refresh token is invalid or expired.',
            ], 401);
        }

        // Rotate: revoke old refresh token, issue a new pair
        $tokens = $this->tokenService->rotateRefreshToken($refreshToken);

        return $this->tokenResponse($refreshToken->user, $tokens, 200);
    }

    /**
     * Revoke the current user's tokens and log them out.
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        // Delete the current Sanctum access token
        $user->currentAccessToken()->delete();

        // Revoke the refresh token from the cookie if present
        $plainToken = $request->cookie('refresh_token');
        if ($plainToken) {
            $refreshToken = $this->tokenService->findValidRefreshToken($plainToken);
            $refreshToken?->update(['revoked' => true]);
        }

        return response()->json(['message' => 'Logged out successfully.'])
            ->withoutCookie('refresh_token');
    }

    /**
     * Return the authenticated user's profile.
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'data' => new UserResource($request->user()),
        ]);
    }

    /**
     * Build the standard token response with the refresh token as httpOnly cookie.
     *
     * DSA — Set membership check via cookie:
     * The refresh token lives in an httpOnly cookie — inaccessible to
     * JavaScript. This prevents XSS attacks from stealing it.
     * The access token goes in the response body — readable by JS
     * so the frontend can attach it to Authorization headers.
     */
    private function tokenResponse(User $user, array $tokens, int $status): JsonResponse
    {
        return response()->json([
            'data' => [
                'user'         => new UserResource($user),
                'access_token' => $tokens['access_token'],
                'token_type'   => 'Bearer',
            ],
        ], $status)->cookie(
         'refresh_token',                 // name
            $tokens['refresh_token'],        // value
            60 * 24 * 7,                     // minutes
            '/',                             // path
            null,                            // domain
            false,                           // secure
            true,                            // httpOnly
            false,                           // raw
            'lax'                            // sameSite
                );
    }
}
