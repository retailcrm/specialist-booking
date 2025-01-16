<?php

namespace App\Controller;

use App\Entity\Account;
use App\Form\Model\AccountModel;
use App\Form\Type\AccountType;
use App\Service\AccountManager;
use App\Service\CustomFieldManager;
use Doctrine\ORM\EntityManagerInterface;
use RetailCrm\Api\Interfaces\ApiExceptionInterface;
use RetailCrm\Api\Interfaces\ClientExceptionInterface;
use RetailCrm\Api\Model\Entity\Integration\EmbedJs\EmbedJsConfiguration;
use RetailCrm\Api\Model\Entity\Integration\IntegrationModule;
use RetailCrm\Api\Model\Entity\Integration\Integrations;
use RetailCrm\Api\Model\Request\Integration\IntegrationModulesEditRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class AccountController extends AbstractController
{
    public function __construct(
        private readonly AccountManager $accountManager,
        private readonly TranslatorInterface $translator,
    ) {
    }

    #[Route(path: '/', name: 'index', methods: ['GET'])]
    public function index(): RedirectResponse
    {
        return $this->redirectToRoute('account_register');
    }

    #[Route(path: '/register', name: 'account_register', methods: ['GET', 'POST'])]
    public function register(
        Request $request,
        EntityManagerInterface $em,
        CustomFieldManager $customFieldManager,
    ): Response {
        $accountModel = new AccountModel();
        $form = $this->createForm(AccountType::class, $accountModel);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            assert(null !== $accountModel->url && null !== $accountModel->apiKey);

            $account = new Account($accountModel->url, $accountModel->apiKey);
            $this->accountManager->setAccount($account);
            $client = $this->accountManager->getClient();

            // get locale
            try {
                $settingsFromCrm = $client->settings->get()->settings;
                $account->getSettings()->setFromCrmSettings($settingsFromCrm);
            } catch (ApiExceptionInterface|ClientExceptionInterface $e) {
                $form->addError(new FormError(sprintf('Error of module registering: %s', $e->getMessage())));
            }

            if ($form->isValid()) {
                // register module
                try {
                    $client->integration->edit(
                        $account->getClientId(),
                        new IntegrationModulesEditRequest(
                            $this->getIntegrationModuleData($account)
                        )
                    );
                } catch (ApiExceptionInterface|ClientExceptionInterface $e) {
                    $form->addError(new FormError(sprintf('Error of module registering: %s', $e->getMessage())));
                }
            }

            if ($form->isValid()) {
                // create necessary custom fields
                try {
                    $customFieldManager->ensureCustomFields($client);
                } catch (ApiExceptionInterface|ClientExceptionInterface $e) {
                    $form->addError(new FormError(sprintf('Error of module registering: %s', $e->getMessage())));
                }
            }

            if ($form->isValid()) {
                $em->persist($account);
                $em->flush();

                return $this->redirectToRoute('account_settings', ['clientId' => $account->getClientId()]);
            }
        }

        return $this->render('account/register.html.twig', [
            'form' => $form,
        ]);
    }

    private function getIntegrationModuleData(Account $account): IntegrationModule
    {
        $embedJsConfiguration = new EmbedJsConfiguration();
        $embedJsConfiguration->entrypoint = EmbedStaticController::EMBED_JS_PATH . '/index.html';
        $embedJsConfiguration->stylesheet = $this->generateUrl('embed_static', ['path' => 'booking.css']);
        $embedJsConfiguration->targets = ['order/card:customer.after'];

        $integrations = new Integrations();
        $integrations->embedJs = $embedJsConfiguration;

        $integrationModuleData = new IntegrationModule();
        $integrationModuleData->code = Account::MODULE_CODE;
        $integrationModuleData->integrationCode = $account->getClientId();
        $integrationModuleData->active = true;
        $integrationModuleData->name = $this->translator->trans('booking_name');
        $integrationModuleData->clientId = $account->getClientId();
        $integrationModuleData->baseUrl = $this->generateUrl(
            'index',
            referenceType: UrlGeneratorInterface::ABSOLUTE_URL
        );
        $integrationModuleData->logo = $integrationModuleData->baseUrl . 'logo.svg';
        $integrationModuleData->accountUrl = $this->generateUrl(
            'account_settings',
            referenceType: UrlGeneratorInterface::ABSOLUTE_URL
        );
        $integrationModuleData->integrations = $integrations;

        return $integrationModuleData;
    }

    #[Route(
        path: '/settings',
        name: 'account_settings',
        methods: ['GET', 'POST'],
    )]
    public function settings(): Response
    {
        if (!$this->accountManager->hasAccount()) {
            throw $this->createNotFoundException();
        }

        return $this->render('account/settings.html.twig', [
            'account' => $this->accountManager->getAccount(),
        ]);
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
