
export interface Departamento {
  id: number
  nombre: string
}

export interface Municipio {
  id: number
  nombre_municipio: string
  codigo_municipio: string
  departamento_id: number
}

export interface Nacionalidad {
  id: number
  pais: string
  gentilicio: string
  codigo_nacionalidad: number
}

export interface Persona {
  id: number
  nombres: string
  apellido_paterno: string
  apellido_materno: string
  tipo_documento: 'ci' | 'pasaporte'
  nro_documento: string
  complemento: string | null
  fecha_nacimiento: string
  nacionalidad_id: number
  departamento_id: number
  municipio_id: number
  ciudad_origen: string | null
  sexo: 'M' | 'F' | 'O'
  estado_civil: 'soltero' | 'casado' | 'divorciado' | 'viudo' | 'union_libre'
  ocupacion: string | null
}

export interface Reserva {
  id: number
  persona_id: number
  fecha_reserva: string
  check_in: string
  check_out: string
  numero_adultos: number
  numero_ninos: number
  estado: 'confirmada' | 'cancelada' | 'pendiente'
  persona?: Persona
}

export interface Estancia {
  id: number
  reserva_id: number
  habitacion_id: number // Asumiendo que existirá un modelo Habitacion
  check_in: string
  check_out: string
  estado: 'activa' | 'finalizada' | 'cancelada'
  reserva?: Reserva
}

// Props para componentes de Inertia
export interface PageProps {
  auth: {
    user: {
      id: number
      name: string
      email: string
    }
  }
  // Aquí se pueden agregar otras props que vengan de Laravel
}
