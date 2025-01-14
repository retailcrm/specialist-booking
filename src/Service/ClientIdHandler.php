<?php

namespace App\Service;

use App\Entity\Account;
use App\Repository\AccountRepository;
use Symfony\Component\HttpFoundation\Request;

final readonly class ClientIdHandler
{
    private const string CLIENT_ID_KEY = 'current_client_id';

    public function __construct(
        private AccountRepository $accountRepository,
    ) {
    }

    public function handle(Request $request, bool $onlyFromRequest = false): string
    {
        $session = $request->getSession();

        $clientId = $request->query->getString('clientId') ?: $request->request->getString('clientId');

        if ($onlyFromRequest) {
            return $clientId;
        }

        if ('' !== $clientId) {
            $session->set(self::CLIENT_ID_KEY, $clientId);

            return $clientId;
        }

        return (string) $session->get(self::CLIENT_ID_KEY, '');
    }

    public function getAccount(Request $request, bool $onlyFromRequest = false): ?Account
    {
        $clientId = $this->handle($request, $onlyFromRequest);
        if ('' === $clientId) {
            return null;
        }

        return $this->accountRepository->getByClientId($clientId);
    }
}
