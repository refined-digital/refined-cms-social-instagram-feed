<?php

namespace RefinedDigital\Social\InstagramFeed\Module\Http\Repositories;

use GuzzleHttp\Client;

class InstagramFeedRepository {

  protected $apiBasePath = 'https://graph.instagram.com/';
  protected $client;
  protected $mediaFields = 'id,caption,media_url,permalink';

  public function __construct()
  {
    $this->client = new Client([
      'base_uri' => $this->apiBasePath
    ]);
  }

  public function getUserMedia($limit=20)
  {
    $params = [
      'access_token='.config('instagram-feed.accessToken'),
      'fields='.$this->mediaFields,
      'limit='.$limit
    ];

    try {
      $response = $this->client->request('GET', 'me/media', [
        'query' =>  implode('&', $params)
      ]);

      $body = json_decode($response->getBody()->getContents());
      return collect($body->data);

    } catch (\Exception $error) {
      help()->trace($error->getMessage());
    }

    return collect([]);
  }

  public function getUserImage($imageId)
  {
    $params = [
      'access_token='.config('instagram-feed.accessToken'),
      'fields='.$this->mediaFields,
    ];

    try {
      $response = $this->client->request('GET', $imageId, [
        'query' =>  implode('&', $params)
      ]);

      return json_decode($response->getBody()->getContents());

    } catch (\Exception $error) {
      help()->trace($error->getMessage());
    }

    return null;
  }
}
