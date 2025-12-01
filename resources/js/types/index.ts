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
  codigo_reserva: number
  establecimiento_id: string
  sucursal_id: string
  usuario_registra_id: string
  fecha_entrada: number
  fecha_salida: number
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

// Types from index.d.ts
export interface Sucursal {
  id_sucursal: number;
  nombre_sucursal: string;
  ciudad: string;
  direccion_sucursal: string;
}

export interface Establecimiento {
  id_establecimiento: number;
  codigo: string;
  razon_social: string;
  ciudad: string;
  direccion_establecimiento: string;
  id_departamento: number;
  sucursales?: Sucursal | null;
}

export interface User {
  id: number;
  nombres: string;
  apellido_paterno: string;
  apellido_materno: string | null;
  ci: string;
  celular: string | null;
  email: string;
  avatar?: string;
  email_verified_at: string | null;
  created_at: string;
  updated_at: string;
  nacionalidad_id: number | null;
  departamento_id: number | null;
  municipio_id: number | null;
  establecimiento_id: number | null;
  sucursal_id: number | null;
  establecimiento?: Establecimiento;
  sucursal?: Sucursal;
  permissions: string[];
  departamento: Departamento;
}

export type Permissions = string[];

export interface NavItem {
  title: string;
  href: string | null;
  icon: any;
  permission?: string | string[];
}

export interface Auth {
  user: User;
}

export type AppPageProps<
  T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
  name: string;
  quote: { message: string; author: string };
  auth: Auth;
  sidebarOpen: boolean;
};

export interface Role {
  id: number;
  name: string;
}

export interface TipoCuarto {
  id: number;
  nombre: string;
  nro_habitaciones: number;
  nro_personas: number;
}
