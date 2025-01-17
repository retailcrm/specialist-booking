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

export const CustomFieldSpecialistCode = 's_booking_specialist'
export const CustomFieldSpecialistDateTimeCode = 's_booking_specialist_datetime'
