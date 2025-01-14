<?php

namespace App\Controller;

use App\Controller\Response\Specialist;
use App\Entity\Specialist as SpecialistEntity;
use App\Repository\SpecialistRepository;
use App\Service\ClientIdHandler;
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

        $specialists = $this->specialistRepository->findByAccountOrderedByOrdering($account);
        $list = array_map(
            static fn (SpecialistEntity $specialist): Specialist => Specialist::fromEntity($specialist, $baseUrl),
            $specialists
        );

        // @TODO fill slots

        return $this->json(['specialists' => $list]);
    }
}
