<?php

namespace App\Services\Social\Meta;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class MetaApiClient
{
    protected string $baseUrl = 'https://graph.facebook.com';
    protected string $version;

    public function __construct()
    {
        $this->version = config('services.meta.graph_version', 'v20.0');
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getBaseUrl(): string
    {
        return "{$this->baseUrl}/{$this->version}";
    }

    /**
     * Perform a GET request to the Graph API.
     */
    public function get(string $endpoint, array $query = [], ?string $accessToken = null): array
    {
        $url = $this->buildUrl($endpoint);
        
        $request = Http::timeout(30);
        if ($accessToken) {
            $request = $request->withToken($accessToken);
        }

        $response = $request->get($url, $query);

        if ($response->failed()) {
            $this->handleError($response, "GET {$endpoint}");
        }

        return $response->json() ?? [];
    }

    /**
     * Perform a POST request to the Graph API.
     */
    public function post(string $endpoint, array $data = [], ?string $accessToken = null): array
    {
        $url = $this->buildUrl($endpoint);

        $request = Http::timeout(30);
        if ($accessToken) {
            $request = $request->withToken($accessToken);
        }

        $response = $request->post($url, $data);

        if ($response->failed()) {
            $this->handleError($response, "POST {$endpoint}");
        }

        return $response->json() ?? [];
    }

    /**
     * Perform a DELETE request to the Graph API.
     */
    public function delete(string $endpoint, array $query = [], ?string $accessToken = null): array
    {
        $url = $this->buildUrl($endpoint);

        $request = Http::timeout(30);
        if ($accessToken) {
            $request = $request->withToken($accessToken);
        }

        $response = $request->delete($url, $query);

        if ($response->failed()) {
            $this->handleError($response, "DELETE {$endpoint}");
        }

        return $response->json() ?? [];
    }

    /**
     * Build standard Graph API URL or preserve full URL.
     */
    protected function buildUrl(string $endpoint): string
    {
        if (str_starts_with($endpoint, 'http://') || str_starts_with($endpoint, 'https://')) {
            return $endpoint;
        }

        $trimmed = ltrim($endpoint, '/');
        return "{$this->baseUrl}/{$this->version}/{$trimmed}";
    }

    /**
     * Unified Graph API error handling.
     */
    protected function handleError($response, string $context): void
    {
        $body = $response->json();
        $error = $body['error'] ?? [];
        $message = $error['message'] ?? $response->body() ?? 'Unknown Graph API Error';
        $code = $error['code'] ?? $response->status();
        $subcode = $error['error_subcode'] ?? null;

        Log::error("Meta Graph API Error [{$context}]", [
            'status' => $response->status(),
            'code' => $code,
            'subcode' => $subcode,
            'message' => $message,
            'fbtrace_id' => $error['fbtrace_id'] ?? null,
        ]);

        throw new Exception("Meta API error [{$code}]: {$message}", $response->status());
    }
}

