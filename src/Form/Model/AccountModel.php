<?php

namespace App\Form\Model;

use App\Validator\CrmAccess;
use Symfony\Component\Validator\Constraints as Assert;

#[CrmAccess]
final class AccountModel
{
    #[Assert\NotBlank]
    #[Assert\Url]
    public ?string $url = null;

    #[Assert\NotBlank]
    public ?string $apiKey = null;
}
