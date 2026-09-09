<?php

namespace App\Controller;

use App\Controller\Payload\AdminSettingsPayload;
use App\Controller\Response\AdminSettingsResponse;
use App\Service\AccountManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AdminSettingsController extends AdminApiController
{
    public function __construct(
        AccountManager $accountManager,
        ValidatorInterface $validator,
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct($accountManager, $validator);
    }

    #[Route(path: '/embed/api/admin/settings', name: 'embed_api_admin_settings', methods: ['GET', 'POST'])]
    public function __invoke(Request $request): Response
    {
        $account = $this->requireAccount();
        $payload = AdminSettingsPayload::fromArray($this->getPayload($request));

        if ($request->isMethod('POST') && $payload->hasSettings()) {
            if (null !== $response = $this->validatePayload($payload)) {
                return $response;
            }

            $account->getSettings()
                ->setSlotDuration($payload->getSlotDuration())
                ->setChooseStore($payload->chooseStore)
                ->setChooseCity($payload->chooseStore && $payload->chooseCity)
            ;
            $this->em->flush();
        }

        return $this->json(AdminSettingsResponse::fromAccount($account));
    }
}
