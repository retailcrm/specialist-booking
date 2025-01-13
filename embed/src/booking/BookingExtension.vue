<template>
    <UiToolbarButton @click="opened = true">
        {{ t('book') }}
    </UiToolbarButton>
</template>

<script setup lang="ts">
import {
    UiToolbarButton,
} from '@retailcrm/embed-ui-v1-components/remote'

import { ref } from 'vue'

// data
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
