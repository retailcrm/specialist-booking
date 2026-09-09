<?php

namespace App\Controller;

use App\Controller\Payload\BookPayload;
use App\Controller\Payload\CalendarPayload;
use App\Controller\Response\AdminSpecialty;
use App\Entity\Specialist;
use App\Repository\SpecialistRepository;
use App\Repository\SpecialtyRepository;
use App\Repository\UserPreferenceRepository;
use App\Service\AccountManager;
use App\Service\CustomerSearchInterface;
use App\Service\DTO\Booking;
use App\Service\OrderBookingReaderInterface;
use App\Service\OrderBookingWriterInterface;
use App\Service\SpecialistBusySlotFetcherInterface;
use App\Service\SpecialistSchedule;
use Doctrine\ORM\EntityManagerInterface;
use Gaufrette\Extras\Resolvable\ResolvableFilesystem;
use RetailCrm\Api\Interfaces\ApiExceptionInterface;
use RetailCrm\Api\Interfaces\ClientExceptionInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Календарь записей для менеджера: сводка по неделе, поиск клиента и
 * оформление записи с той же проверкой слота, что у агента.
 */
class AdminCalendarController extends AdminApiController
{
    public function __construct(
        AccountManager $accountManager,
        ValidatorInterface $validator,
        private readonly SpecialistRepository $specialistRepository,
        private readonly SpecialtyRepository $specialtyRepository,
        private readonly SpecialistSchedule $specialistSchedule,
        private readonly UserPreferenceRepository $preferenceRepository,
    ) {
        parent::__construct($accountManager, $validator);
    }

    #[Route(path: '/embed/api/admin/calendar', name: 'embed_api_admin_calendar', methods: ['POST'])]
    public function calendar(
        Request $request,
        OrderBookingReaderInterface $bookingReader,
        SpecialistBusySlotFetcherInterface $busySlotFetcher,
        ResolvableFilesystem $fileSystem,
    ): Response {
        $account = $this->requireAccount();
        $payload = CalendarPayload::fromArray($this->getPayload($request));

        if (null !== $response = $this->validatePayload($payload)) {
            return $response;
        }

        $specialists = array_values(array_filter(
            $this->specialistRepository->findByAccountOrderedByOrdering($account),
            static fn (Specialist $specialist): bool => (null === $payload->specialistId || $specialist->getId() === $payload->specialistId)
                && (null === $payload->specialtyId || $specialist->getSpecialty()?->getId() === $payload->specialtyId),
        ));

        $from = $payload->getDateFrom();
        $to = $payload->getDateTo();
        $now = $this->specialistSchedule->now();

        try {
            $bookings = $bookingReader->fetch(
                array_map(static fn (Specialist $specialist): string => $specialist->getDictionaryElementCode(), $specialists),
                $from,
                $to,
            );
            $statusNames = [] === $bookings ? [] : $bookingReader->statusNames();
        } catch (ApiExceptionInterface|ClientExceptionInterface) {
            return $this->error('Bookings could not be loaded from CRM.', Response::HTTP_BAD_GATEWAY);
        }

        $bookingsByCode = [];
        foreach ($bookings as $booking) {
            $bookingsByCode[$booking->specialistCode][$booking->dateTime->format('Y-m-d')][] = $this->serializeBooking($booking);
        }

        $days = [];
        for ($day = $from; $day <= $to; $day = $day->modify('+1 day')) {
            $days[] = $day->format('Y-m-d');
        }

        $schedule = [];
        foreach ($specialists as $specialist) {
            $freeByDay = [];
            foreach ($this->specialistSchedule->getSpecialistSlots($specialist, $from, $to, $now) as $daySlots) {
                $freeByDay[$daySlots->getDate()] = array_map(
                    static fn (\DateTimeImmutable $slot): string => $slot->format('H:i'),
                    $daySlots->getSlots(),
                );
            }

            $code = $specialist->getDictionaryElementCode();
            $dayRows = [];
            foreach ($days as $date) {
                $dayRows[$date] = [
                    'free' => $freeByDay[$date] ?? [],
                    'bookings' => $bookingsByCode[$code][$date] ?? [],
                ];
            }

            $schedule[] = [
                'specialist' => [
                    'id' => $specialist->getId(),
                    'name' => $specialist->getName(),
                    'specialtyId' => $specialist->getSpecialty()?->getId(),
                    'storeCode' => $specialist->getStoreCode(),
                    'photoUrl' => $this->resolvePhoto($specialist->getPhoto(), $fileSystem),
                ],
                'days' => (object) $dayRows,
            ];
        }

        $settings = $account->getSettings();
        $preference = null === $payload->userId ? null : $this->preferenceRepository->findByUser($account, $payload->userId);

        return $this->json([
            'preferredStore' => $preference?->getCalendarStoreCode(),
            'statuses' => (object) $statusNames,
            'days' => $days,
            'slotDuration' => $busySlotFetcher->getSlotDuration(),
            'chooseStore' => $settings->chooseStore(),
            'chooseCity' => $settings->chooseCity(),
            'stores' => $settings->chooseStore() ? $this->getStores($busySlotFetcher, $specialists) : [],
            'specialties' => array_map(
                static fn ($specialty): AdminSpecialty => AdminSpecialty::fromEntity($specialty),
                $this->specialtyRepository->findByAccountOrderingByName($account),
            ),
            'schedule' => $schedule,
        ]);
    }

