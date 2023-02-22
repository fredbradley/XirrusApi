<?php

namespace FredBradley\XirrusApi;

use Illuminate\Support\Facades\Http;

/**
 * Trait XirrusAuth
 * @package FredBradley\XirrusApi
 */
trait XirrusAuth
{
    public static $token_refresh_count = 'xirrus_token_refresh_count';

    /**
     * @param  string  $client_id
     * @param  string  $client_secret
     *
     * @return object
     * @throws \Illuminate\Http\Client\RequestException
     */
    private function getRefreshedAuthToken(string $client_id, string $client_secret): object
    {
        return Http::withoutVerifying()
                   ->asForm()
                   ->baseUrl($this->api_base_uri)
                   ->acceptJson()
                   ->post('oauth/token', [
                       'client_id' => $client_id,
                       'client_secret' => $client_secret,
                       'grant_type' => 'client_credentials',
                   ])->throw()->object();
    }

    private function removeTokenJsonFile(): void
    {
        if (file_exists($this->token_filename)) {
            unlink($this->token_filename);
        }
    }

    /**
     * @param  string  $client_id
     * @param  string  $client_secret
     *
     * @return string
     * @throws \Illuminate\Http\Client\RequestException
     * @throws \Exception
     */
    private function getAuthBearerToken(string $client_id, string $client_secret): string
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
     * @param  string  $json_string
     *
     * @return int The number of bytes of token_filename
     * @throws \Exception
     */
    private function saveTokenJsonFile(string $json_string): int
    {
        $put = file_put_contents($this->token_filename, $json_string);
        if ($put === false) {
            throw new \Exception("Could not put auth file");
        }
        return $put;
    }

    /**
     * @param  \object  $json
     *
     * @return bool
     */
    private function tokenHasExpired(object $json): bool
    {
        if (isset($json->expires_at) && is_int($json->expires_at) && $json->expires_at > time()) {
            return false;
        }
        return true;
        /*
        if ($json->expires_at < time() || $json->expires_at === false) {
            return true;
        }

        return false;*/
    }

    /**
     * Calculates the expires at time as a unix timestamp..
     * Time now, plus seconds left on token
     *
     * @param  int  $expires_at
     *
     * @return int Unix Timestamp
     */
    private function generateExpiresAtTime(int $expires_at): int
    {
        return now()->addSeconds($expires_at)->format('U');
    }
}
