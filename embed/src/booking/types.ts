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