    /** Запоминает филиал календаря за пользователем CRM. */
    #[Route(path: '/embed/api/admin/calendar/preferences', name: 'embed_api_admin_calendar_preferences', methods: ['POST'])]
    public function preferences(Request $request, EntityManagerInterface $em): Response
    {
        $account = $this->requireAccount();
        $payload = $this->getPayload($request);
        $userId = $payload['userId'] ?? null;
        if (!is_numeric($userId) || (int) $userId <= 0) {
            return $this->fieldError('userId', 'User id is required.');
        }

        $storeCode = trim((string) ($payload['storeCode'] ?? ''));
        $this->preferenceRepository->getOrCreate($account, (int) $userId)->setCalendarStoreCode('' === $storeCode ? null : $storeCode);
        $em->flush();

        return $this->json(['preferredStore' => '' === $storeCode ? null : $storeCode]);
    }

    #[Route(path: '/embed/api/admin/customers', name: 'embed_api_admin_customers', methods: ['POST'])]
    public function customers(Request $request, CustomerSearchInterface $customerSearch): Response
    {
        $this->requireAccount();
        $query = trim((string) ($this->getPayload($request)['query'] ?? ''));
        if (mb_strlen($query) < 2) {
            return $this->json(['customers' => []]);
        }

        try {
            return $this->json(['customers' => $customerSearch->search($query)]);
        } catch (ApiExceptionInterface|ClientExceptionInterface) {
            return $this->error('Customers could not be loaded from CRM.', Response::HTTP_BAD_GATEWAY);
        }
    }

    #[Route(path: '/embed/api/admin/book', name: 'embed_api_admin_book', methods: ['POST'])]
    public function book(Request $request, OrderBookingWriterInterface $bookingWriter): Response
    {
        $account = $this->requireAccount();
        $payload = BookPayload::fromArray($this->getPayload($request));

        if (null !== $response = $this->validatePayload($payload)) {
            return $response;
        }

        $dateTime = $payload->getDateTime();
        if (null === $dateTime) {
            return $this->fieldError('time', 'Invalid date or time.');
        }

        $specialist = $this->specialistRepository->find($payload->specialistId);
        if (null === $specialist || $specialist->getAccount() !== $account) {
            throw $this->createNotFoundException();
        }

        // финальная проверка: слот всё ещё свободен на момент записи
        if (!$this->isSlotAvailable($specialist, $dateTime)) {
            return $this->json(['error' => 'slot_not_available'], Response::HTTP_CONFLICT);
        }

        $customer = array_filter(['first_name' => $payload->firstName, 'phone' => $payload->phone], static fn (string $v): bool => '' !== $v);
        if (null !== $payload->lastName) {
            $customer['last_name'] = $payload->lastName;
        }
        if (null !== $payload->comment) {
            $customer['comment'] = $payload->comment;
        }

        try {
            $order = $bookingWriter->book($specialist, $dateTime, null, $customer, null, $payload->customerId);
        } catch (ApiExceptionInterface|ClientExceptionInterface $e) {
            return $this->error('CRM error: ' . $e->getMessage(), Response::HTTP_BAD_GATEWAY);
        }

        return $this->json([
            'orderId' => $order['id'],
            'orderNumber' => $order['number'],
            'datetime' => $dateTime->format('Y-m-d H:i'),
        ]);
    }

    /**
     * Филиалы, к которым привязан хотя бы один специалист, — для фильтра календаря.
     *
     * @param Specialist[] $specialists
     *
     * @return array<array{code: string, name: string, city: ?string}>
     */
    private function getStores(SpecialistBusySlotFetcherInterface $busySlotFetcher, array $specialists): array
    {
        $usedCodes = array_filter(array_unique(array_map(
            static fn (Specialist $specialist): ?string => $specialist->getStoreCode(),
            $specialists,
        )));

        try {
            $crmStores = $busySlotFetcher->getStores();
        } catch (ApiExceptionInterface|ClientExceptionInterface) {
            return [];
        }

        $stores = [];
        foreach ($crmStores as $store) {
            if (null === $store->code || !in_array($store->code, $usedCodes, true)) {
                continue;
            }
            /* @phpstan-ignore-next-line nullCoalesce.expr */
            $city = trim($store->address?->city ?? '');
            $stores[] = ['code' => $store->code, 'name' => (string) $store->name, 'city' => '' === $city ? null : $city];
        }

        usort($stores, static fn (array $a, array $b): int => $a['name'] <=> $b['name']);

        return $stores;
    }

    private function isSlotAvailable(Specialist $specialist, \DateTimeImmutable $dateTime): bool
    {
        $day = $dateTime->setTime(0, 0);
        foreach ($this->specialistSchedule->getSpecialistSlots($specialist, $day, $day, $this->specialistSchedule->now()) as $daySlots) {
            foreach ($daySlots->getSlots() as $slot) {
                if ($slot->format('H:i') === $dateTime->format('H:i')) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeBooking(Booking $booking): array
    {
        return [
            'time' => $booking->dateTime->format('H:i'),
            'orderId' => $booking->orderId,
            'orderNumber' => $booking->orderNumber,
            'customer' => $booking->customerName,
            'phone' => $booking->phone,
            'status' => $booking->statusCode,
        ];
    }

    private function resolvePhoto(?string $photo, ResolvableFilesystem $fileSystem): ?string
    {
        if (null === $photo) {
            return null;
        }

        return str_starts_with($photo, 'http://') || str_starts_with($photo, 'https://')
            ? $photo
            : $fileSystem->resolve($photo);
    }
}
