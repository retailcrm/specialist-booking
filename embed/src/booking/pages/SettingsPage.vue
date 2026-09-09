<template>
    <section :class="$style['settings-page']">
        <UiPageHeader :value="t('title')">
            <template #actions>
                <UiButton appearance="primary" size="lg" :disabled="saving" @click="save">
                    {{ saving ? t('saving') : t('save') }}
                </UiButton>
            </template>
        </UiPageHeader>

        <UiLoader v-if="loading" :overlay="false" />
        <UiAlert v-if="error" variant="danger" :text="error" fluid />
        <UiAlert v-if="saved" variant="success" :text="t('saved')" closable />

        <div v-if="!loading" :class="$style['settings-page__panel']">
            <UiField
                id="slot-duration"
                v-slot="{ id, required, disabled, readonly }: FieldSlot"
                :label="t('slot_duration')"
                :hint="t('slot_duration_hint')"
                :invalid="Boolean(fieldErrors.slotDuration)"
            >
                <UiNumberStepper
                    :id="id"
                    v-model:value="settings.slotDuration"
                    :min="15"
                    :max="360"
                    :step="15"
                    :clamp="false"
                    :disabled="disabled"
                    :required="required"
                    :readonly="readonly"
                />
                <UiError
                    v-if="fieldErrors.slotDuration"
                    :class="$style['settings-page__field-error']"
                    :message="fieldErrors.slotDuration"
                />
            </UiField>

            <!-- настройки-переключатели: слева переключатель, справа заголовок и пояснение -->
            <div :class="$style['settings-page__setting']">
                <UiSwitch
                    id="choose-store"
                    v-model:value="settings.chooseStore"
                    :disabled="saving"
                />
                <div :class="$style['settings-page__setting-text']">
                    <label for="choose-store" :class="$style['settings-page__setting-title']">
                        {{ t('choose_store_before') }}
                        <UiLink accent @click.prevent="openStores">{{ t('choose_store_link') }}</UiLink>
                    </label>
                    <span :class="$style['settings-page__setting-hint']">{{ t('choose_store_hint') }}</span>
                </div>
            </div>

            <div :class="[$style['settings-page__setting'], { [$style['settings-page__setting_disabled']]: !settings.chooseStore }]">
                <UiSwitch
                    id="choose-city"
                    v-model:value="settings.chooseCity"
                    :disabled="saving || !settings.chooseStore"
                />
                <div :class="$style['settings-page__setting-text']">
                    <label for="choose-city" :class="$style['settings-page__setting-title']">{{ t('choose_city') }}</label>
                    <span :class="$style['settings-page__setting-hint']">{{ t('choose_city_hint') }}</span>
                </div>
            </div>
        </div>

        <!-- блок для настройщика AI-агента: clientId копируется в параметры действий бронирования -->
        <div v-if="!loading && settings.clientId" :class="$style['settings-page__panel']">
            <div :class="$style['settings-page__section-title']">
                {{ t('agent_title') }}
            </div>
            <div :class="$style['settings-page__hint']">
                {{ t('agent_hint') }}
            </div>

            <UiField
                id="client-id"
                v-slot="{ id }: FieldSlot"
                :label="t('client_id')"
            >
                <div :class="$style['settings-page__copy-row']">
                    <UiTextbox
                        :id="id"
                        :value="settings.clientId"
                        width="fluid"
                        readonly
                    />
                    <UiCopyButton :text="settings.clientId" size="sm">
                        <template #hint>
                            {{ t('copy') }}
                        </template>
                        <template #hint-copied>
                            {{ t('copied') }}
                        </template>
                    </UiCopyButton>
                </div>
            </UiField>
        </div>
    </section>
</template>

<script lang="ts" remote setup>
import type { AdminSettings } from '../types'

import {
    UiAlert,
    UiButton,
    UiError,
    UiCopyButton,
    UiField,
    UiLink,
    UiLoader,
    UiNumberStepper,
    UiPageHeader,
    UiSwitch,
    UiTextbox,
} from '@retailcrm/embed-ui-v1-components/remote'
import { onMounted, ref, watch } from 'vue'
import { AdminApiError, loadAdminSettings, saveAdminSettings } from '../api/adminApi'
import { useHost } from '@retailcrm/embed-ui'
import { useI18n } from 'vue-i18n'

const host = useHost()

// филиалы — склады CRM, заводятся в её настройках
const openStores = () => {
    host.goTo('crm_stores')
}
const { t } = useI18n()

type FieldSlot = {
    id: string
    required: boolean
    disabled: boolean
    readonly: boolean
}

const settings = ref<AdminSettings>({
    slotDuration: 60,
    chooseStore: false,
    chooseCity: false,
    clientId: '',
})
const loading = ref(false)
const saving = ref(false)
const saved = ref(false)
const error = ref('')
const fieldErrors = ref<Record<string, string>>({})
const savedSettingsKey = ref('')

const serializeSettings = (settings: AdminSettings): string => JSON.stringify(settings)

watch(() => settings.value.chooseStore, chooseStore => {
    if (!chooseStore) {
        settings.value.chooseCity = false
    }
})

watch(settings, () => {
    if (saved.value && serializeSettings(settings.value) !== savedSettingsKey.value) {
        saved.value = false
    }
}, { deep: true })

const load = async () => {
    loading.value = true
    error.value = ''
    fieldErrors.value = {}

    try {
        settings.value = await loadAdminSettings(host)
        savedSettingsKey.value = serializeSettings(settings.value)
    } catch (e) {
        error.value = e instanceof Error ? e.message : String(e)
    } finally {
        loading.value = false
    }
}

