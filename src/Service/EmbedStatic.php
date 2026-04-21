<?php

namespace App\Service;

use App\Entity\Account;
use App\Exception\EmbedStaticException;
use App\Service\DTO\JsModuleManifest;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class EmbedStatic
{
    public const array TARGETS = ['order/card:customer.after'];
    public const string EMBED_JS_PATH = '/embed/booking';
    public const string ENTRY_NAME = 'booking';
    public const string RUNNER = 'worker';
    public const string SCRIPT_PATH = self::EMBED_JS_PATH . '/' . self::ENTRY_NAME . '.js';
    public const string STYLESHEET_PATH = self::EMBED_JS_PATH . '/' . self::ENTRY_NAME . '.css';

    private readonly string $embedDir;
    /** @var ?array<string, mixed> */
    private ?array $manifest = null;

    public function __construct(
        #[Autowire('%kernel.project_dir%/public')]
        string $embedDir,
    ) {
        $this->embedDir = $embedDir . self::EMBED_JS_PATH;
    }

    public function getPath(string $path): string
    {
        $manifest = $this->getManifest();
        if (!isset($manifest[$path])) {
            throw new EmbedStaticException(sprintf('File %s not found in manifest', $path));
        }

        return $this->embedDir . '/' . $manifest[$path];
    }

    public function getJsModuleManifest(int $version): JsModuleManifest
    {
        $manifest = $this->getManifest();
        $entrypoint = $manifest[self::ENTRY_NAME . '.js'] ?? null;
        if (null === $entrypoint) {
            throw new EmbedStaticException(sprintf('Manifest does not contain %s', self::ENTRY_NAME . '.js'));
        }
        $entrypoint = $this->normalizePath($entrypoint);

        $scripts = [];
        $stylesheet = null;
        foreach ($this->getManifest() as $file => $path) {
            if (str_ends_with($file, '.js')) {
                $scripts[] = $this->normalizePath($path);
            } elseif (str_ends_with($file, '.css')) {
                $stylesheet = $this->normalizePath($path);
            }
        }

        return new JsModuleManifest(
            Account::MODULE_CODE,
            (string) $version,
            self::RUNNER,
            self::TARGETS,
            $entrypoint,
            $scripts,
            $stylesheet,
        );
    }

    /**
     * @return array<string, string>
     */
    public function getManifest(): array
    {
        if (null === $this->manifest) {
            $manifest = @file_get_contents($this->embedDir . '/manifest.json');
            if (false === $manifest) {
                throw new EmbedStaticException('Manifest file not found');
            }

            try {
                $this->manifest = json_decode($manifest, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                throw new EmbedStaticException('Invalid manifest file: ' . $e->getMessage());
            }
        }

        return $this->manifest;
    }

    private function normalizePath(string $path): string
    {
        $result = preg_replace('#^\./#', '', $path);
        if (null === $result) {
            throw new EmbedStaticException(sprintf('Invalid path: %s', $path));
        }

        return $result;
    }
}
