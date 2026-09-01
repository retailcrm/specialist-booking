<?php

namespace App\Tests\Tests\Controller;

use App\Entity\Account;
use App\Entity\Specialist;
use App\Entity\Specialty;
use App\Tests\Mock\Service\OrderBookingWriter as MockOrderBookingWriter;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AgentApiControllerTest extends WebTestCase
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

    public function testNotFoundClientId(): void
    {
        $client = self::getClient();
        $this->assertInstanceOf(AbstractBrowser::class, $client);

        $client->request(
            'POST',
            '/api/specialties?clientId=' . self::CLIENT_ID . '__',
        );
        self::assertResponseStatusCodeSame(404);
    }

    public function testSpecialties(): void
    {
        $this->loadSpecialists();

        $client = self::getClient();
        $this->assertInstanceOf(AbstractBrowser::class, $client);

        $client->request(
            'POST',
            '/api/specialties?clientId=' . self::CLIENT_ID,
        );

        self::assertResponseIsSuccessful();
        $response = json_decode((string) $this->getResponse($client)->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals(
            ['specialties' => [
                ['name' => 'Barber', 'specialistCount' => 2],
                ['name' => 'Stylist', 'specialistCount' => 1],
            ]],
            $response
        );
    }

    public function testSpecialistsFilteredBySpecialty(): void
    {
        $this->loadSpecialists();

        $client = self::getClient();
        $this->assertInstanceOf(AbstractBrowser::class, $client);

        $client->request(
            'POST',
            '/api/specialists?clientId=' . self::CLIENT_ID,
            content: json_encode(['specialty' => 'barber'], JSON_THROW_ON_ERROR),
        );

        self::assertResponseIsSuccessful();
        $response = json_decode((string) $this->getResponse($client)->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $names = array_column($response['specialists'], 'name');
        $this->assertEquals(['Specialist1', 'Specialist2'], $names);

        foreach ($response['specialists'] as $specialist) {
            $this->assertEquals('Barber', $specialist['specialty']);
            // рабочие дни в моке есть каждую неделю — ближайший свободный день должен найтись
            $this->assertNotNull($specialist['nearest_slots']);
            $this->assertNotEmpty($specialist['nearest_slots']['slots']);
        }
    }

    public function testSlotsForDateRange(): void
    {
        $this->loadSpecialists();

        $client = self::getClient();
        $this->assertInstanceOf(AbstractBrowser::class, $client);

        $specialistId = $this->getSpecialistCode('Specialist1');

        // понедельник в будущем; мок нерабочих дней содержит только 17 января
        $monday = new \DateTimeImmutable('next monday');
        if ('01.17' === $monday->format('m.d')) {
            $monday = $monday->modify('+7 days');
        }

        $client->request(
            'POST',
            sprintf('/api/specialists/%s/slots?clientId=%s', $specialistId, self::CLIENT_ID),
            content: json_encode([
                'date_from' => $monday->format('Y-m-d'),
                'date_to' => $monday->format('Y-m-d'),
            ], JSON_THROW_ON_ERROR),
        );

        self::assertResponseIsSuccessful();
        $response = json_decode((string) $this->getResponse($client)->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertEquals($specialistId, $response['specialist_id']);
        // понедельник в моке: 09:00-13:00 и 14:00-17:00, слот 60 минут
        $this->assertEquals(
            [$monday->format('Y-m-d') => ['09:00', '10:00', '11:00', '12:00', '14:00', '15:00', '16:00']],
            $response['slots']
        );
    }

    public function testSlotsInvalidRange(): void
    {
        $this->loadSpecialists();

        $client = self::getClient();
        $this->assertInstanceOf(AbstractBrowser::class, $client);

        $specialistId = $this->getSpecialistCode('Specialist1');

        $client->request(
            'POST',
            sprintf('/api/specialists/%s/slots?clientId=%s', $specialistId, self::CLIENT_ID),
            content: json_encode([
                'date_from' => '2030-01-01',
                'date_to' => '2030-03-01',
            ], JSON_THROW_ON_ERROR),
        );
        self::assertResponseStatusCodeSame(400);

        $client->request(
            'POST',
            sprintf('/api/specialists/%s/slots?clientId=%s', $specialistId, self::CLIENT_ID),
            content: json_encode(['date_from' => 'not-a-date'], JSON_THROW_ON_ERROR),
        );
        self::assertResponseStatusCodeSame(400);
    }

    public function testSlotsBySpecialty(): void
    {
        $this->loadSpecialists();

        $client = self::getClient();
        $this->assertInstanceOf(AbstractBrowser::class, $client);

        $monday = new \DateTimeImmutable('next monday');
        if ('01.17' === $monday->format('m.d')) {
            $monday = $monday->modify('+7 days');
        }

        $client->request(
            'POST',
            '/api/slots?clientId=' . self::CLIENT_ID,
            content: json_encode([
                'specialty' => 'Barber',
                'date_from' => $monday->format('Y-m-d'),
                'date_to' => $monday->format('Y-m-d'),
            ], JSON_THROW_ON_ERROR),
        );

        self::assertResponseIsSuccessful();
        $response = json_decode((string) $this->getResponse($client)->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $code1 = $this->getSpecialistCode('Specialist1');
        $code2 = $this->getSpecialistCode('Specialist2');

        // у обоих барберов одинаковый график в моке — в каждом слоте оба
        $this->assertEquals(
            [$code1 => 'Specialist1', $code2 => 'Specialist2'],
            $response['specialists']
        );
        $daySlots = $response['slots'][$monday->format('Y-m-d')];
        $this->assertEquals(
            ['09:00', '10:00', '11:00', '12:00', '14:00', '15:00', '16:00'],
            array_keys($daySlots)
        );
        $this->assertEquals([$code1, $code2], $daySlots['09:00']);
    }

    public function testBook(): void
    {
        $this->loadSpecialists();

        $client = self::getClient();
        $this->assertInstanceOf(AbstractBrowser::class, $client);

        $code = $this->getSpecialistCode('Specialist1');

        $monday = new \DateTimeImmutable('next monday');
        if ('01.17' === $monday->format('m.d')) {
            $monday = $monday->modify('+7 days');
        }

        $client->request(
            'POST',
            '/api/book?clientId=' . self::CLIENT_ID,
            content: json_encode([
                'specialist_id' => $code,
                'date' => $monday->format('Y-m-d'),
                'time' => '10:00',
                'site' => 'main-site',
                'customer_id' => 1000,
                'customer' => ['first_name' => 'Ivan', 'phone' => '+700000000'],
            ], JSON_THROW_ON_ERROR),
        );

        self::assertResponseIsSuccessful();
        $response = json_decode((string) $this->getResponse($client)->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals(
            [
                'order_id' => 42,
                'order_number' => '42C',
                'specialist_id' => $code,
                'datetime' => $monday->format('Y-m-d') . ' 10:00',
            ],
            $response
        );

        $writer = self::getContainer()->get(MockOrderBookingWriter::class);
        $this->assertInstanceOf(MockOrderBookingWriter::class, $writer);
        $this->assertEquals(
            [[
                'specialist' => $code,
                'datetime' => $monday->format('Y-m-d') . ' 10:00',
                'orderId' => null,
                'customer' => ['first_name' => 'Ivan', 'phone' => '+700000000'],
                'site' => 'main-site',
                'customerId' => 1000,
            ]],
            $writer->calls
        );
    }

    public function testBookSlotNotAvailable(): void
    {
        $this->loadSpecialists();

        $client = self::getClient();
        $this->assertInstanceOf(AbstractBrowser::class, $client);

        $code = $this->getSpecialistCode('Specialist1');

        $monday = new \DateTimeImmutable('next monday');
        if ('01.17' === $monday->format('m.d')) {
            $monday = $monday->modify('+7 days');
        }

        // 13:00 — обед в моке рабочего времени, слота не существует
        $client->request(
            'POST',
            '/api/book?clientId=' . self::CLIENT_ID,
            content: json_encode([
                'specialist_id' => $code,
                'date' => $monday->format('Y-m-d'),
                'time' => '13:00',
            ], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(409);
        $response = json_decode((string) $this->getResponse($client)->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals(['error' => 'slot_not_available'], $response);

        $writer = self::getContainer()->get(MockOrderBookingWriter::class);
        $this->assertInstanceOf(MockOrderBookingWriter::class, $writer);
        $this->assertEquals([], $writer->calls);
    }

    private function loadSpecialists(): void
    {
        $em = $this->getEntityManager();

        $barber = new Specialty('Barber');
        $barber->setAccount($this->account);
        $em->persist($barber);

        $stylist = new Specialty('Stylist');
        $stylist->setAccount($this->account);
        $em->persist($stylist);

        $specialist1 = new Specialist('Specialist1');
        $specialist1
            ->setAccount($this->account)
            ->setSpecialty($barber)
            ->setStoreCode('store1')
        ;
        $em->persist($specialist1);

        $specialist2 = new Specialist('Specialist2');
        $specialist2
            ->setAccount($this->account)
            ->setSpecialty($barber)
            ->setStoreCode('store2')
        ;
        $em->persist($specialist2);

        $specialist3 = new Specialist('Specialist3');
        $specialist3
            ->setAccount($this->account)
            ->setSpecialty($stylist)
            ->setStoreCode('store1')
        ;
        $em->persist($specialist3);

        $em->flush();
    }

    private function getSpecialistCode(string $name): string
    {
        $em = $this->getEntityManager();
        $specialist = $em->getRepository(Specialist::class)->findOneBy(['name' => $name]);
        $this->assertInstanceOf(Specialist::class, $specialist);

        return $specialist->getDictionaryElementCode();
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
