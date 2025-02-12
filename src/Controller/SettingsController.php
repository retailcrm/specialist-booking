<?php

namespace App\Controller;

use App\Form\Model\AccountSettingsModel;
use App\Form\Type\AccountSettingsType;
use App\Service\AccountManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
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

    #[Route(
        path: '/settings/details',
        name: 'account_settings_settings',
        methods: ['GET', 'POST'],
    )]
    public function settings(Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->accountManager->hasAccount()) {
            throw $this->createNotFoundException();
        }

        $account = $this->accountManager->getAccount();
        $systemInfo = $this->accountManager->getClient()->api->systemInfo();

        $accountSettingsModel = new AccountSettingsModel();
        $accountSettingsModel->length = $account->getSettings()->getSlotDuration();
        $accountSettingsModel->chooseStore = $account->getSettings()->chooseStore();
        $accountSettingsModel->chooseCity = $account->getSettings()->chooseCity();

        $accountSettingsForm = $this->createForm(AccountSettingsType::class, $accountSettingsModel, [
            'public_url' => $systemInfo->publicUrl,
        ]);

        $accountSettingsForm->handleRequest($request);
        if ($accountSettingsForm->isSubmitted() && $accountSettingsForm->isValid()) {
            if (!$accountSettingsModel->chooseStore) {
                $accountSettingsModel->chooseCity = false;
            }

            $account->getSettings()
                ->setSlotDuration($accountSettingsModel->length)
                ->setChooseStore($accountSettingsModel->chooseStore)
                ->setChooseCity($accountSettingsModel->chooseCity)
            ;
            $em->flush();

            return $this->redirectToRoute('account_settings_settings');
        }

        return $this->render('account/settings.html.twig', [
            'account' => $account,
            'publicUrl' => $systemInfo->publicUrl,
            'settingsForm' => $accountSettingsForm,
        ]);
    }
}
