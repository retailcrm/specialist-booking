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
            <SpecialistsList
                v-if="currentView === 'specialists'"
                :specialists="specialists"
                :t="t"
                :locale="locale"
                @select-slot="handleSlotSelect"
                @select-specialist="handleSpecialistSelect"
            />
            <SpecialistCalendar
                v-else
                :specialist="selectedSpecialist"
                :available-slots="availableSlots"
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
import { ref, watch } from 'vue'
import IconCalendar from  '@retailcrm/embed-ui-v1-components/assets/sprites/actions/calendar-month.svg'
import { UiToolbarButton, UiModalSidebar, UiButton } from '@retailcrm/embed-ui-v1-components/remote'
import { useI18n } from 'vue-i18n'
import { useSettingsContext as useSettings, useField } from '@retailcrm/embed-ui'
import SpecialistsList from './components/SpecialistsList.vue'
import SpecialistCalendar from './components/SpecialistCalendar.vue'
import type { Specialist } from './types'

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

// Mock data for available slots
const availableSlots = ref<Record<string, string[]>>({
    '2025-01-16': ['11:00', '12:00', '13:00', '14:00', '15:00'],
    '2025-01-17': ['10:00', '11:00', '14:00', '15:00'],
    '2025-01-18': ['11:00', '11:30', '12:00', '12:30', '13:00'],
})

// Mock data for specialists
const specialists = ref<Specialist[]>([
    {
        id: '1',
        name: 'Виталий Князь',
        position: 'Старший мастер',
        photo: '/path/to/photo1.jpg',
        nearestSlots: {
            date: '2024-01-16',
            slots: ['11:00', '12:00', '13:00', '14:00', '15:00'],
        },
    },
    {
        id: '2',
        name: 'Даниил Орлов',
        position: 'Парикмахер',
        photo: '/path/to/photo2.jpg',
        nearestSlots: {
            date: '2024-01-18',
            slots: ['11:00', '11:30', '12:00', '12:30', '13:00'],
        },
    },
])

const handleSpecialistSelect = (specialist: Specialist) => {
    selectedSpecialist.value = specialist
    currentView.value = 'calendar'
}

const handleSlotSelect = (specialistId: string, date: string, time: string) => {
  // Handle slot selection (e.g., save to backend)
    console.log('Selected slot:', { specialistId, date, time })
    showBookingSidebar.value = false
    currentView.value = 'specialists' // Reset view for next opening
}
</script>

<i18n locale="en-GB">
{
  "button": "Book a specialist",
  "title": "Booking a specialist",
  "reviews": "reviews",
  "back": "Back to specialists",
  "close": "Close"
}
</i18n>

<i18n locale="es-ES">
{
  "button": "Reservar Cita",
  "title": "Reservar Cita",
  "reviews": "reseñas",
  "back": "Volver a los especialistas",
  "close": "Cerrar"
}
</i18n>

<i18n locale="ru-RU">
{
  "button": "Записать к специалисту",
  "title": "Запись к специалисту",
  "reviews": "отзывов",
  "back": "Назад к специалистам",
  "close": "Закрыть"
}
</i18n>

<style lang="less" module>
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
