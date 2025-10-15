import { defineStore } from 'pinia'
import { createBaseStore } from './baseStore'
import type { Persona } from '@/types'
import axios from 'axios'

// Creamos el store base para Persona
const baseStore = createBaseStore<Persona>()

export const usePersonaStore = defineStore('persona', () => {
  // Función para buscar personas por documento
  const searchPersonasByDocumento = async (documento: string): Promise<Persona[]> => {
    baseStore.setLoading(true)
    baseStore.setError(null)
    try {
      const response = await axios.get<Persona[]>('/personas/search', {
        params: { documento },
      })
      // El controlador devuelve un array de personas directamente
      baseStore.setItems(response.data)
      return response.data
    } catch (err) {
      baseStore.setError('Error al buscar personas.')
      // Asegurarse de que el error se muestra en la consola para depuración
      console.error(err)
      return []
    } finally {
      baseStore.setLoading(false)
    }
  }

  return {
    ...baseStore,
    searchPersonasByDocumento,
  }
})
