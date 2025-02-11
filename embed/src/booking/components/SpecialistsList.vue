<template>
    <UiButton
        v-if="showBackButton"
        appearance="tertiary"
        :class="$style.back"
        @click="$emit('back')"
    >
        <IconBack class="UiIcon-icon-2pR-" />
        {{ t('back_to_branches') }}
    </UiButton>
    
    <div :class="$style.container">
        <UiLoader :class="{ [$style.hide]: !loading }" :overlay="false" />

        <UiError
            v-for="(error, index) in errors"
            :key="index"
            :message="error"
        />

        <div
            v-for="specialist in specialists"
            :key="specialist.id"
            :class="[$style.specialist, { [$style.specialist_selected]: specialist.id === currentSpecialist }]"
        >
            <div :class="$style.info" @click="$emit('select-specialist', specialist)">
                <UiAvatar
                    :src="specialist.photo"
                    :size="'lg'"
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

            <div v-if="specialist.nearestSlots" :class="$style.slots">
                <div :class="$style.date">
                    {{ formatDate(specialist.nearestSlots.date) }}
                </div>
                <div :class="$style.times">
                    <UiButton
                        v-for="time in specialist.nearestSlots.slots"
                        :key="time"
                        appearance="outlined"
                        @click="$emit('select-slot', specialist, specialist.nearestSlots.date, time)"
                    >
                        {{ time }}
                    </UiButton>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { UiButton, UiAvatar, UiLoader, UiError } from '@retailcrm/embed-ui-v1-components/remote'
import IconBack from '@retailcrm/embed-ui-v1-components/assets/sprites/arrows/arrow-backward.svg'
import { format } from 'date-fns'
import { enGB, es, ru } from 'date-fns/locale'
import type { Specialist } from '../types'
import { ref, onMounted, computed } from 'vue'
import { useHost } from '@retailcrm/embed-ui'

const props = defineProps<{
    currentSpecialist: string | null
    t: (key: string) => string
    locale: string
    branchCode?: string
}>()

defineEmits<{
    (e: 'select-specialist', specialist: Specialist): void
    (e: 'select-slot', specialist: Specialist, date: string, time: string): void
    (e: 'back'): void
}>()

const showBackButton = computed(() => !!props.branchCode)

const host = useHost()
const specialists = ref<Specialist[]>([])
const loading = ref(false)
const errors = ref<string[]>([])

const loadSpecialists = async () => {
    loading.value = true

    const payload = props.branchCode ? { branch_code: props.branchCode } : {}
    const { body, status } = await host.httpCall('/embed/api/specialists', payload)
    if (status === 200) {
        specialists.value = JSON.parse(body).specialists as Array<Specialist>
    } else {
        errors.value = ['Error of loading: ' + body]
    }

    loading.value = false
}

onMounted(async () => {
    await loadSpecialists()
})

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
.hide {
    display: none !important;
}

@blue-transparent: rgba(232, 241, 255, 1);
@gray-border: #dee2e6;

.back {
    margin-bottom: 16px;
}

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

.specialist_selected {
  border-color: #005EEB;
  box-shadow: 0 0 0 4px rgba(0, 94, 235, 0.2);
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
