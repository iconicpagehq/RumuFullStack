<?php

namespace App\Http\Controllers\OAuth;

use App\Http\Controllers\Controller;
use App\Models\McpOAuthAuthorizationCode;
use App\Models\McpOAuthClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthorizationController extends Controller
{
    /**
     * Validate the OAuth authorization request parameters.
     *
     * Returns ['client' => ..., 'params' => [...], 'errorRedirect' => ?RedirectResponse].
     * When 'errorRedirect' is set the caller must return it instead of proceeding.
     *
     * @return array{client: McpOAuthClient, params: array, errorRedirect: ?RedirectResponse}
     */
    private function validateAuthRequest(Request $request): array
    {
        $responseType = (string) $request->input('response_type', '');
        $clientId = (string) $request->input('client_id', '');
        $redirectUri = (string) $request->input('redirect_uri', '');

        $client = $clientId !== '' ? McpOAuthClient::where('client_id', $clientId)->first() : null;

        if (! $client || $redirectUri === '' || ! $client->allowsRedirectUri($redirectUri)) {
            abort(400, 'Unknown client_id or unregistered redirect_uri.');
        }

        $state = $request->input('state');
        $errorRedirect = function (string $error, string $description) use ($redirectUri, $state): RedirectResponse {
            $query = ['error' => $error, 'error_description' => $description];
            if ($state !== null) {
                $query['state'] = (string) $state;
            }

            return redirect()->away($redirectUri . '?' . http_build_query($query));
        };

        $redirect = null;

        if ($responseType !== 'code') {
            $redirect = $errorRedirect('unsupported_response_type', 'Only response_type=code is supported.');
        } elseif ($request->filled('code_challenge')) {
            if ($request->input('code_challenge_method') !== 'S256') {
                $redirect = $errorRedirect('invalid_request', 'PKCE code_challenge_method must be S256.');
            } else {
                $challenge = trim((string) $request->input('code_challenge'));
                if (strlen($challenge) < 43 || strlen($challenge) > 128) {
                    $redirect = $errorRedirect('invalid_request', 'code_challenge must be 43-128 characters.');
                }
            }
        } elseif (empty($client->client_secret)) {
            $redirect = $errorRedirect('invalid_request', 'PKCE with code_challenge_method=S256 is required for public clients.');
        }

        return [
            'client' => $client,
            'params' => [
                'client_id' => $clientId,
                'client_name' => $client->name,
                'redirect_uri' => $redirectUri,
                'scopes' => (string) $request->input('scope', 'mcp'),
                'state' => $state,
                'code_challenge' => (string) $request->input('code_challenge', ''),
                'code_challenge_method' => 'S256',
            ],
            'errorRedirect' => $redirect,
        ];
    }

    public function show(Request $request)
    {
        $result = $this->validateAuthRequest($request);

        if ($result['errorRedirect'] !== null) {
            return $result['errorRedirect'];
        }

        return view('oauth.authorize', ['params' => $result['params']]);
    }

    public function approve(Request $request)
    {
        $result = $this->validateAuthRequest($request);

        if ($result['errorRedirect'] !== null) {
            return $result['errorRedirect'];
        }

        $params = $result['params'];

        $deny = function (string $error, string $description) use ($params): RedirectResponse {
            $query = ['error' => $error, 'error_description' => $description];
            if ($params['state'] !== null) {
                $query['state'] = (string) $params['state'];
            }

            return redirect()->away($params['redirect_uri'] . '?' . http_build_query($query));
        };

        if ($request->input('decision') !== 'approve') {
            return $deny('access_denied', 'The user denied the authorization request.');
        }

        try {
            $credentials = $request->validate([
                'email' => 'required|email',
                'password' => 'required|string',
            ]);
        } catch (ValidationException) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Enter your Rumu email and password.']);
        }

        if (! Auth::attempt($credentials)) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'These credentials do not match our records.']);
        }

        $request->session()->regenerate();

        $code = McpOAuthAuthorizationCode::issue(
            $request->user(),
            $params['client_id'],
            $params['redirect_uri'],
            $params['scopes'],
            $params['code_challenge'],
            $params['code_challenge_method'],
        );

        $query = ['code' => $code];
        if ($params['state'] !== null) {
            $query['state'] = (string) $params['state'];
        }

        return redirect()->away($params['redirect_uri'] . '?' . http_build_query($query));
    }
}