const save = async () => {
    saving.value = true
    saved.value = false
    error.value = ''
    fieldErrors.value = {}

    try {
        const nextSettings = {
            ...settings.value,
            chooseCity: settings.value.chooseStore && settings.value.chooseCity,
        }

        settings.value = await saveAdminSettings(host, nextSettings)
        savedSettingsKey.value = serializeSettings(settings.value)
        saved.value = true
    } catch (e) {
        if (e instanceof AdminApiError) {
            fieldErrors.value = Object.fromEntries(e.fieldErrors.map(error => [error.path, error.message]))
            error.value = e.fieldErrors.length > 0 ? '' : e.message
        } else {
            error.value = e instanceof Error ? e.message : String(e)
        }
    } finally {
        saving.value = false
    }
}

onMounted(load)
</script>

<i18n locale="en-GB">
{
  "title": "Settings",
  "slot_duration": "Time slot length",
  "slot_duration_hint": "Duration in minutes, from 15 to 360 with a step of 15.",
  "choose_store": "Specialists are distributed by branches",
  "choose_city": "Branches are distributed by cities",
  "save": "Save",
  "saving": "Saving...",
  "saved": "Settings saved",
  "choose_store_hint": "Before choosing a specialist, the user will be asked to pick a branch. Branches are the stores from the CRM «Stores» section.",
  "choose_city_hint": "Before choosing a branch, the user will be asked to pick a city. The city is set in the store settings.",
  "choose_store_before": "Specialists are distributed by",
  "choose_store_link": "branches",
  "agent_title": "AI agent connection",
  "agent_hint": "To use specialist booking in the AI agent, copy the connection identifier below and paste it into the clientId parameter of the booking actions.",
  "client_id": "Connection identifier (clientId)",
  "copy": "Copy",
  "copied": "Copied"
}
</i18n>

<i18n locale="es-ES">
{
  "title": "Configuración",
  "slot_duration": "Duración del intervalo",
  "slot_duration_hint": "Duración en minutos, de 15 a 360 con paso de 15.",
  "choose_store": "Los especialistas están distribuidos por sucursales",
  "choose_city": "Las sucursales están distribuidas por ciudades",
  "save": "Guardar",
  "saving": "Guardando...",
  "saved": "Configuración guardada",
  "choose_store_hint": "Antes de elegir un especialista se pedirá seleccionar una sucursal. Las sucursales son los almacenes de la sección «Almacenes» del CRM.",
  "choose_city_hint": "Antes de elegir una sucursal se pedirá seleccionar una ciudad. La ciudad se indica en la configuración del almacén.",
  "choose_store_before": "Los especialistas están distribuidos por",
  "choose_store_link": "sucursales",
  "agent_title": "Conexión del agente de IA",
  "agent_hint": "Para usar la reserva de especialistas en el agente de IA, copie el identificador de conexión que aparece abajo y péguelo en el parámetro clientId de las acciones de reserva.",
  "client_id": "Identificador de conexión (clientId)",
  "copy": "Copiar",
  "copied": "Copiado"
}
</i18n>

<i18n locale="ru-RU">
{
  "title": "Настройки",
  "slot_duration": "Длительность временного слота",
  "slot_duration_hint": "Длительность в минутах, от 15 до 360 с шагом 15.",
  "choose_store": "Специалисты распределены по филиалам",
  "choose_city": "Филиалы распределены по городам",
  "save": "Сохранить",
  "saving": "Сохраняем...",
  "saved": "Настройки сохранены",
  "choose_store_hint": "Перед выбором специалиста будет предложено выбрать филиал. Филиалы — это склады из раздела «Склады» в CRM.",
  "choose_city_hint": "Перед выбором филиала будет предложено выбрать город. Город указывается в настройках склада.",
  "choose_store_before": "Специалисты распределены по",
  "choose_store_link": "филиалам",
  "agent_title": "Подключение AI-агента",
  "agent_hint": "Для работы с бронированием специалиста в AI агенте скопируйте идентификатор подключения ниже и вставьте его в параметр clientId действий бронирования.",
  "client_id": "Идентификатор подключения (clientId)",
  "copy": "Скопировать",
  "copied": "Скопировано"
}
</i18n>

<style lang="less" module>
@import (reference) "~@retailcrm/embed-ui-v1-components/assets/stylesheets/variables.less";
@import (reference) "~@retailcrm/embed-ui-v1-components/assets/stylesheets/typography.less";
@import (reference) "~@retailcrm/embed-ui-v1-components/assets/stylesheets/geometry.less";
@import (reference) "~@retailcrm/embed-ui-v1-components/assets/stylesheets/palette.less";

.settings-page {
    &__setting {
        display: flex;
        align-items: flex-start;
        gap: @spacing-s;

        &_disabled &-title,
        &_disabled &-hint {
            color: @grey-500;
        }
    }

    &__setting-text {
        display: flex;
        flex-direction: column;
        gap: 2px;
        min-width: 0;
    }

    &__setting-title {
        .text-regular();

        cursor: pointer;
    }

    &__setting-hint {
        .text-small();

        color: @grey-700;
    }

    &__copy-row {
        display: flex;
        align-items: center;
        gap: @spacing-s;
    }

    &__copy-row > :first-child {
        flex: 1;
        min-width: 0;
    }

    &__section-title {
        .text-small-accent();
    }

    &__hint {
        .text-small();

        color: @grey-700;
    }

    .reset-box-sizing();

    display: flex;
    flex-direction: column;
    gap: @spacing-m;
    min-width: 0;

    &__panel {
        display: flex;
        flex-direction: column;
        gap: @spacing-s;
        max-width: 640px;
        padding: @spacing-m @spacing-l @spacing-l;
        background: #fff;
        border: 1px solid @grey-300;
        border-radius: @border-radius-md;
    }

    &__field-error {
        margin-top: @spacing-xs;
    }
}
</style>
