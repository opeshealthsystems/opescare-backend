<?php

namespace App\Modules\CareMap\Services;

class MapProviderService
{
    protected $provider;

    public function __construct()
    {
        $this->provider = config('caremap.provider', 'openstreetmap');
    }

    /**
     * Get the configured maps provider name
     */
    public function getProviderName()
    {
        return $this->provider;
    }

    /**
     * Generate safe embed or tile rendering parameters for the frontend
     */
    public function getMapConfiguration($lat, $lon, $zoom = 15)
    {
        if ($this->provider === 'google') {
            return [
                'type' => 'google_maps',
                'url' => "https://www.google.com/maps/embed/v1/view?key=" . config('caremap.google_key') . "&center={$lat},{$lon}&zoom={$zoom}",
            ];
        }

        if ($this->provider === 'mapbox') {
            return [
                'type' => 'mapbox',
                'style' => 'mapbox://styles/mapbox/streets-v11',
                'center' => [$lon, $lat],
                'zoom' => $zoom,
                'token' => config('caremap.mapbox_token'),
            ];
        }

        // OpenStreetMap Default
        return [
            'type' => 'openstreetmap',
            'url' => "https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png",
            'attribution' => '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            'center' => [$lat, $lon],
            'zoom' => $zoom,
        ];
    }
}
