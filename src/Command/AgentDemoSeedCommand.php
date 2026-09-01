<?php

namespace App\Command;

use App\Entity\Account;
use App\Entity\Specialist;
use App\Entity\Specialty;
use App\Repository\AccountRepository;
use App\Service\AccountManager;
use App\Service\CustomFieldManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Dev-посев для прототипа работы с AI-агентом: аккаунт + специальности +
 * специалисты + синхронизация кастомных полей и словаря в CRM.
 * Повторный запуск обновляет словарь, не плодя дубли.
 */
#[AsCommand(name: 'app:agent-demo-seed', description: 'Seed demo account and specialists for agent prototype')]
class AgentDemoSeedCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly AccountRepository $accountRepository,
        private readonly AccountManager $accountManager,
        private readonly CustomFieldManager $customFieldManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('crmUrl', InputArgument::REQUIRED)
            ->addArgument('apiKey', InputArgument::REQUIRED)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $crmUrl = trim((string) $input->getArgument('crmUrl'), '/');
        $apiKey = (string) $input->getArgument('apiKey');

        $account = $this->accountRepository->getByUrl($crmUrl);
        if (null === $account) {
            $account = new Account($crmUrl, $apiKey);
            $this->em->persist($account);
        } else {
            $account->setApiKey($apiKey);
        }

        $this->accountManager->setAccount($account);
        $client = $this->accountManager->getClient();

        // локаль и расписание из CRM
        $account->getSettings()->setFromCrmSettings($client->settings->get()->settings);

        $specialtiesData = ['Ветеринар', 'Грумер'];
        $specialistsData = [
            ['Мария Иванова', 'Ветеринар', 1],
            ['Пётр Сидоров', 'Ветеринар', 2],
            ['Анна Козлова', 'Грумер', 3],
        ];

        $specialties = [];
        foreach ($specialtiesData as $name) {
            $specialty = $this->em->getRepository(Specialty::class)
                ->findOneBy(['name' => $name, 'account' => $account])
            ;
            if (null === $specialty) {
                $specialty = new Specialty($name);
                $specialty->setAccount($account);
                $this->em->persist($specialty);
            }
            $specialties[$name] = $specialty;
        }

        foreach ($specialistsData as [$name, $specialtyName, $ordering]) {
            $specialist = $this->em->getRepository(Specialist::class)
                ->findOneBy(['name' => $name, 'account' => $account])
            ;
            if (null === $specialist) {
                $specialist = new Specialist($name);
                $specialist->setAccount($account);
                $this->em->persist($specialist);
            }
            $specialist
                ->setSpecialty($specialties[$specialtyName])
                ->setOrdering($ordering)
            ;
        }

        $this->em->flush();

        // кастомные поля заказа и словарь специалистов в CRM
        $specialists = $this->em->getRepository(Specialist::class)
            ->findBy(['account' => $account])
        ;
        $this->customFieldManager->ensureCustomFields($client, $specialists);

        $output->writeln(sprintf(
            'Seeded: account clientId=%s, specialists=%d',
            $account->getClientId(),
            count($specialists),
        ));

        return Command::SUCCESS;
    }
}
