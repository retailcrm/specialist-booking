import type {
    AdminSettings,
    AdminSpecialist,
    AdminSpecialty,
    BookRequest,
    BookResponse,
    CalendarResponse,
    CustomerHit,
    Store,
} from '../types'
import type { HostApi } from '@retailcrm/embed-ui-v1-types/host'
import type { Pojo } from '@retailcrm/embed-ui-v1-types/scaffolding'

type AdminSettingsResponse = {
    settings: AdminSettings
}

type SpecialtiesResponse = {
    specialties: AdminSpecialty[]
}

export type SpecialistsResponse = {
    specialists: AdminSpecialist[]
    specialties: AdminSpecialty[]
    stores: Store[]
    chooseStore: boolean
}

export type FieldError = {
    path: string
    message: string
}

export class AdminApiError extends Error {
    constructor (
        message: string,
        public readonly fieldErrors: FieldError[] = []
    ) {
        super(message)
    }
}

const parseResponse = <T>(body: string): T => JSON.parse(body) as T

const parseError = (body: string, status: number): AdminApiError => {
    if (!body) {
        return new AdminApiError(`HTTP ${status}`)
    }

    try {
        const response = JSON.parse(body) as { error?: unknown, message?: unknown, errors?: unknown }
        const fieldErrors = Array.isArray(response.errors)
            ? response.errors
                .filter((error): error is FieldError => {
                    return typeof error === 'object'
                        && null !== error
                        && typeof (error as FieldError).path === 'string'
                        && typeof (error as FieldError).message === 'string'
                })
            : []

        if (typeof response.error === 'string' && response.error.length > 0) {
            return new AdminApiError(response.error, fieldErrors)
        }

        if (typeof response.message === 'string' && response.message.length > 0) {
            return new AdminApiError(response.message, fieldErrors)
        }
    } catch {
        return new AdminApiError(body)
    }

    return new AdminApiError(body)
}

const call = async <T>(host: HostApi, action: string, payload?: Pojo): Promise<T> => {
    const { body, status } = await host.httpCall(action, payload)

    if (status < 200 || status >= 300) {
        throw parseError(body, status)
    }

    return parseResponse<T>(body)
}

export const loadAdminSettings = async (host: HostApi): Promise<AdminSettings> => {
    const response = await call<AdminSettingsResponse>(host, '/embed/api/admin/settings')

    return response.settings
}

export const saveAdminSettings = async (host: HostApi, settings: AdminSettings): Promise<AdminSettings> => {
    const response = await call<AdminSettingsResponse>(host, '/embed/api/admin/settings', {
        slotDuration: settings.slotDuration,
        chooseStore: settings.chooseStore,
        chooseCity: settings.chooseCity,
    })

    return response.settings
}

export const loadSpecialties = async (host: HostApi): Promise<AdminSpecialty[]> => {
    const response = await call<SpecialtiesResponse>(host, '/embed/api/admin/specialties')

    return response.specialties
}

export const saveSpecialty = async (host: HostApi, specialty: AdminSpecialty): Promise<AdminSpecialty[]> => {
    const response = await call<SpecialtiesResponse>(host, '/embed/api/admin/specialties/save', {
        id: specialty.id,
        name: specialty.name,
    })

    return response.specialties
}

export const deleteSpecialty = async (host: HostApi, id: number): Promise<AdminSpecialty[]> => {
    const response = await call<SpecialtiesResponse>(host, '/embed/api/admin/specialties/delete', { id })

    return response.specialties
}

export const loadSpecialists = async (host: HostApi): Promise<SpecialistsResponse> => {
    return call<SpecialistsResponse>(host, '/embed/api/admin/specialists')
}

export const saveSpecialist = async (
    host: HostApi,
    specialist: AdminSpecialist
): Promise<SpecialistsResponse> => {
    const payload: Pojo = {
        id: specialist.id,
        name: specialist.name,
        specialtyId: specialist.specialtyId,
        storeCode: specialist.storeCode,
        ordering: specialist.ordering,
        removePhoto: specialist.removePhoto || false,
        // вложенные массивы интервалов уже, чем тип Pojo, хотя это обычный JSON
        workTimes: specialist.workTimes as unknown as Pojo | null,
        nonWorkingDays: specialist.nonWorkingDays as unknown as Pojo | null,
    }

    if (specialist.photoUrlChanged) {
        payload.photoUrl = specialist.photoUrl
    }

    return call<SpecialistsResponse>(host, '/embed/api/admin/specialists/save', payload)
}

export const deleteSpecialist = async (host: HostApi, id: number): Promise<SpecialistsResponse> => {
    return call<SpecialistsResponse>(host, '/embed/api/admin/specialists/delete', { id })
}

export const SLOT_NOT_AVAILABLE = 'slot_not_available'

export const loadCalendar = async (
    host: HostApi,
    dateFrom: string,
    dateTo: string,
    userId: number | null
): Promise<CalendarResponse> => {
    return call<CalendarResponse>(host, '/embed/api/admin/calendar', { dateFrom, dateTo, userId })
}

// филиал календаря запоминается за пользователем CRM на сервере: у страницы в воркере своего хранилища нет
export const saveCalendarStore = async (host: HostApi, userId: number, storeCode: string | null): Promise<void> => {
    await call<{ preferredStore: string | null }>(host, '/embed/api/admin/calendar/preferences', { userId, storeCode })
}

export const searchCustomers = async (host: HostApi, query: string): Promise<CustomerHit[]> => {
    const response = await call<{ customers: CustomerHit[] }>(host, '/embed/api/admin/customers', { query })

    return response.customers
}

// занятый слот приходит как 409 с кодом slot_not_available — страница различает его по сообщению
export const bookSlot = async (host: HostApi, request: BookRequest): Promise<BookResponse> => {
    return call<BookResponse>(host, '/embed/api/admin/book', { ...request })
}
