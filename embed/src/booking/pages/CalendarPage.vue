<template>
    <section :class="$style['calendar-page']">
        <UiPageHeader :value="t('title')" />

        <div :class="$style['calendar-page__toolbar']">
            <div :class="$style['calendar-page__week']">
                <UiButton appearance="secondary" size="sm" :aria-label="t('prev_week')" @click="shiftWeek(-1)">
                    <IconPrev aria-hidden="true" />
                </UiButton>
                <span :class="$style['calendar-page__week-label']">{{ weekLabel }}</span>
                <UiButton appearance="secondary" size="sm" :aria-label="t('next_week')" @click="shiftWeek(1)">
                    <IconNext aria-hidden="true" />
                </UiButton>
                <UiButton appearance="tertiary" size="sm" @click="goToday">
                    {{ t('today') }}
                </UiButton>
            </div>

            <div :class="$style['calendar-page__filters']">
                <UiField
                    v-if="chooseCity"
                    id="calendar-city"
                    v-slot="{ id }: FieldSlot"
                    :label="t('city')"
                >
                    <UiSelect
                        :id="id"
                        :value="cityFilter"
                        :placeholder="t('all')"
                        clearable
                        width="fluid"
                        @update:value="onCityFilter"
                    >
                        <UiSelectOption
                            v-for="city in cityOptions"
                            :key="city"
                            :value="city"
                            :label="city"
                        />
                    </UiSelect>
                </UiField>

                <UiField
                    v-if="chooseStore"
                    id="calendar-store"
                    v-slot="{ id }: FieldSlot"
                    :label="t('branch')"
                    required
                >
                    <UiSelect
                        :id="id"
                        :value="storeFilter"
                        :placeholder="t('branch_placeholder')"
                        filterable
                        width="fluid"
                        @update:value="onStoreFilter"
                    >
                        <UiSelectOption
                            v-for="store in storeOptions"
                            :key="store.code"
                            :value="store.code"
                            :label="store.city ? `${store.name} (${store.city})` : store.name"
                        />
                    </UiSelect>
                </UiField>

                <UiField
                    id="calendar-specialty"
                    v-slot="{ id }: FieldSlot"
                    :label="t('specialty')"
                >
                    <UiSelect
                        :id="id"
                        :value="specialtyFilter"
                        :placeholder="t('all')"
                        clearable
                        width="fluid"
                        @update:value="onSpecialtyFilter"
                    >
                        <UiSelectOption
                            v-for="specialty in specialtyOptions"
                            :key="specialty.id ?? specialty.name"
                            :value="specialty.id"
                            :label="specialty.name"
                        />
                    </UiSelect>
                </UiField>

                <UiField
                    id="calendar-specialist"
                    v-slot="{ id }: FieldSlot"
                    :label="t('specialist')"
                >
                    <UiSelect
                        :id="id"
                        :value="specialistFilter"
                        :placeholder="t('all')"
                        clearable
                        filterable
                        width="fluid"
                        @update:value="specialistFilter = toNumberOrNull($event)"
                    >
                        <UiSelectOption
                            v-for="row in specialistOptions"
                            :key="row.specialist.id"
                            :value="row.specialist.id"
                            :label="row.specialist.name"
                        />
                    </UiSelect>
                </UiField>
            </div>
        </div>

        <UiAlert v-if="error" variant="danger" :text="error" fluid />

        <UiAlert
            v-if="booked"
            variant="success"
            fluid
        >
            <template #default>
                {{ bookedText }}
                <UiLink accent @click.prevent="openBookedOrder">
                    {{ t('open_order') }}
                </UiLink>
            </template>
        </UiAlert>

        <UiLoader v-if="loading" :overlay="false" />

        <template v-if="!loading">
            <AdminEmptyState
                v-if="!schedule.length"
                :title="t('empty_title')"
                :text="t('empty_text')"
            />
            <AdminEmptyState
                v-else-if="!hourRows.length && windowIsPast"
                :title="t('empty_past_title')"
                :text="t('empty_past_text')"
            />
            <AdminEmptyState
                v-else-if="!hourRows.length"
                :title="t('empty_period_title')"
                :text="t('empty_period_text')"
            />

            <UiTable
                v-else
                :class="[$style['calendar-page__table'], ...weekendColumns.map(n => $style[`calendar-page__table_we-${n}`])]"
                :rows="hourRows"
                :row-key="rowKey"
                bordered
                fixed
            >
                <UiTableColumn v-slot="{ row }: { row: HourRow }" :label="t('hour')" width="88" min-width="88">
                    <span :class="$style['calendar-page__hour']">{{ row.label }}</span>
                </UiTableColumn>

                <UiTableColumn
                    v-for="day in days"
                    :key="day"
                    :label="dayLabel(day)"
                    min-width="150"
                >
                    <template #cell="{ row }: { row: HourRow }">
                        <div :class="$style['calendar-page__cell']">
                            <button
                                v-for="chip in chipsFor(row.hour, day)"
                                :key="chip.key"
                                type="button"
                                :class="$style['calendar-page__chip']"
                                :title="chip.title"
                                @click="openBooking(day, chip.slots)"
                            >
                                {{ chip.label }}
                            </button>

                            <button
                                v-if="cellAt(row.hour, day).bookings.length"
                                type="button"
                                :class="$style['calendar-page__busy']"
                                @click="openBookings(day, row)"
                            >
                                {{ t('bookings_count', cellAt(row.hour, day).bookings.length) }}
                            </button>
                        </div>
                    </template>
                </UiTableColumn>
            </UiTable>
        </template>

        <UiModalSidebar
            v-if="bookingsOpen"
            v-model:opened="bookingsOpen"
            size="lg"
            direction="right"
            role="dialog"
        >
            <template #title>
                {{ bookingsTitle }}
            </template>

            <div :class="$style['calendar-page__list']">
                <div
                    v-for="item in bookingsList"
                    :key="item.booking.orderId"
                    :class="$style['calendar-page__list-item']"
                >
                    <div :class="$style['calendar-page__list-main']">
                        <span :class="$style['calendar-page__list-time']">{{ item.booking.time }}</span>
                        <span :class="$style['calendar-page__specialist-name']">
                            {{ [item.booking.customer, item.booking.phone].filter(Boolean).join(' · ') || '—' }}
                        </span>
                        <span :class="$style['calendar-page__meta']">
                            {{ item.specialist.name }} · {{ specialtyName(item.specialist.specialtyId) }}
                        </span>
                    </div>
                    <div :class="$style['calendar-page__list-aside']">
                        <UiLink accent @click.prevent="openOrder(item.booking.orderId)">
                            {{ item.booking.orderNumber ? t('order_number', { number: item.booking.orderNumber }) : t('open_order') }}
                        </UiLink>
                        <span v-if="item.booking.status" :class="$style['calendar-page__meta']">
                            {{ statusNames[item.booking.status] ?? item.booking.status }}
                        </span>
                    </div>
                </div>
            </div>

            <template #footer>
                <UiButton appearance="secondary" type="button" @click="bookingsOpen = false">
                    {{ t('close') }}
                </UiButton>
            </template>
        </UiModalSidebar>

        <UiModalSidebar
            v-if="bookingOpen"
            v-model:opened="bookingOpen"
            :closable="!saving"
            size="lg"
            direction="right"
            role="dialog"
            :class="$style['calendar-page__drawer']"
        >
            <template #title>
                {{ t('new_booking') }}
            </template>

            <form
                id="calendar-booking-form"
                :class="$style['calendar-page__drawer-body']"
                @submit.prevent="submitBooking"
            >
                <UiAlert v-if="formError" variant="danger" :text="formError" fluid />

                <div :class="$style['calendar-page__summary']">
                    <UiAvatar :src="selectedSlot?.specialist.photoUrl" :name="selectedSlot?.specialist.name ?? ''" size="sm" />
                    <div :class="$style['calendar-page__specialist-text']">
                        <span :class="$style['calendar-page__specialist-name']">
                            {{ selectedSlot ? `${selectedSlot.time} · ${selectedSlot.specialist.name}` : t('time_placeholder') }}
                        </span>
                        <span :class="$style['calendar-page__meta']">{{ draftDateLabel }}</span>
                    </div>
                </div>

                <UiField
                    v-if="draft.options.length > 1"
                    id="calendar-booking-slot"
                    v-slot="{ id, invalid }: FieldSlot"
                    :label="t('slot')"
                    :invalid="Boolean(fieldErrors.time)"
                    required
                >
                    <UiSelect
                        :id="id"
                        :value="draft.slotKey"
                        :placeholder="t('time_placeholder')"
                        width="fluid"
                        :disabled="saving"
                        :invalid="invalid"
                        @update:value="draft.slotKey = toStringOrNull($event)"
                    >
                        <UiSelectOption
                            v-for="option in draft.options"
                            :key="slotKey(option)"
                            :value="slotKey(option)"
                            :label="`${option.time} — ${option.specialist.name}`"
                        />
                    </UiSelect>
                    <UiError
                        v-if="fieldErrors.time"
                        :class="$style['calendar-page__field-error']"
                        :message="fieldErrors.time"
                    />
                </UiField>

                <UiField
                    id="calendar-customer-search"
                    v-slot="{ id }: FieldSlot"
                    :label="t('customer_search')"
                    :hint="t('customer_search_hint')"
                >
                    <UiTextbox
                        :id="id"
                        :value="customerQuery"
                        width="fluid"
                        :placeholder="t('customer_search_placeholder')"
                        :disabled="saving"
                        @update:value="onCustomerQuery"
                    />
                </UiField>

                <div v-if="customerHits.length" :class="$style['calendar-page__hits']">
                    <button
                        v-for="hit in customerHits"
                        :key="hit.id"
                        type="button"
                        :class="$style['calendar-page__hit']"
                        @click="pickCustomer(hit)"
                    >
                        <span>{{ customerLabel(hit) }}</span>
                        <span v-if="hit.phone" :class="$style['calendar-page__meta']">{{ hit.phone }}</span>
                    </button>
                </div>
                <div v-else-if="customerSearched && !customerSearching" :class="$style['calendar-page__meta']">
                    {{ t('customer_not_found') }}
                </div>

                <div v-if="linkedCustomer" :class="$style['calendar-page__tags']">
                    <UiTag removable @remove="unlinkCustomer">
                        {{ t('linked_customer') }}
                        <UiLink accent @click.prevent="openLinkedCustomer">
                            {{ linkedCustomerText }}
                        </UiLink>
                    </UiTag>
                    <span :class="$style['calendar-page__meta']">{{ t('linked_customer_hint') }}</span>
                </div>

                <div :class="$style['calendar-page__grid']">
                    <UiField
                        id="calendar-first-name"
                        v-slot="{ id, invalid }: FieldSlot"
                        :label="t('first_name')"
                        :invalid="Boolean(fieldErrors.firstName)"
                        :required="!linkedCustomer"
                    >
                        <UiTextbox
                            :id="id"
                            :value="draft.firstName"
                            width="fluid"
                            :disabled="saving"
                            :readonly="customerLocked"
                            :invalid="invalid"
                            @update:value="draft.firstName = String($event ?? '')"
                        />
                        <UiError
                            v-if="fieldErrors.firstName"
                            :class="$style['calendar-page__field-error']"
                            :message="fieldErrors.firstName"
                        />
                    </UiField>

                    <UiField
                        id="calendar-last-name"
                        v-slot="{ id, invalid }: FieldSlot"
                        :label="t('last_name')"
                        :invalid="Boolean(fieldErrors.lastName)"
                    >
                        <UiTextbox
                            :id="id"
                            :value="draft.lastName"
                            width="fluid"
                            :disabled="saving"
                            :readonly="customerLocked"
                            :invalid="invalid"
                            @update:value="draft.lastName = String($event ?? '')"
                        />
                    </UiField>

                    <UiField
                        id="calendar-phone"
                        v-slot="{ id, invalid }: FieldSlot"
                        :label="t('phone')"
                        :invalid="Boolean(fieldErrors.phone)"
                        :required="!linkedCustomer"
                    >
                        <UiTextbox
                            :id="id"
                            :value="draft.phone"
                            width="fluid"
                            :disabled="saving"
                            :readonly="customerLocked"
                            :invalid="invalid"
                            @update:value="draft.phone = String($event ?? '')"
                        />
                        <UiError
                            v-if="fieldErrors.phone"
                            :class="$style['calendar-page__field-error']"
                            :message="fieldErrors.phone"
                        />
                    </UiField>

                    <UiField
                        id="calendar-comment"
                        v-slot="{ id }: FieldSlot"
                        :label="t('comment')"
                        :hint="t('comment_hint')"
                    >
                        <UiTextbox
                            :id="id"
                            :value="draft.comment"
                            width="fluid"
                            :disabled="saving"
                            @update:value="draft.comment = String($event ?? '')"
                        />
                    </UiField>
                </div>
            </form>

            <template #footer>
                <div :class="$style['calendar-page__drawer-actions']">
                    <UiButton
                        appearance="primary"
                        variant="success"
                        type="submit"
                        form="calendar-booking-form"
                        :disabled="saving"
                    >
                        {{ saving ? t('saving') : t('book') }}
                    </UiButton>
                    <UiButton appearance="secondary" type="button" :disabled="saving" @click="bookingOpen = false">
                        {{ t('cancel') }}
                    </UiButton>
                </div>
            </template>
        </UiModalSidebar>
    </section>
