<script setup lang="ts">
import { computed } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';
import NavFooter from '@/components/NavFooter.vue';
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
import { BookOpen, Folder, LayoutGrid, User } from 'lucide-vue-next';
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

// 2. Asigna los permisos necesarios a cada ruta
const mainNavItems: NavItemWithPermission[] = [
    {
        title: 'Dashboard',
        href: 'dashboard',
        icon: LayoutGrid,
        // No requiere permiso, se muestra siempre
    },

    {
        title: 'Usuarios',
        href: '/usuarios', // Corregido a la ruta base
        icon: User,
        permission: ['gestionar-usuarios', 'gestionar-empleados'],
    },
    {
        title: 'Roles y Permisos',
        href: '/settings/roles',
        icon: User,
        permission: 'gestionar-roles',
    },
    {
        title: 'Cuartos',
        href: '/cuartos',
        icon: User,
        permission: 'gestionar-cuartos',
    },
    {
        title: 'Registrar Estancia',
        href: '/checkin',
        icon: User,
        permission: 'registrar-estancia',
    },
    {
        title: 'Ver Estancia',
        href: '/estancias',
        icon: User,
        permission: 'ver-estancia',
    },
    {
        title: 'Ver Parte Diario Departamental',
        href: '/revision',
        icon: User,
        permission: 'ver-parte-diario-departamental',
    },
    {
        title: 'Ver Parte Diario Nacional',
        href: '/confirmacion',
        icon: User,
        permission: 'ver-parte-diario-nacional',
    },
        {
        title: 'Solicitudes',
        href: '/solicitud/create',
        icon: User,
    },
];

// 3. Filtra los items del menú basados en los permisos del usuario
const filteredMainNavItems = computed(() => {
    return mainNavItems.filter(item => {
        // Si el item no tiene la propiedad 'permission', siempre se muestra
        if (!item.permission) {
            return true;
        }

        const requiredPermissions = Array.isArray(item.permission)
            ? item.permission
            : [item.permission];
        // Si la tiene, comprueba si el usuario tiene ese permiso
        return requiredPermissions.some(p => userPermissions.value.includes(p));
    });
});

// --- FIN DE LA LÓGICA DE PERMISOS ---

const footerNavItems: NavItem[] = [
    {
        title: 'Github Repo',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: Folder,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
    },
];
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
            <!-- 4. Usa la lista filtrada para renderizar el menú -->
            <NavMain :items="filteredMainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
