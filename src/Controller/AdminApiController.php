<?php

namespace App\Controller;

use App\Controller\Response\ApiError;
use App\Controller\Response\ApiFieldError;
use App\Entity\Account;
use App\Service\AccountManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class AdminApiController extends AbstractController
{
    public function __construct(
        protected readonly AccountManager $accountManager,
        protected readonly ValidatorInterface $validator,
    ) {
    }

    protected function requireAccount(): Account
    {
        if (!$this->accountManager->hasAccount()) {
            throw $this->createNotFoundException();
        }

        return $this->accountManager->getAccount();
    }

    /**
     * @return array<string, mixed>
     */
    protected function getPayload(Request $request): array
    {
        if ($request->request->has('payload')) {
            $payload = $request->request->get('payload');
            if (!is_string($payload)) {
                return [];
            }

            try {
                $decodedPayload = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                return [];
            }

            return is_array($decodedPayload) ? $this->withoutTransportFields($decodedPayload) : [];
        }

        $content = $request->getContent();
        if ('' !== $content) {
            try {
                $payload = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                return [];
            }

            return is_array($payload) ? $this->withoutTransportFields($payload) : [];
        }

        return $this->withoutTransportFields($request->request->all());
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    private function withoutTransportFields(array $payload): array
    {
        unset($payload['clientId']);

        return $payload;
    }

    protected function validatePayload(object $payload): ?JsonResponse
    {
        $errors = $this->validator->validate($payload);
        if (0 === $errors->count()) {
            return null;
        }

        return $this->json(
            new ApiError('Validation failed.', $this->formatValidationErrors($errors)),
            Response::HTTP_BAD_REQUEST,
        );
    }

    protected function badRequest(string $error): JsonResponse
    {
        return $this->error($error, Response::HTTP_BAD_REQUEST);
    }

    protected function error(string $error, int $status): JsonResponse
    {
        return $this->json(new ApiError($error), $status);
    }

    protected function fieldError(string $path, string $message, int $status = Response::HTTP_BAD_REQUEST): JsonResponse
    {
        return $this->json(new ApiError('Validation failed.', [new ApiFieldError($path, $message)]), $status);
    }

    /**
     * @return ApiFieldError[]
     */
    private function formatValidationErrors(ConstraintViolationListInterface $errors): array
    {
        $result = [];
        foreach ($errors as $error) {
            $result[] = new ApiFieldError($error->getPropertyPath(), (string) $error->getMessage());
        }

        return $result;
    }
}
