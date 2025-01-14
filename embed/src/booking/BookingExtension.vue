<template>
    <UiToolbarButton @click="opened = true">
        {{ t('book') }}
    </UiToolbarButton>
</template>

<script setup lang="ts">
import {
    UiToolbarButton,
} from '@retailcrm/embed-ui-v1-components/remote'

import { useSettingsContext as useSettings } from '@retailcrm/embed-ui'

import {
    useField,
} from '@retailcrm/embed-ui'

import { useI18n } from 'vue-i18n'
import { ref, watch } from 'vue'

// ### LOCALE ###

const settings = useSettings()
const locale = useField(settings, 'system.locale')

settings.initialize()

const i18n = useI18n()
const t = i18n.t

watch(locale, locale => i18n.locale.value = locale, { immediate: true })

// ### DATA ###

const opened = ref(false)

</script>

<i18n locale="en-GB">
{
    "book": "Book"
}
</i18n>

<i18n locale="es-ES">
{
    "book": "Reservar"
}
</i18n>

<i18n locale="ru-RU">
{
    "book": "Записаться"
}
</i18n>

<style lang="less" module>
.cell {
    border: 1px solid #C9C9C9;
}
</style>
