<?php

namespace App\Controller;

use App\Entity\Account;
use App\Form\Model\AccountModel;
use App\Form\Type\AccountType;
use App\Repository\AccountRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AccountController extends AbstractController
{
    #[Route(path: '/', name: 'index', methods: ['GET'])]
    public function index(): RedirectResponse
    {
        return $this->redirectToRoute('account_register');
    }

    #[Route(path: '/register', name: 'account_register', methods: ['GET', 'POST'])]
    public function register(Request $request, EntityManagerInterface $em): Response
    {
        $accountModel = new AccountModel();
        $form = $this->createForm(AccountType::class, $accountModel);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $account = new Account(
                $accountModel->url,
                $accountModel->apiKey
            );
            $em->persist($account);
            $em->flush();

            return $this->redirectToRoute('account_settings', ['clientId' => $account->getClientId()]);
        }

        return $this->render('account/register.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route(
        path: '/settings',
        name: 'account_settings',
        methods: ['GET', 'POST'],
    )]
    public function settings(Request $request, AccountRepository $repository): Response
    {
        $clientId = $request->query->getString('clientId', '') ?: $request->request->getString('clientId', '');
        if (!$clientId) {
            throw $this->createNotFoundException();
        }

        $account = $repository->getByClientId($clientId);
        if (!$account) {
            throw $this->createNotFoundException();
        }

        return $this->render('account/settings.html.twig', [
            'account' => $account,
        ]);
    }
}
