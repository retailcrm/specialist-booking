<?php

namespace App\Controller;

use App\Controller\Response\Specialist;
use App\Repository\SpecialistRepository;
use App\Service\ClientIdHandler;
use App\Service\SpecialistBusySlotFetcher;
use App\Service\SpecialistSchedule;
use RetailCrm\Api\Factory\SimpleClientFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class EmbedApiController extends AbstractController
{
    public function __construct(
        private readonly ClientIdHandler $clientIdHandler,
        private readonly SpecialistRepository $specialistRepository,
        #[Autowire('%specialists_dir%')]
        private readonly string $specialistsUploadDir,
    ) {
    }

    #[Route(path: '/embed/api/specialists', name: 'embed_api_specialists', methods: ['POST'])]
    public function specialists(Request $request): JsonResponse
    {
        $account = $this->clientIdHandler->getAccount($request, true);
        if (null === $account) {
            throw $this->createNotFoundException();
        }

        $baseUrl = sprintf(
            '%s%s',
            rtrim($this->generateUrl('index', [], UrlGeneratorInterface::ABSOLUTE_URL), '/'),
            $this->specialistsUploadDir
        );

        $client = SimpleClientFactory::createClient($account->getUrl(), $account->getApiKey());
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
