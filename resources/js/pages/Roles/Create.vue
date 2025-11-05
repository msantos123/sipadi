<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle, CardFooter } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import InputError from '@/components/InputError.vue';

interface Permission {
    id: number;
    name: string;
}

const props = defineProps<{
    permissions: Permission[];
}>();

const form = useForm({
    name: '',
    permissions: [] as number[],
});

const submit = () => {
    console.log(form.permissions);
    form.post('/settings/roles', {
        onFinish: () => form.reset('name', 'permissions'),
    });
};

const handleCheckboxChange = (permissionId: number) => {
    console.log('Checkbox changed for permission ID:', permissionId);
    if (form.permissions.includes(permissionId)) {
        form.permissions = form.permissions.filter((id) => id !== permissionId);
    } else {
        form.permissions.push(permissionId);
    }
};

const breadcrumbs = [
    { title: 'Roles', href: '/settings/roles' },
    { title: 'Crear Rol', href: '#' },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 rounded-xl p-4">
            <h1 class="text-2xl font-bold">Crear Nuevo Rol</h1>
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
                                    <Checkbox
                                        :id="`perm_${permission.id}`"
                                        :checked="form.permissions.includes(permission.id)"
                                        @click="() => handleCheckboxChange(permission.id)"
                                    />
                                    <Label :for="`perm_${permission.id}`" class="font-normal">{{ permission.name }}</Label>
                                </div>
                            </div>
                            <InputError :message="form.errors.permissions" />
                        </div>
                    </CardContent>
                    <CardFooter class="flex justify-end gap-4">
                        <Button as-child variant="outline">
                            <Link href= "/settings/roles">Cancelar</Link>
                        </Button>
                        <Button type="submit" :disabled="form.processing">
                            {{ form.processing ? 'Guardando...' : 'Guardar' }}
                        </Button>
                    </CardFooter>
                </Card>
            </form>
        </div>
    </AppLayout>
</template>
