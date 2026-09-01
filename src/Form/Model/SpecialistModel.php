<?php

namespace App\Form\Model;

use App\Entity\Specialist;
use App\Entity\Specialty;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class SpecialistModel
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 255)]
    public string $name;

    public ?Specialty $specialty = null;

    #[Assert\Range(min: 0, max: 9999)]
    public int $ordering = 99;

    public ?string $photo = null;

    public ?UploadedFile $photoFile = null;

    public ?string $storeCode = null;

    /** Личное недельное расписание, по строке на день: "1: 10:00-13:00, 14:00-18:00". */
    public ?string $workTimesText = null;

    /** Личные нерабочие дни: "мм.дд" или "мм.дд-мм.дд" через запятую. */
    public ?string $nonWorkingDaysText = null;

    public function __construct(public ?int $id = null)
    {
    }

    public static function fromSpecialist(Specialist $s): self
    {
        $specialistModel = new self($s->getId());
        $specialistModel->name = $s->getName();
        $specialistModel->specialty = $s->getSpecialty();
        $specialistModel->ordering = $s->getOrdering() ?? 99;
        $specialistModel->photo = $s->getPhoto();
        $specialistModel->storeCode = $s->getStoreCode();
        $specialistModel->workTimesText = self::formatWorkTimes($s->getWorkTimes());
        $specialistModel->nonWorkingDaysText = self::formatNonWorkingDays($s->getNonWorkingDays());

        return $specialistModel;
    }

    #[Assert\Callback]
    public function validateSchedule(ExecutionContextInterface $context): void
    {
        try {
            self::parseWorkTimes($this->workTimesText);
        } catch (\InvalidArgumentException $e) {
            $context->buildViolation($e->getMessage())
                ->atPath('workTimesText')
                ->addViolation()
            ;
        }

        try {
            self::parseNonWorkingDays($this->nonWorkingDaysText);
        } catch (\InvalidArgumentException $e) {
            $context->buildViolation($e->getMessage())
                ->atPath('nonWorkingDaysText')
                ->addViolation()
            ;
        }
    }

    /**
     * @param array<int, array<array{string, string}>>|null $workTimes
     */
    public static function formatWorkTimes(?array $workTimes): ?string
    {
        if (null === $workTimes || [] === $workTimes) {
            return null;
        }

        ksort($workTimes);
        $lines = [];
        foreach ($workTimes as $day => $periods) {
            $lines[] = $day . ': ' . implode(', ', array_map(
                static fn (array $period) => $period[0] . '-' . $period[1],
                $periods
            ));
        }

        return implode("\n", $lines);
    }

    /**
     * Формат: по строке на день, "день: интервалы", день 1 (пн) — 7 (вс),
     * интервалы "10:00-13:00, 14:00-18:00". Пусто — общий график.
     *
     * @return array<int, array<array{string, string}>>|null
     */
    public static function parseWorkTimes(?string $text): ?array
    {
        $text = trim((string) $text);
        if ('' === $text) {
            return null;
        }

        $result = [];
        foreach (preg_split('/\R/', $text) ?: [] as $line) {
            $line = trim($line);
            if ('' === $line) {
                continue;
            }

            if (!preg_match('/^([1-7])\s*:\s*(.+)$/', $line, $matches)) {
                throw new \InvalidArgumentException(sprintf('Bad schedule line "%s", expected "1: 10:00-18:00"', $line));
            }

            $day = (int) $matches[1];
            $periods = [];
            foreach (explode(',', $matches[2]) as $interval) {
                $interval = trim($interval);
                if (!preg_match('/^(\d{1,2}:\d{2})\s*-\s*(\d{1,2}:\d{2})$/', $interval, $times)) {
                    throw new \InvalidArgumentException(sprintf('Bad time interval "%s", expected "10:00-18:00"', $interval));
                }
                $periods[] = [$times[1], $times[2]];
            }

            $result[$day] = $periods;
        }

        return [] === $result ? null : $result;
    }

    /**
     * @param array<array{string, string}>|null $nonWorkingDays
     */
    public static function formatNonWorkingDays(?array $nonWorkingDays): ?string
    {
        if (null === $nonWorkingDays || [] === $nonWorkingDays) {
            return null;
        }

        return implode(', ', array_map(
            static fn (array $range) => $range[0] === $range[1] ? $range[0] : $range[0] . '-' . $range[1],
            $nonWorkingDays
        ));
    }

    /**
     * Формат: "мм.дд" или "мм.дд-мм.дд" через запятую, как нерабочие дни
     * системы. Пусто — без личных нерабочих дней.
     *
     * @return array<array{string, string}>|null
     */
    public static function parseNonWorkingDays(?string $text): ?array
    {
        $text = trim((string) $text);
        if ('' === $text) {
            return null;
        }

        $result = [];
        foreach (explode(',', $text) as $range) {
            $range = trim($range);
            if ('' === $range) {
                continue;
            }

            if (!preg_match('/^(\d{2}\.\d{2})\s*(?:-\s*(\d{2}\.\d{2}))?$/', $range, $matches)) {
                throw new \InvalidArgumentException(sprintf('Bad days range "%s", expected "mm.dd" or "mm.dd-mm.dd"', $range));
            }

            $result[] = [$matches[1], $matches[2] ?? $matches[1]];
        }

        return [] === $result ? null : $result;
    }
}
