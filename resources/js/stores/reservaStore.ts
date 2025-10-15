
import { defineStore } from 'pinia'
import { createBaseStore } from './baseStore'
import type { Reserva } from '@/types'

// Creamos el store base para Reserva
const baseStore = createBaseStore<Reserva>()

export const useReservaStore = defineStore('reserva', () => {
  // Aquí se podrían añadir acciones específicas para reservas, como:
  // - confirmarReserva(id: number)
  // - cancelarReserva(id: number)

  return {
    ...baseStore,
  }
})
