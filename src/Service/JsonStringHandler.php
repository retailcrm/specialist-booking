<?php

namespace App\Service;

use App\Exception\JsonStringException;
use Symfony\Component\HttpFoundation\Request;

final class JsonStringHandler
{
    /**
     * @return array<string, mixed>
     */
    public function handle(Request $request, string $key): array
    {
        $jsonString = $request->request->getString($key);
        if (!$jsonString) {
            throw new JsonStringException(sprintf('Not defined %s parameter', $key));
        }

        try {
            $jsonArray = json_decode($jsonString, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new JsonStringException(sprintf('Invalid json in %s parameter: %s', $key, $e->getMessage()));
        }

        return $jsonArray;
    }
}
