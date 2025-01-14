export interface Barber {
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

export interface Reservation {
  id: string
  barberId: string
  date: string
  time: string
  customerName: string
  phone: string
  service: 'haircut' | 'shave' | 'beard-trim'
} 