<?php

namespace App\Service;

use App\Entity\Account;
use App\Exception\EmbedStaticException;
use App\Service\DTO\JsModuleManifest;
use RetailCrm\Api\Model\Entity\Integration\EmbedJs\EmbedJsPage;
use RetailCrm\Api\Model\Entity\Integration\EmbedJs\EmbedJsTranslation;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class EmbedStatic
{
    public const array TARGETS = ['order/card:customer.after'];
    public const string EMBED_JS_PATH = '/embed/booking';
    public const string ENTRY_NAME = 'booking';
    public const string RUNNER = 'worker';
    public const string SCRIPT_PATH = self::EMBED_JS_PATH . '/' . self::ENTRY_NAME . '.js';
    public const string STYLESHEET_PATH = self::EMBED_JS_PATH . '/' . self::ENTRY_NAME . '.css';
    public const string PAGE_CODE_ROOT = 'specialist-booking';
    public const string PAGE_CODE_CALENDAR = 'specialist-booking-calendar';
    public const string PAGE_CODE_SETTINGS = 'specialist-booking-settings';
    public const string PAGE_CODE_SPECIALTIES = 'specialist-booking-specialties';
    public const string PAGE_CODE_SPECIALISTS = 'specialist-booking-specialists';

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
            self::getPagesManifest(),
        );
    }

    /**
     * @return EmbedJsPage[]
     */
    public static function getPages(): array
    {
        return [
            self::createPage(
                self::PAGE_CODE_ROOT,
                200,
                self::translation('Booking to specialist', 'Reserva a especialistas', 'Запись к специалисту'),
                'private_main_menu',
            ),
            self::createPage(
                self::PAGE_CODE_SETTINGS,
                300,
                self::translation('Settings', 'Configuración', 'Настройки'),
                'private_main_menu',
                'page:' . self::PAGE_CODE_ROOT,
                true,
            ),
            self::createPage(
                self::PAGE_CODE_SPECIALTIES,
                200,
                self::translation('Specialties', 'Especialidades', 'Специализации'),
                'private_main_menu',
                'page:' . self::PAGE_CODE_ROOT,
            ),
            self::createPage(
                self::PAGE_CODE_SPECIALISTS,
                100,
                self::translation('Specialists', 'Especialistas', 'Специалисты'),
                'private_main_menu',
                'page:' . self::PAGE_CODE_ROOT,
            ),
            self::createPage(
                self::PAGE_CODE_CALENDAR,
                30,
                self::translation('Booking calendar', 'Calendario de citas', 'Календарь записей'),
                'activity_main_menu',
            ),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function getPagesManifest(): array
    {
        return array_map(
            static fn (EmbedJsPage $page): array => self::serializePage($page),
            self::getPages(),
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

    private static function createPage(
        string $code,
        int $ordering,
        EmbedJsTranslation $title,
        ?string $menu = null,
        ?string $parentMenuItemCode = null,
        bool $isSettingsMainPage = false,
    ): EmbedJsPage {
        $page = new EmbedJsPage();
        $page->code = $code;
        $page->menu = $menu;
        $page->parentMenuItemCode = $parentMenuItemCode;
        $page->menuItemOrdering = $ordering;
        $page->menuItemTitle = $title;
        $page->isSettingsMainPage = $isSettingsMainPage;

        return $page;
    }

    private static function translation(string $en, string $es, string $ru): EmbedJsTranslation
    {
        $translation = new EmbedJsTranslation();
        $translation->en = $en;
        $translation->es = $es;
        $translation->ru = $ru;

        return $translation;
    }

    /**
     * @return array<string, mixed>
     */
    private static function serializePage(EmbedJsPage $page): array
    {
        $result = ['code' => $page->code];

        foreach (['menu', 'parentMenuItemCode', 'menuItemOrdering'] as $property) {
            if (null !== $page->{$property}) {
                $result[$property] = $page->{$property};
            }
        }

        if ($page->menuItemTitle instanceof EmbedJsTranslation) {
            $result['menuItemTitle'] = self::serializeTranslation($page->menuItemTitle);
        }

        if (true === $page->isSettingsMainPage) {
            $result['isSettingsMainPage'] = true;
        }

        return $result;
    }

    /**
     * @return array{en?: string, es?: string, ru?: string}
     */
    private static function serializeTranslation(EmbedJsTranslation $translation): array
    {
        return array_filter([
            'en' => $translation->en,
            'es' => $translation->es,
            'ru' => $translation->ru,
        ], static fn (?string $value): bool => null !== $value);
    }
}
