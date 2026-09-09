<template>
    <section :class="$style['specialists-page']">
        <UiPageHeader :value="t('title')">
            <template #actions>
                <UiButton appearance="primary" size="lg" @click="openCreate">
                    <IconAdd aria-hidden="true" />
                    {{ t('add') }}
                </UiButton>
            </template>
        </UiPageHeader>

        <UiLoader v-if="loading" :overlay="false" />
        <UiAlert v-if="error" variant="danger" :text="error" fluid />

        <UiTable
            v-if="!loading"
            :class="$style['specialists-page__table']"
            :rows="specialists"
            :row-key="rowKey"
            :row-class="$style['specialists-page__row']"
            bordered
            fixed
            @row:click="onRowClick"
        >
            <UiTableColumn v-slot="{ row }: { row: AdminSpecialist }" :label="t('name')" min-width="220">
                <div :class="$style['specialists-page__name-cell']">
                    <UiAvatar :src="row.photoUrl" :name="row.name" size="sm" />
                    <UiLink
                        accent
                        size="small"
                        :class="$style['specialists-page__title-link']"
                        ellipsis
                        @click.prevent.stop="openEdit(row)"
                    >
                        {{ row.name }}
                    </UiLink>
                </div>
            </UiTableColumn>

            <UiTableColumn v-slot="{ row }: { row: AdminSpecialist }" :label="t('specialty')" min-width="180">
                <span v-if="specialtyName(row)" :class="$style['specialists-page__meta']">
                    {{ specialtyName(row) }}
                </span>
                <span v-else :class="$style['specialists-page__placeholder']">—</span>
            </UiTableColumn>

            <UiTableColumn
                v-if="chooseStore"
                v-slot="{ row }: { row: AdminSpecialist }"
                :label="t('branch')"
                min-width="180"
            >
                <span v-if="storeName(row)" :class="$style['specialists-page__meta']">
                    {{ storeName(row) }}
                </span>
                <span v-else :class="$style['specialists-page__placeholder']">—</span>
            </UiTableColumn>

            <UiTableColumn v-slot="{ row }: { row: AdminSpecialist }" :label="t('schedule')" min-width="140">
                <span :class="$style['specialists-page__meta']">
                    {{ row.workTimes ? t('schedule_own') : t('schedule_company') }}
                </span>
            </UiTableColumn>

            <UiTableColumn
                v-slot="{ row }: { row: AdminSpecialist }"
                :label="t('ordering')"
                width="100"
                min-width="100"
                align="right"
            >
                <span :class="$style['specialists-page__meta']">{{ row.ordering }}</span>
            </UiTableColumn>

            <UiTableColumn
                v-slot="{ row }: { row: AdminSpecialist }"
                label=""
                width="112"
                min-width="112"
                align="right"
            >
                <div :class="$style['specialists-page__actions']">
                    <UiPopperConnector>
                        <UiButton
                            appearance="tertiary"
                            size="sm"
                            :aria-label="t('edit_named', { name: row.name })"
                            @click.stop="openEdit(row)"
                        >
                            <IconEdit aria-hidden="true" />
                        </UiButton>

                        <UiTooltip>{{ t('edit_action') }}</UiTooltip>
                    </UiPopperConnector>

                    <UiPopperConnector>
                        <UiButton
                            appearance="tertiary"
                            variant="danger"
                            size="sm"
                            :aria-label="t('remove_named', { name: row.name })"
                            :disabled="saving"
                            @click.stop="removeRow(row)"
                        >
                            <IconDelete aria-hidden="true" />
                        </UiButton>

                        <UiTooltip>{{ t('remove') }}</UiTooltip>
                    </UiPopperConnector>
                </div>
            </UiTableColumn>

            <template #empty>
                <AdminEmptyState :title="t('empty_title')" :text="t('empty_text')" />
            </template>

            <template #footer-summary="{ rowsCount }: { rowsCount: number }">
                {{ t('items', rowsCount) }}
            </template>
        </UiTable>

        <UiModalSidebar
            v-if="editorOpen"
            v-model:opened="editorOpen"
            :closable="!saving"
            size="lg"
            direction="right"
            role="dialog"
            :class="$style['specialists-page__drawer']"
        >
            <template #title>
                {{ isEditing ? t('edit') : t('create') }}
            </template>

            <form
                id="specialist-editor-form"
                :class="$style['specialists-page__drawer-body']"
                @submit.prevent="submit"
            >
                <UiAlert v-if="formError" variant="danger" :text="formError" fluid />

                <div :class="$style['specialists-page__photo-row']">
                    <UiAvatar :src="previewPhoto" :name="form.name" size="lg" />

                    <div :class="$style['specialists-page__photo-actions']">
                        <UiButton
                            v-if="previewPhoto"
                            appearance="tertiary"
                            variant="danger"
                            type="button"
                            :disabled="saving"
                            @click="clearPhoto"
                        >
                            {{ t('remove_photo') }}
                        </UiButton>
                    </div>
                </div>
                <UiError v-if="photoError" :class="$style['specialists-page__field-error']" :message="photoError" />

                <UiField
                    id="specialist-photo-url"
                    v-slot="{ id, disabled, readonly, invalid, ariaLabelledby, ariaInvalid }: FieldSlot"
                    :label="t('photo_url')"
                    :invalid="Boolean(fieldErrors.photoUrl)"
                >
                    <UiTextbox
                        :id="id"
                        :value="form.photoUrl ?? ''"
                        width="fluid"
                        :disabled="saving || disabled"
                        :readonly="readonly"
                        :invalid="invalid"
                        :input-attributes="{
                            'aria-labelledby': ariaLabelledby,
                            'aria-invalid': ariaInvalid,
                        }"
                        @update:value="updatePhotoUrl"
                    />
                    <UiError
                        v-if="fieldErrors.photoUrl"
                        :class="$style['specialists-page__field-error']"
                        :message="fieldErrors.photoUrl"
                    />
                </UiField>

                <UiField
                    id="specialist-name"
                    v-slot="{ id, required, disabled, readonly, invalid, ariaLabelledby, ariaInvalid }: FieldSlot"
                    :label="t('name')"
                    :invalid="Boolean(fieldErrors.name)"
                    required
                >
                    <UiTextbox
                        :id="id"
                        v-model:value="form.name"
                        width="fluid"
                        :disabled="saving || disabled"
                        :readonly="readonly"
                        :required="required"
                        :invalid="invalid"
                        :input-attributes="{
                            'aria-labelledby': ariaLabelledby,
                            'aria-invalid': ariaInvalid,
                        }"
                    />
                    <UiError
                        v-if="fieldErrors.name"
                        :class="$style['specialists-page__field-error']"
                        :message="fieldErrors.name"
                    />
                </UiField>

                <div :class="$style['specialists-page__grid']">
                    <UiField
                        id="specialist-specialty"
                        v-slot="{ id, disabled, readonly, invalid }: FieldSlot"
                        :label="t('specialty')"
                        :invalid="Boolean(fieldErrors.specialtyId)"
                    >
                        <UiSelect
                            :id="id"
                            :value="form.specialtyId"
                            :placeholder="t('specialty_placeholder')"
                            clearable
                            width="fluid"
                            :disabled="saving || disabled"
                            :readonly="readonly"
                            :invalid="invalid"
                            @update:value="form.specialtyId = toNumberOrNull($event)"
                        >
                            <UiSelectOption
                                v-for="specialty in specialties"
                                :key="specialty.id ?? specialty.name"
                                :value="specialty.id"
                                :label="specialty.name"
                            />
                        </UiSelect>
                        <UiError
                            v-if="fieldErrors.specialtyId"
                            :class="$style['specialists-page__field-error']"
                            :message="fieldErrors.specialtyId"
                        />
                    </UiField>

                    <UiField
                        v-if="chooseStore"
                        id="specialist-store"
                        v-slot="{ id, disabled, readonly, invalid }: FieldSlot"
                        :label="t('branch')"
                        :invalid="Boolean(fieldErrors.storeCode)"
                    >
                        <UiSelect
                            :id="id"
                            :value="form.storeCode"
                            :placeholder="t('branch_placeholder')"
                            clearable
                            width="fluid"
                            :disabled="saving || disabled"
                            :readonly="readonly"
                            :invalid="invalid"
                            @update:value="form.storeCode = toStringOrNull($event)"
                        >
                            <UiSelectOption
                                v-for="store in stores"
                                :key="store.code"
                                :value="store.code"
                                :label="store.name"
                            />
                        </UiSelect>
                        <UiError
                            v-if="fieldErrors.storeCode"
                            :class="$style['specialists-page__field-error']"
                            :message="fieldErrors.storeCode"
                        />
                    </UiField>

                    <UiField
                        id="specialist-ordering"
                        v-slot="{ id, required, disabled, readonly }: FieldSlot"
                        :label="t('ordering')"
                        :hint="t('ordering_hint')"
                        :invalid="Boolean(fieldErrors.ordering)"
                    >
                        <UiNumberStepper
                            :id="id"
                            v-model:value="form.ordering"
                            :min="0"
                            :step="1"
                            :disabled="saving || disabled"
                            :readonly="readonly"
                            :required="required"
                        />
                        <UiError
                            v-if="fieldErrors.ordering"
                            :class="$style['specialists-page__field-error']"
                            :message="fieldErrors.ordering"
                        />
                    </UiField>
                </div>

                <section :class="$style['specialists-page__schedule']" :aria-label="t('schedule')">
                    <div :class="$style['specialists-page__section-title']">
                        {{ t('schedule') }}
                    </div>

                    <label :class="$style['specialists-page__switch']">
                        <UiSwitch
                            :value="ownSchedule"
                            :disabled="saving"
                            @update:value="toggleOwnSchedule"
                        />
                        <span>{{ t('schedule_own_switch') }}</span>
                    </label>
                    <div :class="$style['specialists-page__hint']">
                        {{ ownSchedule ? t('schedule_own_hint') : t('schedule_company_hint') }}
                    </div>

                    <template v-if="ownSchedule">
                        <div
                            v-for="row in scheduleRows"
                            :key="row.key"
                            :class="$style['specialists-page__schedule-row']"
                            role="group"
                            :aria-label="t('schedule_window')"
                        >
                            <UiToggleGroup
                                :model="row.weekdays"
                                :disabled="saving"
                                rubber
                                size="sm"
                                @update:model="updateRow(row, { weekdays: sortWeekdays($event) })"
                            >
                                <UiToggleGroupOption
                                    v-for="weekday in WEEKDAYS"
                                    :key="weekday"
                                    :value="weekday"
                                    :label="t(`weekday_${weekday}`)"
                                />
                            </UiToggleGroup>

                            <div :class="$style['specialists-page__schedule-range']">
                                <UiTimePicker
                                    :value="row.startTime"
                                    :disabled="saving"
                                    :aria-label="t('time_from')"
                                    @update:value="updateRow(row, { startTime: $event || null })"
                                />
                                <span :class="$style['specialists-page__dash']">–</span>
                                <UiTimePicker
                                    :value="endTimeForPicker(row.endTime)"
                                    :disabled="saving"
                                    :aria-label="t('time_to')"
                                    @update:value="updateRow(row, { endTime: endTimeFromPicker($event) })"
                                />
                            </div>

                            <UiButton
                                v-if="scheduleRows.length > 1"
                                appearance="tertiary"
                                variant="danger"
                                size="sm"
                                type="button"
                                :aria-label="t('schedule_remove_window')"
                                :disabled="saving"
                                @click="removeScheduleRow(row)"
                            >
                                <IconDelete aria-hidden="true" />
                            </UiButton>
                        </div>

                        <div :class="$style['specialists-page__hint']">
                            {{ t('end_of_day_hint') }}
                        </div>

                        <UiButton
                            appearance="secondary"
                            size="sm"
                            type="button"
                            :disabled="saving"
                            @click="addRow"
                        >
                            {{ t('schedule_add_window') }}
                        </UiButton>

                        <UiError
                            v-if="scheduleError"
                            :class="$style['specialists-page__field-error']"
                            :message="scheduleError"
                        />
                    </template>
                </section>

                <section :class="$style['specialists-page__schedule']" :aria-label="t('non_working_days')">
                    <div :class="$style['specialists-page__section-title']">
                        {{ t('non_working_days') }}
                    </div>
                    <div :class="$style['specialists-page__hint']">
                        {{ t('non_working_days_hint') }}
                    </div>

                    <div v-if="form.nonWorkingDays?.length" :class="$style['specialists-page__tags']">
                        <UiTag
                            v-for="(period, index) in form.nonWorkingDays"
                            :key="`${period[0]}-${period[1]}-${index}`"
                            removable
                            @remove="removeNonWorkingDay(index)"
                        >
                            {{ formatNonWorkingDay(period) }}
                        </UiTag>
                    </div>

                    <div :class="$style['specialists-page__schedule-range']">
                        <UiTextbox
                            :value="holidayFrom"
                            :placeholder="t('date_placeholder')"
                            :disabled="saving"
                            :invalid="holidayInvalid"
                            :aria-label="`${t('non_working_period')} ${t('time_from')}`"
                            @update:value="onHolidayInput('from', $event)"
                        />
                        <span :class="$style['specialists-page__dash']">–</span>
                        <UiTextbox
                            :value="holidayTo"
                            :placeholder="t('date_placeholder')"
                            :disabled="saving"
                            :invalid="holidayInvalid"
                            :aria-label="`${t('non_working_period')} ${t('time_to')}`"
                            @update:value="onHolidayInput('to', $event)"
                        />
                        <UiButton
                            appearance="secondary"
                            size="sm"
                            type="button"
                            :disabled="saving || !holidayFrom.trim()"
                            @click="addNonWorkingDay"
                        >
                            {{ t('add') }}
                        </UiButton>
                    </div>

                    <UiError
                        v-if="holidayInvalid"
                        :class="$style['specialists-page__field-error']"
                        :message="t('invalid_date')"
                    />
                    <UiError
                        v-if="fieldErrors.nonWorkingDays"
                        :class="$style['specialists-page__field-error']"
                        :message="fieldErrors.nonWorkingDays"
                    />
                </section>
            </form>

            <template #footer>
                <div :class="$style['specialists-page__drawer-actions']">
                    <div :class="$style['specialists-page__drawer-actions-main']">
                        <UiButton
                            appearance="primary"
                            variant="success"
                            type="submit"
                            form="specialist-editor-form"
                            :disabled="saving"
                        >
                            {{ saving ? t('saving') : t('save') }}
                        </UiButton>
                        <UiButton appearance="secondary" type="button" :disabled="saving" @click="closeEditor">
                            {{ t('cancel') }}
                        </UiButton>
                    </div>

                    <div v-if="isEditing" :class="$style['specialists-page__drawer-actions-aside']">
                        <UiPopconfirm
                            :title="t('delete_confirm_title')"
                            ok-variant="danger"
                            @ok="remove"
                        >
                            <template #trigger="{ open: popconfirmOpen }: { open: boolean }">
                                <UiButton
                                    :active="popconfirmOpen"
                                    :aria-label="t('remove')"
                                    appearance="tertiary"
                                    variant="danger"
                                    type="button"
                                    :disabled="saving"
                                >
                                    <IconDelete aria-hidden="true" />
                                </UiButton>
                            </template>

                            {{ t('delete_confirm_text') }}
                        </UiPopconfirm>
                    </div>
                </div>
            </template>
        </UiModalSidebar>
    </section>
