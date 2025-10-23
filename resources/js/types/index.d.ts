import { InertiaLinkProps } from '@inertiajs/vue3';
import type { LucideIcon } from 'lucide-vue-next';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon;
    isActive?: boolean;
}

export type AppPageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    sidebarOpen: boolean;
};

export interface Establecimiento {
    id_establecimiento: number;
    razon_social: string;
}

export interface User {
    id: number;
    apellido_paterno: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    establecimiento_id: number;
    establecimiento: Establecimiento;
}

export type BreadcrumbItemType = BreadcrumbItem;

export interface Usuarios {
    id: number;
    apellido_paterno: string;
    apellido_materno: string;
    nombres: string;
    ci: string;
    celular: string;
    cargo: string;
    estado: 'activo' | 'inactivo'; // Restringe los valores posibles
    email: string;
}

declare module '@inertiajs/core' {
    interface PageProps extends AppPageProps {}
}
