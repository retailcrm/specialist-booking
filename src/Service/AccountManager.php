<?php

namespace App\Service;

use App\Entity\Account;
use RetailCrm\Api\Client;
use RetailCrm\Api\Factory\SimpleClientFactory;

final class AccountManager
{
    private ?Account $account = null;
    private ?Client $client = null;

    public function hasAccount(): bool
    {
        return null !== $this->account;
    }

    public function getAccount(): Account
    {
        if (null === $this->account) {
            throw new \LogicException('Account is not set');
        }

        return $this->account;
    }

    public function getClient(): Client
    {
        if (null === $this->account) {
            throw new \LogicException('Account is not set');
        }

        if (null === $this->client) {
            $this->client = SimpleClientFactory::createClient(
                $this->account->getUrl(),
                $this->account->getApiKey()
            );
        }

        return $this->client;
    }

    public function setAccount(Account $account): void
    {
        $this->account = $account;
    }
}
