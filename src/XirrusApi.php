<?php

namespace FredBradley\XirrusApi;

use FredBradley\XirrusApi\Traits\Search;
use FredBradley\XirrusApi\Traits\Arrays;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Class XirrusApi
 * @package FredBradley\XirrusApi
 */
class XirrusApi
{
    use XirrusAuth;
    use Search;
    use Arrays;

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
     * @var \Illuminate\Http\Client\PendingRequest
     */
    protected $client;

    /**
     * Set a default name, and users can overwrite if they need to.
     */
    protected $token_filename = 'xirrus_token.json';

    /**
     * XirrusApi constructor.
     *
     * @param  string  $client_id
     * @param  string  $client_secret
     * @param  array  $options
     */
    public function __construct(string $base_uri, string $client_id, string $client_secret, array $options = null)
    {
        $this->api_base_uri = $base_uri;
        $this->api_base_path = $options[ 'path' ] ?? '/api/v1/'; // Default to version 1

        $this->ssl_verify = $options[ 'verify' ] ?? true; // Default to true, but some users will need to overwrite this
        $this->token_filename = base_path($options[ 'token_filename' ] ?? $this->token_filename);

        $this->setupClient($client_id, $client_secret);
    }

    /**
     * @param  string  $client_id
     * @param  string  $client_secret
     *
     * @return void
     * @throws \Illuminate\Http\Client\RequestException
     */
    private function setupClient(string $client_id, string $client_secret): void
    {
        $authToken = $this->getAuthBearerToken($client_id, $client_secret);

        $this->client = Http::acceptJson()
                            ->withoutVerifying()
                            ->withToken($authToken)
                            ->baseUrl($this->api_base_uri . $this->api_base_path);
    }

    /**
     * @param  string  $uri
     * @param  array  $query
     * @param  \Closure|null  $closure
     *
     * @return array|mixed|object
     * @throws \FredBradley\XirrusApi\XirrusApiException
     */
    public function get(string $uri, array $query = [], \Closure $closure = null)
    {
        try {
            $response = $this->client->get($uri, $query)->object();
            if (is_callable($closure)) {
                return $closure($response);
            }
            if (isset($response->error) && $response->error === 'invalid_token' && is_null(Cache::get(self::$token_refresh_count))) {
                Cache::put(self::$token_refresh_count, 1, now()->addMinutes(5));
                $this->removeTokenJsonFile();
                return $this->get($uri, $query, $closure);
            }

            if (Cache::get(self::$token_refresh_count)) {
                Cache::forget(self::$token_refresh_count);
            }

            return $response;
        } catch (\Exception $exception) {
            throw new XirrusApiException($exception->getMessage(), $exception->getCode());
        }
    }

    /**
     * Shorthand function to create requests with JSON body and query parameters.
     *
     * @param  string  $method
     * @param  string  $uri
     * @param  array  $json
     * @param  array  $query
     * @param  array  $options
     * @param  bool|\Closure  $closure  JSON decode response body (defaults to true), or
     *                                 Or pass a Closure through to manipulate the output.
     *
     * @return mixed
     * @throws \FredBradley\XirrusApi\XirrusApiException
     * @deprecated Now encouraging people to use get method directly.
     */
    public function request(
        string $method,
        $uri = '',
        array $json = [],
        array $query = [],
        array $options = [],
        $closure = true
    ) {
        try {
            $options = array_merge($json, $query, $options);
            $response = $this->client->$method($uri, $options)->throw()->object();

            if (is_callable($closure)) {
                return $closure($response);
            }
            return $response;
        } catch (\Exception $exception) {
            throw new XirrusApiException($exception->getMessage(), $exception->getCode());
        }
    }


    /**
     * @param  array  $pieces
     *
     * @return string
     */
    public function generateEndpoint(array $pieces): string
    {
        $str = implode('/', $pieces);

        return str_replace('//', '/', $str);
    }
}
