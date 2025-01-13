<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

class EmbedStaticController extends AbstractController
{
    public function __construct(
        #[Autowire('%kernel.project_dir%/public/embed/booking')]
        private readonly string $embedDir,
    ) {
    }

    #[Route(path: '/embed/booking/{path}', name: 'embed_static', methods: ['GET'])]
    public function index(string $path): BinaryFileResponse
    {
        $manifest = @file_get_contents($this->embedDir . '/manifest.json');
        if (false === $manifest) {
            throw $this->createNotFoundException();
        }

        try {
            $manifest = json_decode($manifest, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw $this->createNotFoundException('Invalid manifest file: ' . $e->getMessage());
        }

        if (!isset($manifest[$path])) {
            throw $this->createNotFoundException();
        }

        return $this->file(
            $this->embedDir . '/' . $manifest[$path],
            $path,
            ResponseHeaderBag::DISPOSITION_INLINE
        );
    }
}
