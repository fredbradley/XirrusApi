<?php

namespace FredBradley\XirrusApi;

use GuzzleHttp\Client;

/**
 * Class XirrusApi
 * @package FredBradley\XirrusApi
 */
class XirrusApi
{
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
    public const TOKEN_FILENAME = "xirrus_token.json";

    /**
     * XirrusApi constructor.
     * @param string $client_id
     * @param string $client_secret
     * @param array $options
     */
    public function __construct(string $base_uri, string $client_id, string $client_secret, array $options)
    {
        $this->api_base_uri = $base_uri;
        $this->api_base_path = $options[ 'path' ] ?? '/api/v1/'; // Default to version 1

        $this->ssl_verify = (bool)$options[ 'verify' ] ?? true; // Default to true, but some users will need to overwrite this
        $token_filename = $options[ 'token_filename' ] ?? self::TOKEN_FILENAME;

        $authToken = $this->getAuthBearerToken($client_id, $client_secret, $token_filename);

        $this->client = new Client([
                'verify' => $this->ssl_verify,
                'base_uri' => $this->api_base_uri . $this->api_base_path,
                'headers' => [
                    'Authorization' => 'Bearer ' . $authToken,
                    'Accept' => 'application/json'
                ]
            ]
        );
    }

    /**
     * @param string $client_id
     * @param string $client_secret
     * @param string $token_filename
     * @return mixed
     */
    private function getAuthBearerToken(string $client_id, string $client_secret, string $token_filename)
    {
        if (file_exists($token_filename)) {
            $json = json_decode(file_get_contents($token_filename));
        } else {
            $json = new \stdClass();
            $json->expires_at = false;
        }
        if ($json->expires_at < time() || $json->expires_at === false) {

            $client = new Client([
                'verify' => $this->ssl_verify,
                'base_uri' => $this->api_base_uri,
                'headers' => [
                    'Accept' => 'application/json'
                ]
            ]);

            $request = $client->post('oauth/token', [
                'form_params' => [
                    'client_id' => $client_id,
                    'client_secret' => $client_secret,
                    'grant_type' => 'client_credentials'
                ]
            ]);

            $response = json_decode($request->getBody()->getContents());
            $token = $response->access_token;
            $expires_at = $response->expires_in;

            $json = new \stdClass();
            $json->expires_at = time() + ($expires_at * 60); // Now, plus  (seconds left on token * 60 to convert to milliseconds)
            $json->token = $token;

            $fp = fopen($token_filename, 'w');
            fwrite($fp, json_encode($json));
            fclose($fp);
        }

        return $json->token;
    }

    /**
     * Shorthand function to create requests with JSON body and query parameters.
     * @param $method
     * @param string $uri
     * @param array $json
     * @param array $query
     * @param array $options
     * @param boolean $decode JSON decode response body (defaults to true).
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function request(
        $method,
        $uri = '',
        array $json = [],
        array $query = [],
        array $options = [],
        $decode = true
    ) {
        $response = $this->client->request($method, $uri, array_merge([
            'json' => $json,
            'query' => $query
        ], $options));

        return $decode ? json_decode((string)$response->getBody(), true) : (string)$response->getBody();
    }
}