</template>

<script lang="ts" remote setup>
import type {
    AdminSpecialty,
    BookResponse,
    CalendarBooking,
    CalendarRow,
    CalendarSpecialist,
    CalendarStore,
    CustomerHit,
} from '../types'

import IconNext from '@retailcrm/embed-ui-v1-components/assets/sprites/arrows/chevron-right.svg'
import IconPrev from '@retailcrm/embed-ui-v1-components/assets/sprites/arrows/chevron-left.svg'

import {
    UiAlert,
    UiAvatar,
    UiButton,
    UiError,
    UiField,
    UiLink,
    UiLoader,
    UiModalSidebar,
    UiPageHeader,
    UiSelect,
    UiSelectOption,
    UiTable,
    UiTableColumn,
    UiTag,
    UiTextbox,
} from '@retailcrm/embed-ui-v1-components/remote'
import AdminEmptyState from '../components/AdminEmptyState.vue'
import { computed, onMounted, ref, watch } from 'vue'
import {
    AdminApiError,
    SLOT_NOT_AVAILABLE,
    bookSlot,
    loadCalendar,
    saveCalendarStore,
    searchCustomers,
} from '../api/adminApi'
import { addDays, format, isWeekend, parseISO, startOfDay } from 'date-fns'
import { enGB, es, ru } from 'date-fns/locale'
import { useContext as useCurrentUser } from '@retailcrm/embed-ui-v1-contexts/remote/user/current'
import { useField, useHost, useSettingsContext as useSettings } from '@retailcrm/embed-ui'
import { useI18n } from 'vue-i18n'

