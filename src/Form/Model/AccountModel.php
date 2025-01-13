<?php

namespace App\Form\Model;

use Symfony\Component\Validator\Constraints as Assert;

final class AccountModel
{
    #[Assert\NotBlank]
    #[Assert\Url]
    public ?string $url = null;

    #[Assert\NotBlank]
    public ?string $apiKey = null;
}
