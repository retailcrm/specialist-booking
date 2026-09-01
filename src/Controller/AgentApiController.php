<?php

namespace App\Controller;

use App\Controller\Response\SpecialistSlots;
use App\Entity\Specialist;
use App\Repository\SpecialistRepository;
use App\Repository\SpecialtyRepository;
use App\Service\AccountManager;
use App\Service\OrderBookingWriterInterface;
use App\Service\SpecialistSchedule;
use RetailCrm\Api\Interfaces\ApiExceptionInterface;
use RetailCrm\Api\Interfaces\ClientExceptionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Серверный API для AI-агента: авторизация токеном в заголовке X-Agent-Token,
 * аккаунт определяется по clientId в query (см. AccountListener).
 */
class AgentApiController extends AbstractController
{
    private const int DEFAULT_RANGE_DAYS = 14;
    private const int MAX_RANGE_DAYS = 31;

    public function __construct(
        private readonly AccountManager $accountManager,
        private readonly SpecialistRepository $specialistRepository,
    ) {
    }

    #[Route(path: '/api/specialties', name: 'api_specialties', methods: ['POST'])]
    public function specialties(Request $request, SpecialtyRepository $specialtyRepository): Response
    {
        $this->checkAccess();

        $rows = $specialtyRepository->getNamesWithSpecialistCount($this->accountManager->getAccount());

        return $this->json(['specialties' => array_map(
            static fn (array $row) => ['name' => $row['name'], 'specialistCount' => (int) $row['cnt']],
            $rows
        )]);
    }

    #[Route(path: '/api/specialists', name: 'api_specialists', methods: ['POST'])]
    public function specialists(Request $request, SpecialistSchedule $specialistSchedule): Response
    {
        $this->checkAccess();

        try {
            $payload = $this->getJsonBody($request);
        } catch (\JsonException) {
            return new Response('Invalid JSON body', Response::HTTP_BAD_REQUEST);
        }

        $specialists = $this->specialistRepository->findByAccountOrderedByOrdering(
            $this->accountManager->getAccount(),
            isset($payload['branch_code']) ? (string) $payload['branch_code'] : null,
            isset($payload['specialty']) ? (string) $payload['specialty'] : null,
        );

        $nearestSlots = $specialistSchedule->getNearestDaySchedule($specialists, $specialistSchedule->now());

        $result = [];
        foreach ($specialists as $specialist) {
            $daySlots = $nearestSlots[(int) $specialist->getId()] ?? null;

            $result[] = [
                'id' => $specialist->getDictionaryElementCode(),
                'name' => $specialist->getName(),
                'specialty' => $specialist->getSpecialty()?->getName(),
                'branch_code' => $specialist->getStoreCode(),
                'nearest_slots' => null === $daySlots ? null : [
                    'date' => $daySlots->getDate(),
                    'slots' => array_map(
                        static fn (\DateTimeImmutable $slot): string => $slot->format('H:i'),
                        $daySlots->getSlots()
                    ),
                ],
            ];
        }

        return $this->json(['specialists' => $result]);
    }