type FieldSlot = {
    id: string
    required: boolean
    disabled: boolean
    readonly: boolean
    invalid: boolean
    ariaLabelledby?: string
    ariaInvalid?: 'true'
}

type SlotItem = { time: string, specialist: CalendarSpecialist }
type BookingItem = { booking: CalendarBooking, specialist: CalendarSpecialist }
type Cell = { free: SlotItem[], bookings: BookingItem[] }
type HourRow = { hour: string, label: string }
type Chip = { key: string, label: string, title: string, slots: SlotItem[] }

type BookingDraft = {
    date: string
    options: SlotItem[]
    slotKey: string | null
    customerId: number | null
    firstName: string
    lastName: string
    phone: string
    comment: string
}

const host = useHost()
const i18n = useI18n()
const t = i18n.t

// язык страницы — язык аккаунта, как у виджета в карточке заказа
const settings = useSettings()
const locale = useField(settings, 'system.locale')
settings.initialize()
watch(locale, value => {
    if (value) i18n.locale.value = value
}, { immediate: true })

// id пользователя CRM — ключ для запоминания филиала; контекст заполняется
// асинхронно, поэтому первая загрузка ждёт его, иначе запомненный филиал не придёт
const currentUser = useCurrentUser()
const currentUserId = useField(currentUser, 'id')

