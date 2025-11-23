<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, usePage, Link, router } from '@inertiajs/vue3';
import type { PageProps } from '@inertiajs/core';
import { computed, ref, watch } from 'vue';
import { debounce } from 'lodash';

// Componentes de UI
import { Table, TableBody, TableCaption, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { CirclePlus, Pencil, Search } from 'lucide-vue-next';
import {
  Pagination,
  PaginationContent,
  PaginationEllipsis,
  PaginationItem,
  PaginationNext,
  PaginationPrevious,
} from '@/components/ui/pagination';

// Define las interfaces para el tipado estricto
interface UsuarioRole {
    id: number;
    name: string;
}

interface Establecimiento {
    id_establecimiento: number;
    razon_social: string;
}

interface Sucursal {
    id_sucursal: number;
    nombre_sucursal: string;
}

interface Usuario {
    id: number;
    nombres: string;
    apellido_paterno: string;
    apellido_materno: string;
    ci: string;
    celular: string;
    estado: string;
    email: string;
    roles: UsuarioRole[];
    establecimiento?: Establecimiento;
    sucursal?: Sucursal;
}

interface Paginator {
    data: Usuario[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

// Define las propiedades de la página
interface Props {
    usuarios: Paginator;
    search?: string;
}

const props = defineProps<Props>();

const page = usePage<PageProps & { auth: { user: { permissions: string[] } } } & { usuarios: Paginator, search?: string }>();

// Helper function to check permissions
const can = (permission: string) => {
    const user = page.props.auth.user;
    console.log(user);
    if (!user || !user.permissions) {
        return false;
    }
    return user.permissions.includes(permission);
};

// 'usuarios' ahora apunta a la data del paginador
const usuarios = computed(() => page.props.usuarios.data || []);

// Variable reactiva para la página actual y un watcher
const currentPage = ref(page.props.usuarios.current_page);
const searchQuery = ref(page.props.search || '');

watch(currentPage, (newPage) => {
    router.get('/usuarios', { page: newPage, search: searchQuery.value }, {
        preserveState: true,
        preserveScroll: true,
    });
});

// Debounce para la búsqueda
const debouncedSearch = debounce(() => {
    router.get('/usuarios', { search: searchQuery.value, page: 1 }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}, 300);

watch(searchQuery, () => {
    debouncedSearch();
});

const toggleEstado = (usuario: Usuario) => {
    const nextLabel = usuario.estado === 'activo' ? 'inactivar' : 'activar';
    if (!window.confirm(`¿Desea ${nextLabel} al usuario ${usuario.nombres}?`)) {
        return;
    }

    router.patch(`/usuarios/${usuario.id}/toggle-estado`, {}, {
        preserveScroll: true,
        preserveState: true,
    });
};

const breadcrumbs = [
    {
        title: 'Usuarios',
        href: '/usuarios',
    },
];
</script>

<template>
    <Head title="Usuarios" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="flex" v-if="can('gestionar-empleados')">
                <Button as-child size="sm" class="bg-indigo-500 text-white hover:bg-indigo-700">
                    <Link href="/usuarios/create">
                        <CirclePlus class="w-4 h-4 mr-2" />
                        Nuevo Empleado
                    </Link>
                </Button>
            </div>
            <div class="flex" v-if="can('gestionar-usuarios')">
                <Button as-child size="sm" class="bg-indigo-500 text-white hover:bg-indigo-700">
                    <Link href="/usuarios/crear-usuario">
                        <CirclePlus class="w-4 h-4 mr-2" />
                        Nuevo Usuario
                    </Link>
                </Button>
            </div>

            <div class="relative w-full max-w-sm items-center">
                <Input id="search" v-model="searchQuery" type="text" placeholder="Buscar por..." class="pl-10" />
                <span class="absolute start-0 inset-y-0 flex items-center justify-center px-2">
                <Search class="size-6 text-muted-foreground" />
                </span>
            </div>

            <div class="relative flex-l rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                <Table>
                    <TableCaption>Una lista de los usuarios de tu cuenta.</TableCaption>
                    <TableHeader>
                        <TableRow>
                            <TableHead class="w-[100px]">ID</TableHead>
                            <TableHead>Nombres</TableHead>
                            <TableHead>A. Paterno</TableHead>
                            <TableHead>A. Materno</TableHead>
                            <TableHead>CI</TableHead>
                            <TableHead>Celular</TableHead>
                            <TableHead>Rol</TableHead>
                            <TableHead>Establecimiento/Sucursal</TableHead>
                            <TableHead>Estado</TableHead>
                            <TableHead>Email</TableHead>
                            <TableHead class="text-right">Acciones</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-if="usuarios.length === 0">
                            <TableCell colspan="11" class="text-center">
                                No hay usuarios para mostrar.
                            </TableCell>
                        </TableRow>

                        <TableRow v-for="usuario in usuarios" :key="usuario.id">
                            <TableCell class="font-medium">{{ usuario.id }}</TableCell>
                            <TableCell>{{ usuario.nombres }}</TableCell>
                            <TableCell>{{ usuario.apellido_paterno }}</TableCell>
                            <TableCell>{{ usuario.apellido_materno }}</TableCell>
                            <TableCell>{{ usuario.ci }}</TableCell>
                            <TableCell>{{ usuario.celular }}</TableCell>
                            <TableCell>
                                <span v-if="usuario.roles && usuario.roles.length">
                                    {{ usuario.roles.map(role => role.name).join(', ') }}
                                </span>
                                <span v-else>
                                    Sin rol
                                </span>
                            </TableCell>
                            <TableCell>
                                <span v-if="usuario.sucursal">
                                    {{ usuario.sucursal.nombre_sucursal}}
                                </span>
                                <span v-else-if="usuario.establecimiento">
                                    {{ usuario.establecimiento.razon_social }}
                                </span>
                                <span v-else class="text-gray-400 italic">
                                    N/A
                                </span>
                            </TableCell>
                            <TableCell>{{ usuario.estado }}</TableCell>
                            <TableCell>{{ usuario.email }}</TableCell>
                            <TableCell class="flex justify-center gap-2">
                                <Button as-child size="sm" class="bg-indigo-500 text-white hover:bg-indigo-700">
                                    <Link :href="`/usuarios/${usuario.id}/edit`">
                                        <Pencil class="w-4 h-4 mr-2"/>
                                    </Link>
                                </Button>
                                <Button
                                    type="button"
                                    size="sm"
                                    variant="secondary"
                                    class="bg-red-400 text-white-700 hover:bg-red-500"
                                    @click="toggleEstado(usuario)"
                                >
                                    {{ usuario.estado === 'activo' ? 'Inactivar' : 'Activar' }}
                                </Button>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <Pagination
                v-if="page.props.usuarios.total > page.props.usuarios.per_page"
                v-model:page="currentPage"
                :total="page.props.usuarios.total"
                :items-per-page="page.props.usuarios.per_page"
                :sibling-count="1"
                show-edges
                class="mt-4"
            >
                <PaginationContent v-slot="{ items }">
                    <PaginationPrevious />
                    <template v-for="(item, index) in items">
                        <PaginationItem v-if="item.type === 'page'" :key="index" :value="item.value">
                        {{ item.value }}
                        </PaginationItem>
                        <PaginationEllipsis v-else :key="item.type" :index="index" />
                    </template>
                    <PaginationNext />
                </PaginationContent>
            </Pagination>
        </div>
    </AppLayout>
</template>
