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
