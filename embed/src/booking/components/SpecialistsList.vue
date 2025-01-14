<template>
    <div :class="$style.container">
        <div
            v-for="specialist in specialists"
            :key="specialist.id"
            :class="$style.specialist"
        >
            <div :class="$style.info" @click="$emit('select-specialist', specialist)">
                <img :src="specialist.photo" :class="$style.photo" :alt="specialist.name" />
                <div :class="$style.details">
                    <div :class="$style.name">
                        {{ specialist.name }}
                    </div>
                    <div :class="$style.position">
                        {{ specialist.position }}
                    </div>
                </div>
            </div>

            <div :class="$style.slots">
                <div :class="$style.date">
                    {{ formatDate(specialist.nearestSlots.date) }}
                </div>
                <div :class="$style.times">
                    <UiButton
                        v-for="time in specialist.nearestSlots.slots"
                        :key="time"
                        @click="$emit('select-slot', specialist.id, specialist.nearestSlots.date, time)"
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
import type { Specialist } from '../types'

const props = defineProps<{
    specialists: Specialist[]
    t: (key: string) => string
    locale: string
}>()

defineEmits<{
    (e: 'select-specialist', specialist: Specialist): void
    (e: 'select-slot', specialistId: string, date: string, time: string): void
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

.specialist {
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
