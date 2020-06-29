<?php

namespace FredBradley\XirrusApi;

use FredBradley\XirrusApi\Traits\Search;
use GuzzleHttp\Client;

/**
 * Class XirrusApi
 * @package FredBradley\XirrusApi
 */
class XirrusApi
{
    use Search;

    /**
     * @var string
     */
    protected $api_base_uri;
    /**
     * @var string
     */
    protected $api_base_path;
    /**
     * @var bool
     */
    protected $ssl_verify;
    /**
     * @var Client
     */
    protected $client;

    /**
     * Set a default name, and users can overwrite if they need to.
     */
    protected $token_filename = "xirrus_token.json";

    /**
     * XirrusApi constructor.
     * @param string $client_id
     * @param string $client_secret
     * @param array $options
     */
    public function __construct(string $base_uri, string $client_id, string $client_secret, array $options=null)
    {
        $this->api_base_uri = $base_uri;
        $this->api_base_path = $options[ 'path' ] ?? '/api/v1/'; // Default to version 1

        $this->ssl_verify = $options[ 'verify' ] ?? true; // Default to true, but some users will need to overwrite this
        $this->token_filename = $options[ 'token_filename' ] ?? $this->token_filename;

        $authToken = $this->getAuthBearerToken($client_id, $client_secret);

        $this->client = new Client(
            [
                'verify' => $this->ssl_verify,
                'base_uri' => $this->api_base_uri . $this->api_base_path,
                'headers' => [
                    'Authorization' => 'Bearer ' . $authToken,
                    'Accept' => 'application/json',
                ],
            ]
        );
    }

    /**
     * @param string $client_id
     * @param string $client_secret
     * @return mixed
     */
    private function getRefreshedAuthToken(string $client_id, string $client_secret)
    {
        $client = new Client([
            'verify' => $this->ssl_verify,
            'base_uri' => $this->api_base_uri,
            'headers' => [
                'Accept' => 'application/json',
            ],
        ]);

        $request = $client->post('oauth/token', [
            'form_params' => [
                'client_id' => $client_id,
                'client_secret' => $client_secret,
                'grant_type' => 'client_credentials',
            ],
        ]);

        return json_decode($request->getBody()->getContents());
    }

    /**
     * @param string $client_id
     * @param string $client_secret
     * @return mixed
     */
    private function getAuthBearerToken(string $client_id, string $client_secret)
    {
        if (file_exists($this->token_filename)) {
            $json = json_decode(file_get_contents($this->token_filename));
        } else {
            $json = new \stdClass();
            $json->expires_at = false;
        }

        if ($this->tokenHasExpired($json)) {
            $freshResponse = $this->getRefreshedAuthToken($client_id, $client_secret);
            $token = $freshResponse->access_token;
            $expires_at = $freshResponse->expires_in;

            $json = new \stdClass();
            $json->expires_at = $this->generateExpiresAtTime($expires_at);
            $json->token = $token;

            $this->saveTokenJsonFile(json_encode($json));
        }

        return $json->token;
    }

    /**
     * @param string $json_string
     * @return void
     */
    private function saveTokenJsonFile(string $json_string): void
    {
        $fp = fopen($this->token_filename, 'w');
        fwrite($fp, $json_string);
        fclose($fp);
    }

    /**
     * @param object $json
     * @return bool
     */
    private function tokenHasExpired(object $json): bool
    {
        if ($json->expires_at < time() || $json->expires_at === false) {
            return true;
        }

        return false;
    }

    /**
     * Calculates the expires at time as a unix timestamp..
     * Time now, plus (seconds left on token, times by 60 to get milliseconds)
     *
     * @param int $expires_at
     * @return int
     */
    private function generateExpiresAtTime(int $expires_at): int
    {
        return time() + ($expires_at * 60);
    }

    /**
     * Shorthand function to create requests with JSON body and query parameters.
     * @param $method
     * @param string $uri
     * @param array $json
     * @param array $query
     * @param array $options
     * @param bool $decode JSON decode response body (defaults to true).
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function request(
        string $method,
        $uri = '',
        array $json = [],
        array $query = [],
        array $options = [],
        $decode = true
    ) {
        $response = $this->client->request($method, $uri, array_merge([
            'json' => $json,
            'query' => $query,
        ], $options));

        return $decode ? json_decode((string)$response->getBody(), true) : (string)$response->getBody();
    }


    /**
     * @param array $pieces
     * @return string
     */
    public function generateEndpoint(array $pieces): string
    {
        $str = implode("/", $pieces);

        return str_replace("//", "/", $str);
    }
}
