<template>
    <div :class="$style.container">
        <UiButton appearance="tertiary" @click="$emit('back')">
            <IconBack class="UiIcon-icon-2pR-" />
            {{ t('back') }}
        </UiButton>

        <div :class="$style.specialist_info">
            <UiAvatar
                :src="specialist.photo"
                :name="specialist.name"
            />
            <div :class="$style.details">
                <div :class="$style.name">
                    {{ specialist.name }}
                </div>
                <div :class="$style.position">
                    {{ specialist.position }}
                </div>
            </div>
        </div>

        <div :class="$style.calendar">
            <div :class="$style.calendar_header">
                <UiButton appearance="tertiary" @click="previousMonth">
                    <IconPrev :class="$style.nav_button_left" />
                </UiButton>
                <span>{{ formatMonth(currentDate) }}</span>
                <UiButton appearance="tertiary" @click="nextMonth">
                    <IconNext class="UiIcon-icon-2pR-" />
                </UiButton>
            </div>

            <div :class="$style.weekdays">
                <div v-for="day in weekDays" :key="day">
                    {{ day }}
                </div>
            </div>

            <div :class="$style.days">
                <div
                    v-for="{ date, isCurrentMonth, isAvailable } in calendarDays"
                    :key="date.toISOString()"
                    :class="[
                        $style.day,
                        { [$style.other_month]: !isCurrentMonth },
                        { [$style.available]: isAvailable }
                    ]"
                    @click="isAvailable && selectDate(date)"
                >
                    {{ date.getDate() }}
                </div>
            </div>
        </div>

        <div v-if="selectedDate" :class="$style.time_slots">
            <div :class="$style.date_header">
                {{ selectedDate && formatDate(selectedDate) }}
            </div>
            <div :class="$style.slots">
                <UiButton
                    v-for="slot in (selectedDate ? availableSlots[formatDateKey(selectedDate)] : [])"
                    :key="slot"
                    appearance="outlined"
                    @click="$emit('select-slot', formatDateKey(selectedDate!), slot)"
                >
                    {{ slot }}
                </UiButton>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import IconBack from '@retailcrm/embed-ui-v1-components/assets/sprites/arrows/arrow-backward.svg'
import IconPrev from '@retailcrm/embed-ui-v1-components/assets/sprites/arrows/chevron-right.svg'
import IconNext from '@retailcrm/embed-ui-v1-components/assets/sprites/arrows/chevron-right.svg'
import { UiButton, UiAvatar } from '@retailcrm/embed-ui-v1-components/remote'
import { ref, computed, onMounted } from 'vue'
import { useHost } from '@retailcrm/embed-ui'
import {
    startOfMonth,
    endOfMonth,
    startOfWeek,
    endOfWeek,
    eachDayOfInterval,
    addMonths,
    subMonths,
    format,
    addDays,
} from 'date-fns'
import { enGB, es, ru } from 'date-fns/locale'
import type { Specialist } from '../types'

const props = defineProps<{
    specialist: Specialist
    t: (key: string) => string
    locale: string
}>()

defineEmits<{
    (e: 'back'): void
    (e: 'select-slot', date: string, time: string): void
}>()

const host = useHost()

const availableSlots = ref<Record<string, string[]>>({})

const currentDate = ref(new Date())
const selectedDate = ref<Date | null>(null)

const locales = {
    'en-GB': enGB,
    'es-ES': es,
    'ru-RU': ru,
}

const loadSlots = async () => {
    const { body, status } = await host.httpCall(`/embed/api/specialists/${props.specialist.id}/slots`, {
        current_date: formatDateKey(currentDate.value),
    })
    if (status === 200) {
        availableSlots.value = JSON.parse(body).slots as Record<string, string[]>
    }
}

const weekDays = computed(() => {
    const days = []
    const date = startOfWeek(new Date(), { weekStartsOn: 1 })
    
    for (let i = 0; i < 7; i++) {
        days.push(format(addDays(date, i), 'EEEEEE', {
            locale: locales[props.locale as keyof typeof locales],
        }))
    }
    
    return days
})

const calendarDays = computed(() => {
    const start = startOfWeek(startOfMonth(currentDate.value), { weekStartsOn: 1 })
    const end = endOfWeek(endOfMonth(currentDate.value), { weekStartsOn: 1 })
  
    return eachDayOfInterval({ start, end }).map(date => ({
        date,
        isCurrentMonth: date.getMonth() === currentDate.value.getMonth(),
        isAvailable: availableSlots.value[formatDateKey(date)]?.length > 0,
    }))
})

const formatMonth = (date: Date) => {
    const monthAndYear = format(date, 'LLLL yyyy', {
        locale: locales[props.locale as keyof typeof locales],
    })
    
    return monthAndYear.charAt(0).toUpperCase() + monthAndYear.slice(1)
}

const formatDate = (date: Date) => format(date, 'EEEE, d MMMM', {
    locale: locales[props.locale as keyof typeof locales],
})

const formatDateKey = (date: Date) => format(date, 'yyyy-MM-dd')

const previousMonth = async () => {
    currentDate.value = subMonths(currentDate.value, 1)
    selectedDate.value = null
    await loadSlots()
}

const nextMonth = async () => {
    currentDate.value = addMonths(currentDate.value, 1)
    selectedDate.value = null
    await loadSlots()
}

const selectDate = (date: Date) => {
    selectedDate.value = date
}

onMounted(async () => {
    await loadSlots()
})
</script>

<style lang="less" module>
@blue-transparent: rgba(232, 241, 255, 1);
@gray-border: #dee2e6;

.container {
  font-size: 14px;
}

.specialist_info {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-top: 16px;
  margin-bottom: 24px;
}

.name {
  font-size: 16px;
  font-weight: 500;
}

.position {
  color: #636F7F;
  font-size: 14px;
}

.calendar {
  border: 1px solid @gray-border;
  border-radius: 8px;
  overflow: hidden;
}

.calendar_header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px;
  font-weight: 500;
  font-size: 16px;
}

.nav_button_left {
  transform: rotate(180deg);
}

.weekdays {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  text-align: center;
  padding: 8px;
  color: #8A96A6;
}

.days {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  padding: 8px;
  gap: 4px;
}

.day {
  aspect-ratio: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 4px;

  &.other_month {
    color: #C7CDD4;
  }

  &.available {
    cursor: pointer;
    background: @blue-transparent;

    &:hover {
      background: @gray-border;
    }
  }
}

.time_slots {
  margin-top: 24px;
}

.date_header {
  margin-bottom: 12px;
  font-weight: 500;
  font-size: 16px;
}

.slots {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
}
</style>