const dateLocales = { 'en-GB': enGB, 'es-ES': es, 'ru-RU': ru }
const dateLocale = computed(() => dateLocales[i18n.locale.value as keyof typeof dateLocales] ?? ru)

// окно — семь дней от сегодня: прошедшие дни недели для поиска свободного времени бесполезны
const weekStart = ref<Date>(startOfDay(new Date()))
const days = ref<string[]>([])
const schedule = ref<CalendarRow[]>([])
const specialties = ref<AdminSpecialty[]>([])
const statusNames = ref<Record<string, string>>({})
const loading = ref(false)
const error = ref('')

const chooseStore = ref(false)
const chooseCity = ref(false)
const stores = ref<CalendarStore[]>([])
const cityFilter = ref<string | null>(null)
const storeFilter = ref<string | null>(null)
const specialtyFilter = ref<number | null>(null)
const specialistFilter = ref<number | null>(null)

const toDateKey = (date: Date) => format(date, 'yyyy-MM-dd')

const weekLabel = computed(() => {
    const end = addDays(weekStart.value, 6)
    const sameMonth = weekStart.value.getMonth() === end.getMonth()

    return sameMonth
        ? `${format(weekStart.value, 'd', { locale: dateLocale.value })} – ${format(end, 'd MMMM yyyy', { locale: dateLocale.value })}`
        : `${format(weekStart.value, 'd MMM', { locale: dateLocale.value })} – ${format(end, 'd MMM yyyy', { locale: dateLocale.value })}`
})

const dayLabel = (day: string) => format(parseISO(day), 'EEEEEE, d.MM', { locale: dateLocale.value })

// в прошлом свободных слотов не бывает: пустая сетка там означает только отсутствие записей
const windowIsPast = computed(() => addDays(weekStart.value, 6) < startOfDay(new Date()))

const isWeekendDay = (day: string) => isWeekend(parseISO(day))

// номера столбцов выходных (1 — столбец времени): у столбца таблицы нет своего
// класса, поэтому фон задаётся модификатором на таблице и nth-child по ячейкам
const weekendColumns = computed(() => days.value.flatMap((day, index) => (isWeekendDay(day) ? [index + 2] : [])))

const specialtyName = (specialtyId: number | null): string => (
    specialties.value.find(item => item.id === specialtyId)?.name ?? t('no_specialty')
)

// фильтры применяются на клиенте: неделя грузится целиком, переключение мгновенное.
// При включённом выборе филиала он обязателен и идёт первым: календарь показывает один филиал
const cityOptions = computed(() => [...new Set(stores.value.map(store => store.city).filter((city): city is string => Boolean(city)))])

const storeOptions = computed(() => stores.value.filter(store => cityFilter.value === null || store.city === cityFilter.value))

const storeRows = computed(() => schedule.value.filter(row => (
    !chooseStore.value || row.specialist.storeCode === storeFilter.value
)))

const specialtyOptions = computed(() => specialties.value.filter(specialty => (
    storeRows.value.some(row => row.specialist.specialtyId === specialty.id)
)))

const specialistOptions = computed(() => storeRows.value.filter(row => (
    specialtyFilter.value === null || row.specialist.specialtyId === specialtyFilter.value
)))

const ensureStoreSelected = () => {
    if (!chooseStore.value) return

    if (!storeOptions.value.some(store => store.code === storeFilter.value)) {
        storeFilter.value = storeOptions.value[0]?.code ?? null
    }
    if (specialtyFilter.value !== null && !specialtyOptions.value.some(specialty => specialty.id === specialtyFilter.value)) {
        specialtyFilter.value = null
    }
}

const onCityFilter = (value: unknown) => {
    cityFilter.value = toStringOrNull(value)
    ensureStoreSelected()
}

const onStoreFilter = (value: unknown) => {
    storeFilter.value = toStringOrNull(value)
    ensureStoreSelected()
    const userId = currentUserId.value
    if (typeof userId === 'number' && userId > 0) {
        void saveCalendarStore(host, userId, storeFilter.value).catch(() => undefined)
    }
    if (specialistFilter.value !== null && !specialistOptions.value.some(row => row.specialist.id === specialistFilter.value)) {
        specialistFilter.value = null
    }
}

const visibleRows = computed(() => specialistOptions.value.filter(row => (
    specialistFilter.value === null || row.specialist.id === specialistFilter.value
)))

const onSpecialtyFilter = (value: unknown) => {
    specialtyFilter.value = toNumberOrNull(value)
    if (specialistFilter.value !== null && !specialistOptions.value.some(row => row.specialist.id === specialistFilter.value)) {
        specialistFilter.value = null
    }
}

// --- сетка «час × день»: слоты всех видимых специалистов раскладываются по часу начала
const hourOf = (time: string) => time.slice(0, 2)

const cells = computed(() => {
    const result = new Map<string, Cell>()
    const cellFor = (hour: string, day: string): Cell => {
        const key = `${hour}|${day}`
        let cell = result.get(key)
        if (!cell) {
            cell = { free: [], bookings: [] }
            result.set(key, cell)
        }

        return cell
    }

    for (const row of visibleRows.value) {
        for (const day of days.value) {
            const dayData = row.days[day]
            if (!dayData) continue

            for (const time of dayData.free) {
                cellFor(hourOf(time), day).free.push({ time, specialist: row.specialist })
            }
            for (const booking of dayData.bookings) {
                cellFor(hourOf(booking.time), day).bookings.push({ booking, specialist: row.specialist })
            }
        }
    }

    for (const cell of result.values()) {
        cell.free.sort((a, b) => a.time.localeCompare(b.time) || a.specialist.name.localeCompare(b.specialist.name))
        cell.bookings.sort((a, b) => a.booking.time.localeCompare(b.booking.time))
    }

    return result
})

