<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle, CardFooter } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import InputError from '@/components/InputError.vue';
import type { BreadcrumbItem } from '@/types';

interface Permission {
    id: number;
    name: string;
}

interface Role {
    id: number;
    name: string;
    permissions: Permission[];
}

const props = defineProps<{
    role: Role;
    permissions: Permission[];
}>();

const form = useForm({
    name: props.role.name,
    permissions: props.role.permissions.map(p => p.id) as number[],
});

const handleCheckboxChange = (permissionId: number) => {
    // 1. Clonar el array existente para no mutar directamente (aunque useForm lo maneja)
    let currentPermissions = [...form.permissions];
    const index = currentPermissions.indexOf(permissionId);

    if (index > -1) {
        // Quitar el ID si ya está
        currentPermissions.splice(index, 1);
    } else {
        // Añadir el ID si no está
        currentPermissions.push(permissionId);
    }

    // 2. Asignar el nuevo array (CLAVE para la reactividad en componentes complejos como shadcn-vue/Radix)
    form.permissions = currentPermissions;
};

const submit = () => {
    form.put(`/settings/roles/${props.role.id}`);
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Roles', href: "/settings/roles" },
    { title: `Editar: ${props.role.name}`, href: '#' },
];

</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 rounded-xl p-4">
            <h1 class="text-2xl font-bold">Editar Rol: {{ props.role.name }}</h1>
            <form @submit.prevent="submit">
                <Card>
                    <CardHeader>
                        <CardTitle>Detalles del Rol</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <div class="space-y-2">
                            <Label for="name">Nombre del Rol</Label>
                            <Input id="name" v-model="form.name" type="text" required />
                            <InputError :message="form.errors.name" />
                        </div>

                        <div class="space-y-2">
                            <Label>Permisos</Label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                                <div v-for="permission in permissions" :key="permission.id" class="flex items-center space-x-2">

                                    <input
                                        :id="`perm_${permission.id}`"
                                        type="checkbox"
                                        :value="permission.id"
                                        v-model="form.permissions"
                                        class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary"
                                    />

                                    <Label :for="`perm_${permission.id}`" class="font-normal">{{ permission.name }}</Label>
                                </div>
                            </div>
                            <InputError :message="form.errors.permissions" />
                        </div>
                    </CardContent>
                    <CardFooter class="flex justify-end gap-4">
                        <Button as-child variant="outline">
                            <Link href="/settings/roles">Cancelar</Link>
                        </Button>
                        <Button type="submit" :disabled="form.processing">
                            {{ form.processing ? 'Actualizando...' : 'Actualizar' }}
                        </Button>
                    </CardFooter>
                </Card>
            </form>
        </div>
    </AppLayout>
</template>
