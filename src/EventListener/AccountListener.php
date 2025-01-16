<?php

namespace App\EventListener;

use App\Repository\AccountRepository;
use App\Service\AccountManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final readonly class AccountListener implements EventSubscriberInterface
{
    private const string CLIENT_ID_KEY = 'current_client_id';

    public function __construct(
        private AccountManager $accountManager,
        private AccountRepository $accountRepository,
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        $clientId = $this->getClientId($request);
        if (!$clientId) {
            return;
        }

        $account = $this->accountRepository->getByClientId($clientId);
        if (null === $account) {
            return;
        }

        $this->accountManager->setAccount($account);
        $request->setLocale($account->getSettings()->getRequiredLocale());
    }

    private function getClientId(Request $request): string
    {
        $session = $request->getSession();
        $clientId = $request->query->getString('clientId') ?: $request->request->getString('clientId');
        if ('' !== $clientId) {
            $session->set(self::CLIENT_ID_KEY, $clientId);

            return $clientId;
        }

        return (string) $session->get(self::CLIENT_ID_KEY, '');
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [['onKernelRequest', 20]],
        ];
    }
}
