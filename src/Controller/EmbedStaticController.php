<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Profiler\Profiler;

class EmbedStaticController extends AbstractController
{
    public const string EMBED_JS_PATH = '/embed/booking';

    private readonly string $embedDir;

    public function __construct(
        #[Autowire('%kernel.project_dir%/public')]
        string $embedDir,
    ) {
        $this->embedDir = $embedDir . self::EMBED_JS_PATH;
    }

    public function staticFile(string $path, ?Profiler $profiler): BinaryFileResponse
    {
        if ($profiler) {
            $profiler->disable();
        }

        $manifest = $this->getManifest();
        if ($manifest instanceof NotFoundHttpException) {
            throw $manifest;
        }

        if (!isset($manifest[$path])) {
            throw $this->createNotFoundException();
        }

        $response = new BinaryFileResponse($this->embedDir . '/' . $manifest[$path]);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, $path);

        $contentType = match (pathinfo($path, PATHINFO_EXTENSION)) {
            'css' => 'text/css',
            'js' => 'application/javascript',
            default => null,
        };
        if (null !== $contentType) {
            $response->headers->set('Content-Type', $contentType);
        }

        return $response;
    }

    /**
     * @return array<string, string>|NotFoundHttpException
     */
    private function getManifest(): array|NotFoundHttpException
    {
        $manifest = @file_get_contents($this->embedDir . '/manifest.json');
        if (false === $manifest) {
            return $this->createNotFoundException();
        }

        try {
            $manifest = json_decode($manifest, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return $this->createNotFoundException('Invalid manifest file: ' . $e->getMessage());
        }

        return $manifest;
    }
}