</template>

<script lang="ts" remote setup>
import type { AdminSpecialist, AdminSpecialty, Store } from '../types'

import IconAdd from '@retailcrm/embed-ui-v1-components/assets/sprites/actions/add-circle-outlined.svg'
import IconDelete from '@retailcrm/embed-ui-v1-components/assets/sprites/ui/delete-outlined.svg'
import IconEdit from '@retailcrm/embed-ui-v1-components/assets/sprites/ui/edit.svg'

import {
    UiAlert,
    UiAvatar,
    UiButton,
    UiError,
    UiField,
    UiLink,
    UiLoader,
    UiModalSidebar,
    UiNumberStepper,
    UiPageHeader,
    UiPopconfirm,
    UiPopperConnector,
    UiSelect,
    UiSelectOption,
    UiSwitch,
    UiTable,
    UiTableColumn,
    UiTag,
    UiTextbox,
    UiTimePicker,
    UiToggleGroup,
    UiToggleGroupOption,
    UiTooltip,
} from '@retailcrm/embed-ui-v1-components/remote'
import AdminEmptyState from '../components/AdminEmptyState.vue'
import {
    type ScheduleError,
    type ScheduleRow,
    WEEKDAYS,
    createRow,
    endTimeForPicker,
    endTimeFromPicker,
    formatNonWorkingDay,
    parseDayMonth,
    rowsFromWorkTimes,
    validateRows,
    workTimesFromRows,
} from '../schedule'
import { computed, onMounted, ref } from 'vue'
import {
    AdminApiError,
    deleteSpecialist,
    loadSpecialists,
    saveSpecialist,
    type SpecialistsResponse,
} from '../api/adminApi'
import { useHost } from '@retailcrm/embed-ui'
import { useI18n } from 'vue-i18n'

