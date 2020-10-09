<?php

namespace FredBradley\XirrusApi\Traits;

/**
 * Trait Arrays
 * @package FredBradley\XirrusApi\Traits
 */
trait Arrays
{
    /**
     * @param string $macAddress
     *
     * @return string
     */
    public function getAccessPointSerialNumber(string $macAddress): string
    {
        $result = $this->request("GET", "arrays.json/" . $macAddress . "/system-information");
        $data = collect($result->hardware->components)->where("component", "=", "Access Point")->first();
        return $data->serialNumber;
    }
}
