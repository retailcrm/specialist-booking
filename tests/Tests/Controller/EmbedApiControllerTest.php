<?php

namespace App\Tests\Tests\Controller;

use App\Entity\Account;
use App\Entity\Specialist;
use App\Entity\Specialty;
use App\Tests\Mock\Service\OrderBookingWriter as OrderBookingWriterMock;
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
        $em->getConnection()->executeStatement('DELETE FROM user_preference');
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

    public function testAdminSettings(): void
    {
        $client = self::getClient();
        $this->assertInstanceOf(AbstractBrowser::class, $client);

        $client->request(
            'POST',
            '/embed/api/admin/settings',
            ['clientId' => self::CLIENT_ID],
        );

        self::assertResponseIsSuccessful();
        $response = json_decode((string) $this->getResponse($client)->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(60, $response['settings']['slotDuration']);
        $this->assertFalse($response['settings']['chooseStore']);
        $this->assertFalse($response['settings']['chooseCity']);
        $this->assertSame(self::CLIENT_ID, $response['settings']['clientId']);

        $client->request(
            'POST',
            '/embed/api/admin/settings',
            [
                'clientId' => self::CLIENT_ID,
                'payload' => json_encode([
                    'slotDuration' => 30,
                    'chooseStore' => false,
                    'chooseCity' => true,
                ], JSON_THROW_ON_ERROR),
            ],
        );

        self::assertResponseIsSuccessful();
        $response = json_decode((string) $this->getResponse($client)->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(30, $response['settings']['slotDuration']);
        $this->assertFalse($response['settings']['chooseStore']);
        $this->assertFalse($response['settings']['chooseCity']);
    }

    public function testAdminSettingsNotValidClientId(): void
    {
        $client = self::getClient();
        $this->assertInstanceOf(AbstractBrowser::class, $client);

        $client->request(
            'POST',
            '/embed/api/admin/settings',
            ['clientId' => self::CLIENT_ID . '__'],
        );

        self::assertResponseStatusCodeSame(404);
    }

    public function testAdminSettingsRejectsInvalidSlotDuration(): void
    {
        $client = self::getClient();
        $this->assertInstanceOf(AbstractBrowser::class, $client);

        foreach ([20, 9999399] as $slotDuration) {
            $client->request(
                'POST',
                '/embed/api/admin/settings',
                [
                    'clientId' => self::CLIENT_ID,
                    'payload' => json_encode([
                        'slotDuration' => $slotDuration,
                        'chooseStore' => true,
                        'chooseCity' => true,
                    ], JSON_THROW_ON_ERROR),
                ],
            );

            self::assertResponseStatusCodeSame(400);
        }
    }

    public function testAdminSpecialtiesCrud(): void
    {
        $client = self::getClient();
        $this->assertInstanceOf(AbstractBrowser::class, $client);

        $client->request(
            'POST',
            '/embed/api/admin/specialties/save',
            [
                'clientId' => self::CLIENT_ID,
                'payload' => json_encode(['name' => 'Dermatology'], JSON_THROW_ON_ERROR),
            ],
        );

        self::assertResponseIsSuccessful();
        $response = json_decode((string) $this->getResponse($client)->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(['Dermatology'], array_column($response['specialties'], 'name'));

        $client->request(
            'POST',
            '/embed/api/admin/specialties/save',
            [
                'clientId' => self::CLIENT_ID,
                'payload' => json_encode(['name' => 'Therapy'], JSON_THROW_ON_ERROR),
            ],
        );

        self::assertResponseIsSuccessful();
        $response = json_decode((string) $this->getResponse($client)->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(['Dermatology', 'Therapy'], array_column($response['specialties'], 'name'));

        $firstId = $response['specialties'][0]['id'];
        $client->request(
            'POST',
            '/embed/api/admin/specialties/save',
            [
                'clientId' => self::CLIENT_ID,
                'payload' => json_encode([
                    'id' => $firstId,
                    'name' => 'Dermatology updated',
                ], JSON_THROW_ON_ERROR),
            ],
        );

        self::assertResponseIsSuccessful();
        $response = json_decode((string) $this->getResponse($client)->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(['Dermatology updated', 'Therapy'], array_column($response['specialties'], 'name'));

        $client->request(
            'POST',
            '/embed/api/admin/specialties/delete',
            [
                'clientId' => self::CLIENT_ID,
                'payload' => json_encode(['id' => $firstId], JSON_THROW_ON_ERROR),
            ],
        );

        self::assertResponseIsSuccessful();
        $response = json_decode((string) $this->getResponse($client)->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(['Therapy'], array_column($response['specialties'], 'name'));
    }

    public function testAdminSpecialtySaveRejectsEmptyName(): void
    {
        $client = self::getClient();
        $this->assertInstanceOf(AbstractBrowser::class, $client);

        $client->request(
            'POST',
            '/embed/api/admin/specialties/save',
            [
                'clientId' => self::CLIENT_ID,
                'payload' => json_encode(['name' => ' '], JSON_THROW_ON_ERROR),
            ],
        );

        self::assertResponseStatusCodeSame(400);
    }

    public function testAdminSpecialtySaveRejectsTooLongName(): void
    {
        $client = self::getClient();
        $this->assertInstanceOf(AbstractBrowser::class, $client);

        $client->request(
            'POST',
            '/embed/api/admin/specialties/save',
            [
                'clientId' => self::CLIENT_ID,
                'payload' => json_encode(['name' => str_repeat('a', 256)], JSON_THROW_ON_ERROR),
            ],
        );

        self::assertResponseStatusCodeSame(400);
    }

    public function testAdminSpecialtyDeleteUnknown(): void
    {
        $client = self::getClient();
        $this->assertInstanceOf(AbstractBrowser::class, $client);

        $client->request(
            'POST',
            '/embed/api/admin/specialties/delete',
            [
                'clientId' => self::CLIENT_ID,
                'payload' => json_encode(['id' => 999999], JSON_THROW_ON_ERROR),
            ],
        );

        self::assertResponseStatusCodeSame(404);
    }

    public function testAdminSpecialistsLoad(): void
    {
        $client = self::getClient();
        $this->assertInstanceOf(AbstractBrowser::class, $client);

        $this->loadSpecialists();
        $this->account->getSettings()->setChooseStore(true);
        $this->getEntityManager()->flush();

        $client->request(
            'POST',
            '/embed/api/admin/specialists',
            ['clientId' => self::CLIENT_ID],
        );

        self::assertResponseIsSuccessful();
        $response = json_decode((string) $this->getResponse($client)->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertCount(6, $response['specialists']);
        $this->assertSame('Specialist1', $response['specialists'][0]['name']);
        $this->assertSame('Specialty', $response['specialties'][0]['name']);
        $this->assertSame('Store 1 (Moscow)', $response['stores'][0]['name']);
        $this->assertTrue($response['chooseStore']);
    }

    public function testAdminSpecialistSaveRejectsEmptyName(): void
    {
        $client = self::getClient();
        $this->assertInstanceOf(AbstractBrowser::class, $client);

        $client->request(
            'POST',
            '/embed/api/admin/specialists/save',
            [
                'clientId' => self::CLIENT_ID,
                'payload' => json_encode(['name' => ' '], JSON_THROW_ON_ERROR),
            ],
        );

        self::assertResponseStatusCodeSame(400);
    }

    public function testAdminSpecialistSaveRejectsTooShortName(): void
    {
        $client = self::getClient();
        $this->assertInstanceOf(AbstractBrowser::class, $client);

        $client->request(
            'POST',
            '/embed/api/admin/specialists/save',
            [
                'clientId' => self::CLIENT_ID,
                'payload' => json_encode(['name' => 'A'], JSON_THROW_ON_ERROR),
            ],
        );

        self::assertResponseStatusCodeSame(400);
    }

    public function testAdminSpecialistSaveRejectsInvalidOrdering(): void
    {
        $client = self::getClient();
        $this->assertInstanceOf(AbstractBrowser::class, $client);

        $client->request(
            'POST',
            '/embed/api/admin/specialists/save',
            [
                'clientId' => self::CLIENT_ID,
                'payload' => json_encode([
                    'name' => 'Valid specialist',
                    'ordering' => 10000,
                ], JSON_THROW_ON_ERROR),
            ],
        );

        self::assertResponseStatusCodeSame(400);
    }

    public function testAdminSpecialistSaveRejectsNonNumericOrdering(): void
    {
        $client = self::getClient();
        $this->assertInstanceOf(AbstractBrowser::class, $client);

        $client->request(
            'POST',
            '/embed/api/admin/specialists/save',
            [
                'clientId' => self::CLIENT_ID,
                'payload' => json_encode([
                    'name' => 'Valid specialist',
                    'ordering' => 'abc',
                ], JSON_THROW_ON_ERROR),
            ],
        );

        self::assertResponseStatusCodeSame(400);
        $this->assertFieldError($client, 'ordering');
    }

    public function testAdminSpecialistSaveRejectsUnknownSpecialty(): void
    {
        $client = self::getClient();
        $this->assertInstanceOf(AbstractBrowser::class, $client);

        $client->request(
            'POST',
            '/embed/api/admin/specialists/save',
            [
                'clientId' => self::CLIENT_ID,
                'payload' => json_encode([
                    'name' => 'Valid specialist',
                    'specialtyId' => 999999,
                ], JSON_THROW_ON_ERROR),
            ],
        );

        self::assertResponseStatusCodeSame(400);
        $this->assertFieldError($client, 'specialtyId');
    }

    public function testAdminSpecialistSaveRejectsUnknownStore(): void
    {
        $client = self::getClient();
        $this->assertInstanceOf(AbstractBrowser::class, $client);

        $this->account->getSettings()->setChooseStore(true);
        $this->getEntityManager()->flush();

        $client->request(
            'POST',
            '/embed/api/admin/specialists/save',
            [
                'clientId' => self::CLIENT_ID,
                'payload' => json_encode([
                    'name' => 'Valid specialist',
                    'storeCode' => 'unknown-store',
                ], JSON_THROW_ON_ERROR),
            ],
        );

        self::assertResponseStatusCodeSame(400);
        $this->assertFieldError($client, 'storeCode');
    }

    public function testAdminSpecialistsLoadKeepsExternalPhotoUrl(): void
    {
        $client = self::getClient();
        $this->assertInstanceOf(AbstractBrowser::class, $client);

        $specialist = new Specialist('Valid specialist');
        $specialist->setPhoto('https://example.com/photo.png');
        $this->account->addSpecialist($specialist);
        $this->getEntityManager()->persist($specialist);
        $this->getEntityManager()->flush();

        $client->request(
            'POST',
            '/embed/api/admin/specialists',
            ['clientId' => self::CLIENT_ID],
        );

        self::assertResponseIsSuccessful();
        $response = json_decode((string) $this->getResponse($client)->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('https://example.com/photo.png', $response['specialists'][0]['photo']);
        $this->assertSame('https://example.com/photo.png', $response['specialists'][0]['photoUrl']);
    }

    public function testAdminSpecialistSaveRejectsInvalidPhotoUrl(): void
    {
        $client = self::getClient();
        $this->assertInstanceOf(AbstractBrowser::class, $client);

        $client->request(
            'POST',
            '/embed/api/admin/specialists/save',
            [
                'clientId' => self::CLIENT_ID,
                'payload' => json_encode([
                    'name' => 'Valid specialist',
                    'photoUrl' => 'javascript:alert(1)',
                ], JSON_THROW_ON_ERROR),
            ],
        );

        self::assertResponseStatusCodeSame(400);
    }

    public function testAdminSpecialistSaveRejectsTooLongPhotoUrl(): void
    {
        $client = self::getClient();
        $this->assertInstanceOf(AbstractBrowser::class, $client);

        $client->request(
            'POST',
            '/embed/api/admin/specialists/save',
            [
                'clientId' => self::CLIENT_ID,
                'payload' => json_encode([
                    'name' => 'Valid specialist',
                    'photoUrl' => 'https://example.com/' . str_repeat('a', 255),
                ], JSON_THROW_ON_ERROR),
            ],
        );

        self::assertResponseStatusCodeSame(400);
        $this->assertFieldError($client, 'photoUrl');
    }

    public function testAdminSpecialistSaveSchedule(): void
    {
        $client = self::getClient();
        $this->assertInstanceOf(AbstractBrowser::class, $client);

        $workTimes = [1 => [['10:00', '13:00'], ['14:00', '18:00']], 3 => [['09:00', '24:00']]];
        $nonWorkingDays = [['01.01', '01.08'], ['05.09', '05.09']];

        // сохранение доходит до синхронизации полей CRM, которой в тестах нет
        // (502), но специалист к этому моменту уже записан
        $client->request(
            'POST',
            '/embed/api/admin/specialists/save',
            [
                'clientId' => self::CLIENT_ID,
                'payload' => json_encode([
                    'name' => 'Scheduled',
                    'workTimes' => $workTimes,
                    'nonWorkingDays' => $nonWorkingDays,
                ], JSON_THROW_ON_ERROR),
            ],
        );

        $em = $this->getEntityManager();
        $em->clear();
        $specialist = $em->getRepository(Specialist::class)->findOneBy(['name' => 'Scheduled']);
        $this->assertNotNull($specialist);
        $this->assertSame($workTimes, $specialist->getWorkTimes());
        $this->assertSame($nonWorkingDays, $specialist->getNonWorkingDays());

        $client->request(
            'POST',
            '/embed/api/admin/specialists',
            ['clientId' => self::CLIENT_ID],
        );

        self::assertResponseIsSuccessful();
        $response = json_decode((string) $this->getResponse($client)->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(['1' => $workTimes[1], '3' => $workTimes[3]], $response['specialists'][0]['workTimes']);
        $this->assertSame($nonWorkingDays, $response['specialists'][0]['nonWorkingDays']);

        // пустое расписание — снова общий график компании
        $client->request(
            'POST',
            '/embed/api/admin/specialists/save',
            [
                'clientId' => self::CLIENT_ID,
                'payload' => json_encode([
                    'id' => $specialist->getId(),
                    'name' => 'Scheduled',
                    'workTimes' => null,
                    'nonWorkingDays' => [],
                ], JSON_THROW_ON_ERROR),
            ],
        );

        $em->clear();
        $specialist = $em->getRepository(Specialist::class)->find($specialist->getId());
        $this->assertNotNull($specialist);
        $this->assertNull($specialist->getWorkTimes());
        $this->assertNull($specialist->getNonWorkingDays());
    }

    /**
     * @dataProvider badScheduleProvider
     *
     * @param array<string, mixed> $schedule
     */
    public function testAdminSpecialistSaveRejectsBadSchedule(array $schedule, string $path): void
    {
        $client = self::getClient();
        $this->assertInstanceOf(AbstractBrowser::class, $client);

        $client->request(
            'POST',
            '/embed/api/admin/specialists/save',
            [
                'clientId' => self::CLIENT_ID,
                'payload' => json_encode(['name' => 'Scheduled'] + $schedule, JSON_THROW_ON_ERROR),
            ],
        );

        self::assertResponseStatusCodeSame(400);
        $this->assertFieldError($client, $path);
    }

    /**
     * @return iterable<string, array{array<string, mixed>, string}>
     */
    public static function badScheduleProvider(): iterable
    {
        yield 'unknown weekday' => [['workTimes' => ['8' => [['10:00', '18:00']]]], 'workTimes'];
        yield 'end before start' => [['workTimes' => ['1' => [['18:00', '10:00']]]], 'workTimes'];
        yield 'bad time' => [['workTimes' => ['1' => [['10:00', '25:00']]]], 'workTimes'];
        yield 'overlapping intervals' => [['workTimes' => ['1' => [['10:00', '14:00'], ['13:00', '18:00']]]], 'workTimes'];
        yield 'day without intervals' => [['workTimes' => ['1' => []]], 'workTimes'];
        yield 'bad day off' => [['nonWorkingDays' => [['13.01', '13.02']]], 'nonWorkingDays'];
        yield 'day off without dots' => [['nonWorkingDays' => [['0101', '0108']]], 'nonWorkingDays'];
    }

    public function testAdminSpecialistDeleteUnknown(): void
    {
        $client = self::getClient();
        $this->assertInstanceOf(AbstractBrowser::class, $client);

        $client->request(
            'POST',
            '/embed/api/admin/specialists/delete',
            [
                'clientId' => self::CLIENT_ID,
                'payload' => json_encode(['id' => 999999], JSON_THROW_ON_ERROR),
            ],
        );

        self::assertResponseStatusCodeSame(404);
    }

    public function testAdminCalendar(): void
    {
        $client = self::getClient();
        $this->assertInstanceOf(AbstractBrowser::class, $client);
        $this->loadSpecialists();

        // будущая неделя: прошедшие слоты календарь не показывает
        $monday = new \DateTimeImmutable('next monday');
        $sunday = $monday->modify('+6 days');

        $client->request(
            'POST',
            '/embed/api/admin/calendar',
            [
                'clientId' => self::CLIENT_ID,
                'payload' => json_encode([
                    'dateFrom' => $monday->format('Y-m-d'),
                    'dateTo' => $sunday->format('Y-m-d'),
                ], JSON_THROW_ON_ERROR),
            ],
        );

        self::assertResponseIsSuccessful();
        $response = json_decode((string) $this->getResponse($client)->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertCount(7, $response['days']);
        $this->assertSame(60, $response['slotDuration']);
        $this->assertCount(6, $response['schedule']);
        $this->assertSame('Specialty', $response['specialties'][0]['name']);

        $first = $response['schedule'][0];
        $this->assertSame('Specialist1', $first['specialist']['name']);
        $mondayRow = $first['days'][$monday->format('Y-m-d')];
        // понедельник общего графика: 09:00–13:00 и 14:00–17:00 по часу
        $this->assertSame(['09:00', '10:00', '11:00', '12:00', '14:00', '15:00', '16:00'], $mondayRow['free']);
        $this->assertSame('B-777', $mondayRow['bookings'][0]['orderNumber']);
        $this->assertSame('12:00', $mondayRow['bookings'][0]['time']);
        $this->assertSame('Петров Иван', $mondayRow['bookings'][0]['customer']);
        $this->assertSame(['new' => 'Новый'], $response['statuses']);
        // воскресенье нерабочий — свободных слотов нет
        $this->assertSame([], $first['days'][$sunday->format('Y-m-d')]['free']);

        $this->assertFalse($response['chooseStore']);
        $this->assertSame([], $response['stores']);
        $this->assertSame('store1', $first['specialist']['storeCode']);

        // с выбором филиала календарь отдаёт филиалы, к которым привязаны специалисты
        $this->account->getSettings()->setChooseStore(true);
        $this->getEntityManager()->flush();

        $client->request(
            'POST',
            '/embed/api/admin/calendar',
            [
                'clientId' => self::CLIENT_ID,
                'payload' => json_encode([
                    'dateFrom' => $monday->format('Y-m-d'),
                    'dateTo' => $sunday->format('Y-m-d'),
                ], JSON_THROW_ON_ERROR),
            ],
        );

        self::assertResponseIsSuccessful();
        $response = json_decode((string) $this->getResponse($client)->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertTrue($response['chooseStore']);
        $this->assertSame(
            [
                ['code' => 'store1', 'name' => 'Store 1', 'city' => 'Moscow'],
                ['code' => 'store2', 'name' => 'Store 2', 'city' => 'Moscow'],
                ['code' => 'store3', 'name' => 'Store 3', 'city' => 'Tula'],
                ['code' => 'store4', 'name' => 'Store 4', 'city' => null],
            ],
            $response['stores'],
        );

        // фильтр по специализации оставляет двоих
        $client->request(
            'POST',
            '/embed/api/admin/calendar',
            [
                'clientId' => self::CLIENT_ID,
                'payload' => json_encode([
                    'dateFrom' => $monday->format('Y-m-d'),
                    'dateTo' => $sunday->format('Y-m-d'),
                    'specialtyId' => $response['specialties'][0]['id'],
                ], JSON_THROW_ON_ERROR),
            ],
        );

        self::assertResponseIsSuccessful();
        $response = json_decode((string) $this->getResponse($client)->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertCount(2, $response['schedule']);
    }

    public function testAdminCalendarRemembersStorePerUser(): void
    {
        $client = self::getClient();
        $this->assertInstanceOf(AbstractBrowser::class, $client);
        $monday = new \DateTimeImmutable('next monday');

        $client->request(
            'POST',
            '/embed/api/admin/calendar/preferences',
            ['clientId' => self::CLIENT_ID, 'payload' => json_encode(['userId' => 7, 'storeCode' => 'store2'], JSON_THROW_ON_ERROR)],
        );
        self::assertResponseIsSuccessful();

        foreach ([7 => 'store2', 8 => null] as $userId => $expected) {
            $client->request(
                'POST',
                '/embed/api/admin/calendar',
                [
                    'clientId' => self::CLIENT_ID,
                    'payload' => json_encode([
                        'dateFrom' => $monday->format('Y-m-d'),
                        'dateTo' => $monday->format('Y-m-d'),
                        'userId' => $userId,
                    ], JSON_THROW_ON_ERROR),
                ],
            );

            self::assertResponseIsSuccessful();
            $response = json_decode((string) $this->getResponse($client)->getContent(), true, 512, JSON_THROW_ON_ERROR);
            $this->assertSame($expected, $response['preferredStore']);
        }

        $client->request(
            'POST',
            '/embed/api/admin/calendar/preferences',
            ['clientId' => self::CLIENT_ID, 'payload' => json_encode(['storeCode' => 'store2'], JSON_THROW_ON_ERROR)],
        );
        self::assertResponseStatusCodeSame(400);
        $this->assertFieldError($client, 'userId');
    }

    public function testAdminCalendarRejectsLongPeriod(): void
    {
        $client = self::getClient();
        $this->assertInstanceOf(AbstractBrowser::class, $client);

        $client->request(
            'POST',
            '/embed/api/admin/calendar',
            [
                'clientId' => self::CLIENT_ID,
                'payload' => json_encode(['dateFrom' => '2026-01-01', 'dateTo' => '2026-03-01'], JSON_THROW_ON_ERROR),
            ],
        );

        self::assertResponseStatusCodeSame(400);
        $this->assertFieldError($client, 'dateTo');
    }

    public function testAdminCustomers(): void
    {
        $client = self::getClient();
        $this->assertInstanceOf(AbstractBrowser::class, $client);

        $client->request(
            'POST',
            '/embed/api/admin/customers',
            ['clientId' => self::CLIENT_ID, 'payload' => json_encode(['query' => 'Ив'], JSON_THROW_ON_ERROR)],
        );

        self::assertResponseIsSuccessful();
        $response = json_decode((string) $this->getResponse($client)->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('Петров', $response['customers'][0]['lastName']);

        // короткий запрос — пустой список без похода в CRM
        $client->request(
            'POST',
            '/embed/api/admin/customers',
            ['clientId' => self::CLIENT_ID, 'payload' => json_encode(['query' => 'И'], JSON_THROW_ON_ERROR)],
        );

        self::assertResponseIsSuccessful();
        $response = json_decode((string) $this->getResponse($client)->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame([], $response['customers']);
    }

    public function testAdminBook(): void
    {
        $client = self::getClient();
        $this->assertInstanceOf(AbstractBrowser::class, $client);
        $this->loadSpecialists();
        $specialist = $this->getEntityManager()->getRepository(Specialist::class)->findOneBy(['name' => 'Specialist1']);
        $this->assertNotNull($specialist);

        $monday = new \DateTimeImmutable('next monday');

        $client->request(
            'POST',
            '/embed/api/admin/book',
            [
                'clientId' => self::CLIENT_ID,
                'payload' => json_encode([
                    'specialistId' => $specialist->getId(),
                    'date' => $monday->format('Y-m-d'),
                    'time' => '10:00',
                    'customerId' => 5,
                    'firstName' => 'Иван',
                    'lastName' => 'Петров',
                    'phone' => '+79990000000',
                    'comment' => 'первичный приём',
                ], JSON_THROW_ON_ERROR),
            ],
        );

        self::assertResponseIsSuccessful();
        $response = json_decode((string) $this->getResponse($client)->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(42, $response['orderId']);
        $this->assertSame('42C', $response['orderNumber']);

        $writer = static::getContainer()->get(OrderBookingWriterMock::class);
        $this->assertInstanceOf(OrderBookingWriterMock::class, $writer);
        $call = $writer->calls[array_key_last($writer->calls)];
        $this->assertSame($specialist->getDictionaryElementCode(), $call['specialist']);
        $this->assertSame($monday->format('Y-m-d') . ' 10:00', $call['datetime']);
        $this->assertSame(5, $call['customerId']);
        $this->assertSame(
            ['first_name' => 'Иван', 'phone' => '+79990000000', 'last_name' => 'Петров', 'comment' => 'первичный приём'],
            $call['customer'],
        );
    }

    public function testAdminBookLinkedCustomerWithoutContacts(): void
    {
        $client = self::getClient();
        $this->assertInstanceOf(AbstractBrowser::class, $client);
        $this->loadSpecialists();
        $specialist = $this->getEntityManager()->getRepository(Specialist::class)->findOneBy(['name' => 'Specialist1']);
        $this->assertNotNull($specialist);

        // у привязанного клиента имя и телефон берутся из карточки CRM
        $client->request(
            'POST',
            '/embed/api/admin/book',
            [
                'clientId' => self::CLIENT_ID,
                'payload' => json_encode([
                    'specialistId' => $specialist->getId(),
                    'date' => (new \DateTimeImmutable('next monday'))->format('Y-m-d'),
                    'time' => '11:00',
                    'customerId' => 5,
                ], JSON_THROW_ON_ERROR),
            ],
        );

        self::assertResponseIsSuccessful();
        $writer = static::getContainer()->get(OrderBookingWriterMock::class);
        $this->assertInstanceOf(OrderBookingWriterMock::class, $writer);
        $call = $writer->calls[array_key_last($writer->calls)];
        $this->assertSame(5, $call['customerId']);
        $this->assertSame([], $call['customer']);
    }

    public function testAdminBookRejectsBusyAndInvalid(): void
    {
        $client = self::getClient();
        $this->assertInstanceOf(AbstractBrowser::class, $client);
        $this->loadSpecialists();
        $specialist = $this->getEntityManager()->getRepository(Specialist::class)->findOneBy(['name' => 'Specialist1']);
        $this->assertNotNull($specialist);

        // прошедший день — слота уже нет
        $client->request(
            'POST',
            '/embed/api/admin/book',
            [
                'clientId' => self::CLIENT_ID,
                'payload' => json_encode([
                    'specialistId' => $specialist->getId(),
                    'date' => '2025-01-16',
                    'time' => '10:00',
                    'firstName' => 'Иван',
                    'phone' => '+79990000000',
                ], JSON_THROW_ON_ERROR),
            ],
        );

        self::assertResponseStatusCodeSame(409);

        // без телефона
        $client->request(
            'POST',
            '/embed/api/admin/book',
            [
                'clientId' => self::CLIENT_ID,
                'payload' => json_encode([
                    'specialistId' => $specialist->getId(),
                    'date' => (new \DateTimeImmutable('next monday'))->format('Y-m-d'),
                    'time' => '10:00',
                    'firstName' => 'Иван',
                ], JSON_THROW_ON_ERROR),
            ],
        );

        self::assertResponseStatusCodeSame(400);
        $this->assertFieldError($client, 'phone');
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

    /**
     * @param AbstractBrowser<Request, Response> $client
     */
    private function assertFieldError(AbstractBrowser $client, string $path): void
    {
        $response = json_decode((string) $this->getResponse($client)->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertContains($path, array_column($response['errors'] ?? [], 'path'));
    }
}
