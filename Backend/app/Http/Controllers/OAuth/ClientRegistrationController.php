<?php

namespace App\Http\Controllers\OAuth;

use App\Http\Controllers\Controller;
use App\Models\McpOAuthClient;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ClientRegistrationController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'client_name' => 'required|string|min:1|max:120',
            'redirect_uris' => 'required|array|min:1|max:10',
            'redirect_uris.*' => [
                'required',
                'string',
                'max:2000',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (! is_string($value)) {
                        $fail('redirect_uris must be strings.');
                        return;
                    }
                    $scheme = parse_url($value, PHP_URL_SCHEME);
                    $isLoopbackHttp = str_starts_with($value, 'http://localhost')
                        || str_starts_with($value, 'http://127.0.0.1');
                    if (! in_array($scheme, ['https', 'http'], true) || ($scheme === 'http' && ! $isLoopbackHttp)) {
                        $fail('redirect_uris must use https, or http on localhost for development.');
                    }
                },
            ],
            'grant_types' => 'sometimes|array',
            'grant_types.*' => 'in:authorization_code',
            'token_endpoint_auth_method' => 'sometimes|in:none',
        ]);

        $client = McpOAuthClient::create([
            'client_id' => 'mcp_' . Str::random(32),
            'name' => $validated['client_name'],
            'redirect_uris' => array_values($validated['redirect_uris']),
            'grant_types' => $validated['grant_types'] ?? ['authorization_code'],
        ]);

        return response()->json([
            'client_id' => $client->client_id,
            'client_name' => $client->name,
            'redirect_uris' => $client->redirect_uris,
            'grant_types' => $client->grant_types,
            'response_types' => ['code'],
            'token_endpoint_auth_method' => 'none',
            'client_id_issued_at' => $client->created_at->getTimestamp(),
        ], 201);
    }
}
