<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class McpOAuthAuthorizationCode extends Model
{
    protected $table = 'mcp_oauth_authorization_codes';

    public const TTL_MINUTES = 10;
    public const TOKEN_TTL_DAYS = 30;

    protected $fillable = [
        'code_hash',
        'user_id',
        'client_id',
        'redirect_uri',
        'scopes',
        'code_challenge',
        'code_challenge_method',
        'expires_at',
        'consumed_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'consumed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function hash(string $plainCode): string
    {
        return hash('sha256', $plainCode);
    }

    public static function issue(
        User $user,
        string $clientId,
        string $redirectUri,
        ?string $scopes,
        ?string $codeChallenge = null,
        ?string $codeChallengeMethod = null,
    ): string {
        $plain = Str::random(64);

        self::create([
            'code_hash' => self::hash($plain),
            'user_id' => $user->id,
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'scopes' => $scopes,
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => $codeChallengeMethod,
            'expires_at' => now()->addMinutes(self::TTL_MINUTES),
        ]);

        return $plain;
    }

    public function isUsable(): bool
    {
        return $this->consumed_at === null && $this->expires_at->isFuture();
    }

    public function verifyChallenge(?string $verifier): bool
    {
        if (empty($this->code_challenge)) {
            return true;
        }

        if (empty($verifier)) {
            return false;
        }

        $expected = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');

        return hash_equals($this->code_challenge, $expected);
    }

    public function consume(): void
    {
        $this->update(['consumed_at' => now()]);
    }
}
