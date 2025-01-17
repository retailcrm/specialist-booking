<?php

namespace App\ArgumentResolver;

use App\Service\Serializer\Adapter;
use RetailCrm\Api\Model\Callback\Entity\Integration\IntegrationModule;
use RetailCrm\Api\Model\Callback\Entity\SimpleConnection\RequestProperty\RequestConnectionRegister;
use RetailCrm\Api\Model\Entity\Settings\Settings;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class CallbackValueResolver implements ValueResolverInterface
{
    private const array TYPES = [
        IntegrationModule::class,
        Settings::class,
        RequestConnectionRegister::class,
    ];

    private const array PARAMS = [
        'activity',
        'settings',
        'register',
    ];

    public function __construct(
        private ValidatorInterface $validator,
        private Adapter $serializer,
    ) {
    }

    private function validate(object $data): void
    {
        $errors = $this->validator->validate($data);

        if (0 !== count($errors)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid request parameter %s', $data::class)
            );
        }
    }

    public function supports(Request $request, ArgumentMetadata $argument): bool
    {
        if (Request::METHOD_POST !== $request->getMethod()) {
            return false;
        }

        return null !== $this->search($request, $argument);
    }

    /**
     * @return iterable<object>
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if (!$this->supports($request, $argument)) {
            return [];
        }

        $parameter = $this->search($request, $argument);
        if (null === $parameter) {
            return [];
        }

        $data = $this->serializer->deserialize(
            $request->request->getString($parameter),
            (string) $argument->getType()
        );
        $this->validate($data);

        return [$data];
    }

    private function search(Request $request, ArgumentMetadata $argument): ?string
    {
        foreach (self::TYPES as $typeCallback) {
            if ($argument->getType() !== $typeCallback) {
                continue;
            }

            foreach (self::PARAMS as $param) {
                if ($request->request->has($param)) {
                    return $param;
                }
            }
        }

        return null;
    }
}
