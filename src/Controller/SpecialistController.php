<?php

namespace App\Controller;

use App\Entity\Specialist;
use App\Form\Model\SpecialistCollectionModel;
use App\Form\Model\SpecialistModel;
use App\Form\Type\SpecialistCollectionType;
use App\Repository\SpecialistRepository;
use App\Service\AccountManager;
use App\Service\CustomFieldManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class SpecialistController extends AbstractController
{
    public function __construct(
        private readonly AccountManager $accountManager,
        private readonly EntityManagerInterface $em,
        private readonly SpecialistRepository $specialistRepository,
        private readonly SluggerInterface $slugger,
        #[Autowire('%kernel.project_dir%/public%specialists_dir%')]
        private readonly string $specialistsUploadDir,
    ) {
    }

    #[Route(path: '/settings/specialists', name: 'specialist_index', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        CustomFieldManager $customFieldManager,
    ): Response {
        if (!$this->accountManager->hasAccount()) {
            throw $this->createNotFoundException();
        }

        $account = $this->accountManager->getAccount();
        $specialists = $this->specialistRepository->findByAccountOrderedByOrdering($account);
        $collectionModel = new SpecialistCollectionModel($specialists);

        $form = $this->createForm(SpecialistCollectionType::class, $collectionModel);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle removed specialists
            $existingIds = $collectionModel
                ->getSpecialists()
                ->map(fn (SpecialistModel $model): ?int => $model->id)
                ->filter(fn (?int $id): bool => null !== $id)
                ->toArray()
            ;

            foreach ($specialists as $specialist) {
                if (!in_array($specialist->getId(), $existingIds, true)) {
                    $account->removeSpecialist($specialist);
                    $this->em->remove($specialist);
                }
            }

            // Handle new and updated specialists
            foreach ($collectionModel->getSpecialists() as $specialistModel) {
                $specialist = null;
                if ($specialistModel->id) {
                    foreach ($specialists as $existingSpecialist) {
                        if ($existingSpecialist->getId() === $specialistModel->id) {
                            $specialist = $existingSpecialist;
                            break;
                        }
                    }
                }

                if (!$specialist) {
                    $specialist = new Specialist($specialistModel->name);
                    $account->addSpecialist($specialist);

                    $this->em->persist($specialist);
                } else {
                    $specialist->setName($specialistModel->name);
                }

                $specialist->setPosition($specialistModel->position);
                $specialist->setOrdering($specialistModel->ordering);

                if ($specialistModel->photoFile) {
                    $originalFilename = pathinfo($specialistModel->photoFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $this->slugger->slug($originalFilename);
                    $newFilename = $safeFilename . '-' . uniqid('', false) . '.' . $specialistModel->photoFile->guessExtension();

                    try {
                        $specialistModel->photoFile->move($this->specialistsUploadDir, $newFilename);
                        $specialist->setPhoto($newFilename);
                    } catch (\Exception) {
                        // Handle the exception
                    }
                }
            }

            $this->em->flush();

            // sync with custom fields
            $client = $this->accountManager->getClient();
            $specialists = $this->specialistRepository->findByAccountOrderedByOrdering($account);
            $customFieldManager->ensureCustomFields($client, $specialists);

            return $this->redirectToRoute('specialist_index');
        }

        return $this->render('specialist/index.html.twig', [
            'form' => $form,
        ]);
    }
}