const host = useHost()
const { t } = useI18n()

type FieldSlot = {
    id: string
    required: boolean
    disabled: boolean
    readonly: boolean
    invalid: boolean
    ariaLabelledby?: string
    ariaInvalid?: 'true'
}

type FormState = AdminSpecialist & { previewUrl: string | null }

const specialists = ref<AdminSpecialist[]>([])
const specialties = ref<AdminSpecialty[]>([])
const stores = ref<Store[]>([])
const chooseStore = ref(false)
const loading = ref(false)
const error = ref('')

const editorOpen = ref(false)
const saving = ref(false)
const formError = ref('')
const fieldErrors = ref<Record<string, string>>({})

const createEmptyForm = (): FormState => ({
    id: null,
    name: '',
    specialtyId: null,
    storeCode: null,
    ordering: 99,
    photo: null,
    photoUrl: null,
    workTimes: null,
    nonWorkingDays: null,
    photoUrlChanged: false,
    removePhoto: false,
    previewUrl: null,
})

const form = ref<FormState>(createEmptyForm())

// личное расписание редактируется строками; выключенный переключатель — общий график
const ownSchedule = ref(false)
const scheduleRows = ref<ScheduleRow[]>([])
const scheduleClientError = ref<ScheduleError | null>(null)
const holidayFrom = ref('')
const holidayTo = ref('')
const holidayInvalid = ref(false)

