<?php

namespace App\Http\Controllers\Api;

use App\Models\SsoToken;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

/**
 * SsoController — Token Exchange, Heartbeat & Revoke
 * Exact copy of EntryEase's SsoController — adapted for EnrollEase.
 */
class SsoController extends Controller
{
    /**
     * POST /api/sso/exchange
     * Exchange portal-issued single-use token for an authenticated session.
     */
    public function exchange(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'token'    => 'required|string|max:255',
                'embedded' => 'sometimes|boolean',
            ]);

            $tokenString = $validated['token'];
            $embedded    = $validated['embedded'] ?? false;

            \Log::info('[EnrollEase][SSO] Token exchange attempt', [
                'token'    => substr($tokenString, 0, 8) . '...',
                'embedded' => $embedded,
            ]);

            $token = SsoToken::findValid($tokenString);

            if (! $token) {
                \Log::warning('[EnrollEase][SSO] Token exchange failed: invalid or expired', [
                    'token' => substr($tokenString, 0, 8) . '...',
                ]);
                return response()->json([
                    'success' => false,
                    'error'   => 'invalid_token',
                    'message' => 'Token invalid, expired, or already exchanged',
                ], 401);
            }

            $portalPublicKey = $this->getPortalPublicKey();
            if (! $token->validateSignature($portalPublicKey)) {
                \Log::warning('[EnrollEase][SSO] Token exchange failed: invalid signature', [
                    'sso_id' => $token->sso_id,
                ]);
                return response()->json([
                    'success' => false,
                    'error'   => 'signature_invalid',
                    'message' => 'Portal signature validation failed',
                ], 403);
            }

            $token->markExchanged();

            $role = $this->normalizeRole($token->sso_role);

            $request->session()->flush();
            session([
                'sso_id'               => $token->sso_id,
                'sso_role'             => $role,
                'sso_name'             => $token->sso_name,
                'sso_email'            => $token->sso_email,
                'sso_embedded'         => $embedded,
                'student_email'        => $token->sso_email,
                'sso_authenticated_at' => now()->timestamp,
            ]);

            \Log::info('[EnrollEase][SSO] Token exchange successful', [
                'sso_id'   => $token->sso_id,
                'role'     => $role,
                'embedded' => $embedded,
            ]);

            return response()->json([
                'success'  => true,
                'user'     => [
                    'id'    => $token->sso_id,
                    'name'  => $token->sso_name,
                    'email' => $token->sso_email,
                    'role'  => $role,
                ],
                'embedded' => $embedded,
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'error'   => 'validation_error',
                'message' => 'Invalid request format',
                'errors'  => $e->errors(),
            ], 400);

        } catch (\Exception $e) {
            \Log::error('[EnrollEase][SSO] Token exchange error', [
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'error'   => 'exchange_error',
                'message' => 'An error occurred during token exchange',
            ], 500);
        }
    }

    /**
     * GET /api/sso/heartbeat
     * Check if the current session is still valid.
     */
    public function heartbeat(Request $request): JsonResponse
    {
        if (! $request->session()->has('sso_id') || ! $request->session()->has('sso_role')) {
            return response()->json([
                'valid'   => false,
                'error'   => 'no_session',
                'message' => 'Not authenticated',
            ], 401);
        }

        return response()->json([
            'valid' => true,
            'user'  => [
                'id'    => $request->session()->get('sso_id'),
                'name'  => $request->session()->get('sso_name'),
                'email' => $request->session()->get('sso_email'),
                'role'  => $request->session()->get('sso_role'),
            ],
        ], 200);
    }

    /**
     * POST /api/sso/revoke
     * Revoke a token before it is exchanged.
     */
    public function revoke(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'token' => 'required|string|max:255',
            ]);

            $token = SsoToken::where('token', $validated['token'])
                ->whereNull('exchanged_at')
                ->first();

            if ($token) {
                $token->markExchanged();
                \Log::info('[EnrollEase][SSO] Token revoked', [
                    'token' => substr($validated['token'], 0, 8) . '...',
                ]);
            }

            return response()->json(['success' => true], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error'   => 'revoke_error',
            ], 500);
        }
    }

    protected function getPortalPublicKey(): string
    {
        return config('deoris_sso.portal_public_key') ?? config('deoris.portal_public_key') ?? '';
    }

    protected function normalizeRole(?string $role): string
    {
        return match ($role) {
            'hr', 'officer', 'admission_officer' => 'officer',
            'admin'                               => 'admin',
            default                               => 'student',
        };
    }
}
