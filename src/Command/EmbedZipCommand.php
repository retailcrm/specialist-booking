<?php

namespace App\Command;

use App\Service\EmbedStatic;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;

#[AsCommand(
    name: 'app:embed:zip',
    description: 'Create js-module zip archive',
)]
class EmbedZipCommand extends Command
{
    public function __construct(
        private readonly EmbedStatic $embedStatic,
        private readonly Filesystem $filesystem,
        #[Autowire('%kernel.project_dir%/var')]
        private readonly string $varDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('version', InputArgument::OPTIONAL, 'Js module version');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $version = (int) $input->getArgument('version');
        if (!$version) {
            $io->error('Version should be a number');

            return Command::FAILURE;
        }

        $targetFile = $this->varDir . '/module.zip';

        if (!$this->filesystem->exists($targetFile)) {
            $this->filesystem->remove($targetFile);
        }

        $zip = new \ZipArchive();
        if (!$zip->open($targetFile, \ZipArchive::CREATE)) {
            $io->error('Failed to create zip archive');

            return Command::FAILURE;
        }

        foreach ($this->embedStatic->getManifest() as $file => $path) {
            $zip->addFile($this->embedStatic->getPath($file), mb_substr($path, 2));
        }

        $zip->addFromString(
            'manifest.json',
            json_encode($this->embedStatic->getJsModuleManifest($version), JSON_THROW_ON_ERROR)
        );
        $zip->close();

        $io->success('Module archive created successfully in ' . $targetFile);

        return Command::SUCCESS;
    }
}