const scheduleErrorMessages: Record<ScheduleError, () => string> = {
    requiredWeekdays: () => t('schedule_error_weekdays'),
    requiredTime: () => t('schedule_error_time'),
    timeOrder: () => t('schedule_error_order'),
    overlap: () => t('schedule_error_overlap'),
}

const scheduleError = computed(() => (
    scheduleClientError.value ? scheduleErrorMessages[scheduleClientError.value]() : fieldErrors.value.workTimes || ''
))

const resetScheduleEditor = (specialist: AdminSpecialist) => {
    ownSchedule.value = null !== specialist.workTimes
    scheduleRows.value = rowsFromWorkTimes(specialist.workTimes)
    scheduleClientError.value = null
    holidayFrom.value = ''
    holidayTo.value = ''
    holidayInvalid.value = false
}

const toggleOwnSchedule = (value: boolean) => {
    ownSchedule.value = value
    scheduleClientError.value = null
    if (value && !scheduleRows.value.length) {
        scheduleRows.value = [createRow([1, 2, 3, 4, 5])]
    }
}

const sortWeekdays = (value: unknown): number[] => (Array.isArray(value) ? value.map(Number) : [])
    .sort((left, right) => left - right)

const updateRow = (row: ScheduleRow, patch: Partial<ScheduleRow>) => {
    Object.assign(row, patch)
    scheduleClientError.value = null
    fieldErrors.value = omitFieldErrors(fieldErrors.value, ['workTimes'])
}

