<script setup lang="ts">
import { computed } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { type NavItem } from '@/types';
import { UserSearch, BookOpen, ChartNoAxesCombined, BookDown, Upload, Eye, Folder, LayoutGrid, User, UserCheck, BedDouble, BookOpenCheck   } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';

// --- INICIO DE LA LÓGICA DE PERMISOS ---

// 1. Accede a la página y a los permisos del usuario
const page = usePage();
// Los permisos están en page.props.auth.user.permissions según HandleInertiaRequests.php
const userPermissions = computed(() => page.props.auth.user?.permissions || []);

// Define una interfaz extendida para tus items de navegación
interface NavItemWithPermission extends NavItem {
    title: string;
    href: string | null;
    icon: { };
    permission?: string | string[];
}

interface NavSection {
    title: string;
    items: NavItemWithPermission[];
}

// 2. Asigna los permisos necesarios a cada ruta, organizados por secciones
const navSections: NavSection[] = [
    {
        title: 'MÓDULO DE INICIO',
        items: [
            {
                title: 'Dashboard',
                href: 'dashboard',
                icon: LayoutGrid,
            },
        ]
    },
    {
        title: 'MÓDULO DE USUARIOS',
        items: [
            {
                title: 'Usuarios',
                href: '/usuarios',
                icon: User,
                permission: ['gestionar-usuarios', 'gestionar-empleados'],
            },
            {
                title: 'Roles y Permisos',
                href: '/settings/roles',
                icon: UserCheck,
                permission: 'gestionar-roles',
            },
        ]
    },
    {
        title: 'MÓDULO DE REGISTRO DE ESTANCIA',
        items: [
            {
                title: 'Habitaciones',
                href: '/cuartos',
                icon: BedDouble,
                permission: 'gestionar-cuartos',
            },
            {
                title: 'Registrar Estancia',
                href: '/checkin',
                icon: BookOpenCheck,
                permission: 'registrar-estancia',
            },
            {
                title: 'Ver Estancia',
                href: '/estancias',
                icon: Eye,
                permission: 'ver-estancia',
            },
        ]
    },
    {
        title: 'MÓDULO DE PARTES DIARIOS',
        items: [
            {
                title: 'Ver Parte Diario Departamental',
                href: '/revision',
                icon: Eye,
                permission: 'ver-parte-diario-departamental',
            },
            {
                title: 'Ver Parte Diario Nacional',
                href: '/confirmacion',
                icon: Eye,
                permission: 'ver-parte-diario-nacional',
            },
        ]
    },
    {
        title: 'MÓDULO DE GESTIÓN',
        items: [
            {
                title: 'Ver Solicitudes Información',
                href: '/solicitudes',
                permission: 'gestionar-solicitud',
                icon: Eye,
            },
            {
                title: 'Subir Información',
                href: '/csv-upload',
                permission: 'gestionar-csv',
                icon: Upload,
            },
            {
                title: 'Busqueda',
                href: '/busqueda',
                permission: 'gestionar-busqueda',
                icon: UserSearch,
            },
            {
                title: 'Reportes',
                href: '/reportes',
                permission: 'gestionar-reportes',
                icon: BookDown,
            },
            {
                title: 'Estadísticas',
                href: '/estadisticas',
                permission: 'gestionar-estadisticas',
                icon: ChartNoAxesCombined,
            },
        ]
    },
];

// 3. Filtra las secciones y sus items basados en los permisos del usuario
const filteredNavSections = computed(() => {
    return navSections.map(section => ({
        title: section.title,
        items: section.items.filter(item => {
            // Si el item no tiene la propiedad 'permission', siempre se muestra
            if (!item.permission) {
                return true;
            }

            const requiredPermissions = Array.isArray(item.permission)
                ? item.permission
                : [item.permission];
            // Si la tiene, comprueba si el usuario tiene ese permiso
            return requiredPermissions.some(p => userPermissions.value.includes(p));
        })
    })).filter(section => section.items.length > 0); // Solo mostrar secciones con items
});

</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <!-- 4. Renderiza las secciones con títulos -->
            <div v-for="section in filteredNavSections" :key="section.title" class="mb-4">
                <div class="px-3 py-2 text-xs font-semibold text-sidebar-foreground/70 uppercase tracking-wider">
                    {{ section.title }}
                </div>
                <NavMain :items="section.items" />
            </div>
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
