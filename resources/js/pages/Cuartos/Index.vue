<script setup lang="ts">
import { ref, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Heading from '@/components/Heading.vue';
import { Select, SelectContent, SelectGroup, SelectItem, SelectLabel, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';

const props = defineProps({
    locations: {
        type: Array as () => Array<{ id: string; nombre: string }>,
        required: true,
    },
    cuartos: {
        type: Array as () => Array<{ nombre: string; nro_habitaciones: number; nro_personas: number }>,
        required: true,
    },
    selected_location_id: {
        type: String,
        default: null,
    },
});

const selectedLocation = ref(props.selected_location_id);

const form = useForm({
    cuartos: props.cuartos.map(c => ({ ...c })),
    location_id: props.selected_location_id,
});

watch(selectedLocation, (newValue) => {
    router.get('/cuartos', {
        location_id: newValue,
    }, {
        preserveState: true,
        replace: true,
        onSuccess: (page) => {
            form.cuartos = (page.props.cuartos as typeof props.cuartos).map(c => ({ ...c }));
            form.location_id = page.props.selected_location_id as string;
        },
    });
});

const submit = () => {
    form.post('/cuartos', {
        preserveScroll: true,
        onSuccess: () => {
            // Opcional: mostrar notificación de éxito
        },
    });
};

</script>

<template>
    <AppLayout>
        <div class="p-4 md:p-8">
            <Heading title="Gestión de Tipos de Cuarto" />

            <div class="mt-6 max-w-xl">
                <Select v-model="selectedLocation">
                    <SelectTrigger>
                        <SelectValue placeholder="Seleccione un establecimiento o sucursal" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectGroup>
                            <SelectLabel>Establecimientos y Sucursales</SelectLabel>
                            <SelectItem v-for="location in locations" :key="location.id" :value="location.id">
                                {{ location.nombre }}
                            </SelectItem>
                        </SelectGroup>
                    </SelectContent>
                </Select>
            </div>

            <div v-if="selectedLocation" class="mt-8">
                <form @submit.prevent="submit">
                    <div class="space-y-6">
                        <div v-for="(cuarto, index) in form.cuartos" :key="index" class="p-4 border rounded-lg">
                            <h3 class="text-lg font-medium mb-4">{{ cuarto.nombre }}</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <Label :for="`nro_habitaciones_${index}`">Nro. de Habitaciones</Label>
                                    <Input :id="`nro_habitaciones_${index}`" v-model="cuarto.nro_habitaciones" type="number" min="0" class="w-full mt-1" />
                                    <InputError class="mt-2" :message="form.errors[`cuartos.${index}.nro_habitaciones`]" />
                                </div>
                                <div>
                                    <Label :for="`nro_personas_${index}`">Capacidad de Personas</Label>
                                    <Input :id="`nro_personas_${index}`" v-model="cuarto.nro_personas" type="number" min="0" class="w-full mt-1" />
                                    <InputError class="mt-2" :message="form.errors[`cuartos.${index}.nro_personas`]" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end">
                        <Button type="submit" :disabled="form.processing">
                            Guardar Cambios
                        </Button>
                    </div>
                </form>
            </div>

            <div v-else class="mt-8 text-center py-12 border-2 border-dashed rounded-lg">
                 <p class="text-gray-500">Por favor, seleccione un establecimiento o sucursal para gestionar los tipos de cuarto.</p>
            </div>
        </div>
    </AppLayout>
</template>
