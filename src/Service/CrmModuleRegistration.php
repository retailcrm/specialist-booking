<?php

namespace App\Service;

use App\Entity\Account;
use RetailCrm\Api\Model\Entity\Integration\EmbedJs\EmbedJsConfiguration;
use RetailCrm\Api\Model\Entity\Integration\IntegrationModule;
use RetailCrm\Api\Model\Entity\Integration\Integrations;
use RetailCrm\Api\Model\Request\Integration\IntegrationModulesEditRequest;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Описание интеграционного модуля для CRM: адреса, действия и фронтовая
 * часть (виджет и страницы). Отправляется при регистрации аккаунта и при
 * пересинхронизации уже подключённых — CRM узнаёт о новых страницах меню
 * только из этого описания.
 */
final readonly class CrmModuleRegistration
{
    public function __construct(
        private RouterInterface $router,
        private TranslatorInterface $translator,
        private AccountManager $accountManager,
        // код маркетплейс-модуля несёт JS из биллинга, и CRM запрещает ему
        // встроенную JS-конфигурацию; локальный экземпляр регистрируется
        // под своим кодом интеграции
        #[Autowire('%env(default::MODULE_INTEGRATION_CODE)%')]
        private ?string $integrationCode = null,
    ) {
    }

    /**
     * @param ?string $baseUrl публичный адрес модуля; без него берётся адрес текущего запроса
     */
    public function build(Account $account, ?string $baseUrl = null): IntegrationModule
    {
        $context = $this->router->getContext();
        $saved = clone $context;

        if (null !== $baseUrl) {
            $parts = parse_url($baseUrl);
            $context->setScheme($parts['scheme'] ?? 'https');
            $context->setHost($parts['host'] ?? $baseUrl);
            $context->setHttpPort((int) ($parts['port'] ?? 80));
            $context->setHttpsPort((int) ($parts['port'] ?? 443));
            $context->setBaseUrl(rtrim($parts['path'] ?? '', '/'));
        }

        try {
            return $this->buildWithContext($account);
        } finally {
            $this->router->setContext($saved);
        }
    }

    public function sync(Account $account, ?string $baseUrl = null): void
    {
        $this->accountManager->setAccount($account);
        $this->register($this->build($account, $baseUrl));
    }

    /** Код подключения в адресе запроса — тот же, что в описании модуля. */
    public function register(IntegrationModule $module): void
    {
        $this->accountManager->getClient()->integration->edit(
            (string) $module->code,
            new IntegrationModulesEditRequest($module),
        );
    }

    private function buildWithContext(Account $account): IntegrationModule
    {
        $integrationCode = $this->integrationCode ?: Account::MODULE_CODE;

        $module = new IntegrationModule();
        // код подключения обязан начинаться с кода интеграции; clientId
        // остаётся прежним — по нему модуль узнаёт аккаунт в запросах CRM
        $module->code = $integrationCode . substr($account->getClientId(), strlen(Account::MODULE_CODE));
        $module->integrationCode = $integrationCode;
        $module->active = true;
        $module->name = $this->translator->trans('booking_name', locale: $account->getSettings()->getRequiredLocale());
        $module->clientId = $account->getClientId();
        $module->baseUrl = $this->router->generate('index', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $module->logo = $module->baseUrl . 'logo.svg';
        $module->accountUrl = $this->router->generate('account_settings_index', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $module->actions = [
            'activity' => $this->router->generate('account_callback_activity'),
            'settings' => $this->router->generate('account_callback_settings'),
        ];

        if (!$account->isSimpleConnection()) {
            $embedJs = new EmbedJsConfiguration();
            $embedJs->entrypoint = EmbedStatic::SCRIPT_PATH;
            $embedJs->stylesheet = EmbedStatic::STYLESHEET_PATH;
            $embedJs->targets = EmbedStatic::TARGETS;
            $embedJs->runner = EmbedStatic::RUNNER;
            $embedJs->pages = EmbedStatic::getPages();

            $integrations = new Integrations();
            $integrations->embedJs = $embedJs;
            $module->integrations = $integrations;
        }

        return $module;
    }
}
