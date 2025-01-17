<template>
    <div>
        <UiToolbarButton @click="showBookingSidebar = true">
            <IconCalendar class="UiIcon-icon-2pR-" />
            {{ t('button') }}
        </UiToolbarButton>
    
        <UiModalSidebar
            v-model:opened="showBookingSidebar"
            :closable="true"
            @update:opened="onSidebarOpened"
        >
            <template #title>
                {{ t('title') }}
            </template>
            
            <UiLoader :class="{ [$style.hide]: !loading }" :overlay="false" />
            
            <UiError
                v-for="(error, index) in errors"
                :key="index"
                :message="error"
            />
            
            <template v-if="errors.length === 0">
                <SpecialistsList
                    v-if="currentView === 'specialists'"
                    :current-specialist="customFieldSpecialist"
                    :specialists="specialists"
                    :t="t"
                    :locale="locale"
                    @select-slot="handleSpecialistSlotSelect"
                    @select-specialist="handleSpecialistSelect"
                />
                <SpecialistCalendar
                    v-else
                    :specialist="selectedSpecialist"
                    :t="t"
                    :locale="locale"
                    @select-slot="handleSlotSelect"
                    @back="currentView = 'specialists'"
                />
            </template>

            <template #footer>
                <UiButton appearance="secondary" @click="showBookingSidebar = false">
                    {{ t('close') }}
                </UiButton>
            </template>
        </UiModalSidebar>
    </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import IconCalendar from  '@retailcrm/embed-ui-v1-components/assets/sprites/actions/calendar-month.svg'
import { UiToolbarButton, UiModalSidebar, UiButton, UiError, UiLoader } from '@retailcrm/embed-ui-v1-components/remote'
import { useI18n } from 'vue-i18n'
import { useSettingsContext as useSettings, useField, useHost, useCustomField } from '@retailcrm/embed-ui'
import { useContext } from '@retailcrm/embed-ui-v1-contexts/remote/custom'
import SpecialistsList from './components/SpecialistsList.vue'
import SpecialistCalendar from './components/SpecialistCalendar.vue'
import type { Specialist } from './types'
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
const currentView = ref<'specialists' | 'calendar'>('specialists')
const selectedSpecialist = ref<Specialist | null>(null)

const host = useHost()

// data
const loading = ref(false)
const specialists = ref<Specialist[]>([])
const errors = ref<string[]>([])

// custom fields
const custom = useContext('order')
custom.initialize()
const customFieldSpecialist = useCustomField(custom, CustomFieldSpecialistCode, { kind: 'dictionary' })
const customFieldDateTime = useCustomField(custom, CustomFieldSpecialistDateTimeCode, { kind: 'datetime' })

const onSidebarOpened = async (opened: boolean) => {
    if (!opened) {
        return
    }
    
    loading.value = true
    
    const { body, status } = await host.httpCall('/embed/api/specialists')
    if (status === 200) {
        specialists.value = JSON.parse(body).specialists as Array<Specialist>
    } else {
        errors.value = ['Error of loading: ' + body]
    }
    
    loading.value = false
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
    currentView.value = 'specialists' // Reset view for next opening
}

const setToCustomFields = (date: string, time: string) => {
    customFieldSpecialist.value = selectedSpecialist.value?.id || null
    customFieldDateTime.value = new Date(`${date}T${time}`).toISOString()
}
</script>

<i18n locale="en-GB">
{
  "button": "Book a specialist",
  "title": "Booking a specialist",
  "back": "Back to specialists",
  "close": "Close"
}
</i18n>

<i18n locale="es-ES">
{
  "button": "Reservar Cita",
  "title": "Reservar Cita",
  "back": "Volver a los especialistas",
  "close": "Cerrar"
}
</i18n>

<i18n locale="ru-RU">
{
  "button": "Записать к специалисту",
  "title": "Запись к специалисту",
  "back": "Назад к специалистам",
  "close": "Закрыть"
}
</i18n>

<style lang="less" module>
.hide {
    display: none !important;
}

.container {
  display: flex;
  flex-direction: column;
}

.modal {
  &__content {
    padding: 20px;
  }
}
</style>