const EMPTY_CELL: Cell = { free: [], bookings: [] }

const cellAt = (hour: string, day: string): Cell => cells.value.get(`${hour}|${day}`) ?? EMPTY_CELL

// строки — часы от первого до последнего занятого или свободного слота недели
const hourRows = computed<HourRow[]>(() => {
    const hours = [...cells.value.keys()].map(key => Number(key.slice(0, 2)))
    if (!hours.length) return []

    const rows: HourRow[] = []
    for (let hour = Math.min(...hours); hour <= Math.max(...hours); hour++) {
        const hh = String(hour).padStart(2, '0')
        rows.push({ hour: hh, label: `${hh}:00` })
    }

    return rows
})

const rowKey = (row: HourRow) => row.hour

// без фильтров — чип на специализацию со счётчиком, с фильтром по специализации —
// конкретные «время специалист», с фильтром по специалисту — только время
const chipsFor = (hour: string, day: string): Chip[] => {
    const free = cellAt(hour, day).free
    if (!free.length) return []

    if (specialistFilter.value !== null) {
        return free.map(slot => ({ key: slot.time, label: slot.time, title: slot.specialist.name, slots: [slot] }))
    }

    if (specialtyFilter.value !== null) {
        return free.map(slot => ({
            key: `${slot.time}|${slot.specialist.id}`,
            label: `${slot.time} ${slot.specialist.name}`,
            title: slot.specialist.name,
            slots: [slot],
        }))
    }

    const groups = new Map<string, SlotItem[]>()
    for (const slot of free) {
        const key = String(slot.specialist.specialtyId ?? 'none')
        groups.set(key, [...(groups.get(key) ?? []), slot])
    }

    return [...groups.entries()].map(([key, slots]) => ({
        key,
        label: `${specialtyName(slots[0].specialist.specialtyId)} · ${slots.length}`,
        title: slots.map(slot => `${slot.time} ${slot.specialist.name}`).join(', '),
        slots,
    }))
}

const load = async () => {
    loading.value = true
    error.value = ''

    try {
        const userId = typeof currentUserId.value === 'number' ? currentUserId.value : null
        const response = await loadCalendar(host, toDateKey(weekStart.value), toDateKey(addDays(weekStart.value, 6)), userId)
        days.value = response.days
        schedule.value = response.schedule
        specialties.value = response.specialties
        statusNames.value = response.statuses
        chooseStore.value = response.chooseStore
        chooseCity.value = response.chooseCity
        stores.value = response.stores
        // запомненный филиал берётся один раз, при первой загрузке страницы
        if (storeFilter.value === null && response.preferredStore) {
            storeFilter.value = response.preferredStore
        }
        ensureStoreSelected()
    } catch (e) {
        error.value = e instanceof Error ? e.message : String(e)
    } finally {
        loading.value = false
    }
}

const shiftWeek = (weeks: number) => {
    weekStart.value = addDays(weekStart.value, 7 * weeks)
    booked.value = null
    void load()
}

const goToday = () => {
    weekStart.value = startOfDay(new Date())
    booked.value = null
    void load()
}

const openOrder = (orderId: number) => {
    host.goTo('crm_orders_edit', { id: orderId })
}

// --- занятые слоты часа
const bookingsOpen = ref(false)
const bookingsList = ref<BookingItem[]>([])
const bookingsTitle = ref('')

const openBookings = (day: string, row: HourRow) => {
    bookingsList.value = cellAt(row.hour, day).bookings
    bookingsTitle.value = `${format(parseISO(day), 'EEEE, d MMMM', { locale: dateLocale.value })}, ${row.label}`
    bookingsOpen.value = true
}

// --- оформление записи
const bookingOpen = ref(false)
const saving = ref(false)
const formError = ref('')
const fieldErrors = ref<Record<string, string>>({})
const booked = ref<BookResponse | null>(null)

const slotKey = (slot: SlotItem) => `${slot.specialist.id}|${slot.time}`

const createDraft = (): BookingDraft => ({
    date: '',
    options: [],
    slotKey: null,
    customerId: null,
    firstName: '',
    lastName: '',
    phone: '',
    comment: '',
})

const draft = ref<BookingDraft>(createDraft())

const selectedSlot = computed(() => draft.value.options.find(option => slotKey(option) === draft.value.slotKey) ?? null)

const draftDateLabel = computed(() => (
    draft.value.date ? format(parseISO(draft.value.date), 'EEEE, d MMMM', { locale: dateLocale.value }) : ''
))

const bookedText = computed(() => (
    booked.value ? t('booked', { number: booked.value.orderNumber ?? booked.value.orderId }) : ''
))

const openBookedOrder = () => {
    if (booked.value) openOrder(booked.value.orderId)
}

const customerQuery = ref('')
const customerHits = ref<CustomerHit[]>([])
// привязанный клиент CRM: его данные подставляются и не редактируются, чтобы
// запись не разошлась с карточкой; пустое у клиента поле остаётся вводимым
const linkedCustomer = ref<CustomerHit | null>(null)

