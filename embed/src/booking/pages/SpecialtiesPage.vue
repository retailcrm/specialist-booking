<template>
    <section :class="$style['specialties-page']">
        <UiPageHeader :value="t('title')">
            <template #actions>
                <UiButton appearance="primary" size="lg" @click="openCreate">
                    <IconAdd aria-hidden="true" />
                    {{ t('add') }}
                </UiButton>
            </template>
        </UiPageHeader>

        <UiLoader v-if="loading" :overlay="false" />
        <UiAlert v-if="error" variant="danger" :text="error" fluid />

        <UiTable
            v-if="!loading"
            :class="$style['specialties-page__table']"
            :rows="specialties"
            :row-key="rowKey"
            :row-class="$style['specialties-page__row']"
            bordered
            fixed
            @row:click="onRowClick"
        >
            <UiTableColumn v-slot="{ row }: { row: AdminSpecialty }" :label="t('name')" min-width="220">
                <UiLink
                    accent
                    size="small"
                    :class="$style['specialties-page__title-link']"
                    @click.prevent.stop="openEdit(row)"
                >
                    {{ row.name }}
                </UiLink>
            </UiTableColumn>

            <UiTableColumn
                v-slot="{ row }: { row: AdminSpecialty }"
                label=""
                width="112"
                min-width="112"
                align="right"
            >
                <div :class="$style['specialties-page__actions']">
                    <UiPopperConnector>
                        <UiButton
                            appearance="tertiary"
                            size="sm"
                            :aria-label="t('edit_named', { name: row.name })"
                            @click.stop="openEdit(row)"
                        >
                            <IconEdit aria-hidden="true" />
                        </UiButton>

                        <UiTooltip>{{ t('edit_action') }}</UiTooltip>
                    </UiPopperConnector>

                    <UiPopperConnector>
                        <UiButton
                            appearance="tertiary"
                            variant="danger"
                            size="sm"
                            :aria-label="t('remove_named', { name: row.name })"
                            :disabled="saving"
                            @click.stop="removeRow(row)"
                        >
                            <IconDelete aria-hidden="true" />
                        </UiButton>

                        <UiTooltip>{{ t('remove') }}</UiTooltip>
                    </UiPopperConnector>
                </div>
            </UiTableColumn>

            <template #empty>
                <AdminEmptyState :title="t('empty_title')" :text="t('empty_text')" />
            </template>

            <template #footer-summary="{ rowsCount }: { rowsCount: number }">
                {{ t('items', rowsCount) }}
            </template>
        </UiTable>

        <UiModalSidebar
            v-if="editorOpen"
            v-model:opened="editorOpen"
            :closable="!saving"
            size="sm"
            direction="right"
            role="dialog"
            :class="$style['specialties-page__drawer']"
        >
            <template #title>
                {{ isEditing ? t('edit') : t('create') }}
            </template>

            <form
                id="specialty-editor-form"
                :class="$style['specialties-page__drawer-body']"
                @submit.prevent="submit"
            >
                <UiAlert v-if="formError" variant="danger" :text="formError" fluid />

                <UiField
                    id="specialty-name"
                    v-slot="{ id, required, disabled, readonly, invalid, ariaLabelledby, ariaInvalid }: FieldSlot"
                    :label="t('name')"
                    :invalid="Boolean(fieldErrors.name)"
                    required
                >
                    <UiTextbox
                        :id="id"
                        v-model:value="form.name"
                        width="fluid"
                        :disabled="saving || disabled"
                        :readonly="readonly"
                        :required="required"
                        :invalid="invalid"
                        :input-attributes="{
                            'aria-labelledby': ariaLabelledby,
                            'aria-invalid': ariaInvalid,
                        }"
                    />
                    <UiError
                        v-if="fieldErrors.name"
                        :class="$style['specialties-page__field-error']"
                        :message="fieldErrors.name"
                    />
                </UiField>
            </form>

            <template #footer>
                <div :class="$style['specialties-page__drawer-actions']">
                    <div :class="$style['specialties-page__drawer-actions-main']">
                        <UiButton
                            appearance="primary"
                            variant="success"
                            type="submit"
                            form="specialty-editor-form"
                            :disabled="saving"
                        >
                            {{ saving ? t('saving') : t('save') }}
                        </UiButton>
                        <UiButton appearance="secondary" type="button" :disabled="saving" @click="closeEditor">
                            {{ t('cancel') }}
                        </UiButton>
                    </div>

                    <div v-if="isEditing" :class="$style['specialties-page__drawer-actions-aside']">
                        <UiPopconfirm
                            :title="t('delete_confirm_title')"
                            ok-variant="danger"
                            @ok="remove"
                        >
                            <template #trigger="{ open: popconfirmOpen }: { open: boolean }">
                                <UiButton
                                    :active="popconfirmOpen"
                                    :aria-label="t('remove')"
                                    appearance="tertiary"
                                    variant="danger"
                                    type="button"
                                    :disabled="saving"
                                >
                                    <IconDelete aria-hidden="true" />
                                </UiButton>
                            </template>

                            {{ t('delete_confirm_text') }}
                        </UiPopconfirm>
                    </div>
                </div>
            </template>
        </UiModalSidebar>
    </section>
