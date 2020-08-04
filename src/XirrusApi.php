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
    use XirrusAuth, Search;

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
     *
     * @param string $client_id
     * @param string $client_secret
     * @param array  $options
     */
    public function __construct(string $base_uri, string $client_id, string $client_secret, array $options = null)
    {
        $this->api_base_uri = $base_uri;
        $this->api_base_path = $options[ 'path' ] ?? '/api/v1/'; // Default to version 1

        $this->ssl_verify = $options[ 'verify' ] ?? true; // Default to true, but some users will need to overwrite this
        $this->token_filename = $options[ 'token_filename' ] ?? $this->token_filename;

        $this->setupClient($client_id, $client_secret);
    }

    /**
     * @param string $client_id
     * @param string $client_secret
     */
    private function setupClient(string $client_id, string $client_secret)
    {
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
     * Shorthand function to create requests with JSON body and query parameters.
     *
     * @param        $method
     * @param string $uri
     * @param array  $json
     * @param array  $query
     * @param array  $options
     * @param bool|\Closure   $closure JSON decode response body (defaults to true), or
     *                                 Or pass a Closure through to manipulate the output.
     *
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function request(
        string $method,
        $uri = '',
        array $json = [],
        array $query = [],
        array $options = [],
        $closure = true
    ) {
        $response = $this->client->request($method, $uri, array_merge([
            'json' => $json,
            'query' => $query,
        ], $options));

        if (is_callable($closure)) {
            return $closure(json_decode((string)$response->getBody()));
        }
        return $closure ? json_decode((string)$response->getBody()) : (string)$response->getBody();
    }


    /**
     * @param array $pieces
     *
     * @return string
     */
    public function generateEndpoint(array $pieces): string
    {
        $str = implode("/", $pieces);

        return str_replace("//", "/", $str);
    }
}