// данные клиента правятся только в его карточке CRM, здесь они лишь подставляются
const customerLocked = computed(() => linkedCustomer.value !== null)

const linkedCustomerText = computed(() => (linkedCustomer.value ? customerLabel(linkedCustomer.value) : ''))

const openLinkedCustomer = () => {
    if (linkedCustomer.value) host.goTo('crm_customer_edit', { id: linkedCustomer.value.id })
}
const customerSearching = ref(false)
const customerSearched = ref(false)
let customerSearchTimer: ReturnType<typeof setTimeout> | null = null
let customerSearchSeq = 0

// шторка открывается с уже выбранным первым вариантом; выбор остаётся, если вариантов несколько
const openBooking = (day: string, slots: SlotItem[]) => {
    draft.value = {
        ...createDraft(),
        date: day,
        options: slots,
        slotKey: slots[0] ? slotKey(slots[0]) : null,
    }
    customerQuery.value = ''
    customerHits.value = []
    customerSearched.value = false
    linkedCustomer.value = null
    formError.value = ''
    fieldErrors.value = {}
    booked.value = null
    bookingOpen.value = true
}

const onCustomerQuery = (value: unknown) => {
    customerQuery.value = String(value ?? '')
    customerSearched.value = false

    if (customerSearchTimer) clearTimeout(customerSearchTimer)

    const query = customerQuery.value.trim()
    if (query.length < 2) {
        customerHits.value = []
        return
    }

    customerSearchTimer = setTimeout(() => {
        void runCustomerSearch(query)
    }, 300)
}

const runCustomerSearch = async (query: string) => {
    const seq = ++customerSearchSeq
    customerSearching.value = true

    try {
        const hits = await searchCustomers(host, query)
        // ответ на устаревший запрос не должен перекрыть свежий
        if (seq === customerSearchSeq) {
            customerHits.value = hits
            customerSearched.value = true
        }
    } catch (e) {
        if (seq === customerSearchSeq) {
            formError.value = e instanceof Error ? e.message : String(e)
        }
    } finally {
        if (seq === customerSearchSeq) customerSearching.value = false
    }
}

const customerLabel = (hit: CustomerHit) => (
    [hit.lastName, hit.firstName].filter(Boolean).join(' ') || t('customer_without_name', { id: hit.id })
)

const pickCustomer = (hit: CustomerHit) => {
    linkedCustomer.value = hit
    draft.value.customerId = hit.id
    draft.value.lastName = hit.lastName ?? ''
    draft.value.firstName = hit.firstName ?? ''
    draft.value.phone = hit.phone ?? draft.value.phone
    customerHits.value = []
    customerQuery.value = ''
    customerSearched.value = false
    fieldErrors.value = {}
}

const unlinkCustomer = () => {
    linkedCustomer.value = null
    draft.value.customerId = null
}

const validateDraft = (): boolean => {
    fieldErrors.value = {}
    formError.value = ''

    if (!selectedSlot.value) {
        fieldErrors.value = { time: t('required') }
    } else if (!linkedCustomer.value && !draft.value.firstName.trim()) {
        fieldErrors.value = { firstName: t('required') }
    } else if (!linkedCustomer.value && !draft.value.phone.trim()) {
        fieldErrors.value = { phone: t('required') }
    }

    return Object.keys(fieldErrors.value).length === 0
}

// слот заняли, пока форма была открыта: обновляем неделю и оставляем форму
const handleSlotTaken = async () => {
    formError.value = t('slot_taken')
    await load()
    const stillFree = (slot: SlotItem) => {
        const row = schedule.value.find(item => item.specialist.id === slot.specialist.id)

        return row?.days[draft.value.date]?.free.includes(slot.time) ?? false
    }
    draft.value.options = draft.value.options.filter(stillFree)
    if (!draft.value.options.some(option => slotKey(option) === draft.value.slotKey)) {
        draft.value.slotKey = draft.value.options[0] ? slotKey(draft.value.options[0]) : null
    }
}

const handleBookingError = async (e: unknown) => {
    if (e instanceof AdminApiError && e.message === SLOT_NOT_AVAILABLE) {
        await handleSlotTaken()
    } else if (e instanceof AdminApiError) {
        fieldErrors.value = Object.fromEntries(e.fieldErrors.map(item => [item.path, item.message]))
        formError.value = e.fieldErrors.length > 0 ? '' : e.message
    } else {
        formError.value = e instanceof Error ? e.message : String(e)
    }
}

const submitBooking = async () => {
    const slot = selectedSlot.value
    if (saving.value || !validateDraft() || !slot) return

    saving.value = true

    try {
        booked.value = await bookSlot(host, {
            specialistId: slot.specialist.id,
            date: draft.value.date,
            time: slot.time,
            customerId: draft.value.customerId,
            firstName: draft.value.firstName.trim(),
            lastName: draft.value.lastName.trim() || null,
            phone: draft.value.phone.trim(),
            comment: draft.value.comment.trim() || null,
        })
        bookingOpen.value = false
        await load()
    } catch (e) {
        await handleBookingError(e)
    } finally {
        saving.value = false
    }
}

const toNumberOrNull = (value: unknown): number | null => value === null || value === undefined || value === ''
    ? null
    : Number(value)

const toStringOrNull = (value: unknown): string | null => value === null || value === undefined || value === ''
    ? null
    : String(value)

onMounted(async () => {
    try {
        await currentUser.initialize()
    } catch {
        // без пользователя календарь работает, просто не запоминает филиал
    }
    await load()
})
</script>

