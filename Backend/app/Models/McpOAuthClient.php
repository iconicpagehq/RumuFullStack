<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class McpOAuthClient extends Model
{
    protected $table = 'mcp_oauth_clients';

    protected $fillable = [
        'client_id',
        'client_secret',
        'name',
        'redirect_uris',
        'grant_types',
    ];

    protected $casts = [
        'redirect_uris' => 'array',
        'grant_types' => 'array',
    ];

    public function allowsRedirectUri(string $redirectUri): bool
    {
        if (in_array($redirectUri, $this->redirect_uris ?? [], true)) {
            return true;
        }

        // Support wildcard patterns (e.g. https://chat.openai.com/aip/*)
        foreach ($this->redirect_uris ?? [] as $pattern) {
            if (str_ends_with($pattern, '*') && str_starts_with($redirectUri, rtrim($pattern, '*'))) {
                return true;
            }
        }

        return false;
    }
}
