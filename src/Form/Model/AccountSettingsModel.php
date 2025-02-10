<?php

namespace App\Form\Model;

use Symfony\Component\Validator\Constraints as Assert;

final class AccountSettingsModel
{
    #[Assert\NotBlank]
    #[Assert\Range(min: 15, max: 360)]
    public ?int $length = null;

    public bool $chooseStore = false;
    public bool $chooseCity = false;
}
