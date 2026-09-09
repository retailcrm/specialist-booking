<?php

namespace App\Service\DTO;

final readonly class JsModuleManifest implements \JsonSerializable
{
    /**
     * @param string[]                         $targets
     * @param string[]                         $scripts
     * @param array<int, array<string, mixed>> $pages
     */
    public function __construct(
        private string $code,
        private string $version,
        private string $runner,
        private array $targets,
        private string $entrypoint,
        private array $scripts,
        private ?string $stylesheet = null,
        private array $pages = [],
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        $result = [
            'code' => $this->code,
            'version' => $this->version,
            'runner' => $this->runner,
            'targets' => $this->targets,
            'entrypoint' => $this->entrypoint,
            'scripts' => $this->scripts,
        ];

        if (null !== $this->stylesheet) {
            $result['stylesheet'] = $this->stylesheet;
        }

        if ([] !== $this->pages) {
            $result['pages'] = $this->pages;
        }

        return $result;
    }
}
