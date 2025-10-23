
import { defineStore } from 'pinia'

// Definimos la interfaz para una persona, basada en la migración
export interface PersonaState {
  id?: number
  nombres: string
  apellido_paterno: string
  apellido_materno: string | null
  tipo_documento: string
  nro_documento: string
  complemento: string | null
  fecha_nacimiento: string | null
  nacionalidad_id: number | null
  departamento_id: number | null
  municipio_id: number | null
  ciudad_origen: string | null
  sexo: string | null
  estado_civil: string | null
  ocupacion: string | null
  parentesco?: string
}

// Interfaz para un dependiente
export interface DependienteState extends PersonaState {
  // Los dependientes son personas completas
}

export const useCheckinStore = defineStore('checkin', {
  state: () => ({
    reserva:{
        usuario_registra_id: null as number | null,
        codigo_reserva: '',
        establecimiento_id: null as number | null,
        fecha_entrada: '',
        fecha_salida: '',
        nro_cuarto: 1,
    },
    titular: null as PersonaState | null,
    dependientes: [] as DependienteState[],
  }),
  actions: {
    setTitular(persona: PersonaState | null) {
      this.titular = persona
      console.log('Huésped Titular establecido en Pinia:', this.titular)
      console.log('Acompañantes en Pinia:', this.dependientes)
    },
    addDependiente(persona: PersonaState) {
      // Evitar añadir duplicados
      if (!this.dependientes.some(d => d.id === persona.id)) {
        this.dependientes.push(persona)
        console.log('Huésped Titular establecido en Pinia:', this.titular)
        console.log('Acompañantes en Pinia:', this.dependientes)
      }
    },
    removeDependiente(personaId: number) {
      this.dependientes = this.dependientes.filter(d => d.id !== personaId)
    },
    reset() {
      this.$reset()
    },
  }
})
