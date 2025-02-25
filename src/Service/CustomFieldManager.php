<?php

namespace App\Service;

use App\Entity\Specialist;
use RetailCrm\Api\Client;
use RetailCrm\Api\Exception\Api\ApiErrorException;
use RetailCrm\Api\Interfaces\ApiExceptionInterface;
use RetailCrm\Api\Interfaces\ClientExceptionInterface;
use RetailCrm\Api\Model\Entity\CustomFields\CustomDictionary;
use RetailCrm\Api\Model\Entity\CustomFields\CustomField;
use RetailCrm\Api\Model\Entity\CustomFields\SerializedCustomDictionaryElement;
use RetailCrm\Api\Model\Request\CustomFields\CustomDictionaryCreateRequest;
use RetailCrm\Api\Model\Request\CustomFields\CustomFieldsCreateRequest;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class CustomFieldManager
{
    private const string ENTITY = 'order';

    public const string CUSTOM_FIELD_SPECIALIST_CODE = 's_booking_specialist';
    public const string CUSTOM_FIELD_DATETIME_CODE = 's_booking_specialist_datetime';

    public function __construct(
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @param ?Specialist[] $specialists
     *
     * @throws ClientExceptionInterface
     * @throws ApiExceptionInterface
     */
    public function ensureCustomFields(Client $client, ?array $specialists = null): void
    {
        // Check and create dictionary if needed
        $dictionaryElements = [];
        if (null === $specialists) {
            $element = new SerializedCustomDictionaryElement();
            $element->code = Specialist::CUSTOM_DICTIONARY_ELEMENT_CODE_PREFIX . '1';
            $element->name = 'None';
            $element->ordering = 1;

            $dictionaryElements[] = $element;
        } else {
            foreach ($specialists as $specialist) {
                $element = new SerializedCustomDictionaryElement();
                $element->code = $specialist->getDictionaryElementCode();
                $element->name = $specialist->getName();
                $element->ordering = $specialist->getOrdering() ?? 99;

                $dictionaryElements[] = $element;
            }
        }

        $dictionary = new CustomDictionary();
        $dictionary->elements = $dictionaryElements;
        $dictionary->code = self::CUSTOM_FIELD_SPECIALIST_CODE;
        $dictionary->name = $this->translator->trans('specialists');

        try {
            $client->customFields->dictionariesGet(self::CUSTOM_FIELD_SPECIALIST_CODE);
            $client->customFields->dictionariesEdit(
                self::CUSTOM_FIELD_SPECIALIST_CODE,
                new CustomDictionaryCreateRequest($dictionary)
            );
        } catch (ApiExceptionInterface $e) {
            if (!$this->isNotFoundError($e)) {
                throw $e;
            }

            $client->customFields->dictionariesCreate(new CustomDictionaryCreateRequest($dictionary));
        }

        // Check and create/update specialist field
        try {
            $specialistField = $client->customFields->get(self::ENTITY, self::CUSTOM_FIELD_SPECIALIST_CODE)->customField;
            $this->updateField($client, $specialistField, [
                'name' => $this->translator->trans('specialist'),
            ]);
        } catch (ApiExceptionInterface $e) {
            if (!$this->isNotFoundError($e)) {
                throw $e;
            }

            $field = new CustomField();
            $field->code = self::CUSTOM_FIELD_SPECIALIST_CODE;
            $field->name = $this->translator->trans('specialist');
            $field->type = 'dictionary';
            $field->dictionary = self::CUSTOM_FIELD_SPECIALIST_CODE;
            $field->entity = 'order';
            $field->displayArea = 'customer';
            $field->ordering = 201;
            $field->viewMode = 'editable';
            $field->inFilter = true;
            $field->inList = true;

            $client->customFields->create(
                $field->entity,
                new CustomFieldsCreateRequest($field)
            );
        }

        // Check and create/update datetime field
        try {
            $datetimeField = $client->customFields->get(self::ENTITY, self::CUSTOM_FIELD_DATETIME_CODE)->customField;
            $this->updateField($client, $datetimeField, [
                'name' => $this->translator->trans('record_time'),
            ]);
        } catch (ApiExceptionInterface $e) {
            if (!$this->isNotFoundError($e)) {
                throw $e;
            }

            $field = new CustomField();
            $field->code = self::CUSTOM_FIELD_DATETIME_CODE;
            $field->name = $this->translator->trans('record_time');
            $field->type = 'datetime';
            $field->entity = 'order';
            $field->displayArea = 'customer';
            $field->ordering = 202;
            $field->viewMode = 'editable';
            $field->inFilter = true;
            $field->inList = true;

            $client->customFields->create(
                $field->entity,
                new CustomFieldsCreateRequest($field)
            );
        }
    }

    /**
     * @param array<string, mixed> $updates
     *
     * @throws ClientExceptionInterface
     * @throws ApiExceptionInterface
     */
    private function updateField(Client $client, CustomField $field, array $updates): void
    {
        $needsUpdate = false;
        foreach ($updates as $property => $value) {
            if ($field->{$property} !== $value) {
                $field->{$property} = $value;
                $needsUpdate = true;
            }
        }

        if ($needsUpdate) {
            $client->customFields->edit(
                $field->entity,
                $field->code,
                new CustomFieldsCreateRequest($field)
            );
        }
    }

    private function isNotFoundError(ApiExceptionInterface $e): bool
    {
        return $e instanceof ApiErrorException && 404 === $e->getStatusCode();
    }
}