const addRow = () => {
    scheduleRows.value.push(createRow())
}

const removeScheduleRow = (row: ScheduleRow) => {
    scheduleRows.value = scheduleRows.value.filter(item => item.key !== row.key)
    scheduleClientError.value = null
}

const onHolidayInput = (field: 'from' | 'to', value: unknown) => {
    const text = String(value ?? '')
    if (field === 'from') {
        holidayFrom.value = text
    } else {
        holidayTo.value = text
    }
    holidayInvalid.value = false
}

const addNonWorkingDay = () => {
    const from = parseDayMonth(holidayFrom.value)
    const to = holidayTo.value.trim() ? parseDayMonth(holidayTo.value) : from

    if (!from || !to) {
        holidayInvalid.value = true
        return
    }

    form.value.nonWorkingDays = [...(form.value.nonWorkingDays ?? []), [from, to]]
    holidayFrom.value = ''
    holidayTo.value = ''
    fieldErrors.value = omitFieldErrors(fieldErrors.value, ['nonWorkingDays'])
}

const removeNonWorkingDay = (index: number) => {
    const next = (form.value.nonWorkingDays ?? []).filter((_, itemIndex) => itemIndex !== index)
    form.value.nonWorkingDays = next.length ? next : null
}

const isEditing = computed(() => null !== form.value.id)
const previewPhoto = computed(() => form.value.previewUrl)
const photoError = computed(() => fieldErrors.value.photo
    || fieldErrors.value['photo.name']
    || ''
)

const rowKey = (row: AdminSpecialist) => row.id ?? `new-${row.name}`

const applyResponse = (response: SpecialistsResponse) => {
    specialists.value = response.specialists
    specialties.value = response.specialties
    stores.value = response.stores
    chooseStore.value = response.chooseStore
}

const specialtyName = (specialist: AdminSpecialist): string => {
    const specialty = specialties.value.find(item => item.id === specialist.specialtyId)
    return specialty?.name ?? ''
}

const storeName = (specialist: AdminSpecialist): string => {
    if (!specialist.storeCode) {
        return ''
    }
    const store = stores.value.find(item => item.code === specialist.storeCode)
    return store?.name ?? ''
}

const load = async () => {
    loading.value = true
    error.value = ''

    try {
        applyResponse(await loadSpecialists(host))
    } catch (e) {
        error.value = e instanceof Error ? e.message : String(e)
    } finally {
        loading.value = false
    }
}

const openCreate = () => {
    form.value = createEmptyForm()
    resetScheduleEditor(form.value)
    formError.value = ''
    fieldErrors.value = {}
    editorOpen.value = true
}

const openEdit = (specialist: AdminSpecialist) => {
    form.value = {
        ...specialist,
        photoUrlChanged: false,
        removePhoto: false,
        previewUrl: specialist.photoUrl,
        nonWorkingDays: specialist.nonWorkingDays ? specialist.nonWorkingDays.map(period => [...period] as [string, string]) : null,
    }
    resetScheduleEditor(specialist)
    formError.value = ''
    fieldErrors.value = {}
    editorOpen.value = true
}

const onRowClick = ({ row }: { row: AdminSpecialist }) => {
    openEdit(row)
}

const closeEditor = () => {
    if (saving.value) {
        return
    }

    editorOpen.value = false
}

const updatePhotoUrl = (value: unknown) => {
    const photoUrl = toStringOrNull(value)

    form.value.photoUrl = photoUrl
    form.value.previewUrl = photoUrl
    form.value.photoUrlChanged = true
    form.value.removePhoto = false
    formError.value = ''
    fieldErrors.value = omitFieldErrors(fieldErrors.value, ['photo', 'photoUrl'])
}