</template>

<script lang="ts" remote setup>
import type { AdminSpecialty } from '../types'

import IconAdd from '@retailcrm/embed-ui-v1-components/assets/sprites/actions/add-circle-outlined.svg'
import IconDelete from '@retailcrm/embed-ui-v1-components/assets/sprites/ui/delete-outlined.svg'
import IconEdit from '@retailcrm/embed-ui-v1-components/assets/sprites/ui/edit.svg'

import {
    UiAlert,
    UiButton,
    UiError,
    UiField,
    UiLink,
    UiLoader,
    UiModalSidebar,
    UiPageHeader,
    UiPopconfirm,
    UiPopperConnector,
    UiTable,
    UiTableColumn,
    UiTextbox,
    UiTooltip,
} from '@retailcrm/embed-ui-v1-components/remote'
import AdminEmptyState from '../components/AdminEmptyState.vue'
import { computed, onMounted, ref } from 'vue'
import { AdminApiError, deleteSpecialty, loadSpecialties, saveSpecialty } from '../api/adminApi'
import { useHost } from '@retailcrm/embed-ui'
import { useI18n } from 'vue-i18n'

const host = useHost()
const { t } = useI18n()

type FieldSlot = {
    id: string
    required: boolean
    disabled: boolean
    readonly: boolean
    invalid: boolean
    ariaLabelledby?: string
    ariaInvalid?: 'true'
}

const specialties = ref<AdminSpecialty[]>([])
const loading = ref(false)
const error = ref('')

const editorOpen = ref(false)
const saving = ref(false)
const formError = ref('')
const fieldErrors = ref<Record<string, string>>({})
const form = ref<AdminSpecialty>({ id: null, name: '' })

const isEditing = computed(() => null !== form.value.id)

const rowKey = (row: AdminSpecialty) => row.id ?? `new-${row.name}`

const load = async () => {
    loading.value = true
    error.value = ''

    try {
        specialties.value = await loadSpecialties(host)
    } catch (e) {
        error.value = e instanceof Error ? e.message : String(e)
    } finally {
        loading.value = false
    }
}

const openCreate = () => {
    form.value = { id: null, name: '' }
    formError.value = ''
    fieldErrors.value = {}
    editorOpen.value = true
}

const openEdit = (specialty: AdminSpecialty) => {
    form.value = { id: specialty.id, name: specialty.name }
    formError.value = ''
    fieldErrors.value = {}
    editorOpen.value = true
}

const onRowClick = ({ row }: { row: AdminSpecialty }) => {
    openEdit(row)
}

const closeEditor = () => {
    if (saving.value) {
        return
    }

    editorOpen.value = false
}

const submit = async () => {
    if (saving.value) {
        return
    }

    const name = form.value.name.trim()
    fieldErrors.value = {}
    if ('' === name) {
        fieldErrors.value = { name: t('empty_name') }
        return
    }

    saving.value = true
    formError.value = ''

    try {
        specialties.value = await saveSpecialty(host, { id: form.value.id, name })
        editorOpen.value = false
    } catch (e) {
        if (e instanceof AdminApiError) {
            fieldErrors.value = Object.fromEntries(e.fieldErrors.map(error => [error.path, error.message]))
            formError.value = e.fieldErrors.length > 0 ? '' : e.message
        } else {
            formError.value = e instanceof Error ? e.message : String(e)
        }
    } finally {
        saving.value = false
    }
}

const remove = async () => {
    if (null === form.value.id) {
        return
    }

    await removeById(form.value.id, error => {
        formError.value = error
    }, () => {
        editorOpen.value = false
    })
}

const removeRow = async (specialty: AdminSpecialty) => {
    if (null === specialty.id) {
        return
    }

    await removeById(specialty.id, nextError => {
        error.value = nextError
    }, () => {
        if (editorOpen.value && form.value.id === specialty.id) {
            editorOpen.value = false
        }
    })
}

const removeById = async (
    id: number,
    setError: (error: string) => void,
    onSuccess: () => void
) => {
    saving.value = true
    setError('')
    fieldErrors.value = {}

    try {
        specialties.value = await deleteSpecialty(host, id)
        onSuccess()
    } catch (e) {
        setError(e instanceof Error ? e.message : String(e))
    } finally {
        saving.value = false
    }
}

onMounted(load)
</script>

