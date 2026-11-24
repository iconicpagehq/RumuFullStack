<?php

namespace App\Http\Controllers\OAuth;

use App\Http\Controllers\Controller;
use App\Models\McpOAuthAuthorizationCode;
use App\Models\McpOAuthClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TokenController extends Controller
{
    private function error(string $error, string $description, int $httpStatus): JsonResponse
    {
        return response()->json([
            'error' => $error,
            'error_description' => $description,
        ], $httpStatus, ['Cache-Control' => 'no-store', 'Pragma' => 'no-cache']);
    }

    public function token(Request $request)
    {
        if ($request->input('grant_type') !== 'authorization_code') {
            return $this->error('unsupported_grant_type', 'Only grant_type=authorization_code is supported.', 400);
        }

        $clientId = (string) $request->input('client_id', '');
        $clientSecret = (string) $request->input('client_secret', '');

        // Support HTTP Basic Authentication header (often used by ChatGPT Actions)
        if ($request->hasHeader('Authorization') && str_starts_with($request->header('Authorization'), 'Basic ')) {
            $rawCreds = base64_decode(substr($request->header('Authorization'), 6));
            if (str_contains($rawCreds, ':')) {
                [$basicUser, $basicPass] = explode(':', $rawCreds, 2);
                if ($clientId === '') $clientId = $basicUser;
                if ($clientSecret === '') $clientSecret = $basicPass;
            }
        }

        $client = $clientId !== '' ? McpOAuthClient::where('client_id', $clientId)->first() : null;

        if (! $client) {
            return $this->error('invalid_client', 'Unknown client_id.', 401);
        }

        // If client has a secret, verify it
        if (! empty($client->client_secret)) {
            if ($clientSecret === '' || ! hash_equals($client->client_secret, $clientSecret)) {
                return $this->error('invalid_client', 'Invalid client_secret.', 401);
            }
        }

        $plainCode = (string) $request->input('code', '');
        $code = $plainCode !== ''
            ? McpOAuthAuthorizationCode::where('code_hash', McpOAuthAuthorizationCode::hash($plainCode))->first()
            : null;

        if (! $code || ! $code->isUsable()) {
            return $this->error('invalid_grant', 'Authorization code is invalid, expired, or already used.', 400);
        }

        if ($code->client_id !== $client->client_id) {
            return $this->error('invalid_grant', 'Authorization code was not issued to this client.', 400);
        }

        if ((string) $request->input('redirect_uri', '') !== $code->redirect_uri) {
            return $this->error('invalid_grant', 'redirect_uri does not match the authorization request.', 400);
        }

        // Verify PKCE if code_challenge was provided
        if (! empty($code->code_challenge)) {
            $verifier = (string) $request->input('code_verifier', '');
            if ($verifier === '' || ! $code->verifyChallenge($verifier)) {
                return $this->error('invalid_grant', 'PKCE verification failed (code_verifier mismatch).', 400);
            }
        }

        // Single-use: consume before issuing so a replayed code never succeeds.
        $code->consume();

        $expiresAt = now()->addDays(McpOAuthAuthorizationCode::TOKEN_TTL_DAYS);
        $token = $code->user->createToken('mcp:' . $client->client_id, ['mcp'], $expiresAt);

        return response()->json([
            'access_token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_in' => McpOAuthAuthorizationCode::TOKEN_TTL_DAYS * 86400,
            'scope' => $code->scopes ?? 'mcp',
        ], 200, ['Cache-Control' => 'no-store', 'Pragma' => 'no-cache']);
    }
}
