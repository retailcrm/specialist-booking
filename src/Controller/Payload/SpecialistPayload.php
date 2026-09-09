<?php

namespace App\Controller\Payload;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final readonly class SpecialistPayload
{
    public function __construct(
        #[Assert\Positive]
        public ?int $id,
        #[Assert\NotBlank]
        #[Assert\Length(min: 2, max: 255)]
        public string $name,
        #[Assert\Positive]
        public ?int $specialtyId,
        #[Assert\Length(max: 255)]
        public ?string $storeCode,
        #[Assert\Range(min: 0, max: 9999)]
        public int $ordering = 99,
        #[Assert\Type('bool')]
        public bool $removePhoto = false,
        #[Assert\Length(max: 255)]
        #[Assert\Url(protocols: ['http', 'https'], requireTld: false)]
        public ?string $photoUrl = null,
        public bool $photoUrlProvided = false,
        /** @var array<int, array<array{string, string}>>|null личное недельное расписание, день 1 (пн) — 7 (вс) */
        public ?array $workTimes = null,
        /** @var array<array{string, string}>|null личные нерабочие дни, «мм.дд» */
        public ?array $nonWorkingDays = null,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            self::toNullableInt($payload['id'] ?? null),
            trim((string) ($payload['name'] ?? '')),
            self::toNullableInt($payload['specialtyId'] ?? null),
            self::toNullableString($payload['storeCode'] ?? null),
            self::toInt($payload['ordering'] ?? 99),
            (bool) ($payload['removePhoto'] ?? false),
            self::toNullableString($payload['photoUrl'] ?? null),
            array_key_exists('photoUrl', $payload),
            self::toWorkTimes($payload['workTimes'] ?? null),
            self::toNonWorkingDays($payload['nonWorkingDays'] ?? null),
        );
    }

    public function getName(): string
    {
        return $this->name;
    }

    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context): void
    {
        if ($this->photoUrlProvided && $this->removePhoto && null !== $this->photoUrl) {
            $context
                ->buildViolation('Photo URL and photo removal cannot be requested together.')
                ->atPath('photoUrl')
                ->addViolation()
            ;
        }

        if (null !== $error = self::validateWorkTimes($this->workTimes)) {
            $context->buildViolation($error)->atPath('workTimes')->addViolation();
        }

        if (null !== $error = self::validateNonWorkingDays($this->nonWorkingDays)) {
            $context->buildViolation($error)->atPath('nonWorkingDays')->addViolation();
        }
    }

    /**
     * Форма присылает расписание в формате хранения: день недели → интервалы.
     * Пустое расписание — общий график компании.
     *
     * @return array<int, array<array{string, string}>>|null
     */
    private static function toWorkTimes(mixed $value): ?array
    {
        if (!is_array($value) || [] === $value) {
            return null;
        }

        $result = [];
        foreach ($value as $day => $periods) {
            $periods = is_array($periods) ? array_values(array_map(self::toPair(...), $periods)) : [];
            $result[(int) $day] = $periods;
        }
        ksort($result);

        return $result;
    }

    /**
     * @return array<array{string, string}>|null
     */
    private static function toNonWorkingDays(mixed $value): ?array
    {
        if (!is_array($value) || [] === $value) {
            return null;
        }

        return array_values(array_map(self::toPair(...), $value));
    }

    /**
     * @return array{string, string}
     */
    private static function toPair(mixed $value): array
    {
        if (!is_array($value)) {
            return ['', ''];
        }
        $value = array_values($value);

        return [trim((string) ($value[0] ?? '')), trim((string) ($value[1] ?? ''))];
    }

    /**
     * @param array<int, array<array{string, string}>>|null $workTimes
     */
    public static function validateWorkTimes(?array $workTimes): ?string
    {
        if (null === $workTimes) {
            return null;
        }

        foreach ($workTimes as $day => $periods) {
            if ($day < 1 || $day > 7) {
                return sprintf('Unknown weekday "%s", expected 1 (Monday) to 7 (Sunday).', $day);
            }
            if ([] === $periods) {
                return 'Working day must have at least one time interval.';
            }

            $minutes = [];
            foreach ($periods as [$start, $end]) {
                $from = self::timeToMinutes($start);
                $to = self::timeToMinutes($end);
                if (null === $from || null === $to) {
                    return sprintf('Bad time interval "%s-%s", expected "10:00-18:00".', $start, $end);
                }
                if ($to <= $from) {
                    return sprintf('Interval "%s-%s": end must be later than start.', $start, $end);
                }
                foreach ($minutes as [$busyFrom, $busyTo]) {
                    if ($from < $busyTo && $busyFrom < $to) {
                        return sprintf('Interval "%s-%s" overlaps another interval of the same day.', $start, $end);
                    }
                }
                $minutes[] = [$from, $to];
            }
        }

        return null;
    }

    /**
     * @param array<array{string, string}>|null $nonWorkingDays
     */
    public static function validateNonWorkingDays(?array $nonWorkingDays): ?string
    {
        if (null === $nonWorkingDays) {
            return null;
        }

        foreach ($nonWorkingDays as [$start, $end]) {
            if (!self::isMonthDay($start) || !self::isMonthDay($end)) {
                return sprintf('Bad days range "%s-%s", expected "mm.dd".', $start, $end);
            }
        }

        return null;
    }

    /** «24:00» допустимо как конец интервала — конец суток, как в расписании системы */
    private static function timeToMinutes(string $time): ?int
    {
        if (!preg_match('/^(\d{1,2}):(\d{2})$/', $time, $m)) {
            return null;
        }
        $hours = (int) $m[1];
        $minutes = (int) $m[2];
        if ($minutes > 59 || $hours > 24 || (24 === $hours && 0 !== $minutes)) {
            return null;
        }

        return $hours * 60 + $minutes;
    }

    private static function isMonthDay(string $value): bool
    {
        if (!preg_match('/^(\d{2})\.(\d{2})$/', $value, $m)) {
            return false;
        }
        $month = (int) $m[1];
        $day = (int) $m[2];
        $daysInMonth = [31, 29, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];

        return $month >= 1 && $month <= 12 && $day >= 1 && $day <= $daysInMonth[$month - 1];
    }

    private static function toNullableInt(mixed $value): ?int
    {
        if (null === $value || '' === $value) {
            return null;
        }

        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && preg_match('/^-?\d+$/', $value)) {
            return (int) $value;
        }

        if (is_float($value) && (int) $value == $value) {
            return (int) $value;
        }

        return (int) $value;
    }

    private static function toInt(mixed $value): int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && preg_match('/^-?\d+$/', $value)) {
            return (int) $value;
        }

        if (is_float($value) && (int) $value == $value) {
            return (int) $value;
        }

        return -1;
    }

    private static function toNullableString(mixed $value): ?string
    {
        if (null === $value || '' === $value) {
            return null;
        }

        $value = trim((string) $value);

        return '' === $value ? null : $value;
    }
}
