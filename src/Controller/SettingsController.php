<?php

namespace App\Controller;

use App\Form\Model\TimeSlotModel;
use App\Form\Type\TimeSlotType;
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
    public function settings(): Response
    {
        if (!$this->accountManager->hasAccount()) {
            throw $this->createNotFoundException();
        }

        $systemInfo = $this->accountManager->getClient()->api->systemInfo();

        return $this->render('account/settings.html.twig', [
            'account' => $this->accountManager->getAccount(),
            'publicUrl' => $systemInfo->publicUrl,
        ]);
    }

    #[Route(
        path: '/settings/working-time',
        name: 'account_settings_working_time',
        methods: ['GET', 'POST'],
    )]
    public function workingTime(Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->accountManager->hasAccount()) {
            throw $this->createNotFoundException();
        }
        $account = $this->accountManager->getAccount();

        $timeSlotModel = new TimeSlotModel();
        $timeSlotModel->length = $account->getSettings()->getSlotDuration();
        $timeSlotForm = $this->createForm(TimeSlotType::class, $timeSlotModel);

        $timeSlotForm->handleRequest($request);
        if ($timeSlotForm->isSubmitted() && $timeSlotForm->isValid()) {
            $account->getSettings()->setSlotDuration($timeSlotModel->length);
            $em->flush();

            return $this->redirectToRoute('account_settings_working_time');
        }

        $systemInfo = $this->accountManager->getClient()->api->systemInfo();

        return $this->render('account/workingTime.html.twig', [
            'account' => $account,
            'publicUrl' => $systemInfo->publicUrl,
            'timeSlotForm' => $timeSlotForm,
        ]);
    }
}
