<?php

namespace RefinedDigital\Social\InstagramFeed\Module\Http\Repositories;

use GuzzleHttp\Client;

class InstagramFeedRepository
{

    protected $apiBasePath = 'https://graph.instagram.com/';
    protected $authBasePath = 'https://api.instagram.com/oauth/';
    protected $client;
    protected $mediaFields = 'id,caption,media_url,permalink,timestamp,thumbnail_url';
    protected $authScopes = 'user_profile,user_media';
    protected $tokenFile = 'instagram-token.json';
    protected $clientId;
    protected $clientSecret;
    protected $token;
    protected $redirectUri;

    public function __construct()
    {
        $settings = settings()->get('instagram');

        $this->clientId = $settings->client_id->value ?? null;
        $this->clientSecret = $settings->client_secret->value ?? null;
        $this->redirectUri = $settings->redirect_url->value ?? request()->url();
        $this->token        = '';

        $this->client = new Client([
            'base_uri' => $this->apiBasePath
        ]);

        if (\Storage::disk('local')->exists($this->tokenFile)) {
            $tokenOnFile = json_decode(\Storage::disk('local')->get($this->tokenFile));
            if (isset($tokenOnFile->access_token)) {
                $this->token = $tokenOnFile->access_token;
            }
        }

        // check if we need to refresh the token
        if ($this->token) {
            $this->refreshToken();
        }
    }

    public function refreshToken()
    {
        $tokenOnFile = json_decode(\Storage::disk('local')->get($this->tokenFile));
        $refresh = false;

        if (isset($tokenOnFile->expires)) {
            $now = \Carbon\Carbon::now();
            $expires = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $tokenOnFile->expires)->subDays(30);
            if ($now->gt($expires)) {
                $refresh = true;
            }
        }

        if (!$refresh) {
            return true;
        }

        $params = [
            'grant_type=ig_refresh_token',
            'access_token='.$this->token
        ];

        try {
            $response = $this->client->request('GET', 'refresh_access_token', [
                'query' => implode('&', $params)
            ]);

            $body = $response->getBody()->getContents();
            $this->storeToken($body);

            return true;

        } catch(\Exception $error) {
            // print_r($error->getMessage());
        }

        return false;
    }

    public function getUserMedia($limit = 20)
    {
        $params = [
            'access_token='.$this->token,
            'fields='.$this->mediaFields,
            'limit='.$limit
        ];

        $obj          = new \stdClass();
        $obj->success = true;
        $obj->data    = collect([]);

        try {
            $response = $this->client->request('GET', 'me/media', [
                'query' => implode('&', $params)
            ]);

            $body      = json_decode($response->getBody()->getContents());
            $obj->data = collect($body->data);

        } catch(\Exception $error) {
            if($error->getCode() == 400) {
                $obj->success = false;
            }
        }

        return $obj;
    }

    public function getUserImage($imageId)
    {
        $params = [
            'access_token='.$this->token,
            'fields='.$this->mediaFields,
        ];

        try {
            $response = $this->client->request('GET', $imageId, [
                'query' => implode('&', $params)
            ]);

            return json_decode($response->getBody()->getContents());

        } catch(\Exception $error) {
            print_r($error->getMessage());
        }

        return null;
    }

    public function exchangeCodeForToken($code)
    {
        $data = [
            'form_params' => [
                'client_id'     => $this->clientId,
                'client_secret' => $this->clientSecret,
                'code'          => $code,
                'grant_type'    => 'authorization_code',
                'redirect_uri'  => $this->redirectUri
            ]
        ];

        $client = new Client([
            'base_uri' => $this->authBasePath
        ]);

        try {
            $response = $client->request('POST', 'access_token', $data);

            $token = json_decode($response->getBody()->getContents());

            if (isset($token->access_token)) {
                $this->exchangeShortTokenForLongLivedToken($token->access_token);
            }
            session()->flash('status', 'Successfully connected');
            return redirect()->route('refined.instagram.index')->with('status', 'Successfully connected');
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $response = $e->getResponse();
            $errors = json_decode($response->getBody()->getContents());
            return redirect()
                ->route('refined.instagram.index')
                ->with('status', 'Failed to connect to Instagram. '.($errors->error_message ?? 'Please try again').'.')
                ->with('fail', 1);
        } catch (\Exception $e) {
            return redirect()
                ->route('refined.instagram.index')
                ->with('status', 'Failed to connect to Instagram. '.($e->getMessage() ?? 'Please try again').'.')
                ->with('fail', 1);
        }


    }

    private function exchangeShortTokenForLongLivedToken($shortToken)
    {
        $params = [
            'grant_type=ig_exchange_token',
            'client_secret='.$this->clientSecret,
            'access_token='.$shortToken
        ];

        $response = $this->client->request('GET', 'access_token', [
            'query' => implode('&', $params)
        ]);

        $body = $response->getBody()->getContents();
        $this->storeToken($body);
    }

    public function getAuthorizeLink()
    {
        $params = [
            'client_id='.$this->clientId,
            'redirect_uri='.$this->redirectUri,
            'scope='.$this->authScopes,
            'response_type=code'
        ];

        $link = $this->authBasePath.'authorize?'.implode('&', $params);

        return $link;
    }

    private function storeToken($body) {
        $token = json_decode($body);
        $storeToken = json_decode($body);

        $storeToken->expires = \Carbon\Carbon::now()->addSeconds($storeToken->expires_in)->format('Y-m-d H:i:s');
        \Storage::disk('local')->put($this->tokenFile, json_encode($storeToken));

        if (isset($token->access_token)) {
            $this->token = $token->access_token;
        }
    }

    public function getTokenFile()
    {
        return storage_path('app/'.$this->tokenFile);
    }
}