<i18n locale="en-GB">
{
  "title": "Booking calendar",
  "prev_week": "Previous week",
  "next_week": "Next week",
  "today": "Today",
  "specialty": "Specialty",
  "specialist": "Specialist",
  "all": "All",
  "empty_title": "No specialists yet",
  "empty_text": "Add specialists in the “Specialists” section.",
  "new_booking": "New booking",
  "time": "Time",
  "time_placeholder": "Choose a time",
  "customer_search": "Find customer",
  "customer_search_hint": "Search by name, phone or email among CRM customers; the order is linked to the found customer.",
  "customer_search_placeholder": "Start typing…",
  "customer_not_found": "Nobody found — fill in the details below, a new customer will be created.",
  "customer_without_name": "Customer #{id}",
  "linked_customer": "Linked customer:",
  "linked_customer_hint": "Customer details are edited in the customer card; here only the comment is editable.",
  "last_name": "Last name",
  "first_name": "First name",
  "phone": "Phone",
  "comment": "Comment",
  "book": "Book",
  "saving": "Booking…",
  "cancel": "Cancel",
  "required": "Required field.",
  "slot_taken": "This time has just been taken. Choose another one.",
  "booked": "Booking created, order {number}.",
  "open_order": "Open order",
  "hour": "Time",
  "slot": "Slot",
  "no_specialty": "No specialty",
  "bookings_count": "{count} bookings | {count} booking | {count} bookings",
  "order_number": "Order {number}",
  "close": "Close",
  "comment_hint": "Goes to the customer comment of the order.",
  "city": "City",
  "branch": "Branch",
  "branch_placeholder": "Choose a branch",
  "empty_period_title": "No bookings and no working hours",
  "empty_period_text": "Nothing is booked on these days, and the specialists have no working hours here — check their schedules, or pick another period or branch.",
  "empty_past_title": "No bookings in this period",
  "empty_past_text": "Nobody was booked on these days."
}
</i18n>

<i18n locale="es-ES">
{
  "title": "Calendario de citas",
  "prev_week": "Semana anterior",
  "next_week": "Semana siguiente",
  "today": "Hoy",
  "specialty": "Especialidad",
  "specialist": "Especialista",
  "all": "Todos",
  "empty_title": "Aún no hay especialistas",
  "empty_text": "Añada especialistas en la sección «Especialistas».",
  "new_booking": "Nueva cita",
  "time": "Hora",
  "time_placeholder": "Elija la hora",
  "customer_search": "Buscar cliente",
  "customer_search_hint": "Busque por nombre, teléfono o email entre los clientes del CRM; el pedido se vincula al cliente encontrado.",
  "customer_search_placeholder": "Empiece a escribir…",
  "customer_not_found": "No se encontró a nadie: rellene los datos, se creará un cliente nuevo.",
  "customer_without_name": "Cliente #{id}",
  "linked_customer": "Cliente vinculado:",
  "linked_customer_hint": "Los datos del cliente se editan en su ficha; aquí solo se puede cambiar el comentario.",
  "last_name": "Apellidos",
  "first_name": "Nombre",
  "phone": "Teléfono",
  "comment": "Comentario",
  "book": "Agendar",
  "saving": "Agendando…",
  "cancel": "Cancelar",
  "required": "Campo obligatorio.",
  "slot_taken": "Esta hora acaba de ocuparse. Elija otra.",
  "booked": "Cita creada, pedido {number}.",
  "open_order": "Abrir pedido",
  "hour": "Hora",
  "slot": "Hueco",
  "no_specialty": "Sin especialidad",
  "bookings_count": "{count} citas | {count} cita | {count} citas",
  "order_number": "Pedido {number}",
  "close": "Cerrar",
  "comment_hint": "Va al comentario del cliente en el pedido.",
  "city": "Ciudad",
  "branch": "Sucursal",
  "branch_placeholder": "Elija la sucursal",
  "empty_period_title": "Sin citas y sin horario de trabajo",
  "empty_period_text": "En estos días no hay citas y los especialistas no tienen horario de trabajo: revise sus horarios o elija otro periodo u otra sucursal.",
  "empty_past_title": "Sin citas en este periodo",
  "empty_past_text": "En estos días no hubo citas."
}
</i18n>

<i18n locale="ru-RU">
{
  "title": "Календарь записей",
  "prev_week": "Предыдущая неделя",
  "next_week": "Следующая неделя",
  "today": "Сегодня",
  "specialty": "Специализация",
  "specialist": "Специалист",
  "all": "Все",
  "empty_title": "Специалисты ещё не добавлены",
  "empty_text": "Добавьте специалистов в разделе «Специалисты».",
  "new_booking": "Новая запись",
  "time": "Время",
  "time_placeholder": "Выберите время",
  "customer_search": "Найти клиента",
  "customer_search_hint": "Поиск по имени, телефону или email среди клиентов CRM; заказ привяжется к найденному клиенту.",
  "customer_search_placeholder": "Начните вводить…",
  "customer_not_found": "Никого не нашли — заполните данные ниже, клиент будет создан.",
  "customer_without_name": "Клиент #{id}",
  "linked_customer": "Привязан клиент:",
  "linked_customer_hint": "Данные клиента правятся в его карточке, здесь можно изменить только комментарий.",
  "last_name": "Фамилия",
  "first_name": "Имя",
  "phone": "Телефон",
  "comment": "Комментарий",
  "book": "Записать",
  "saving": "Записываем…",
  "cancel": "Отмена",
  "required": "Обязательное поле.",
  "slot_taken": "Это время только что заняли. Выберите другое.",
  "booked": "Запись создана, заказ {number}.",
  "open_order": "Открыть заказ",
  "hour": "Время",
  "slot": "Вариант",
  "no_specialty": "Без специализации",
  "bookings_count": "{count} записей | {count} запись | {count} записи | {count} записей",
  "order_number": "Заказ {number}",
  "close": "Закрыть",
  "comment_hint": "Попадает в комментарий клиента в заказе.",
  "city": "Город",
  "branch": "Филиал",
  "branch_placeholder": "Выберите филиал",
  "empty_period_title": "Записей нет, рабочего времени тоже",
  "empty_period_text": "В эти дни никто не записан, а у специалистов здесь нет рабочего времени: проверьте их графики или выберите другой период или филиал.",
  "empty_past_title": "За этот период записей нет",
  "empty_past_text": "В эти дни никто не был записан."
}
</i18n>

