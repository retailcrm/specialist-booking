import type { NonWorkingDays, WorkTimes } from './schedule'

export interface Specialist {
  id: string
  name: string
  position: string | null
  photo: string | null
  nearestSlots: {
    date: string
    slots: string[]
  } | null
}

export interface Settings {
  chooseStore: boolean
  chooseCity: boolean
}

export interface AdminSettings extends Settings {
  slotDuration: number
  clientId: string
}

export interface AdminSpecialty {
  id: number | null
  name: string
}

export interface Store {
  code: string
  name: string
}

export interface AdminSpecialist {
  id: number | null
  name: string
  specialtyId: number | null
  storeCode: string | null
  ordering: number
  photo: string | null
  photoUrl: string | null
  // личное расписание: null — общий график компании
  workTimes: WorkTimes | null
  nonWorkingDays: NonWorkingDays | null
  photoUrlChanged?: boolean
  removePhoto?: boolean
}

export interface City {
  name: string
  branchCount: number
}

export interface Branch {
  name: string
  code: string
  specialistCount: number
}

export const CustomFieldSpecialistCode = 's_booking_specialist'
export const CustomFieldSpecialistDateTimeCode = 's_booking_specialist_datetime'

export interface CalendarBooking {
  time: string
  orderId: number
  orderNumber: string | null
  customer: string | null
  phone: string | null
  status: string | null
}

export interface CalendarDay {
  free: string[]
  bookings: CalendarBooking[]
}

export interface CalendarSpecialist {
  id: number
  name: string
  specialtyId: number | null
  storeCode: string | null
  photoUrl: string | null
}

export interface CalendarStore {
  code: string
  name: string
  city: string | null
}

export interface CalendarRow {
  specialist: CalendarSpecialist
  days: Record<string, CalendarDay>
}

export interface CalendarResponse {
  days: string[]
  slotDuration: number
  chooseStore: boolean
  chooseCity: boolean
  stores: CalendarStore[]
  preferredStore: string | null
  statuses: Record<string, string>
  specialties: AdminSpecialty[]
  schedule: CalendarRow[]
}

export interface CustomerHit {
  id: number
  firstName: string | null
  lastName: string | null
  phone: string | null
}

export interface BookRequest {
  specialistId: number
  date: string
  time: string
  customerId: number | null
  firstName: string
  lastName: string | null
  phone: string
  comment: string | null
}

export interface BookResponse {
  orderId: number
  orderNumber: string | null
  datetime: string
}
