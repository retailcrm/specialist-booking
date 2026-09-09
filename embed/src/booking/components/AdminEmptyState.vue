<template>
    <div :class="$style['admin-empty-state']">
        <h3 :class="$style['admin-empty-state__title']">
            {{ title }}
        </h3>
        <p :class="$style['admin-empty-state__text']">
            {{ text }}
        </p>
    </div>
</template>

<script lang="ts" remote setup>
defineProps<{
    title: string
    text: string
}>()
</script>

<style lang="less" module>
@import (reference) "~@retailcrm/embed-ui-v1-components/assets/stylesheets/variables.less";
@import (reference) "~@retailcrm/embed-ui-v1-components/assets/stylesheets/typography.less";
@import (reference) "~@retailcrm/embed-ui-v1-components/assets/stylesheets/geometry.less";
@import (reference) "~@retailcrm/embed-ui-v1-components/assets/stylesheets/palette.less";

.admin-empty-state {
    display: flex;
    flex-direction: column;
    gap: @spacing-xs;
    padding: @spacing-l;
    background: @grey-100;
    border-radius: @border-radius-md;
    text-align: center;

    &__title {
        .h4-accent();

        margin: 0;
    }

    &__text {
        .text-small();

        margin: 0;
        color: @grey-900;
    }
}
</style>
