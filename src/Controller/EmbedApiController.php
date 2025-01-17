<?php

namespace App\Controller;

use App\Controller\Response\Specialist;
use App\Controller\Response\SpecialistSlots;
use App\Exception\JsonStringException;
use App\Repository\SpecialistRepository;
use App\Service\AccountManager;
use App\Service\JsonStringHandler;
use App\Service\SpecialistSchedule;
use Gaufrette\Extras\Resolvable\ResolvableFilesystem;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class EmbedApiController extends AbstractController
{
    public function __construct(
        private readonly AccountManager $accountManager,
        private readonly SpecialistRepository $specialistRepository,
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
        JsonStringHandler $jsonStringHandler,
        SpecialistSchedule $specialistSchedule,
    ): Response {
        try {
            $payload = $jsonStringHandler->handle($request, 'payload');
        } catch (JsonStringException $e) {
            return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        $currentDateString = $payload['current_date'] ?? null;
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

        $specialistId = \App\Entity\Specialist::getIdFromDictionaryElementCode($specialistCode);
        if (null === $specialistId) {
            throw $this->createNotFoundException();
        }

        $specialist = $this->specialistRepository->find($specialistId);
        if (null === $specialist) {
            throw $this->createNotFoundException();
        }

        return $this->json(new SpecialistSlots(
            $specialist,
            $specialistSchedule->getSpecialistSlots($specialist, $startDate, $endDate, new \DateTimeImmutable('now'))
        ));
    }

    #[Route(path: '/embed/api/specialists', name: 'embed_api_specialists', methods: ['POST'])]
    public function specialists(
        SpecialistSchedule $specialistSchedule,
        ResolvableFilesystem $fileSystem,
    ): JsonResponse {
        if (!$this->accountManager->hasAccount()) {
            throw $this->createNotFoundException();
        }

        $account = $this->accountManager->getAccount();

        $specialists = $this->specialistRepository->findByAccountOrderedByOrdering($account);
        $specialistSlots = $specialistSchedule->getNearestDaySchedule($specialists, new \DateTimeImmutable('now'));

        $availableSpecialists = [];
        foreach ($specialists as $specialist) {
            if (!isset($specialistSlots[(int) $specialist->getId()])) {
                continue;
            }

            $availableSpecialists[] = Specialist::fromEntity(
                $specialist,
                $specialistSlots[(int) $specialist->getId()],
                $specialist->getPhoto() ? $fileSystem->resolve($specialist->getPhoto()) : null
            );
        }

        return $this->json(['specialists' => $availableSpecialists]);
    }
}