<style lang="less" module>
@import (reference) "~@retailcrm/embed-ui-v1-components/assets/stylesheets/variables.less";
@import (reference) "~@retailcrm/embed-ui-v1-components/assets/stylesheets/typography.less";
@import (reference) "~@retailcrm/embed-ui-v1-components/assets/stylesheets/geometry.less";
@import (reference) "~@retailcrm/embed-ui-v1-components/assets/stylesheets/palette.less";

.calendar-page {
    .reset-box-sizing();

    display: flex;
    flex-direction: column;
    gap: @spacing-m;
    min-width: 0;

    &__toolbar {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-end;
        justify-content: space-between;
        gap: @spacing-s;
    }

    &__week {
        display: flex;
        align-items: center;
        gap: @spacing-xs;
    }

    &__week-label {
        .text-small-accent();

        min-width: 180px;
        text-align: center;
    }

    &__filters {
        display: flex;
        gap: @spacing-s;
        min-width: 0;

        > * {
            min-width: 200px;
        }
    }

    &__table {
        --ui-v1-table-cell-padding-x: 8px;
        --ui-v1-table-cell-padding-y: 8px;
        --ui-v1-table-rounding: 4px;
    }

    &__hour {
        .text-small-accent();

        color: @grey-700;
    }

    &__summary {
        display: flex;
        align-items: center;
        gap: @spacing-xs;
        min-width: 0;
        padding: @spacing-s;
        background: @grey-100;
        border-radius: @border-radius-md;
    }

    &__specialist-text {
        display: flex;
        flex-direction: column;
        min-width: 0;
    }

    &__specialist-name {
        .text-small-accent();

        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    &__meta {
        .text-tiny();

        color: @grey-700;
    }

    &__cell {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 6px;
        min-width: 0;
    }

    // фон столбца выходного: ячейки чуть темнее обычных, шапка ещё темнее,
    // как и у будних дней шапка темнее тела; цвет подписи не меняется
    .weekend-column(@i) when (@i <= 8) {
        &_we-@{i} :global(td:nth-child(@{i})) {
            background: @grey-100;
        }

        &_we-@{i} :global(th:nth-child(@{i})) {
            background: @grey-300;
        }

        .weekend-column(@i + 1);
    }

    &__table {
        .weekend-column(2);

        // едва заметные разделители дней: у таблицы embed-ui границ между столбцами нет
        :global(th:not(:last-child)),
        :global(td:not(:last-child)) {
            border-right: 1px solid @grey-200;
        }
    }

    &__chip,
    &__busy {
        .reset-box-sizing();
        .text-tiny();

        display: block;
        max-width: 100%;
        padding: 3px 8px;
        border: 0;
        border-radius: @border-radius-sm;
        text-align: left;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        cursor: pointer;
        transition: background-color @transition;
    }

    &__chip {
        background: @blue-100;
        color: @blue-700;

        &:hover {
            background: @blue-200;
        }
    }

    &__busy {
        background: @grey-100;
        color: @grey-700;

        &:hover {
            background: @grey-300;
        }
    }

    &__list {
        display: flex;
        flex-direction: column;
        gap: @spacing-xs;
    }

    &__list-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: @spacing-s;
        padding: @spacing-xs @spacing-s;
        border: 1px solid @grey-300;
        border-radius: @border-radius-md;
    }

    &__list-main {
        display: flex;
        flex-direction: column;
        min-width: 0;
    }

    &__list-aside {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        flex-shrink: 0;
    }

    &__list-time {
        .text-small-accent();
    }

    &__drawer {
        container-type: inline-size;
    }

    &__drawer-body,
    &__grid {
        display: flex;
        flex-direction: column;
        gap: @spacing-s;
    }

    &__hits {
        display: flex;
        flex-direction: column;
        gap: 4px;
        max-height: 220px;
        overflow: auto;
    }

    &__hit {
        .reset-box-sizing();

        display: flex;
        justify-content: space-between;
        gap: @spacing-xs;
        padding: 6px 8px;
        border: 1px solid @grey-300;
        border-radius: @border-radius-sm;
        background: #fff;
        text-align: left;
        cursor: pointer;

        &:hover {
            background: @grey-100;
        }
    }

    &__tags {
        display: flex;
        flex-wrap: wrap;
        gap: @spacing-xs;
    }

    &__field-error {
        margin-top: @spacing-xs;
    }

    &__drawer-actions {
        display: flex;
        gap: @spacing-xs;
        width: 100%;
    }
}
</style>
