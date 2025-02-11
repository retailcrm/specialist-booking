<template>
    <UiButton
        v-if="showBack"
        appearance="tertiary"
        :class="$style.back"
        @click="$emit('back')"
    >
        <IconBack class="UiIcon-icon-2pR-" />
        {{ t('back_to_cities') }}
    </UiButton>
    
    <div :class="$style.container">
        <UiLoader :class="{ [$style.hide]: !loading }" :overlay="false" />

        <UiError
            v-for="(error, index) in errors"
            :key="index"
            :message="error"
        />

        <div :class="$style.branches">
            <div
                v-for="branch in branches"
                :key="branch.code"
                :class="[$style.branch, { [$style.branch_selected]: branch.code === selectedBranch }]"
            >
                <div :class="$style.info" @click="$emit('select-branch', branch.code)">
                    <div :class="$style.details">
                        <div :class="$style.name">
                            {{ branch.name }}
                        </div>
                        <div :class="$style.specialist_count">
                            {{ t('specialist', branch.specialistCount) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { UiButton, UiLoader, UiError } from '@retailcrm/embed-ui-v1-components/remote'
import IconBack from '@retailcrm/embed-ui-v1-components/assets/sprites/arrows/arrow-backward.svg'
import { ref, onMounted, watch } from 'vue'
import { useHost } from '@retailcrm/embed-ui'
import type { Branch } from '../types'

const props = defineProps<{
    city?: string
    t: (key: string, n: number | undefined) => string
    selectedBranch: string | null
    showBack: boolean
}>()

defineEmits<{
    (e: 'select-branch', code: string): void
    (e: 'back'): void
}>()

const host = useHost()
const branches = ref<Branch[]>([])
const loading = ref(false)
const errors = ref<string[]>([])

const loadBranches = async () => {
    loading.value = true
    errors.value = []

    const payload = props.city ? { city: props.city } : {}
    const { body, status } = await host.httpCall('/embed/api/branches', payload)
    if (status === 200) {
        branches.value = JSON.parse(body).branches as Array<Branch>
    } else {
        errors.value = ['Error loading branches: ' + body]
    }

    loading.value = false
}

onMounted(async () => {
    await loadBranches()
})

watch(() => props.city, loadBranches)
</script>

<style lang="less" module>
.hide {
    display: none !important;
}

@gray-border: #dee2e6;
@blue-transparent: rgba(232, 241, 255, 1);

.back {
    margin-bottom: 16px;
}

.container {
    display: flex;
    flex-direction: column;
    gap: 24px;
    font-size: 14px;
}

.branches {
    display: flex;
    flex-direction: column;
    gap: 24px;
}

.branch {
    border: 1px solid @gray-border;
    border-radius: 8px;
    overflow: hidden;
}

.branch_selected {
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

.specialist_count {
    color: #636F7F;
    margin-bottom: 4px;
}
</style>
