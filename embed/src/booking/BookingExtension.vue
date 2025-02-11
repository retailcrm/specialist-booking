<template>
    <div>
        <UiToolbarButton @click="showBookingSidebar = true">
            <IconCalendar class="UiIcon-icon-2pR-" />
            {{ t('button') }}
        </UiToolbarButton>
    
        <UiModalSidebar
            v-model:opened="showBookingSidebar"
            :closable="true"
        >
            <template #title>
                {{ t('title') }}
            </template>
            
            <CitySelector
                v-if="appSettings?.chooseCity && currentView === 'city'"
                :selected-city="selectedCity"
                :t="t"
                @select-city="handleCitySelect"
            />
            <BranchSelector
                v-else-if="(appSettings?.chooseStore || selectedCity) && currentView === 'branch'"
                :city="selectedCity || undefined"
                :selected-branch="selectedBranch"
                :show-back="!!appSettings?.chooseCity"
                :t="t"
                @select-branch="handleBranchSelect"
                @back="currentView = 'city'"
            />
            <SpecialistsList
                v-else-if="currentView === 'specialists'"
                :current-specialist="customFieldSpecialist"
                :t="t"
                :locale="locale"
                :branch-code="selectedBranch || undefined"
                @select-slot="handleSpecialistSlotSelect"
                @select-specialist="handleSpecialistSelect"
                @back="currentView = 'branch'"
            />
            <SpecialistCalendar
                v-else
                :specialist="selectedSpecialist"
                :t="t"
                :locale="locale"
                @select-slot="handleSlotSelect"
                @back="currentView = 'specialists'"
            />

            <template #footer>
                <UiButton appearance="secondary" @click="showBookingSidebar = false">
                    {{ t('close') }}
                </UiButton>
            </template>
        </UiModalSidebar>
    </div>
</template>

<script setup lang="ts">
import { ref, watch, onMounted } from 'vue'
import IconCalendar from  '@retailcrm/embed-ui-v1-components/assets/sprites/actions/calendar-month.svg'
import { UiToolbarButton, UiModalSidebar, UiButton } from '@retailcrm/embed-ui-v1-components/remote'
import { useI18n } from 'vue-i18n'
import { useSettingsContext as useSettings, useField, useCustomField, useHost } from '@retailcrm/embed-ui'
import { useContext } from '@retailcrm/embed-ui-v1-contexts/remote/custom'
import SpecialistsList from './components/SpecialistsList.vue'
import SpecialistCalendar from './components/SpecialistCalendar.vue'
import CitySelector from './components/CitySelector.vue'
import BranchSelector from './components/BranchSelector.vue'
import type { Specialist, Settings } from './types'
import { CustomFieldSpecialistCode, CustomFieldSpecialistDateTimeCode } from './types'

// i18n setup
const settings = useSettings()
const locale = useField(settings, 'system.locale')
settings.initialize()

const i18n = useI18n()
const t = i18n.t

watch(locale, locale => i18n.locale.value = locale, { immediate: true })

// component logic
const showBookingSidebar = ref(false)
const currentView = ref<'city' | 'branch' | 'specialists' | 'calendar'>('city')
const selectedSpecialist = ref<Specialist | null>(null)
const selectedCity = ref<string | null>(null)
const selectedBranch = ref<string | null>(null)
const appSettings = ref<Settings | null>(null)

// custom fields
const custom = useContext('order')
custom.initialize()
const customFieldSpecialist = useCustomField(custom, CustomFieldSpecialistCode, { kind: 'dictionary' })
const customFieldDateTime = useCustomField(custom, CustomFieldSpecialistDateTimeCode, { kind: 'datetime' })

const host = useHost()

const loadSettings = async () => {
    const { body, status } = await host.httpCall('/embed/api/settings')
    if (status === 200) {
        appSettings.value = JSON.parse(body).settings as Settings
        // Set initial view based on settings
        if (appSettings.value.chooseCity) {
            currentView.value = 'city'
        } else if (appSettings.value.chooseStore) {
            currentView.value = 'branch'
        } else {
            currentView.value = 'specialists'
        }
    }
}

const handleCitySelect = (city: string) => {
    selectedCity.value = city
    currentView.value = 'branch'
}

const handleBranchSelect = (code: string) => {
    selectedBranch.value = code
    currentView.value = 'specialists'
}

const handleSpecialistSelect = (specialist: Specialist) => {
    selectedSpecialist.value = specialist
    currentView.value = 'calendar'
}

const handleSpecialistSlotSelect = (specialist: Specialist, date: string, time: string) => {
    selectedSpecialist.value = specialist
    setToCustomFields(date, time)
    showBookingSidebar.value = false
}

const handleSlotSelect = (date: string, time: string) => {
    setToCustomFields(date, time)
    showBookingSidebar.value = false
    currentView.value = appSettings.value?.chooseCity ? 'city' : (appSettings.value?.chooseStore ? 'branch' : 'specialists')
}

const setToCustomFields = (date: string, time: string) => {
    customFieldSpecialist.value = selectedSpecialist.value?.id || null
    customFieldDateTime.value = new Date(`${date}T${time}`).toISOString()
}

onMounted(async () => {
    await loadSettings()
})
</script>

<i18n locale="en-GB">
{
  "button": "Book a specialist",
  "title": "Booking a specialist",
  "back_to_cities": "Back to cities",
  "back_to_branches": "Back to branches",
  "back_to_specialists": "Back to specialists",
  "close": "Close",
  "branch": "no branches|{n} branch|{n} branches",
  "specialist": "no specialists|{n} specialist|{n} specialists"
}
</i18n>

<i18n locale="es-ES">
{
  "button": "Reservar Cita",
  "title": "Reservar Cita",
  "back_to_cities": "Volver a las ciudades",
  "back_to_branches": "Volver a las sucursales",
  "back_to_specialists": "Volver a los especialistas",
  "close": "Cerrar",
  "branch": "no hay sucursales|{n} sucursal|{n} sucursales",
  "specialist": "no hay especialistas|{n} especialista|{n} especialistas"
}
</i18n>

<i18n locale="ru-RU">
{
  "button": "Записать к специалисту",
  "title": "Запись к специалисту",
  "back_to_cities": "Назад к городам",
  "back_to_branches": "Назад к филиалам",
  "back_to_specialists": "Назад к специалистам",
  "close": "Закрыть",
  "branch": "нет филиалов|{n} филиал|{n} филиала|{n} филиалов",
  "specialist": "нет специалистов|{n} специалист|{n} специалиста|{n} специалистов"
}
</i18n>
