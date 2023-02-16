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
        $this->client = new Client([
            'base_uri' => $this->apiBasePath
        ]);

        $this->clientId     = config('instagram-feed.clientId');
        $this->clientSecret = config('instagram-feed.clientSecret');
        $this->token        = config('instagram-feed.accessToken');
        $this->redirectUri  = config('instagram-feed.redirectUri');


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
                if (strpos(config('app.url'), ':8000') || auth()->check()) {
                    $obj->link    = $this->getAuthorizeLink();
                } else {
                    $obj->message = 'Token has expired';
                }
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

        $response = $client->request('POST', 'access_token', $data);

        $token = json_decode($response->getBody()->getContents());

        if (isset($token->access_token)) {
            $this->exchangeShortTokenForLongLivedToken($token->access_token);
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

    private function getAuthorizeLink()
    {
        $params = [
            'client_id='.$this->clientId,
            'redirect_uri='.$this->redirectUri,
            'scope='.$this->authScopes,
            'response_type=code'
        ];
        $link   = $this->authBasePath.'authorize?'.implode('&', $params);

        return '<a href="'.$link.'" class="button">Click here to authorize</a>';
    }

    private function storeToken($body) {
        $token = json_decode($body);
        $storeToken = json_decode($body);

        $storeToken->expires = \Carbon\Carbon::now()->addSeconds($storeToken->expires_in)->format('Y-m-d H:i:s');
        \Storage::disk('local')->put($this->tokenFile, json_encode($storeToken));

        if (isset($token->access_token)) {
            $this->writeTokenToEnv($token->access_token);
            $this->token = $token->access_token;
        }
    }

    private function writeTokenToEnv($newToken)
    {
        $file = base_path('.env');
        $envContents = file_get_contents($file);

        $search = [$this->token];
        $replace = [$newToken];

        $envContents = str_replace($search, $replace, $envContents);

        file_put_contents($file, $envContents);
    }
}
