
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

// Interfaz para un dependiente (es una PersonaState)
export interface DependienteState extends PersonaState {}

// NUEVA Interfaz para un Grupo/Habitación
export interface Grupo {
  id: string;
  titular: PersonaState;
  dependientes: (PersonaState & { parentesco: string })[];
  tipo_cuarto_id: string | number;
  nro_cuarto: string;
}

export const useCheckinStore = defineStore('checkin', {
  state: () => ({
    // El objeto reserva se mantiene, pero sin nro_cuarto
    reserva: {
        usuario_registra_id: null as number | null,
        codigo_reserva: '',
        establecimiento_id: null as number | null,
        departamento_id: null as number | null,
        sucursal_id: null as number | null,
        fecha_entrada: '',
        fecha_salida: '',
    },
    // El estado ahora se basa en grupos
    grupos: [] as Grupo[],
  }),
  actions: {
    // Nueva acción para añadir un grupo completo
    addGrupo(grupo: Grupo) {
      this.grupos.push(grupo)
      console.log('Grupo añadido. Grupos actuales:', this.grupos)
    },

    // Nueva acción para añadir un acompañante a un grupo específico
    addAcompananteToGrupo(payload: { grupoId: string; acompanante: PersonaState & { parentesco: string } }) {
      const grupo = this.grupos.find(g => g.id === payload.grupoId);
      if (grupo) {
        // Evitar duplicados
        if (!grupo.dependientes.some(d => d.id === payload.acompanante.id)) {
            grupo.dependientes.push(payload.acompanante);
        }
      }
    },

    // Nueva acción para eliminar un grupo entero
    removeGrupo(grupoId: string) {
      this.grupos = this.grupos.filter(g => g.id !== grupoId);
    },

    // Nueva acción para eliminar un acompañante de un grupo
    removeAcompananteFromGrupo(payload: { grupoId: string; acompananteId: number }) {
      const grupo = this.grupos.find(g => g.id === payload.grupoId);
      if (grupo) {
        grupo.dependientes = grupo.dependientes.filter(d => d.id !== payload.acompananteId);
      }
    },

    // La acción reset reinicia todo el estado para un nuevo check-in
    reset() {
      this.$reset()
    },
  }
})
