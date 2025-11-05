export interface Sucursal {
    id_sucursal: number;
    ciudad: string;
    nombre_sucursal: string;
}

export interface Establecimiento {
    id_establecimiento: number;
    razon_social: string;
    ciudad: string;
    direccion_establecimiento: string;
    sucursal?: Sucursal | null;
}


