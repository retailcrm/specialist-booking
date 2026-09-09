<?php

namespace App\Controller;

use App\Controller\Payload\DeletePayload;
use App\Controller\Payload\SpecialistPayload;
use App\Controller\Response\AdminSpecialistsResponse;
use App\Controller\Response\AdminStore;
use App\Entity\Specialist;
use App\Repository\SpecialistRepository;
use App\Repository\SpecialtyRepository;
use App\Service\AccountManager;
use App\Service\CustomFieldManager;
use App\Service\SpecialistBusySlotFetcherInterface;
use Doctrine\ORM\EntityManagerInterface;
use Gaufrette\Extras\Resolvable\ResolvableFilesystem;
use RetailCrm\Api\Interfaces\ApiExceptionInterface;
use RetailCrm\Api\Interfaces\ClientExceptionInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AdminSpecialistController extends AdminApiController
{
    public function __construct(
        AccountManager $accountManager,
        ValidatorInterface $validator,
        private readonly SpecialistRepository $specialistRepository,
        private readonly SpecialtyRepository $specialtyRepository,
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct($accountManager, $validator);
    }

    #[Route(path: '/embed/api/admin/specialists', name: 'embed_api_admin_specialists', methods: ['GET', 'POST'])]
    public function list(
        SpecialistBusySlotFetcherInterface $specialistBusySlotFetcher,
        ResolvableFilesystem $fileSystem,
    ): Response {
        $this->requireAccount();

        return $this->json($this->getSpecialistsResponse($specialistBusySlotFetcher, $fileSystem));
    }

    #[Route(path: '/embed/api/admin/specialists/save', name: 'embed_api_admin_specialists_save', methods: ['POST'])]
    public function save(
        Request $request,
        CustomFieldManager $customFieldManager,
        SpecialistBusySlotFetcherInterface $specialistBusySlotFetcher,
        ResolvableFilesystem $fileSystem,
    ): Response {
        $account = $this->requireAccount();
        $payload = SpecialistPayload::fromArray($this->getPayload($request));

        if (null !== $response = $this->validatePayload($payload)) {
            return $response;
        }

        $specialist = null;
        if (null !== $payload->id) {
            $specialist = $this->specialistRepository->find($payload->id);
            if (null === $specialist || $specialist->getAccount() !== $account) {
                throw $this->createNotFoundException();
            }
        }

        if (null === $specialist) {
            $specialist = new Specialist($payload->getName());
            $account->addSpecialist($specialist);
            $this->em->persist($specialist);
        } else {
            $specialist->setName($payload->getName());
        }

        $specialty = null;
        if (null !== $payload->specialtyId) {
            $specialty = $this->specialtyRepository->findOneByIdAndAccount($payload->specialtyId, $account);
            if (null === $specialty) {
                return $this->fieldError('specialtyId', 'Specialty was not found.');
            }
        }

        $storeCode = null;
        if ($account->getSettings()->chooseStore()) {
            $storeCode = $payload->storeCode;
            try {
                $storeExists = null === $storeCode || $this->hasStoreCode($specialistBusySlotFetcher, $storeCode);
            } catch (ApiExceptionInterface|ClientExceptionInterface) {
                return $this->error('Branches could not be loaded.', Response::HTTP_BAD_GATEWAY);
            }

            if (!$storeExists) {
                return $this->fieldError('storeCode', 'Branch was not found.');
            }
        }

        $specialist->setSpecialty($specialty);
        $specialist->setOrdering($payload->ordering);
        $specialist->setStoreCode($storeCode);
        $specialist->setWorkTimes($payload->workTimes);
        $specialist->setNonWorkingDays($payload->nonWorkingDays);

        if ($payload->removePhoto) {
            $specialist->setPhoto(null);
        }

        if ($payload->photoUrlProvided) {
            $specialist->setPhoto($payload->photoUrl);
        }

        $this->em->flush();

        if (null !== $response = $this->syncSpecialists($customFieldManager)) {
            return $response;
        }

        return $this->json($this->getSpecialistsResponse($specialistBusySlotFetcher, $fileSystem));
    }

    #[Route(path: '/embed/api/admin/specialists/delete', name: 'embed_api_admin_specialists_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        CustomFieldManager $customFieldManager,
        SpecialistBusySlotFetcherInterface $specialistBusySlotFetcher,
        ResolvableFilesystem $fileSystem,
    ): Response {
        $account = $this->requireAccount();
        $payload = DeletePayload::fromArray($this->getPayload($request));

        if (null !== $response = $this->validatePayload($payload)) {
            return $response;
        }

        $specialist = $this->specialistRepository->find($payload->getId());
        if (null === $specialist || $specialist->getAccount() !== $account) {
            throw $this->createNotFoundException();
        }

        $account->removeSpecialist($specialist);
        $this->em->remove($specialist);
        $this->em->flush();

        if (null !== $response = $this->syncSpecialists($customFieldManager)) {
            return $response;
        }

        return $this->json($this->getSpecialistsResponse($specialistBusySlotFetcher, $fileSystem));
    }

    private function getSpecialistsResponse(
        SpecialistBusySlotFetcherInterface $specialistBusySlotFetcher,
        ResolvableFilesystem $fileSystem,
    ): AdminSpecialistsResponse {
        $account = $this->accountManager->getAccount();

        return new AdminSpecialistsResponse(
            $this->specialistRepository->findByAccountOrderedByOrdering($account),
            $this->specialtyRepository->findByAccountOrderingByName($account),
            $account->getSettings()->chooseStore() ? $this->getStoresPayload($specialistBusySlotFetcher) : [],
            $account->getSettings()->chooseStore(),
            $fileSystem,
        );
    }

    /**
     * @return AdminStore[]
     */
    private function getStoresPayload(SpecialistBusySlotFetcherInterface $specialistBusySlotFetcher): array
    {
        $stores = [];
        foreach ($specialistBusySlotFetcher->getStores() as $store) {
            $city = null !== $store->address ? $store->address->city : null;
            $stores[] = new AdminStore(
                $store->code,
                $store->name . ($city ? ' (' . $city . ')' : ''),
            );
        }

        usort($stores, static fn (AdminStore $a, AdminStore $b): int => $a->name <=> $b->name);

        return $stores;
    }

    private function hasStoreCode(
        SpecialistBusySlotFetcherInterface $specialistBusySlotFetcher,
        string $storeCode,
    ): bool {
        foreach ($specialistBusySlotFetcher->getStores() as $store) {
            if ($store->code === $storeCode) {
                return true;
            }
        }

        return false;
    }

    private function syncSpecialists(CustomFieldManager $customFieldManager): ?Response
    {
        try {
            $customFieldManager->ensureCustomFields(
                $this->accountManager->getClient(),
                $this->specialistRepository->findByAccountOrderedByOrdering($this->accountManager->getAccount()),
            );
        } catch (ApiExceptionInterface|ClientExceptionInterface) {
            return $this->error(
                'Specialist was saved, but CRM custom fields were not synchronized.',
                Response::HTTP_BAD_GATEWAY,
            );
        }

        return null;
    }
}
