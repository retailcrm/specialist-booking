<?php

namespace App\Tests\Tests\Controller;

use App\Entity\Account;
use App\Entity\Specialist;
use App\Entity\Specialty;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class EmbedApiControllerTest extends WebTestCase
{
    private const string CLIENT_ID = '111_222';

    private Account $account;

    protected function setUp(): void
    {
        static::createClient();

        $em = $this->getEntityManager();
        $em->getConnection()->executeStatement('DELETE FROM specialist');
        $em->getConnection()->executeStatement('DELETE FROM specialty');
        $em->getConnection()->executeStatement('DELETE FROM account');

        $this->account = new Account('https://aa.ru', self::CLIENT_ID);
        $this->account->setClientId(self::CLIENT_ID);

        $em->persist($this->account);
        $em->flush();
    }

    public function testSettingsNotValidClientId(): void
    {
        $client = self::getClient();
        $this->assertInstanceOf(AbstractBrowser::class, $client);

        $client->request(
            'POST',
            '/embed/api/settings',
            ['clientId' => self::CLIENT_ID . '__'],
        );

        self::assertResponseStatusCodeSame(404);

        $client->request(
            'POST',
            '/embed/api/settings',
        );

        self::assertResponseStatusCodeSame(404);
    }

    public function testSettings(): void
    {
        $client = self::getClient();
        $this->assertInstanceOf(AbstractBrowser::class, $client);

        $client->request(
            'POST',
            '/embed/api/settings',
            ['clientId' => self::CLIENT_ID],
        );

        self::assertResponseIsSuccessful();
        $response = json_decode((string) $this->getResponse($client)->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals(
            [
                'settings' => [
                    'chooseStore' => false,
                    'chooseCity' => false,
                ],
            ],
            $response
        );
    }

    public function testCitiesNotValidClientId(): void
    {
        $client = self::getClient();
        $this->assertInstanceOf(AbstractBrowser::class, $client);

        $client->request(
            'POST',
            '/embed/api/cities',
            ['clientId' => self::CLIENT_ID . '__'],
        );

        self::assertResponseStatusCodeSame(404);

        $client->request(
            'POST',
            '/embed/api/cities',
        );

        self::assertResponseStatusCodeSame(404);
    }

    public function testCities(): void
    {
        $client = self::getClient();
        $this->assertInstanceOf(AbstractBrowser::class, $client);

        $client->request(
            'POST',
            '/embed/api/cities',
            ['clientId' => self::CLIENT_ID],
        );

        self::assertResponseIsSuccessful();
        $response = json_decode((string) $this->getResponse($client)->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals(['cities' => []], $response);

        $this->loadSpecialists();

        $client->request(
            'POST',
            '/embed/api/cities',
            ['clientId' => self::CLIENT_ID],
        );

        self::assertResponseIsSuccessful();
        $response = json_decode((string) $this->getResponse($client)->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals(
            ['cities' => [
                ['name' => 'Moscow', 'branchCount' => 2],
                ['name' => 'Tula', 'branchCount' => 1],
            ]],
            $response
        );
    }

    public function testBranchesNotValidClientId(): void
    {
        $client = self::getClient();
        $this->assertInstanceOf(AbstractBrowser::class, $client);

        $client->request(
            'POST',
            '/embed/api/branches',
            ['clientId' => self::CLIENT_ID . '__', 'payload' => '{}'],
        );

        self::assertResponseStatusCodeSame(404);

        $client->request(
            'POST',
            '/embed/api/branches',
            ['payload' => '{}'],
        );

        self::assertResponseStatusCodeSame(404);
    }

    public function testBranches(): void
    {
        $client = self::getClient();
        $this->assertInstanceOf(AbstractBrowser::class, $client);

        $client->request(
            'POST',
            '/embed/api/branches',
            ['clientId' => self::CLIENT_ID, 'payload' => '{}'],
        );

        self::assertResponseIsSuccessful();
        $response = json_decode((string) $this->getResponse($client)->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals(['branches' => []], $response);

        $this->loadSpecialists();

        $client->request(
            'POST',
            '/embed/api/branches',
            ['clientId' => self::CLIENT_ID, 'payload' => '{}'],
        );

        self::assertResponseIsSuccessful();
        $response = json_decode((string) $this->getResponse($client)->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals(
            ['branches' => [
                ['name' => 'Store 1', 'code' => 'store1', 'specialistCount' => 2],
                ['name' => 'Store 2', 'code' => 'store2', 'specialistCount' => 1],
                ['name' => 'Store 3', 'code' => 'store3', 'specialistCount' => 1],
                ['name' => 'Store 4', 'code' => 'store4', 'specialistCount' => 1],
            ]],
            $response
        );

        $client->request(
            'POST',
            '/embed/api/branches',
            ['clientId' => self::CLIENT_ID, 'payload' => json_encode(['city' => 'Moscow'], JSON_THROW_ON_ERROR)],
        );

        self::assertResponseIsSuccessful();
        $response = json_decode((string) $this->getResponse($client)->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals(
            ['branches' => [
                ['name' => 'Store 1', 'code' => 'store1', 'specialistCount' => 2],
                ['name' => 'Store 2', 'code' => 'store2', 'specialistCount' => 1],
            ]],
            $response
        );
    }

    private function loadSpecialists(): void
    {
        $em = $this->getEntityManager();

        $specialty1 = new Specialty('Specialty');
        $specialty1->setAccount($this->account);
        $em->persist($specialty1);

        $specialist1 = new Specialist('Specialist1');
        $specialist1
            ->setAccount($this->account)
            ->setSpecialty($specialty1)
            ->setStoreCode('store1')
        ;
        $em->persist($specialist1);

        $specialist2 = new Specialist('Specialist2');
        $specialist2
            ->setAccount($this->account)
            ->setSpecialty($specialty1)
            ->setStoreCode('store1')
        ;
        $em->persist($specialist2);

        $specialist3 = new Specialist('Specialist3');
        $specialist3
            ->setAccount($this->account)
            ->setStoreCode('store2')
        ;
        $em->persist($specialist3);

        $specialist4 = new Specialist('Specialist4');
        $specialist4
            ->setAccount($this->account)
            ->setStoreCode('store3')
        ;
        $em->persist($specialist4);

        $specialist5 = new Specialist('Specialist5');
        $specialist5
            ->setAccount($this->account)
            ->setStoreCode('store_some')
        ;
        $em->persist($specialist5);

        $specialist6 = new Specialist('Specialist6');
        $specialist6
            ->setAccount($this->account)
            ->setStoreCode('store4')
        ;
        $em->persist($specialist6);

        $em->flush();
    }

    private function getEntityManager(): EntityManagerInterface
    {
        $doctrine = self::getContainer()->get('doctrine');
        $this->assertInstanceOf(ManagerRegistry::class, $doctrine);

        $em = $doctrine->getManager();
        $this->assertInstanceOf(EntityManagerInterface::class, $em);

        return $em;
    }

    /**
     * @param AbstractBrowser<Request, Response> $client
     */
    private function getResponse(AbstractBrowser $client): Response
    {
        $response = $client->getResponse();
        $this->assertInstanceOf(Response::class, $response);

        return $response;
    }
}
