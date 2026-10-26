<?php

namespace RefinedDigital\Social\InstagramFeed\Module\Http\Repositories;

use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class InstagramFeedRepository
{
    protected string $apiBasePath = 'https://graph.instagram.com/';

    protected string $authBasePath = 'https://instagram.com/oauth/';

    protected string $exchangeBasePath = 'https://api.instagram.com/oauth/';

    protected Client $client;

    protected string $mediaFields = 'id,caption,media_url,permalink,timestamp,thumbnail_url';

    protected string $authScopes = 'instagram_business_basic';

    protected string $tokenFile = 'instagram-token.json';

    protected ?string $clientId = null;

    protected ?string $clientSecret = null;

    protected ?string $token = null;

    protected ?string $redirectUri = null;

    protected bool $refreshChecked = false;

    public function __construct()
    {
        $settings = settings()->get('instagram');

        $this->clientId = $settings->client_id->value ?? null;
        $this->clientSecret = $settings->client_secret->value ?? null;
        $this->redirectUri = $settings->redirect_url->value ?? request()->url();

        $this->client = new Client([
            'base_uri' => $this->apiBasePath,
            'timeout' => 10,
        ]);

        $tokenOnFile = $this->readToken();
        if (isset($tokenOnFile->access_token)) {
            $this->token = $tokenOnFile->access_token;
        }
    }

    /**
     * Whether a long lived token has been stored.
     */
    public function isConnected(): bool
    {
        return (bool) $this->token;
    }

    /**
     * Refreshes the long lived token once it is within 30 days of expiring.
     */
    public function refreshToken(): bool
    {
        $tokenOnFile = $this->readToken();

        if (! $this->token || ! isset($tokenOnFile->expires)) {
            return true;
        }

        $expires = Carbon::createFromFormat('Y-m-d H:i:s', $tokenOnFile->expires)->subDays(30);
        if (Carbon::now()->lte($expires)) {
            return true;
        }

        try {
            $response = $this->client->request('GET', 'refresh_access_token', [
                'query' => [
                    'grant_type' => 'ig_refresh_token',
                    'access_token' => $this->token,
                ],
            ]);

            $this->storeToken($response->getBody()->getContents());

            return true;
        } catch (\Throwable $error) {
            Log::info('Instagram token refresh failed: '.$this->redact($error->getMessage()));
        }

        return false;
    }

    /**
     * The latest media for the connected account, as {success, data[, message]}.
     */
    public function getUserMedia(int $limit = 20): \stdClass
    {
        $obj = new \stdClass;
        $obj->success = false;
        $obj->data = collect([]);

        if (! $this->token) {
            $obj->message = 'Instagram is not connected';

            return $obj;
        }

        $this->refreshOnce();

        try {
            $response = $this->client->request('GET', 'me/media', [
                'query' => [
                    'access_token' => $this->token,
                    'fields' => $this->mediaFields,
                    'limit' => $limit,
                ],
            ]);

            $body = json_decode($response->getBody()->getContents());
            $obj->success = true;
            $obj->data = collect($body->data ?? []);
        } catch (\Throwable $error) {
            Log::info('Instagram feed failed: '.$this->redact($error->getMessage()));
            $obj->message = 'Unable to load the Instagram feed';
        }

        return $obj;
    }

    /**
     * A single media item, or null when it can not be loaded.
     */
    public function getUserImage(string $imageId): ?object
    {
        if (! $this->token) {
            return null;
        }

        $this->refreshOnce();

        try {
            $response = $this->client->request('GET', $imageId, [
                'query' => [
                    'access_token' => $this->token,
                    'fields' => $this->mediaFields,
                ],
            ]);

            return json_decode($response->getBody()->getContents());
        } catch (\Throwable $error) {
            Log::info('Instagram media failed: '.$this->redact($error->getMessage()));
        }

        return null;
    }

    /**
     * Swaps the oauth code for a long lived token and returns to the settings page.
     */
    public function exchangeCodeForToken(string $code): RedirectResponse
    {
        $client = new Client([
            'base_uri' => $this->exchangeBasePath,
            'timeout' => 10,
        ]);

        try {
            $response = $client->request('POST', 'access_token', [
                'form_params' => [
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'code' => $code,
                    'grant_type' => 'authorization_code',
                    'redirect_uri' => $this->redirectUri,
                ],
            ]);

            $token = json_decode($response->getBody()->getContents());

            if (isset($token->access_token)) {
                $this->exchangeShortTokenForLongLivedToken($token->access_token);
            }

            return redirect()->route('refined.instagram.index')->with('status', 'Successfully connected');
        } catch (ClientException $e) {
            $errors = json_decode($e->getResponse()->getBody()->getContents());

            return $this->connectFailed($errors->error_message ?? null);
        } catch (\Throwable $e) {
            return $this->connectFailed($e->getMessage());
        }
    }

    public function getAuthorizeLink(): string
    {
        return $this->authBasePath.'authorize?'.http_build_query([
            'enable_fb_login' => 0,
            'force_authentication' => 1,
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'scope' => $this->authScopes,
            'response_type' => 'code',
        ], '', '&', PHP_QUERY_RFC3986);
    }

    public function getTokenFile(): string
    {
        return Storage::disk('local')->path($this->tokenFile);
    }

    private function refreshOnce(): void
    {
        if (! $this->refreshChecked) {
            $this->refreshChecked = true;
            $this->refreshToken();
        }
    }

    private function exchangeShortTokenForLongLivedToken(string $shortToken): void
    {
        $response = $this->client->request('GET', 'access_token', [
            'query' => [
                'grant_type' => 'ig_exchange_token',
                'client_secret' => $this->clientSecret,
                'access_token' => $shortToken,
            ],
        ]);

        $this->storeToken($response->getBody()->getContents());
    }

    private function storeToken(string $body): void
    {
        $token = json_decode($body);

        if (! isset($token->access_token)) {
            Log::info('Instagram returned no access token: '.$this->redact($body));

            return;
        }

        $token->expires = Carbon::now()->addSeconds((int) ($token->expires_in ?? 0))->format('Y-m-d H:i:s');
        Storage::disk('local')->put($this->tokenFile, json_encode($token));

        $this->token = $token->access_token;
    }

    private function connectFailed(?string $message): RedirectResponse
    {
        return redirect()
            ->route('refined.instagram.index')
            ->with('status', 'Failed to connect to Instagram. '.($message ?: 'Please try again').'. Did you add the user as a test user?')
            ->with('fail', 1);
    }

    /**
     * Guzzle puts the request url, and so the token and secret, in its messages.
     */
    private function redact(string $message): string
    {
        return preg_replace('/(access_token|client_secret)=[^&\s`"]+/', '$1=***', $message);
    }

    private function readToken(): ?object
    {
        $disk = Storage::disk('local');

        // laravel 11+ moved the local disk to storage/app/private, carry a token from the old location across
        $legacyFile = storage_path('app/'.$this->tokenFile);
        if (! $disk->exists($this->tokenFile) && is_file($legacyFile) && $legacyFile !== $disk->path($this->tokenFile)) {
            $disk->put($this->tokenFile, file_get_contents($legacyFile));
        }

        if (! $disk->exists($this->tokenFile)) {
            return null;
        }

        $token = json_decode($disk->get($this->tokenFile));

        return is_object($token) ? $token : null;
    }
}