<i18n locale="en-GB">
{
  "title": "Specialties",
  "name": "Name",
  "add": "Add",
  "create": "Add",
  "edit": "Edit",
  "remove": "Delete",
  "edit_action": "Edit",
  "edit_named": "Edit {name}",
  "remove_named": "Delete {name}",
  "save": "Save",
  "saving": "Saving...",
  "cancel": "Cancel",
  "delete_confirm_title": "Delete specialty?",
  "delete_confirm_text": "This action cannot be undone.",
  "empty_title": "No specialties yet",
  "empty_text": "Click \"Add\" to create the first one.",
  "empty_name": "Enter a specialty name.",
  "items": "{count} items | {count} item | {count} items"
}
</i18n>

<i18n locale="es-ES">
{
  "title": "Especialidades",
  "name": "Nombre",
  "add": "Añadir",
  "create": "Añadir",
  "edit": "Editar",
  "remove": "Eliminar",
  "edit_action": "Editar",
  "edit_named": "Editar {name}",
  "remove_named": "Eliminar {name}",
  "save": "Guardar",
  "saving": "Guardando...",
  "cancel": "Cancelar",
  "delete_confirm_title": "¿Eliminar especialidad?",
  "delete_confirm_text": "Esta acción no se puede deshacer.",
  "empty_title": "Aún no hay especialidades",
  "empty_text": "Haz clic en \"Añadir\" para crear la primera.",
  "empty_name": "Introduce el nombre de la especialidad.",
  "items": "{count} elementos | {count} elemento | {count} elementos"
}
</i18n>

<i18n locale="ru-RU">
{
  "title": "Специализации",
  "name": "Название",
  "add": "Добавить",
  "create": "Добавление",
  "edit": "Редактирование",
  "remove": "Удалить",
  "edit_action": "Редактировать",
  "edit_named": "Редактировать {name}",
  "remove_named": "Удалить {name}",
  "save": "Сохранить",
  "saving": "Сохраняем...",
  "cancel": "Отмена",
  "delete_confirm_title": "Удалить специализацию?",
  "delete_confirm_text": "Это действие нельзя отменить.",
  "empty_title": "Специализации ещё не добавлены",
  "empty_text": "Нажмите «Добавить», чтобы создать первую.",
  "empty_name": "Введите название специализации.",
  "items": "{count} элементов | {count} элемент | {count} элемента | {count} элементов"
}
</i18n>

<style lang="less" module>
@import (reference) "~@retailcrm/embed-ui-v1-components/assets/stylesheets/variables.less";
@import (reference) "~@retailcrm/embed-ui-v1-components/assets/stylesheets/typography.less";
@import (reference) "~@retailcrm/embed-ui-v1-components/assets/stylesheets/geometry.less";
@import (reference) "~@retailcrm/embed-ui-v1-components/assets/stylesheets/palette.less";

.specialties-page {
    .reset-box-sizing();

    display: flex;
    flex-direction: column;
    gap: @spacing-m;
    min-width: 0;

    &__row {
        cursor: pointer;
        transition: background-color @transition;

        &:hover {
            background: @grey-100;
        }
    }

    &__actions {
        display: flex;
        justify-content: flex-end;
        gap: @spacing-xs;
    }

    &__table {
        --ui-v1-table-cell-padding-x: 12px;
        --ui-v1-table-cell-padding-y: 12px;
        --ui-v1-table-padding-start: 16px;
        --ui-v1-table-padding-end: 16px;
        --ui-v1-table-rounding: 4px;
        --ui-v1-table-head-cell-padding-block-start: 14px;
        --ui-v1-table-head-cell-padding-block-end: 14px;
        --ui-v1-table-body-cell-padding-block-start: 15px;
        --ui-v1-table-body-cell-padding-block-end: 15px;
    }

    &__title-link {
        max-width: 100%;
        color: @blue-500;
    }

    &__drawer {
        container-type: inline-size;
    }

    &__drawer-body {
        display: flex;
        flex-direction: column;
        gap: @spacing-s;
    }

    &__field-error {
        margin-top: @spacing-xs;
    }

    &__drawer-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: @spacing-s;
        width: 100%;
    }

    &__drawer-actions-main,
    &__drawer-actions-aside {
        display: flex;
        align-items: center;
    }

    &__drawer-actions-main {
        gap: @spacing-xs;
    }

    &__drawer-actions-aside {
        gap: @spacing-s;
        margin-left: auto;
    }

}

@container (max-width: 520px) {
    .specialties-page {
        &__drawer-actions {
            flex-direction: column;
            align-items: stretch;
        }

        &__drawer-actions-main {
            flex-direction: column;
            align-items: stretch;
        }

        &__drawer-actions-aside {
            justify-content: flex-end;
            margin-left: 0;
        }
    }
}
</style>
