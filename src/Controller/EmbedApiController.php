<?php

namespace App\Controller;

use App\Controller\Response\Branch;
use App\Controller\Response\City;
use App\Controller\Response\Settings;
use App\Controller\Response\Specialist;
use App\Controller\Response\SpecialistSlots;
use App\Exception\JsonStringException;
use App\Repository\SpecialistRepository;
use App\Service\AccountManager;
use App\Service\JsonStringHandler;
use App\Service\SpecialistBusySlotFetcherInterface;
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
        Request $request,
        JsonStringHandler $jsonStringHandler,
        SpecialistSchedule $specialistSchedule,
        ResolvableFilesystem $fileSystem,
    ): Response {
        $payload = null;
        if ($request->request->has('payload')) {
            try {
                $payload = $jsonStringHandler->handle($request, 'payload');
            } catch (JsonStringException $e) {
                return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
            }
        }

        $branchCode = $payload['branch_code'] ?? null;

        if (!$this->accountManager->hasAccount()) {
            throw $this->createNotFoundException();
        }

        $account = $this->accountManager->getAccount();

        $specialists = $this->specialistRepository->findByAccountOrderedByOrdering($account, $branchCode);
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

    #[Route(path: '/embed/api/branches', name: 'embed_api_branches', methods: ['POST'])]
    public function branches(
        Request $request,
        JsonStringHandler $jsonStringHandler,
        SpecialistBusySlotFetcherInterface $specialistBusySlotFetcher,
    ): Response {
        $payload = null;
        if ($request->request->has('payload')) {
            try {
                $payload = $jsonStringHandler->handle($request, 'payload');
            } catch (JsonStringException $e) {
                return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
            }
        }

        $city = $payload['city'] ?? null;

        if (!$this->accountManager->hasAccount()) {
            throw $this->createNotFoundException();
        }

        $account = $this->accountManager->getAccount();

        $storeCodes = $this->specialistRepository->getStoreCodes($account);
        /** @var Branch[] $branches */
        $branches = [];
        foreach ($specialistBusySlotFetcher->getStores() as $store) {
            /* @phpstan-ignore-next-line nullsafe.neverNull */
            if ($city && $store->address?->city !== $city) {
                continue;
            }

            $storeCode = null;
            foreach ($storeCodes as $item) {
                if ($item['code'] === $store->code) {
                    $storeCode = $item;
                    break;
                }
            }

            if (null === $storeCode) {
                continue;
            }

            $branches[] = new Branch($store->name, $storeCode['code'], $storeCode['cnt']);
        }

        usort($branches, fn (Branch $a, Branch $b) => $a->getName() <=> $b->getName());

        return $this->json(['branches' => $branches]);
    }

    #[Route(path: '/embed/api/cities', name: 'embed_api_cities', methods: ['POST'])]
    public function cities(SpecialistBusySlotFetcherInterface $specialistBusySlotFetcher): JsonResponse
    {
        if (!$this->accountManager->hasAccount()) {
            throw $this->createNotFoundException();
        }

        $account = $this->accountManager->getAccount();

        /** @var City[] $cities */
        $cities = [];
        $storeCodes = array_column($this->specialistRepository->getStoreCodes($account), 'code');
        foreach ($specialistBusySlotFetcher->getStores() as $store) {
            /* @phpstan-ignore-next-line nullCoalesce.expr */
            $city = trim($store->address?->city ?? '');
            if (!$city) {
                continue;
            }

            if (!in_array($store->code, $storeCodes, true)) {
                continue;
            }

            if (!isset($cities[$city])) {
                $cities[$city] = new City($city);
            } else {
                $cities[$city]->incrementBranchCount();
            }
        }

        usort($cities, fn (City $a, City $b) => -1 * ($a->getBranchCount() <=> $b->getBranchCount()));

        return $this->json(['cities' => $cities]);
    }

    #[Route(path: '/embed/api/settings', name: 'embed_api_settings', methods: ['POST'])]
    public function settings(): JsonResponse
    {
        if (!$this->accountManager->hasAccount()) {
            throw $this->createNotFoundException();
        }

        $account = $this->accountManager->getAccount();

        return $this->json(['settings' => new Settings(
            $account->getSettings()->chooseStore(),
            $account->getSettings()->chooseCity(),
        )]);
    }
}
