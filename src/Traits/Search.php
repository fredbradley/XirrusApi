<?php

namespace FredBradley\XirrusApi\Traits;

/**
 * Trait Search
 * @package FredBradley\XirrusApi\Traits
 */
trait Search
{
    /**
     * @param string $search_query
     * @param string|null $search_type
     * @return mixed
     */
    public function search(string $search_query, string $search_type = null)
    {
        $uri = $this->generateEndpoint(['search.json', $search_type, $search_query]);

        return $this->request('GET', $uri);
    }
}
