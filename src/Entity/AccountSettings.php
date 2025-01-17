<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use RetailCrm\Api\Model\Entity\Settings\NonWorkingDay;
use RetailCrm\Api\Model\Entity\Settings\Settings;
use RetailCrm\Api\Model\Entity\Settings\WorkTime;

#[ORM\Embeddable]
class AccountSettings
{
    private const array WEEKDAY_MAP = [
        'Monday' => 1,
        'Tuesday' => 2,
        'Wednesday' => 3,
        'Thursday' => 4,
        'Friday' => 5,
        'Saturday' => 6,
        'Sunday' => 7,
    ];

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $locale = null;

    /**
     * @var array<int, array<array{string, string}>>|null
     */
    #[ORM\Column(type: 'json', nullable: true, options: ['jsonb' => true])]
    private ?array $workTimes = null;

    /**
     * @var array<array{string, string}>|null in format {'mm.dd', 'mm.dd'}
     */
    #[ORM\Column(type: 'json', nullable: true, options: ['jsonb' => true])]
    private ?array $nonWorkingDays = null;

    #[ORM\Column(options: ['default' => 60])]
    private int $slotDuration = 60;

    public function setFromCrmSettings(Settings $settings): static
    {
        $this->setLocale($settings->systemLanguage->value);
        if (null !== $settings->workTimes) {
            $this->setWorkTimesFromCrm($settings->workTimes);
        }
        if (null !== $settings->nonWorkingDays) {
            $this->setNonWorkingDaysFromCrm($settings->nonWorkingDays);
        }

        return $this;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function getRequiredLocale(): string
    {
        return $this->locale ?? 'en_GB';
    }

    public function setLocale(?string $locale): static
    {
        $this->locale = null === $locale ? null : mb_strtolower($locale);

        return $this;
    }

    /**
     * @return ?array<int, array<array{string, string}>>
     */
    public function getWorkTimes(): ?array
    {
        return $this->workTimes;
    }

    /**
     * @param array<int, array<array{string, string}>>|null $workTimes
     */
    public function setWorkTimes(?array $workTimes): static
    {
        $this->workTimes = $workTimes;

        return $this;
    }

    /**
     * @param WorkTime[] $workTimes
     */
    public function setWorkTimesFromCrm(array $workTimes): static
    {
        $this->workTimes = [];

        foreach ($workTimes as $systemWorkTime) {
            $day = self::WEEKDAY_MAP[$systemWorkTime->dayType] ?? null;
            if (null === $day) {
                continue;
            }

            if ($systemWorkTime->lunchStartTime && $systemWorkTime->lunchEndTime) {
                $this->workTimes[$day] = [
                    [$systemWorkTime->startTime, $systemWorkTime->lunchStartTime],
                    [$systemWorkTime->lunchEndTime, $systemWorkTime->endTime],
                ];
            } else {
                $this->workTimes[$day] = [[$systemWorkTime->startTime, $systemWorkTime->endTime]];
            }
        }

        return $this;
    }

    /**
     * @return ?array<array{string, string}>
     */
    public function getNonWorkingDays(): ?array
    {
        return $this->nonWorkingDays;
    }

    /**
     * @param array<array{string, string}>|null $nonWorkingDays
     */
    public function setNonWorkingDays(?array $nonWorkingDays): static
    {
        $this->nonWorkingDays = $nonWorkingDays;

        return $this;
    }

    /**
     * @param NonWorkingDay[] $systemDays
     */
    public function setNonWorkingDaysFromCrm(array $systemDays): static
    {
        $this->nonWorkingDays = [];
        foreach ($systemDays as $systemDay) {
            $this->nonWorkingDays[] = [$systemDay->startDate, $systemDay->endDate];
        }

        return $this;
    }

    public function getSlotDuration(): int
    {
        return $this->slotDuration;
    }

    public function setSlotDuration(int $slotDuration): static
    {
        $this->slotDuration = $slotDuration;

        return $this;
    }
}
