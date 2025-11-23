<script setup lang="ts">
import PasswordController from '@/actions/App/Http/Controllers/Settings/PasswordController';
import InputError from '@/components/InputError.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { edit } from '@/routes/password';
import { useForm, Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

import HeadingSmall from '@/components/HeadingSmall.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { type BreadcrumbItem } from '@/types';

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Password settings',
        href: edit().url,
    },
];

// Toggles de visibilidad
const currentPasswordVisible = ref(false);
const passwordVisible = ref(false);
const passwordConfirmationVisible = ref(false);

// Patrón de validación de contraseñas
const passwordPattern = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).+$/;

// Crear el formulario con useForm
const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

// Computed properties para validación
const passwordError = computed(() => {
    if (!form.password) return '';
    if (form.password.length < 8) {
        return 'La contraseña debe tener al menos 8 caracteres';
    }
    if (!passwordPattern.test(form.password)) {
        return 'Debe contener un número, una letra mayúscula, una letra minúscula y un carácter especial';
    }
    return '';
});

const passwordConfirmationError = computed(() => {
    if (!form.password_confirmation) return '';
    if (!passwordPattern.test(form.password_confirmation)) {
        return 'Debe contener un número, una letra mayúscula, una letra minúscula y un carácter especial';
    }
    if (form.password && form.password !== form.password_confirmation) {
        return 'Las contraseñas no coinciden';
    }
    return '';
});

const submit = () => {
    form.put('/settings/password', {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
        },
        onError: () => {
            form.reset('password', 'password_confirmation', 'current_password');
        },
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Configuracion de Contraseña" />

        <SettingsLayout>
            <div class="space-y-6">
                <HeadingSmall
                    title="Actualizar Contraseña"
                    description="Asegúrate de que tu cuenta utilice una contraseña larga y aleatoria para mantenerla segura."
                />

                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Contraseña Actual -->
                    <div class="grid gap-2">
                        <Label for="current_password">Contraseña actual</Label>
                        <div class="relative">
                            <Input
                                id="current_password"
                                v-model="form.current_password"
                                :type="currentPasswordVisible ? 'text' : 'password'"
                                class="mt-1 block w-full pr-10"
                                autocomplete="current-password"
                                placeholder="Contraseña actual"
                            />
                            <button
                                type="button"
                                class="absolute inset-y-0 right-0 flex items-center px-3 text-gray-500"
                                @click="currentPasswordVisible = !currentPasswordVisible"
                            >
                                <svg
                                    v-if="currentPasswordVisible"
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-5 w-5"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0 1 12 19c-7 0-11-7-11-7a20.522 20.522 0 0 1 3.377-4.231m4.107-2.69A8.165 8.165 0 0 1 12 5c7 0 11 7 11 7a20.472 20.472 0 0 1-4.22 4.51M3 3l18 18" />
                                </svg>
                                <svg
                                    v-else
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-5 w-5"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                                <span class="sr-only">Alternar visibilidad de contraseña</span>
                            </button>
                        </div>
                        <InputError :message="form.errors.current_password" />
                    </div>

                    <!-- Nueva Contraseña -->
                    <div class="grid gap-2">
                        <Label for="password">Nueva Contraseña</Label>
                        <div class="relative">
                            <Input
                                id="password"
                                v-model="form.password"
                                :type="passwordVisible ? 'text' : 'password'"
                                class="mt-1 block w-full pr-10"
                                autocomplete="new-password"
                                placeholder="Nueva Contraseña"
                            />
                            <button
                                type="button"
                                class="absolute inset-y-0 right-0 flex items-center px-3 text-gray-500"
                                @click="passwordVisible = !passwordVisible"
                            >
                                <svg
                                    v-if="passwordVisible"
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-5 w-5"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0 1 12 19c-7 0-11-7-11-7a20.522 20.522 0 0 1 3.377-4.231m4.107-2.69A8.165 8.165 0 0 1 12 5c7 0 11 7 11 7a20.472 20.472 0 0 1-4.22 4.51M3 3l18 18" />
                                </svg>
                                <svg
                                    v-else
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-5 w-5"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                                <span class="sr-only">Alternar visibilidad de contraseña</span>
                            </button>
                        </div>
                        <div v-if="passwordError" class="text-sm text-red-500">{{ passwordError }}</div>
                        <InputError :message="form.errors.password" />
                    </div>

                    <!-- Confirmar Contraseña -->
                    <div class="grid gap-2">
                        <Label for="password_confirmation">Confirmar Contraseña</Label>
                        <div class="relative">
                            <Input
                                id="password_confirmation"
                                v-model="form.password_confirmation"
                                :type="passwordConfirmationVisible ? 'text' : 'password'"
                                class="mt-1 block w-full pr-10"
                                autocomplete="new-password"
                                placeholder="Confirmar Contraseña"
                            />
                            <button
                                type="button"
                                class="absolute inset-y-0 right-0 flex items-center px-3 text-gray-500"
                                @click="passwordConfirmationVisible = !passwordConfirmationVisible"
                            >
                                <svg
                                    v-if="passwordConfirmationVisible"
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-5 w-5"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0 1 12 19c-7 0-11-7-11-7a20.522 20.522 0 0 1 3.377-4.231m4.107-2.69A8.165 8.165 0 0 1 12 5c7 0 11 7 11 7a20.472 20.472 0 0 1-4.22 4.51M3 3l18 18" />
                                </svg>
                                <svg
                                    v-else
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-5 w-5"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                                <span class="sr-only">Alternar visibilidad de confirmación</span>
                            </button>
                        </div>
                        <div v-if="passwordConfirmationError" class="text-sm text-red-500">{{ passwordConfirmationError }}</div>
                        <InputError :message="form.errors.password_confirmation" />
                    </div>

                    <div class="flex items-center gap-4">
                        <Button
                            type="submit"
                            :disabled="form.processing"
                            data-test="update-password-button"
                            >Guardar Contraseña</Button
                        >

                        <p
                            v-if="form.recentlySuccessful"
                            class="text-sm text-neutral-600"
                        >
                            Guardado con éxito.
                        </p>
                    </div>
                </form>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
