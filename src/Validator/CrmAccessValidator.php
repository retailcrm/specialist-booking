<?php

namespace App\Validator;

use App\Form\Model\AccountModel;
use RetailCrm\Api\Factory\SimpleClientFactory;
use RetailCrm\Api\Interfaces\ApiExceptionInterface;
use RetailCrm\Api\Interfaces\ClientExceptionInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class CrmAccessValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof CrmAccess) {
            throw new UnexpectedTypeException($constraint, CrmAccess::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!$value instanceof AccountModel) {
            throw new UnexpectedTypeException($value, AccountModel::class);
        }

        if (null === $value->url || null === $value->apiKey) {
            $this->context
                ->buildViolation($constraint->message)
                ->addViolation()
            ;

            return;
        }

        $client = SimpleClientFactory::createClient($value->url, $value->apiKey);
        try {
            $client->api->credentials();
        } catch (ApiExceptionInterface|ClientExceptionInterface) {
            $this->context
                ->buildViolation($constraint->message)
                ->addViolation()
            ;
        }
    }
}
