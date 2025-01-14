<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class CrmAccess extends Constraint
{
    public string $message = 'Invalid URL or API key';

    public function __construct(?string $message = null, ?array $groups = null, mixed $payload = null)
    {
        $this->message = $message ?? $this->message;

        parent::__construct([], $groups, $payload);
    }

    #[\Override]
    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