const clearPhoto = () => {
    form.value.removePhoto = true
    form.value.photoUrl = null
    form.value.photoUrlChanged = false
    form.value.previewUrl = null
    fieldErrors.value = omitFieldErrors(fieldErrors.value, ['photo', 'photoUrl', 'photo.name'])
}

const buildPayload = (name: string): AdminSpecialist => {
    const { previewUrl: _preview, ...payload } = form.value
    void _preview

    return {
        ...payload,
        name,
        workTimes: ownSchedule.value ? workTimesFromRows(scheduleRows.value) : null,
    }
}

const submit = async () => {
    if (saving.value) {
        return
    }

    const name = form.value.name.trim()
    fieldErrors.value = {}
    if ('' === name) {
        fieldErrors.value = { name: t('empty_name') }
        return
    }

    scheduleClientError.value = ownSchedule.value ? validateRows(scheduleRows.value) : null
    if (scheduleClientError.value) {
        return
    }

    saving.value = true
    formError.value = ''

    try {
        applyResponse(await saveSpecialist(host, buildPayload(name)))
        editorOpen.value = false
    } catch (e) {
        if (e instanceof AdminApiError) {
            fieldErrors.value = Object.fromEntries(e.fieldErrors.map(error => [error.path, error.message]))
            formError.value = e.fieldErrors.length > 0 ? '' : e.message
        } else {
            formError.value = e instanceof Error ? e.message : String(e)
        }
    } finally {
        saving.value = false
    }
}

const remove = async () => {
    if (null === form.value.id) {
        return
    }

    await removeById(form.value.id, error => {
        formError.value = error
    }, () => {
        editorOpen.value = false
    })
}

const removeRow = async (specialist: AdminSpecialist) => {
    if (null === specialist.id) {
        return
    }

    await removeById(specialist.id, nextError => {
        error.value = nextError
    }, () => {
        if (editorOpen.value && form.value.id === specialist.id) {
            editorOpen.value = false
        }
    })
}

const removeById = async (
    id: number,
    setError: (error: string) => void,
    onSuccess: () => void
) => {
    saving.value = true
    setError('')
    fieldErrors.value = {}

    try {
        applyResponse(await deleteSpecialist(host, id))
        onSuccess()
    } catch (e) {
        setError(e instanceof Error ? e.message : String(e))
    } finally {
        saving.value = false
    }
}

const toNumberOrNull = (value: unknown): number | null => value === null || value === undefined || value === ''
    ? null
    : Number(value)

const toStringOrNull = (value: unknown): string | null => value === null || value === undefined || value === ''
    ? null
    : String(value).trim() || null

const omitFieldErrors = (errors: Record<string, string>, paths: string[]): Record<string, string> => {
    const next = { ...errors }
    paths.forEach(path => {
        delete next[path]
    })

    return next
}

onMounted(load)
</script>

<i18n locale="en-GB">
{
  "title": "Specialists",
  "name": "Name",
  "specialty": "Specialty",
  "specialty_placeholder": "Not set",
  "branch": "Branch",
  "branch_placeholder": "Not set",
  "ordering": "Ordering",
  "ordering_hint": "Specialists with the lower ordering value go first.",
  "photo_url": "Photo URL",
  "add": "Add",
  "create": "Add specialist",
  "edit": "Edit specialist",
  "remove": "Delete",
  "edit_action": "Edit",
  "edit_named": "Edit {name}",
  "remove_named": "Delete {name}",
  "save": "Save",
  "saving": "Saving...",
  "cancel": "Cancel",
  "delete_confirm_title": "Delete specialist?",
  "delete_confirm_text": "This action cannot be undone.",
  "remove_photo": "Remove photo",
  "empty_title": "No specialists yet",
  "empty_text": "Click \"Add\" to create the first one.",
  "empty_name": "Enter a specialist name.",
  "items": "{count} items | {count} item | {count} items",
  "schedule": "Schedule",
  "schedule_own": "Own",
  "schedule_company": "Company",
  "schedule_own_switch": "Own schedule",
  "schedule_company_hint": "The specialist works by the company working hours from CRM settings.",
  "schedule_own_hint": "Own weekly hours replace the company working hours.",
  "schedule_window": "Working hours",
  "schedule_add_window": "Add hours",
  "schedule_remove_window": "Remove hours",
  "end_of_day_hint": "For hours until the end of the day set 00:00 in the “to” field.",
  "time_from": "from",
  "time_to": "to",
  "weekday_1": "Mon",
  "weekday_2": "Tue",
  "weekday_3": "Wed",
  "weekday_4": "Thu",
  "weekday_5": "Fri",
  "weekday_6": "Sat",
  "weekday_7": "Sun",
  "schedule_error_weekdays": "Select at least one day for every row.",
  "schedule_error_time": "Set start and end time for every row.",
  "schedule_error_order": "End time must be later than start time.",
  "schedule_error_overlap": "Hours of the same day overlap.",
  "non_working_days": "Days off",
  "non_working_days_hint": "Personal days off are added to the company holidays and repeat every year.",
  "non_working_period": "Period",
  "date_placeholder": "dd.mm",
  "invalid_date": "Enter a date as dd.mm."
}
</i18n>

