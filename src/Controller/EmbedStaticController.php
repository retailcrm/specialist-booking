<?php

namespace App\Controller;

use App\Service\EmbedStatic;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Profiler\Profiler;

class EmbedStaticController extends AbstractController
{
    public function __construct(
        private readonly EmbedStatic $embedStatic,
    ) {
    }

    public function staticFile(string $path, ?Profiler $profiler): BinaryFileResponse
    {
        if ($profiler) {
            $profiler->disable();
        }

        $response = new BinaryFileResponse($this->embedStatic->getPath($path));
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
}
