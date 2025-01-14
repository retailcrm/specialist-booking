<template>
    <div :class="$style.container">
        <div
            v-for="barber in barbers"
            :key="barber.id"
            :class="$style.barber"
        >
            <div :class="$style.info" @click="$emit('select-barber', barber)">
                <img :src="barber.photo" :class="$style.photo" :alt="barber.name" />
                <div :class="$style.details">
                    <div :class="$style.name">
                        {{ barber.name }}
                    </div>
                    <div :class="$style.position">
                        {{ barber.position }}
                    </div>
                </div>
            </div>

            <div :class="$style.slots">
                <div :class="$style.date">
                    {{ formatDate(barber.nearestSlots.date) }}
                </div>
                <div :class="$style.times">
                    <UiButton
                        v-for="time in barber.nearestSlots.slots"
                        :key="time"
                        @click="$emit('select-slot', barber.id, barber.nearestSlots.date, time)"
                        appearance="outlined"
                    >
                        {{ time }}
                    </UiButton>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { UiButton } from '@retailcrm/embed-ui-v1-components/remote'
import { format } from 'date-fns'
import { enGB, es, ru } from 'date-fns/locale'
import type { Barber } from '../types'

const props = defineProps<{
    barbers: Barber[]
    t: (key: string) => string
    locale: string
}>()

defineEmits<{
    (e: 'select-barber', barber: Barber): void
    (e: 'select-slot', barberId: string, date: string, time: string): void
}>()

const locales = {
    'en-GB': enGB,
    'es-ES': es,
    'ru-RU': ru,
}

const formatDate = (date: string) => {
    return format(new Date(date), 'EEEE, d MMMM', {
        locale: locales[props.locale as keyof typeof locales],
    })
}
</script>

<style lang="less" module>
@blue-transparent: rgba(232, 241, 255, 1);
@gray-border: #dee2e6;

.container {
  display: flex;
  flex-direction: column;
  gap: 24px;
  font-size: 14px;
}

.barber {
  border: 1px solid @gray-border;
  border-radius: 8px;
  overflow: hidden;
}

.info {
  display: flex;
  padding: 16px;
  gap: 16px;
  cursor: pointer;

  &:hover {
    background: @blue-transparent;
  }
}

.photo {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  object-fit: cover;
}

.details {
  flex: 1;
}

.name {
  font-size: 16px;
  font-weight: 500;
  margin-bottom: 4px;
}

.position {
  color: #636F7F;
  margin-bottom: 4px;
}

.slots {
  padding: 16px;
  border-top: 1px solid @gray-border;
}

.date {
  margin-bottom: 12px;
}

.times {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
}
</style>