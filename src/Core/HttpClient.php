<?php
namespace Chenms\Insurance\Core;

use GuzzleHttp\Client;

class HttpClient
{
    protected $client;

    public function __construct()
    {
        $this->client = new Client();
    }

    public function post(string $url, array $data): array
    {
        $response = $this->client->post($url, [
            'json' => $data,
            'headers' => ['Content-Type' => 'application/json'],
        ]);

        return json_decode($response->getBody(), true);
    }

    public function get(string $url): array
    {
        $response = $this->client->get($url);
        return json_decode($response->getBody(), true);
    }
}