<i18n locale="es-ES">
{
  "title": "Especialistas",
  "name": "Nombre",
  "specialty": "Especialidad",
  "specialty_placeholder": "No establecido",
  "branch": "Sucursal",
  "branch_placeholder": "No establecido",
  "ordering": "Orden",
  "ordering_hint": "Los especialistas con menor valor de orden aparecen primero.",
  "photo_url": "URL de la foto",
  "add": "Añadir",
  "create": "Añadir especialista",
  "edit": "Editar especialista",
  "remove": "Eliminar",
  "edit_action": "Editar",
  "edit_named": "Editar {name}",
  "remove_named": "Eliminar {name}",
  "save": "Guardar",
  "saving": "Guardando...",
  "cancel": "Cancelar",
  "delete_confirm_title": "¿Eliminar especialista?",
  "delete_confirm_text": "Esta acción no se puede deshacer.",
  "remove_photo": "Eliminar foto",
  "empty_title": "Aún no hay especialistas",
  "empty_text": "Haz clic en \"Añadir\" para crear el primero.",
  "empty_name": "Introduce el nombre del especialista.",
  "items": "{count} elementos | {count} elemento | {count} elementos",
  "schedule": "Horario",
  "schedule_own": "Propio",
  "schedule_company": "De la empresa",
  "schedule_own_switch": "Horario propio",
  "schedule_company_hint": "El especialista trabaja según el horario de la empresa de los ajustes del CRM.",
  "schedule_own_hint": "El horario semanal propio sustituye al horario de la empresa.",
  "schedule_window": "Horas de trabajo",
  "schedule_add_window": "Añadir horas",
  "schedule_remove_window": "Quitar horas",
  "end_of_day_hint": "Para trabajar hasta el final del día indique 00:00 en el campo «hasta».",
  "time_from": "desde",
  "time_to": "hasta",
  "weekday_1": "Lu",
  "weekday_2": "Ma",
  "weekday_3": "Mi",
  "weekday_4": "Ju",
  "weekday_5": "Vi",
  "weekday_6": "Sá",
  "weekday_7": "Do",
  "schedule_error_weekdays": "Seleccione al menos un día en cada fila.",
  "schedule_error_time": "Indique la hora de inicio y de fin en cada fila.",
  "schedule_error_order": "La hora de fin debe ser posterior a la de inicio.",
  "schedule_error_overlap": "Las horas del mismo día se solapan.",
  "non_working_days": "Días libres",
  "non_working_days_hint": "Los días libres personales se suman a los festivos de la empresa y se repiten cada año.",
  "non_working_period": "Periodo",
  "date_placeholder": "dd.mm",
  "invalid_date": "Introduzca la fecha como dd.mm."
}
</i18n>

<i18n locale="ru-RU">
{
  "title": "Специалисты",
  "name": "Имя",
  "specialty": "Специализация",
  "specialty_placeholder": "Не задана",
  "branch": "Филиал",
  "branch_placeholder": "Не задан",
  "ordering": "Сортировка",
  "ordering_hint": "Специалисты с меньшим значением сортировки идут первыми.",
  "photo_url": "Ссылка на фото",
  "add": "Добавить",
  "create": "Добавление специалиста",
  "edit": "Редактирование специалиста",
  "remove": "Удалить",
  "edit_action": "Редактировать",
  "edit_named": "Редактировать {name}",
  "remove_named": "Удалить {name}",
  "save": "Сохранить",
  "saving": "Сохраняем...",
  "cancel": "Отмена",
  "delete_confirm_title": "Удалить специалиста?",
  "delete_confirm_text": "Это действие нельзя отменить.",
  "remove_photo": "Удалить фото",
  "empty_title": "Специалисты ещё не добавлены",
  "empty_text": "Нажмите «Добавить», чтобы создать первого.",
  "empty_name": "Введите имя специалиста.",
  "items": "{count} элементов | {count} элемент | {count} элемента | {count} элементов",
  "schedule": "График",
  "schedule_own": "Свой",
  "schedule_company": "Общий",
  "schedule_own_switch": "Своё расписание",
  "schedule_company_hint": "Специалист работает по общему графику компании из настроек CRM.",
  "schedule_own_hint": "Своё недельное расписание заменяет общий график компании.",
  "schedule_window": "Рабочее время",
  "schedule_add_window": "Добавить время",
  "schedule_remove_window": "Убрать время",
  "end_of_day_hint": "Для работы до конца суток укажите 00:00 в поле «до».",
  "time_from": "с",
  "time_to": "до",
  "weekday_1": "Пн",
  "weekday_2": "Вт",
  "weekday_3": "Ср",
  "weekday_4": "Чт",
  "weekday_5": "Пт",
  "weekday_6": "Сб",
  "weekday_7": "Вс",
  "schedule_error_weekdays": "Выберите хотя бы один день в каждой строке.",
  "schedule_error_time": "Укажите время начала и конца в каждой строке.",
  "schedule_error_order": "Время конца должно быть позже времени начала.",
  "schedule_error_overlap": "Интервалы одного дня пересекаются.",
  "non_working_days": "Нерабочие дни",
  "non_working_days_hint": "Личные нерабочие дни добавляются к праздникам компании и повторяются каждый год.",
  "non_working_period": "Период",
  "date_placeholder": "дд.мм",
  "invalid_date": "Введите дату в формате дд.мм."
}
</i18n>