    #[Route(
        path: '/api/specialists/{specialistCode}/slots',
        name: 'api_specialist_slots',
        methods: ['POST']
    )]
    public function specialistSlots(
        string $specialistCode,
        Request $request,
        SpecialistSchedule $specialistSchedule,
    ): Response {
        $this->checkAccess();

        try {
            $payload = $this->getJsonBody($request);
        } catch (\JsonException) {
            return new Response('Invalid JSON body', Response::HTTP_BAD_REQUEST);
        }

        $now = $specialistSchedule->now();

        $range = $this->parseDateRange($payload, $now);
        if ($range instanceof Response) {
            return $range;
        }
        [$startDate, $endDate] = $range;

        $specialistId = Specialist::getIdFromDictionaryElementCode($specialistCode);
        $specialist = null === $specialistId ? null : $this->specialistRepository->find($specialistId);
        if (null === $specialist || $specialist->getAccount() !== $this->accountManager->getAccount()) {
            throw $this->createNotFoundException();
        }

        return $this->json(new SpecialistSlots(
            $specialist,
            $specialistSchedule->getSpecialistSlots($specialist, $startDate, $endDate, $now)
        ));
    }

    #[Route(path: '/api/slots', name: 'api_slots', methods: ['POST'])]
    public function slots(Request $request, SpecialistSchedule $specialistSchedule): Response
    {
        $this->checkAccess();

        try {
            $payload = $this->getJsonBody($request);
        } catch (\JsonException) {
            return new Response('Invalid JSON body', Response::HTTP_BAD_REQUEST);
        }

        $now = $specialistSchedule->now();

        $range = $this->parseDateRange($payload, $now);
        if ($range instanceof Response) {
            return $range;
        }
        [$startDate, $endDate] = $range;

        $specialists = $this->specialistRepository->findByAccountOrderedByOrdering(
            $this->accountManager->getAccount(),
            isset($payload['branch_code']) ? (string) $payload['branch_code'] : null,
            isset($payload['specialty']) ? (string) $payload['specialty'] : null,
        );

        // сводка по всем подходящим специалистам: дата -> время -> кто свободен
        $slots = [];
        $specialistNames = [];
        foreach ($specialists as $specialist) {
            $code = $specialist->getDictionaryElementCode();
            $specialistNames[$code] = $specialist->getName();

            foreach ($specialistSchedule->getSpecialistSlots($specialist, $startDate, $endDate, $now) as $daySlots) {
                foreach ($daySlots->getSlots() as $slot) {
                    $slots[$daySlots->getDate()][$slot->format('H:i')][] = $code;
                }
            }
        }

        ksort($slots);
        foreach ($slots as &$times) {
            ksort($times);
        }
        unset($times);

        return $this->json([
            'slots' => $slots,
            'specialists' => $specialistNames,
        ]);
    }

    #[Route(path: '/api/book', name: 'api_book', methods: ['POST'])]
    public function book(
        Request $request,
        SpecialistSchedule $specialistSchedule,
        OrderBookingWriterInterface $bookingWriter,
    ): Response {
        $this->checkAccess();

        try {
            $payload = $this->getJsonBody($request);
        } catch (\JsonException) {
            return new Response('Invalid JSON body', Response::HTTP_BAD_REQUEST);
        }

        $specialistCode = (string) ($payload['specialist_id'] ?? '');
        $dateString = (string) ($payload['date'] ?? '');
        $timeString = (string) ($payload['time'] ?? '');
        if ('' === $specialistCode || '' === $dateString || '' === $timeString) {
            return new Response('specialist_id, date and time are required', Response::HTTP_BAD_REQUEST);
        }

        $dateTime = \DateTimeImmutable::createFromFormat('Y-m-d H:i', $dateString . ' ' . $timeString);
        if (false === $dateTime) {
            return new Response('Invalid date or time format, expected Y-m-d and H:i', Response::HTTP_BAD_REQUEST);
        }

        $specialistId = Specialist::getIdFromDictionaryElementCode($specialistCode);
        $specialist = null === $specialistId ? null : $this->specialistRepository->find($specialistId);
        if (null === $specialist || $specialist->getAccount() !== $this->accountManager->getAccount()) {
            throw $this->createNotFoundException();
        }

        // финальная проверка: слот всё ещё свободен на момент записи
        $now = $specialistSchedule->now();
        $day = $dateTime->setTime(0, 0);
        $available = false;
        foreach ($specialistSchedule->getSpecialistSlots($specialist, $day, $day, $now) as $daySlots) {
            foreach ($daySlots->getSlots() as $slot) {
                if ($slot->format('H:i') === $dateTime->format('H:i')) {
                    $available = true;
                    break 2;
                }
            }
        }

        if (!$available) {
            return $this->json(['error' => 'slot_not_available'], Response::HTTP_CONFLICT);
        }

        $customer = [];
        foreach (['first_name', 'phone', 'comment'] as $key) {
            if (isset($payload['customer'][$key])) {
                $customer[$key] = (string) $payload['customer'][$key];
            }
        }

        try {
            $bookedOrder = $bookingWriter->book(
                $specialist,
                $dateTime,
                isset($payload['order_id']) ? (int) $payload['order_id'] : null,
                $customer,
                isset($payload['site']) ? (string) $payload['site'] : null,
                isset($payload['customer_id']) ? (int) $payload['customer_id'] : null,
            );
        } catch (ApiExceptionInterface|ClientExceptionInterface $e) {
            return $this->json(['error' => 'crm_error', 'message' => $e->getMessage()], Response::HTTP_BAD_GATEWAY);
        }

        return $this->json([
            'order_id' => $bookedOrder['id'],
            'order_number' => $bookedOrder['number'],
            'specialist_id' => $specialistCode,
            'datetime' => $dateTime->format('Y-m-d H:i'),
        ]);
    }

    // Доступ по clientId — та же модель, что у страниц настроек и виджета:
    // аккаунт резолвится слушателем запросов, чужой clientId не найдётся.
    private function checkAccess(): void
    {
        if (!$this->accountManager->hasAccount()) {
            throw $this->createNotFoundException();
        }
    }

    /**
     * @return array<string, mixed>
     *
     * @throws \JsonException
     */
    private function getJsonBody(Request $request): array
    {
        $content = $request->getContent();
        if ('' === $content) {
            return [];
        }

        $payload = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

        return is_array($payload) ? $payload : [];
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array{\DateTimeImmutable, \DateTimeImmutable}|Response
     */
    private function parseDateRange(array $payload, \DateTimeImmutable $now): array|Response
    {
        try {
            $startDate = isset($payload['date_from'])
                ? $this->parseDate((string) $payload['date_from'])
                : $now->setTime(0, 0);
            $endDate = isset($payload['date_to'])
                ? $this->parseDate((string) $payload['date_to'])
                : $startDate->modify(sprintf('+%d days', self::DEFAULT_RANGE_DAYS));
        } catch (\InvalidArgumentException $e) {
            return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        if ($endDate < $startDate) {
            return new Response('date_to is before date_from', Response::HTTP_BAD_REQUEST);
        }

        if ($startDate->diff($endDate)->days > self::MAX_RANGE_DAYS) {
            return new Response(
                sprintf('Date range is limited to %d days', self::MAX_RANGE_DAYS),
                Response::HTTP_BAD_REQUEST
            );
        }

        return [$startDate, $endDate];
    }

    private function parseDate(string $value): \DateTimeImmutable
    {
        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $value);
        if (false === $date) {
            throw new \InvalidArgumentException('Invalid date format, expected Y-m-d');
        }

        return $date->setTime(0, 0);
    }
}
