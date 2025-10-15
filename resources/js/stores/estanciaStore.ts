
import { defineStore } from 'pinia'
import { createBaseStore } from './baseStore'
import type { Estancia } from '@/types'

// Creamos el store base para Estancia
const baseStore = createBaseStore<Estancia>()

export const useEstanciaStore = defineStore('estancia', () => {
  // Acciones específicas para estancias podrían ir aquí, como:
  // - registrarCheckIn(id: number)
  // - registrarCheckOut(id: number)

  return {
    ...baseStore,
  }
})
