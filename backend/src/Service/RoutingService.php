<?php

namespace App\Service;

use Psr\Cache\CacheItemPoolInterface;

class RoutingService
{
    private string $osrmUrl;

    private ?CacheItemPoolInterface $cache;

    public function __construct(string $osrmUrl = 'https://router.project-osrm.org', ?CacheItemPoolInterface $cache = null)
    {
        $this->osrmUrl = rtrim($osrmUrl, '/');
        $this->cache = $cache;
    }

    private function osrmRequest(string $url): string|false
    {
        $ctx = stream_context_create(['http' => ['timeout' => 25]]);
        return @file_get_contents($url, false, $ctx);
    }

    public function getRoute(float $fromLat, float $fromLng, float $toLat, float $toLng): array
    {
        $waypoints = [['lat' => $fromLat, 'lng' => $fromLng], ['lat' => $toLat, 'lng' => $toLng]];
        $key = $this->cacheKey($waypoints);
        $cached = $this->readRouteCache($key);
        if ($cached !== null) {
            return $cached;
        }
        $route = $this->osrmRoute($waypoints);
        if ($route !== null) {
            $this->writeRouteCache($key, $route);
            return $route;
        }
        return $this->haversineRoute($waypoints);
    }

    public function getRouteMulti(array $waypoints): array
    {
        if (count($waypoints) < 2) {
            throw new \InvalidArgumentException('Au moins 2 waypoints requis.');
        }

        $key = $this->cacheKey($waypoints);
        $cached = $this->readRouteCache($key);
        if ($cached !== null) {
            return $cached;
        }
        $route = $this->osrmRoute($waypoints);
        if ($route !== null) {
            $this->writeRouteCache($key, $route);
            return $route;
        }
        return $this->haversineRoute($waypoints);
    }

    /**
     * Calcule plusieurs routes en parallèle (une requête OSRM par jeu de waypoints).
     * Les routes déjà calculées sont relues depuis le cache (5 min) pour ne pas
     * marteler le serveur OSRM à chaque poll de la carte.
     * Retourne un tableau indexé par la clé du jeu de waypoints d'origine.
     */
    public function getRouteMultiBatch(array $waypointSets): array
    {
        $urls = [];
        $keys = [];
        $results = [];
        foreach ($waypointSets as $i => $waypoints) {
            if (count($waypoints) < 2) {
                continue;
            }
            $key = $this->cacheKey($waypoints);
            $cached = $this->readRouteCache($key);
            if ($cached !== null) {
                $results[$i] = $cached;
                continue;
            }
            $keys[$i] = $key;
            $urls[$i] = $this->buildUrl($waypoints);
        }

        if (!empty($urls)) {
            $bodies = $this->osrmRequestMulti($urls);
            foreach ($urls as $i => $url) {
                $data = isset($bodies[$i]) && $bodies[$i] !== false ? json_decode($bodies[$i], true) : null;
                if (!empty($data['routes'][0])) {
                    $route = $data['routes'][0];
                    $result = [
                        'distance' => (float) $route['distance'],
                        'duration' => (float) $route['duration'],
                        'geometry' => $route['geometry'],
                    ];
                    $this->writeRouteCache($keys[$i], $result);
                    $results[$i] = $result;
                } else {
                    $results[$i] = $this->haversineRoute($waypointSets[$i]);
                }
            }
        }
        return $results;
    }

    private function buildUrl(array $waypoints): string
    {
        $coords = [];
        foreach ($waypoints as $wp) {
            $coords[] = $wp['lng'] . ',' . $wp['lat'];
        }
        return sprintf(
            '%s/route/v1/driving/%s?overview=full&geometries=geojson',
            $this->osrmUrl,
            implode(';', $coords)
        );
    }

    private function cacheKey(array $waypoints): string
    {
        $rounded = array_map(
            fn (array $wp) => [round((float) $wp['lat'], 4), round((float) $wp['lng'], 4)],
            $waypoints
        );
        return 'osrm.route.' . sha1(json_encode($rounded));
    }

    private function readRouteCache(string $key): ?array
    {
        if ($this->cache === null) {
            return null;
        }
        $item = $this->cache->getItem($key);
        return $item->isHit() ? $item->get() : null;
    }

    private function writeRouteCache(string $key, array $route): void
    {
        if ($this->cache === null) {
            return;
        }
        $item = $this->cache->getItem($key);
        $item->set($route)->expiresAfter(300);
        $this->cache->save($item);
    }

    private function osrmRoute(array $waypoints): ?array
    {
        $ctx = stream_context_create(['http' => ['timeout' => 25]]);
        $response = @file_get_contents($this->buildUrl($waypoints), false, $ctx);
        if ($response === false) {
            return null;
        }
        $data = json_decode($response, true);
        if (empty($data['routes'][0])) {
            return null;
        }
        $route = $data['routes'][0];
        return [
            'distance' => (float) $route['distance'],
            'duration' => (float) $route['duration'],
            'geometry' => $route['geometry'],
        ];
    }

    private function haversineRoute(array $waypoints): array
    {
        $distance = 0;
        $coordinates = [];
        for ($i = 0; $i < count($waypoints) - 1; $i++) {
            $distance += $this->haversine(
                (float) $waypoints[$i]['lat'],
                (float) $waypoints[$i]['lng'],
                (float) $waypoints[$i + 1]['lat'],
                (float) $waypoints[$i + 1]['lng']
            );
            $coordinates[] = [(float) $waypoints[$i]['lng'], (float) $waypoints[$i]['lat']];
        }
        $coordinates[] = [
            (float) $waypoints[count($waypoints) - 1]['lng'],
            (float) $waypoints[count($waypoints) - 1]['lat'],
        ];

        return [
            'distance' => $distance,
            'duration' => ($distance / 1000 / 15) * 3600,
            'geometry' => [
                'type' => 'LineString',
                'coordinates' => $coordinates,
            ],
        ];
    }

    private function osrmRequestMulti(array $urls): array
    {
        if (empty($urls) || !extension_loaded('curl')) {
            $bodies = [];
            foreach ($urls as $i => $url) {
                $bodies[$i] = $this->osrmRequest($url);
            }
            return $bodies;
        }

        $mh = curl_multi_init();
        $handles = [];
        foreach ($urls as $i => $url) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 25,
                CURLOPT_CONNECTTIMEOUT => 5,
            ]);
            curl_multi_add_handle($mh, $ch);
            $handles[$i] = $ch;
        }

        do {
            $status = curl_multi_exec($mh, $running);
            if ($running) {
                curl_multi_select($mh, 0.5);
            }
        } while ($running && $status === CURLM_OK);

        $bodies = [];
        foreach ($handles as $i => $ch) {
            $bodies[$i] = curl_multi_getcontent($ch);
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }
        curl_multi_close($mh);

        return $bodies;
    }

    private function haversine(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $R = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $R * $c;
    }
}
