<template>
    <div :class="$style.container">
        <UiLoader :class="{ [$style.hide]: !loading }" :overlay="false" />

        <UiError
            v-for="(error, index) in errors"
            :key="index"
            :message="error"
        />

        <div :class="$style.cities">
            <div
                v-for="city in cities"
                :key="city.name"
                :class="[$style.city, { [$style.city_selected]: city.name === selectedCity }]"
            >
                <div :class="$style.info" @click="$emit('select-city', city.name)">
                    <div :class="$style.details">
                        <div :class="$style.name">
                            {{ city.name }}
                        </div>
                        <div :class="$style.branch_count">
                            {{ t('branch', city.branchCount) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { UiLoader, UiError } from '@retailcrm/embed-ui-v1-components/remote'
import { ref, onMounted } from 'vue'
import { useHost } from '@retailcrm/embed-ui'
import type { City } from '../types'

defineProps<{
    selectedCity: string | null,
    t: (key: string, n: number) => string
}>()

defineEmits<{
    (e: 'select-city', city: string): void
}>()

const host = useHost()
const cities = ref<City[]>([])
const loading = ref(false)
const errors = ref<string[]>([])

const loadCities = async () => {
    loading.value = true
    errors.value = []

    const { body, status } = await host.httpCall('/embed/api/cities')
    if (status === 200) {
        cities.value = JSON.parse(body).cities as Array<City>
    } else {
        errors.value = ['Error loading cities: ' + body]
    }

    loading.value = false
}

onMounted(async () => {
    await loadCities()
})
</script>

<style lang="less" module>
.hide {
    display: none !important;
}

@gray-border: #dee2e6;
@blue-transparent: rgba(232, 241, 255, 1);

.container {
    display: flex;
    flex-direction: column;
    gap: 24px;
    font-size: 14px;
}

.cities {
    display: flex;
    flex-direction: column;
    gap: 24px;
}

.city {
    border: 1px solid @gray-border;
    border-radius: 8px;
    overflow: hidden;
}

.city_selected {
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

.branch_count {
    color: #636F7F;
    margin-bottom: 4px;
}
</style>
