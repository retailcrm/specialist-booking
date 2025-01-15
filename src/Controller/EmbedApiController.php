<?php

namespace App\Controller;

use App\Controller\Response\Specialist;
use App\Controller\Response\SpecialistSlots;
use App\Repository\SpecialistRepository;
use App\Service\AccountManager;
use App\Service\SpecialistBusySlotFetcher;
use App\Service\SpecialistSchedule;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class EmbedApiController extends AbstractController
{
    public function __construct(
        private readonly AccountManager $accountManager,
        private readonly SpecialistRepository $specialistRepository,
        #[Autowire('%specialists_dir%')]
        private readonly string $specialistsUploadDir,
    ) {
    }

    #[Route(
        path: '/embed/api/specialists/{specialistCode}/slots',
        name: 'embed_api_specialist_slots',
        methods: ['POST']
    )]
    public function specialistSlots(
        string $specialistCode,
        Request $request,
    ): Response {
        $payloadString = $request->request->getString('payload');
        if (!$payloadString) {
            return new Response('Not defined payload', Response::HTTP_BAD_REQUEST);
        }

        $payload = json_decode($payloadString, true);
        if (false === $payload || !isset($payload['current_date'])) {
            return new Response(
                sprintf('Not found `current_date` in payload: %s', $payloadString),
                Response::HTTP_BAD_REQUEST
            );
        }

        $currentDateString = $payload['current_date'];
        if (!$currentDateString) {
            return new Response('Not defined currentDate', Response::HTTP_BAD_REQUEST);
        }

        $currentDate = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $currentDateString . ' 00:00:00');
        if (false === $currentDate) {
            return new Response('Invalid currentDate format', Response::HTTP_BAD_REQUEST);
        }

        $startDate = $currentDate->setDate((int) $currentDate->format('Y'), (int) $currentDate->format('m'), 1);
        $endDate = $startDate->modify('last day of this month');

        if (!$this->accountManager->hasAccount()) {
            throw $this->createNotFoundException();
        }
        $account = $this->accountManager->getAccount();

        $specialistId = \App\Entity\Specialist::getIdFromDictionaryElementCode($specialistCode);
        if (null === $specialistId) {
            throw $this->createNotFoundException();
        }

        $specialist = $this->specialistRepository->find($specialistId);
        if (null === $specialist) {
            throw $this->createNotFoundException();
        }

        $client = $this->accountManager->getClient();
        $busyFetcher = new SpecialistBusySlotFetcher($client);
        $specialistSchedule = new SpecialistSchedule($busyFetcher, new \DateTimeImmutable('now'));

        return $this->json(new SpecialistSlots(
            $specialist,
            $specialistSchedule->getSpecialistSlots($specialist, $startDate, $endDate)
        ));
    }

    #[Route(path: '/embed/api/specialists', name: 'embed_api_specialists', methods: ['POST'])]
    public function specialists(): JsonResponse
    {
        if (!$this->accountManager->hasAccount()) {
            throw $this->createNotFoundException();
        }

        $account = $this->accountManager->getAccount();
        $baseUrl = sprintf(
            '%s%s',
            rtrim($this->generateUrl('index', [], UrlGeneratorInterface::ABSOLUTE_URL), '/'),
            $this->specialistsUploadDir
        );

        $client = $this->accountManager->getClient();
        $busyFetcher = new SpecialistBusySlotFetcher($client);
        $specialistSchedule = new SpecialistSchedule($busyFetcher, new \DateTimeImmutable('now'));

        $specialists = $this->specialistRepository->findByAccountOrderedByOrdering($account);
        $specialistSlots = $specialistSchedule->getNearestDaySchedule($specialists);

        $availableSpecialists = [];
        foreach ($specialists as $specialist) {
            if (!isset($specialistSlots[(int) $specialist->getId()])) {
                continue;
            }

            $availableSpecialists[] = Specialist::fromEntity(
                $specialist,
                $specialistSlots[(int) $specialist->getId()],
                $baseUrl
            );
        }

        return $this->json(['specialists' => $availableSpecialists]);
    }
}
