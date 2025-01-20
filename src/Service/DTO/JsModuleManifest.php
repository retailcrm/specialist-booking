<?php

namespace App\Service\DTO;

final readonly class JsModuleManifest implements \JsonSerializable
{
    /**
     * @param string[] $targets
     * @param string[] $scripts
     */
    public function __construct(
        private string $code,
        private string $version,
        private array $targets,
        private string $entrypoint,
        private array $scripts,
        private ?string $stylesheet = null,
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
            'targets' => $this->targets,
            'entrypoint' => $this->entrypoint,
            'scripts' => $this->scripts,
        ];

        if (null !== $this->stylesheet) {
            $result['stylesheet'] = $this->stylesheet;
        }

        return $result;
    }
}
