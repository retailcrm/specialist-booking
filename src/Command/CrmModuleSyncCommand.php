<?php

namespace App\Command;

use App\Repository\AccountRepository;
use App\Service\CrmModuleRegistration;
use RetailCrm\Api\Interfaces\ApiExceptionInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:crm-module-sync',
    description: 'Переотправить описание модуля (виджет, страницы меню) в CRM подключённого аккаунта',
)]
class CrmModuleSyncCommand extends Command
{
    public function __construct(
        private readonly AccountRepository $accountRepository,
        private readonly CrmModuleRegistration $registration,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('clientId', InputArgument::OPTIONAL, 'clientId аккаунта; без него — все активные')
            ->addOption('base-url', null, InputOption::VALUE_REQUIRED, 'Публичный адрес модуля, например https://booking.example.com/')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $baseUrl = $input->getOption('base-url');
        $baseUrl = is_string($baseUrl) ? rtrim($baseUrl, '/') . '/' : null;

        $clientId = $input->getArgument('clientId');
        if (is_string($clientId)) {
            $account = $this->accountRepository->getByClientId($clientId);
            if (null === $account) {
                $io->error(sprintf('Account "%s" not found', $clientId));

                return Command::FAILURE;
            }
            $accounts = [$account];
        } else {
            $accounts = $this->accountRepository->findBy(['active' => true]);
        }

        $failed = 0;
        foreach ($accounts as $account) {
            try {
                $this->registration->sync($account, $baseUrl);
                $io->writeln(sprintf('<info>%s</info> %s', $account->getClientId(), $account->getUrl()));
            } catch (\Throwable $e) {
                ++$failed;
                $details = $e instanceof ApiExceptionInterface ? $e->getErrorResponse()->errors : [];
                $io->writeln(sprintf(
                    '<error>%s</error> %s: %s %s',
                    $account->getClientId(),
                    $account->getUrl(),
                    $e->getMessage(),
                    [] === $details ? '' : json_encode($details, JSON_UNESCAPED_UNICODE),
                ));
            }
        }

        return 0 === $failed ? Command::SUCCESS : Command::FAILURE;
    }
}
