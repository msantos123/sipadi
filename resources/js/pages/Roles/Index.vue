<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, usePage, Link, router } from '@inertiajs/vue3';
import type { PageProps } from '@inertiajs/core';
import { computed, ref, watch, watchEffect } from 'vue';
import { debounce } from 'lodash';
import Swal from 'sweetalert2';

// Componentes de UI
import { Table, TableBody, TableCaption, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { CirclePlus, Pencil, Search, Trash2 } from 'lucide-vue-next';
// Define las interfaces para el tipado estricto
interface Permission {
    id: number;
    name: string;
}

interface Role {
    id: number;
    name: string;
    permissions: Permission[];
}

interface Paginator {
    data: Role[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

// Define las propiedades de la página
interface Props {
    roles: Role[];
    search?: string;
}

const props = defineProps<Props>();

const page = usePage<PageProps & { search?: string }>();

const rolesData = computed(() => props.roles || []);

const searchQuery = ref(props.search || '');

const debouncedSearch = debounce(() => {
    router.get('/settings/roles', { search: searchQuery.value, page: 1 }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}, 300);

watch(searchQuery, () => {
    debouncedSearch();
});

const breadcrumbs = [
    {
        title: 'Roles',
        href: '/settings/roles',
    },
];

const destroy = (id: number) => {
    Swal.fire({
        title: '¿Eliminar rol?',
        text: '¿Estás seguro de que quieres eliminar este rol? Esta acción no se puede revertir.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/settings/roles/${id}`, {
                onSuccess: () => {
                    Swal.fire({
                        title: '¡Eliminado!',
                        text: 'El rol ha sido eliminado correctamente',
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    });
                },
                onError: () => {
                    Swal.fire({
                        title: 'Error',
                        text: 'No se pudo eliminar el rol',
                        icon: 'error',
                        confirmButtonText: 'Aceptar'
                    });
                }
            });
        }
    });
};
</script>

<template>
    <Head title="Gestionar Roles" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold">Gestionar Roles</h1>
                <Button as-child size="sm" class="bg-indigo-500 text-white hover:bg-indigo-700">
                    <Link href="/settings/roles/create">
                        <CirclePlus class="w-4 h-4 mr-2" />
                        Crear Rol
                    </Link>
                </Button>
            </div>

            <div class="relative flex-l rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                <Table>
                    <TableCaption>Una lista de todos los roles en el sistema.</TableCaption>
                    <TableHeader>
                        <TableRow>
                            <TableHead class="w-[100px]">ID</TableHead>
                            <TableHead>Nombre</TableHead>
                            <TableHead>Permisos</TableHead>
                            <TableHead class="text-right">Acciones</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-if="rolesData.length === 0">
                            <TableCell colspan="4" class="text-center">
                                No hay roles para mostrar.
                            </TableCell>
                        </TableRow>
                        <TableRow v-for="role in rolesData" :key="role.id">
                            <TableCell class="font-medium">{{ role.id }}</TableCell>
                            <TableCell>{{ role.name }}</TableCell>
                            <TableCell class="whitespace-normal">
                                <span v-for="permission in role.permissions" :key="permission.id"
                                    class="mr-2 inline-flex items-center rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-medium text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200">
                                    {{ permission.name }}
                                </span>
                            </TableCell>
                            <TableCell class="flex justify-end gap-2">
                                <Button as-child size="sm" class="bg-indigo-500 text-white hover:bg-indigo-700">
                                    <Link :href="`/settings/roles/${role.id}/edit`">
                                        <Pencil class="w-4 h-4" />
                                    </Link>
                                </Button>
                                <Button @click="destroy(role.id)" size="sm" variant="destructive">
                                    <Trash2 class="w-4 h-4" />
                                </Button>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </div>
    </AppLayout>
</template>
