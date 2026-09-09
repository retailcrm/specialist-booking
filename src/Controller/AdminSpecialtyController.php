<?php

namespace App\Controller;

use App\Controller\Payload\DeletePayload;
use App\Controller\Payload\SpecialtyPayload;
use App\Controller\Response\AdminSpecialtiesResponse;
use App\Entity\Specialty;
use App\Repository\SpecialistRepository;
use App\Repository\SpecialtyRepository;
use App\Service\AccountManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AdminSpecialtyController extends AdminApiController
{
    public function __construct(
        AccountManager $accountManager,
        ValidatorInterface $validator,
        private readonly SpecialtyRepository $specialtyRepository,
        private readonly SpecialistRepository $specialistRepository,
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct($accountManager, $validator);
    }

    #[Route(path: '/embed/api/admin/specialties', name: 'embed_api_admin_specialties', methods: ['GET', 'POST'])]
    public function list(): Response
    {
        $this->requireAccount();

        return $this->json($this->getSpecialtiesResponse());
    }

    #[Route(path: '/embed/api/admin/specialties/save', name: 'embed_api_admin_specialties_save', methods: ['POST'])]
    public function save(Request $request): Response
    {
        $account = $this->requireAccount();
        $payload = SpecialtyPayload::fromArray($this->getPayload($request));

        if (null !== $response = $this->validatePayload($payload)) {
            return $response;
        }

        $specialty = null;
        if (null !== $payload->id) {
            $specialty = $this->specialtyRepository->find($payload->id);
            if (null === $specialty || $specialty->getAccount() !== $account) {
                throw $this->createNotFoundException();
            }
        }

        if (null === $specialty) {
            $specialty = new Specialty($payload->getName());
            $specialty->setAccount($account);
            $this->em->persist($specialty);
        } else {
            $specialty->setName($payload->getName());
        }

        $this->em->flush();

        return $this->json($this->getSpecialtiesResponse());
    }

    #[Route(path: '/embed/api/admin/specialties/delete', name: 'embed_api_admin_specialties_delete', methods: ['POST'])]
    public function delete(Request $request): Response
    {
        $account = $this->requireAccount();
        $payload = DeletePayload::fromArray($this->getPayload($request));

        if (null !== $response = $this->validatePayload($payload)) {
            return $response;
        }

        $specialty = $this->specialtyRepository->find($payload->getId());
        if (null === $specialty || $specialty->getAccount() !== $account) {
            throw $this->createNotFoundException();
        }

        foreach ($this->specialistRepository->findBy(['specialty' => $specialty]) as $specialist) {
            $specialist->setSpecialty(null);
        }

        $this->em->remove($specialty);
        $this->em->flush();

        return $this->json($this->getSpecialtiesResponse());
    }

    private function getSpecialtiesResponse(): AdminSpecialtiesResponse
    {
        $account = $this->accountManager->getAccount();

        return AdminSpecialtiesResponse::fromSpecialties($this->specialtyRepository->findByAccountOrderingByName($account));
    }
}
