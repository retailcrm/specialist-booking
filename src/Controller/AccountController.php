<?php

namespace App\Controller;

use App\Entity\Account;
use App\Form\Model\AccountModel;
use App\Form\Type\AccountType;
use App\Repository\AccountRepository;
use App\Service\AccountManager;
use App\Service\CustomFieldManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use RetailCrm\Api\Component\SimpleConnection\RequestVerifier;
use RetailCrm\Api\Interfaces\ApiExceptionInterface;
use RetailCrm\Api\Interfaces\ClientExceptionInterface;
use RetailCrm\Api\Model\Callback\Entity\Integration\IntegrationModule as CallbackIntegrationModule;
use RetailCrm\Api\Model\Callback\Entity\SimpleConnection\RequestProperty\RequestConnectionRegister;
use RetailCrm\Api\Model\Callback\Response\ErrorResponse;
use RetailCrm\Api\Model\Callback\Response\SimpleConnection\ConnectionConfigResponse;
use RetailCrm\Api\Model\Callback\Response\SimpleConnection\ConnectionRegisterResponse;
use RetailCrm\Api\Model\Entity\Integration\EmbedJs\EmbedJsConfiguration;
use RetailCrm\Api\Model\Entity\Integration\IntegrationModule;
use RetailCrm\Api\Model\Entity\Integration\Integrations;
use RetailCrm\Api\Model\Entity\Settings\Settings;
use RetailCrm\Api\Model\Request\Integration\IntegrationModulesEditRequest;
use RetailCrm\Api\Model\Response\SuccessResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\LocaleAwareInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class AccountController extends AbstractController
{
    public function __construct(
        private readonly AccountManager $accountManager,
        private readonly TranslatorInterface&LocaleAwareInterface $translator,
        private readonly CustomFieldManager $customFieldManager,
        private readonly LoggerInterface $logger,
    ) {
    }

    #[Route(path: '/', name: 'index', methods: ['GET'])]
    public function index(): RedirectResponse
    {
        return $this->redirectToRoute('account_register');
    }

    #[Route(path: '/callback/config', name: 'account_callback_config', methods: ['GET'])]
    public function simpleConfig(): JsonResponse
    {
        $response = new ConnectionConfigResponse();

        $response->success = true;
        $response->scopes = [
            'integration_read',
            'integration_write',
            'custom_fields_read',
            'custom_fields_write',
            'customer_read',
            'customer_write',
            'order_read',
            // на будущее
            'order_write',
            'user_read',
            'user_write',
            'reference_read',
            'reference_write',
        ];
        $response->registerUrl = $this->generateUrl(
            'account_callback_register',
            referenceType: UrlGeneratorInterface::ABSOLUTE_URL
        );

        return $this->json($response);
    }

    #[Route(path: '/callback/register', name: 'account_callback_register', methods: ['POST'])]
    public function simpleRegister(
        RequestVerifier $requestVerifier,
        RequestConnectionRegister $requestConnectionRegister,
        AccountRepository $accountRepository,
        EntityManagerInterface $em,
        #[Autowire('%env(APP_SECRET)%')]
        string $secret,
    ): Response {
        $verify = $requestVerifier->verify($secret, $requestConnectionRegister);
        if (false === $verify) {
            $this->logger->error(sprintf('Request verification failed: %s', json_encode($requestConnectionRegister)));

            return $this->json(new ErrorResponse(), Response::HTTP_BAD_REQUEST);
        }

        $account = $accountRepository->getByUrl($requestConnectionRegister->systemUrl);
        if (null !== $account) {
            $response = new ErrorResponse();
            $response->errorMsg = 'Account already exists';
            $this->logger->info(sprintf('%s: %s', $response->errorMsg, json_encode($requestConnectionRegister)));

            return $this->json($response, Response::HTTP_BAD_REQUEST);
        }

        $account = $this->registerAccount(
            $requestConnectionRegister->systemUrl,
            $requestConnectionRegister->apiKey,
            true,
        );
        if ($account instanceof Account) {
            $em->persist($account);
            $em->flush();

            $response = new ConnectionRegisterResponse();
            $response->success = true;
            $response->accountUrl = $this->generateUrl(
                'account_settings_index',
                referenceType: UrlGeneratorInterface::ABSOLUTE_URL
            );

            return $this->json($response);
        }

        $response = new ErrorResponse();
        $response->errorMsg = sprintf('Error of module registering: %s', $account->getMessage());
        $this->logger->error(sprintf('%s: %s', $response->errorMsg, json_encode($requestConnectionRegister)));

        return $this->json($response, Response::HTTP_BAD_REQUEST);
    }

    #[Route(path: '/register', name: 'account_register', methods: ['GET', 'POST'])]
    public function register(
        Request $request,
        EntityManagerInterface $em,
        AccountRepository $accountRepository,
    ): Response {
        $accountModel = new AccountModel();
        $form = $this->createForm(AccountType::class, $accountModel);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            assert(null !== $accountModel->url && null !== $accountModel->apiKey);

            $account = $accountRepository->getByUrl($accountModel->url);
            if (null === $account) {
                $account = $this->registerAccount($accountModel->url, $accountModel->apiKey);
                if ($account instanceof Account) {
                    $em->persist($account);
                    $em->flush();

                    return $this->redirectToRoute('account_settings_index', ['clientId' => $account->getClientId()]);
                }

                $form->addError(new FormError(sprintf('Error of module registering: %s', $account->getMessage())));
            } else {
                $form->addError(new FormError('Account already exists'));
            }
        }

        return $this->render('account/register.html.twig', [
            'form' => $form,
        ]);
    }

    private function registerAccount(string $url, string $apiKey, bool $isSimpleConnection = false): Account|\Throwable
    {
        $account = new Account($url, $apiKey);
        $account->setSimpleConnection($isSimpleConnection);
        $this->accountManager->setAccount($account);

        $client = $this->accountManager->getClient();

        // get locale
        try {
            $settingsFromCrm = $client->settings->get()->settings;
            $account->getSettings()->setFromCrmSettings($settingsFromCrm);
            $this->translator->setLocale($account->getSettings()->getRequiredLocale());
        } catch (ApiExceptionInterface|ClientExceptionInterface $e) {
            return $e;
        }

        // register module
        try {
            $client->integration->edit(
                $account->getClientId(),
                new IntegrationModulesEditRequest(
                    $this->getIntegrationModuleData($account)
                )
            );
        } catch (ApiExceptionInterface|ClientExceptionInterface $e) {
            return $e;
        }

        // create necessary custom fields
        try {
            $this->customFieldManager->ensureCustomFields($client);
        } catch (ApiExceptionInterface|ClientExceptionInterface $e) {
            return $e;
        }

        return $account;
    }

    private function getIntegrationModuleData(Account $account): IntegrationModule
    {
        $integrationModuleData = new IntegrationModule();
        $integrationModuleData->code = $account->getClientId();
        $integrationModuleData->integrationCode = Account::MODULE_CODE;
        $integrationModuleData->active = true;
        $integrationModuleData->name = $this->translator->trans('booking_name');
        $integrationModuleData->clientId = $account->getClientId();
        $integrationModuleData->baseUrl = $this->generateUrl(
            'index',
            referenceType: UrlGeneratorInterface::ABSOLUTE_URL
        );
        $integrationModuleData->logo = $integrationModuleData->baseUrl . 'logo.svg';
        $integrationModuleData->accountUrl = $this->generateUrl(
            'account_settings_index',
            referenceType: UrlGeneratorInterface::ABSOLUTE_URL
        );
        $integrationModuleData->actions = [
            'activity' => $this->generateUrl('account_callback_activity'),
            'settings' => $this->generateUrl('account_callback_settings'),
        ];

        if (!$account->isSimpleConnection()) {
            $embedJsConfiguration = new EmbedJsConfiguration();
            $embedJsConfiguration->entrypoint = EmbedStaticController::EMBED_JS_PATH . '/index.html';
            $embedJsConfiguration->stylesheet = $this->generateUrl('embed_static', ['path' => 'booking.css']);
            $embedJsConfiguration->targets = ['order/card:customer.after'];

            $integrations = new Integrations();
            $integrations->embedJs = $embedJsConfiguration;

            $integrationModuleData->integrations = $integrations;
        }

        return $integrationModuleData;
    }

    #[Route(
        path: '/callback/settings',
        name: 'account_callback_settings',
        methods: ['POST'],
    )]
    public function updateSettings(EntityManagerInterface $em, Settings $settings): Response
    {
        if (!$this->accountManager->hasAccount()) {
            throw $this->createNotFoundException();
        }

        $account = $this->accountManager->getAccount();
        $account->getSettings()->setFromCrmSettings($settings);
        $em->flush();

        return $this->json(new SuccessResponse());
    }

    #[Route(
        path: '/callback/activity',
        name: 'account_callback_activity',
        methods: ['POST'],
    )]
    public function updateActivity(
        Request $request,
        EntityManagerInterface $em,
        CallbackIntegrationModule $activity,
    ): Response {
        if (!$this->accountManager->hasAccount()) {
            throw $this->createNotFoundException();
        }
        $account = $this->accountManager->getAccount();

        $systemUrl = $request->request->getString('systemUrl');
        if ($systemUrl) {
            $account->setUrl($systemUrl);
        }
        if (null !== $activity->active) {
            $account->setActive((bool) $activity->active);
        }
        if (null !== $activity->freeze) {
            $account->setFrozen((bool) $activity->freeze);
        }

        $em->flush();

        return $this->json(new SuccessResponse());
    }

    #[Route(
        path: '/settings/for-developers',
        name: 'account_settings_for_developers',
        methods: ['GET', 'POST'],
    )]
    public function forDevelopers(Request $request): Response
    {
        if (!$this->accountManager->hasAccount()) {
            throw $this->createNotFoundException();
        }
        $account = $this->accountManager->getAccount();

        if ($request->isMethod('POST')) {
            $client = $this->accountManager->getClient();
            $client->integration->edit(
                $account->getClientId(),
                new IntegrationModulesEditRequest(
                    $this->getIntegrationModuleData($account)
                )
            );

            $this->addFlash('success', 'settings_updated');

            return $this->redirectToRoute('account_settings_for_developers');
        }

        return $this->render('account/developers.html.twig');
    }
}
