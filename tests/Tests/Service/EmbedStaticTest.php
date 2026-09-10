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
        $this->assertSame(EmbedStatic::getPagesManifest(), $manifest['pages']);
        $this->assertSame('specialist-booking', $manifest['pages'][0]['code']);
        $this->assertSame('private_main_menu', $manifest['pages'][0]['menu']);
        $this->assertSame(200, $manifest['pages'][0]['menuItemOrdering']);
        $this->assertSame('specialist-booking-settings', $manifest['pages'][1]['code']);
        $this->assertTrue($manifest['pages'][1]['isSettingsMainPage']);

        foreach ($manifest['pages'] as $page) {
            // календарь — рабочий экран в продажах, остальные страницы в настройках
            $expectedMenu = EmbedStatic::PAGE_CODE_CALENDAR === $page['code'] ? 'activity_main_menu' : 'private_main_menu';
            $this->assertSame($expectedMenu, $page['menu']);
        }

        $calendar = array_values(array_filter($manifest['pages'], static fn (array $page): bool => EmbedStatic::PAGE_CODE_CALENDAR === $page['code']));
        $this->assertArrayNotHasKey('parentMenuItemCode', $calendar[0]);
    }
}
