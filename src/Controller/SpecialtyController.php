<?php

namespace App\Controller;

use App\Entity\Specialty;
use App\Form\Model\SpecialtyCollectionModel;
use App\Form\Type\SpecialtyCollectionType;
use App\Repository\SpecialtyRepository;
use App\Service\AccountManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SpecialtyController extends AbstractController
{
    public function __construct(
        private readonly AccountManager $accountManager,
        private readonly EntityManagerInterface $em,
        private readonly SpecialtyRepository $specialtyRepository,
    ) {
    }

    #[Route(path: '/settings/specialties', name: 'specialty_index', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        if (!$this->accountManager->hasAccount()) {
            throw $this->createNotFoundException();
        }

        $account = $this->accountManager->getAccount();
        $specialties = $this->specialtyRepository
            ->findByAccountOrderingByNameQueryBuilder($account)
            ->getQuery()
            ->getResult()
        ;
        $collectionModel = new SpecialtyCollectionModel($specialties);

        $form = $this->createForm(SpecialtyCollectionType::class, $collectionModel);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Remove deleted specialties
            foreach ($specialties as $specialty) {
                $found = false;
                foreach ($collectionModel->specialties as $specialtyModel) {
                    if ($specialtyModel->name === $specialty->getName()) {
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $this->em->remove($specialty);
                }
            }

            // Add or update specialties
            foreach ($collectionModel->specialties as $specialtyModel) {
                $specialty = null;
                foreach ($specialties as $existingSpecialty) {
                    if ($existingSpecialty->getName() === $specialtyModel->name) {
                        $specialty = $existingSpecialty;
                        break;
                    }
                }

                if (!$specialty) {
                    $specialty = new Specialty($specialtyModel->name);
                    $specialty->setAccount($account);
                    $this->em->persist($specialty);
                } else {
                    $specialtyModel->updateEntity($specialty);
                }
            }

            $this->em->flush();

            return $this->redirectToRoute('specialty_index');
        }

        return $this->render('specialty/index.html.twig', [
            'form' => $form,
        ]);
    }
}
