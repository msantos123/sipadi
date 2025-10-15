<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Usuarios', href: '/usuarios' },
    { title: 'Crear Usuario', href: '#' },
];

const form = useForm({
  apellido_paterno: '',
  apellido_materno: '',
  nombres: '',
  ci: '',
  celular: '',
  cargo: '',
  email: '',
  password: '',
  password_confirmation: '',
});

const submit = () => {
  form.post('/usuarios', {
    onSuccess: () => {
      form.reset();
    },
  });
};
</script>

<template>
    <Head title="Crear Usuario" />
    <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-1 flex-col gap-4 rounded-xl p-4">
      <h1 class="text-2xl font-bold">Crear un Nuevo Usuario</h1>
      <form @submit.prevent="submit" class="space-y-6 max-w-lg">

        <div class="space-y-2">
            <Label for="nombres">Nombres</Label>
            <Input
                id="nombres"
                v-model="form.nombres"
                type="text"
                placeholder="Nombres del usuario"
                required
            />
            <p v-if="form.errors.nombres" class="text-sm text-red-500 mt-1">{{ form.errors.nombres }}</p>
        </div>

        <div class="space-y-2">
            <Label for="apellido_paterno">Apellido Paterno</Label>
            <Input
                id="apellido_paterno"
                v-model="form.apellido_paterno"
                type="text"
                placeholder="Apellido paterno"
                required
            />
            <p v-if="form.errors.apellido_paterno" class="text-sm text-red-500 mt-1">{{ form.errors.apellido_paterno }}</p>
        </div>

        <div class="space-y-2">
            <Label for="apellido_materno">Apellido Materno</Label>
            <Input
                id="apellido_materno"
                v-model="form.apellido_materno"
                type="text"
                placeholder="Apellido materno"
            />
            <p v-if="form.errors.apellido_materno" class="text-sm text-red-500 mt-1">{{ form.errors.apellido_materno }}</p>
        </div>

        <div class="space-y-2">
            <Label for="ci">Carnet de Identidad (CI)</Label>
            <Input
                id="ci"
                v-model="form.ci"
                type="text"
                placeholder="Número de carnet"
                required
            />
            <p v-if="form.errors.ci" class="text-sm text-red-500 mt-1">{{ form.errors.ci }}</p>
        </div>

        <div class="space-y-2">
            <Label for="celular">Celular</Label>
            <Input
                id="celular"
                v-model="form.celular"
                type="text"
                placeholder="Número de celular"
            />
            <p v-if="form.errors.celular" class="text-sm text-red-500 mt-1">{{ form.errors.celular }}</p>
        </div>

        <div class="space-y-2">
            <Label for="cargo">Cargo</Label>
            <Input
                id="cargo"
                v-model="form.cargo"
                type="text"
                placeholder="Cargo del usuario"
                required
            />
            <p v-if="form.errors.cargo" class="text-sm text-red-500 mt-1">{{ form.errors.cargo }}</p>
        </div>

        <div class="space-y-2">
            <Label for="email">Email</Label>
            <Input
                id="email"
                v-model="form.email"
                type="email"
                placeholder="correo@ejemplo.com"
                required
            />
            <p v-if="form.errors.email" class="text-sm text-red-500 mt-1">{{ form.errors.email }}</p>
        </div>

        <div class="space-y-2">
            <Label for="password">Contraseña</Label>
            <Input
                id="password"
                v-model="form.password"
                type="password"
                placeholder="Contraseña"
                required
            />
            <p v-if="form.errors.password" class="text-sm text-red-500 mt-1">{{ form.errors.password }}</p>
        </div>

        <div class="space-y-2">
            <Label for="password_confirmation">Confirmar Contraseña</Label>
            <Input
                id="password_confirmation"
                v-model="form.password_confirmation"
                type="password"
                placeholder="Repita la contraseña"
                required
            />
        </div>

        <div class="flex items-center gap-4">
          <Button type="submit" :disabled="form.processing" class="bg-indigo-500 text-white hover:bg-indigo-700">
            <span v-if="form.processing">Guardando...</span>
            <span v-else>Guardar</span>
          </Button>
          <Button as="a" href="/usuarios" variant="outline">Cancelar</Button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>
