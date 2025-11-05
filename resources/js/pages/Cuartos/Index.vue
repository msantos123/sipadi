<script setup lang="ts">
import { ref, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Heading from '@/components/Heading.vue';
import { Select, SelectContent, SelectGroup, SelectItem, SelectLabel, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';

const props = defineProps({
    locations: {
        type: Array as () => Array<{ id: string; nombre: string }>,
        required: true,
    },
    cuartos: {
        type: Array as () => Array<{ id: number; nombre: string; nro_habitaciones: number; nro_personas: number }>,
        required: true,
    },
    selected_location_id: {
        type: String,
        default: null,
    },
});

const isCreateModalOpen = ref(false);
const selectedLocation = ref(props.selected_location_id);

const form = useForm({
    nombre: '',
    nro_habitaciones: 1,
    nro_personas: 1,
    location_id: props.selected_location_id,
});

watch(selectedLocation, (newValue) => {
    form.location_id = newValue;
    router.get('/cuartos', {
        location_id: newValue,
    }, {
        preserveState: true,
        replace: true,
    });
});

const submit = () => {
    form.post('/cuartos', {
        preserveScroll: true,
        onSuccess: () => {
            isCreateModalOpen.value = false;
            form.reset();
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
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold">Cuartos Registrados</h2>
                    <Dialog :open="isCreateModalOpen" @update:open="isCreateModalOpen = $event">
                        <DialogTrigger as-child>
                            <Button :disabled="!selectedLocation">
                                Agregar Cuarto
                            </Button>
                        </DialogTrigger>
                        <DialogContent class="sm:max-w-[425px]">
                            <DialogHeader>
                                <DialogTitle>Agregar Nuevo Cuarto</DialogTitle>
                                <DialogDescription>
                                    Complete los detalles del nuevo tipo de cuarto. Se asociará a la ubicación seleccionada.
                                </DialogDescription>
                            </DialogHeader>
                            <form @submit.prevent="submit">
                                <div class="grid gap-4 py-4">
                                    <div class="grid grid-cols-4 items-center gap-4">
                                        <Label for="nombre" class="text-right">Nombre</Label>
                                        <div class="col-span-3">
                                            <Input id="nombre" v-model="form.nombre" class="w-full" />
                                            <InputError class="mt-2" :message="form.errors.nombre" />
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-4 items-center gap-4">
                                        <Label for="nro_habitaciones" class="text-right">Nro. Habitaciones</Label>
                                        <div class="col-span-3">
                                            <Input id="nro_habitaciones" v-model="form.nro_habitaciones" type="number" min="1" class="w-full" />
                                            <InputError class="mt-2" :message="form.errors.nro_habitaciones" />
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-4 items-center gap-4">
                                        <Label for="nro_personas" class="text-right">Nro. Personas</Label>
                                        <div class="col-span-3">
                                            <Input id="nro_personas" v-model="form.nro_personas" type="number" min="1" class="w-full" />
                                            <InputError class="mt-2" :message="form.errors.nro_personas" />
                                        </div>
                                    </div>
                                </div>
                                <DialogFooter>
                                    <Button type="submit" :disabled="form.processing">Guardar Cuarto</Button>
                                </DialogFooter>
                            </form>
                        </DialogContent>
                    </Dialog>
                </div>
                <div class="border rounded-lg">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Nombre del Cuarto</TableHead>
                                <TableHead>Nro. de Habitaciones</TableHead>
                                <TableHead>Capacidad de Personas</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <template v-if="cuartos.length > 0">
                                <TableRow v-for="cuarto in cuartos" :key="cuarto.id">
                                    <TableCell>{{ cuarto.nombre }}</TableCell>
                                    <TableCell>{{ cuarto.nro_habitaciones }}</TableCell>
                                    <TableCell>{{ cuarto.nro_personas }}</TableCell>
                                </TableRow>
                            </template>
                            <template v-else>
                                <TableRow>
                                    <TableCell colspan="3" class="text-center py-8">
                                        No hay cuartos registrados para esta ubicación. Puede agregar uno nuevo.
                                    </TableCell>
                                </TableRow>
                            </template>
                        </TableBody>
                    </Table>
                </div>
            </div>
            <div v-else class="mt-8 text-center py-12 border-2 border-dashed rounded-lg">
                 <p class="text-gray-500">Por favor, seleccione un establecimiento o sucursal para ver los cuartos.</p>
            </div>
        </div>
    </AppLayout>
</template>
