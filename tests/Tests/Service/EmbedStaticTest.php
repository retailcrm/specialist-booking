<?php

namespace App\Tests\Tests\Service;

use App\Service\EmbedStatic;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class EmbedStaticTest extends KernelTestCase
{
    public function testZipManifestContainsWorkerEntrypoint(): void
    {
        self::bootKernel();

        $embedStatic = self::getContainer()->get(EmbedStatic::class);
        $this->assertInstanceOf(EmbedStatic::class, $embedStatic);
        $manifest = $embedStatic->getJsModuleManifest(1010014)->jsonSerialize();

        $this->assertSame(EmbedStatic::TARGETS, $manifest['targets']);
        $this->assertNotSame('index.html', $manifest['entrypoint']);
        $this->assertStringEndsWith('.js', $manifest['entrypoint']);
        $this->assertStringEndsWith('.css', (string) $manifest['stylesheet']);
        $this->assertContains($manifest['entrypoint'], $manifest['scripts']);
    }
}
