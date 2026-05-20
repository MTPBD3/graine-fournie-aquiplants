<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class MeteoController extends AbstractController
{
    private const DEFAULT_LAT = 43.83;
    private const DEFAULT_LON = 4.84;
    private const FIELDS = 'temperature_2m,weathercode,windspeed_10m,relativehumidity_2m';

    #[Route('/api/meteo', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        [$lat, $lon] = $this->resolveCoords($request);

        $url = sprintf(
            'https://api.open-meteo.com/v1/forecast?latitude=%.6f&longitude=%.6f&current=%s',
            $lat, $lon, self::FIELDS
        );

        $ctx  = stream_context_create(['http' => ['timeout' => 5]]);
        $body = @file_get_contents($url, false, $ctx);

        if ($body === false) {
            return $this->json(['error' => 'Météo indisponible'], 503);
        }

        $data = json_decode($body, true);
        $cur  = $data['current'] ?? [];

        return $this->json([
            'temperature' => isset($cur['temperature_2m'])   ? round($cur['temperature_2m'], 1) : null,
            'weathercode' => $cur['weathercode']             ?? null,
            'windspeed'   => isset($cur['windspeed_10m'])    ? (int) round($cur['windspeed_10m']) : null,
            'humidity'    => $cur['relativehumidity_2m']     ?? null,
        ]);
    }

    private function resolveCoords(Request $request): array
    {
        $lat = filter_var($request->query->get('lat'), FILTER_VALIDATE_FLOAT);
        $lon = filter_var($request->query->get('lon'), FILTER_VALIDATE_FLOAT);

        if ($lat === false || $lon === false
            || $lat < -90  || $lat > 90
            || $lon < -180 || $lon > 180
        ) {
            return [self::DEFAULT_LAT, self::DEFAULT_LON];
        }

        return [$lat, $lon];
    }
}
