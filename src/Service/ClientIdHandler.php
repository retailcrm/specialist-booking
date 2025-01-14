<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Request;

final class ClientIdHandler
{
    private const string CLIENT_ID_KEY = 'current_client_id';

    public function handle(Request $request): string
    {
        $session = $request->getSession();

        $clientId = $request->query->getString('clientId') ?: $request->request->getString('clientId');
        if ('' !== $clientId) {
            $session->set(self::CLIENT_ID_KEY, $clientId);

            return $clientId;
        }

        return $session->get(self::CLIENT_ID_KEY, '');
    }
}
