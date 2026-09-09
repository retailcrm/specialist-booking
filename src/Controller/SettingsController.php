<?php

namespace App\Controller;

use App\Service\AccountManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SettingsController extends AbstractController
{
    public function __construct(
        private readonly AccountManager $accountManager,
    ) {
    }

    #[Route(
        path: '/settings',
        name: 'account_settings_index',
        methods: ['GET', 'POST'],
    )]
    public function index(): Response
    {
        if (!$this->accountManager->hasAccount()) {
            throw $this->createNotFoundException();
        }

        $systemInfo = $this->accountManager->getClient()->api->systemInfo();

        return $this->render('account/index.html.twig', [
            'account' => $this->accountManager->getAccount(),
            'publicUrl' => $systemInfo->publicUrl,
        ]);
    }
}
