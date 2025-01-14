export interface Specialist {
  id: string
  name: string
  position: string
  photo: string
  englishSpeaking?: boolean
  rating?: number
  reviewCount?: number
  nearestSlots: {
    date: string
    slots: string[]
  }
}