<style lang="less" module>
@import (reference) "~@retailcrm/embed-ui-v1-components/assets/stylesheets/variables.less";
@import (reference) "~@retailcrm/embed-ui-v1-components/assets/stylesheets/typography.less";
@import (reference) "~@retailcrm/embed-ui-v1-components/assets/stylesheets/geometry.less";
@import (reference) "~@retailcrm/embed-ui-v1-components/assets/stylesheets/palette.less";

.specialists-page {
    .reset-box-sizing();

    display: flex;
    flex-direction: column;
    gap: @spacing-m;
    min-width: 0;

    &__row {
        cursor: pointer;
        transition: background-color @transition;

        &:hover {
            background: @grey-100;
        }
    }

    &__table {
        --ui-v1-table-cell-padding-x: 12px;
        --ui-v1-table-cell-padding-y: 12px;
        --ui-v1-table-padding-start: 16px;
        --ui-v1-table-padding-end: 16px;
        --ui-v1-table-rounding: 4px;
        --ui-v1-table-head-cell-padding-block-start: 14px;
        --ui-v1-table-head-cell-padding-block-end: 14px;
        --ui-v1-table-body-cell-padding-block-start: 15px;
        --ui-v1-table-body-cell-padding-block-end: 15px;
    }

    &__actions {
        display: flex;
        justify-content: flex-end;
        gap: @spacing-xs;
    }

    &__name-cell {
        display: flex;
        align-items: center;
        gap: @spacing-xs;
        min-width: 0;
    }

    &__title-link {
        max-width: 100%;
        color: @blue-500;
    }

    &__meta {
        .text-small();

        color: @grey-900;
    }

    &__placeholder {
        color: @grey-700;
    }

    &__drawer {
        container-type: inline-size;
    }

    &__drawer-body {
        display: flex;
        flex-direction: column;
        gap: @spacing-s;
    }

    &__photo-row {
        display: flex;
        gap: @spacing-s;
        align-items: center;
        padding: @spacing-s;
        background: @grey-100;
        border-radius: @border-radius-md;
    }

    &__photo-actions {
        display: flex;
        gap: @spacing-xs;
        flex-wrap: wrap;
        align-items: center;
    }

    &__grid {
        display: flex;
        flex-direction: column;
        gap: @spacing-s;
    }

    &__field-error {
        margin-top: @spacing-xs;
    }

    &__schedule {
        display: flex;
        flex-direction: column;
        gap: @spacing-xs;
        padding-top: @spacing-s;
        border-top: 1px solid @grey-300;
    }

    &__section-title {
        .text-small-accent();
    }

    &__switch {
        display: flex;
        align-items: center;
        gap: @spacing-xs;
        cursor: pointer;
    }

    &__hint {
        .text-small();

        color: @grey-700;
    }

    &__schedule-row {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: @spacing-xs;
    }

    &__schedule-range {
        display: flex;
        align-items: center;
        gap: @spacing-xs;
    }

    &__dash {
        color: @grey-700;
    }

    &__tags {
        display: flex;
        flex-wrap: wrap;
        gap: @spacing-xs;
    }

    &__drawer-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: @spacing-s;
        width: 100%;
    }

    &__drawer-actions-main,
    &__drawer-actions-aside {
        display: flex;
        align-items: center;
    }

    &__drawer-actions-main {
        gap: @spacing-xs;
    }

    &__drawer-actions-aside {
        gap: @spacing-s;
        margin-left: auto;
    }

}

@container (max-width: 520px) {
    .specialists-page {
        &__photo-row {
            flex-direction: column;
            align-items: flex-start;
        }

        &__drawer-actions {
            flex-direction: column;
            align-items: stretch;
        }

        &__drawer-actions-main {
            flex-direction: column;
            align-items: stretch;
        }

        &__drawer-actions-aside {
            justify-content: flex-end;
            margin-left: 0;
        }
    }
}
</style>
