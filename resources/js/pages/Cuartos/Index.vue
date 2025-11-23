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
import { Trash2, Plus } from 'lucide-vue-next';

interface TipoCuarto {
    nombre: string;
    nro_habitaciones: number;
    nro_personas: number;
}

const props = defineProps({
    locations: {
        type: Array as () => Array<{ id: string; nombre: string }>,
        required: true,
    },
    cuartos: {
        type: Array as () => Array<TipoCuarto>,
        required: true,
    },
    selected_location_id: {
        type: String,
        default: null,
    },
});

const selectedLocation = ref(props.selected_location_id);

// Tipos predefinidos
const tiposPredefinidos = ['SIMPLE', 'DOBLE', 'TRIPLE', 'CUADRUPLE', 'FAMILIAR'];

// Formulario para agregar nuevo tipo
const nuevoTipo = ref({
    tipo: '', // Puede ser un tipo predefinido o 'PERSONALIZADO'
    nombrePersonalizado: '',
    nro_habitaciones: 0,
    nro_personas: 0,
});

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

const agregarTipo = () => {
    const nombre = nuevoTipo.value.tipo === 'PERSONALIZADO' 
        ? nuevoTipo.value.nombrePersonalizado 
        : nuevoTipo.value.tipo;

    if (!nombre || nombre.trim() === '') {
        alert('Por favor, ingrese un nombre para el tipo de cuarto');
        return;
    }

    // Verificar si ya existe
    const existe = form.cuartos.find(c => c.nombre.toUpperCase() === nombre.toUpperCase());
    if (existe) {
        alert('Este tipo de cuarto ya existe');
        return;
    }

    form.cuartos.push({
        nombre: nombre.toUpperCase(),
        nro_habitaciones: nuevoTipo.value.nro_habitaciones,
        nro_personas: nuevoTipo.value.nro_personas,
    });

    // Resetear formulario
    nuevoTipo.value = {
        tipo: '',
        nombrePersonalizado: '',
        nro_habitaciones: 0,
        nro_personas: 0,
    };
};

const eliminarTipo = (index: number) => {
    if (confirm('¿Está seguro de eliminar este tipo de cuarto?')) {
        form.cuartos.splice(index, 1);
    }
};

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
                <!-- Formulario para agregar nuevo tipo -->
                <div class="p-6 border-2 border-dashed rounded-lg bg-gray-50 dark:bg-gray-800">
                    <h3 class="text-lg font-semibold mb-4">Agregar Nuevo Tipo de Cuarto</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <!-- Select de tipo -->
                        <div class="space-y-2">
                            <Label for="tipo">Tipo de Cuarto</Label>
                            <Select v-model="nuevoTipo.tipo">
                                <SelectTrigger id="tipo">
                                    <SelectValue placeholder="Seleccione tipo" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectGroup>
                                        <SelectItem v-for="tipo in tiposPredefinidos" :key="tipo" :value="tipo">
                                            {{ tipo }}
                                        </SelectItem>
                                        <SelectItem value="PERSONALIZADO">PERSONALIZADO</SelectItem>
                                    </SelectGroup>
                                </SelectContent>
                            </Select>
                        </div>

                        <!-- Input personalizado (solo si selecciona PERSONALIZADO) -->
                        <div v-if="nuevoTipo.tipo === 'PERSONALIZADO'" class="space-y-2">
                            <Label for="nombrePersonalizado">Nombre Personalizado</Label>
                            <Input 
                                id="nombrePersonalizado" 
                                v-model="nuevoTipo.nombrePersonalizado" 
                                type="text" 
                                placeholder="Ej: SUITE"
                                class="w-full mt-1" 
                            />
                        </div>

                        <!-- Número de habitaciones -->
                        <div class="space-y-2">
                            <Label for="nro_habitaciones">Nro. Habitaciones</Label>
                            <Input 
                                id="nro_habitaciones" 
                                v-model.number="nuevoTipo.nro_habitaciones" 
                                type="number" 
                                min="0" 
                                class="w-full mt-1" 
                            />
                        </div>

                        <!-- Número de personas -->
                        <div class="space-y-2">
                            <Label for="nro_personas">Capacidad Personas</Label>
                            <Input 
                                id="nro_personas" 
                                v-model.number="nuevoTipo.nro_personas" 
                                type="number" 
                                min="0" 
                                class="w-full mt-1" 
                            />
                        </div>
                    </div>

                    <div class="mt-4 flex justify-end">
                        <Button 
                            type="button" 
                            @click="agregarTipo"
                            :disabled="!nuevoTipo.tipo || (nuevoTipo.tipo === 'PERSONALIZADO' && !nuevoTipo.nombrePersonalizado)"
                            class="bg-green-600 hover:bg-green-700"
                        >
                            <Plus class="w-4 h-4 mr-2" />
                            Agregar Tipo
                        </Button>
                    </div>
                </div>

                <!-- Lista de tipos agregados -->
                <div v-if="form.cuartos.length > 0" class="mt-8">
                    <h3 class="text-lg font-semibold mb-4">Tipos de Cuarto Configurados</h3>
                    
                    <form @submit.prevent="submit">
                        <div class="space-y-4">
                            <div 
                                v-for="(cuarto, index) in form.cuartos" 
                                :key="index" 
                                class="p-4 border rounded-lg bg-white dark:bg-gray-900 flex items-center gap-4"
                            >
                                <div class="flex-1 grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <!-- Nombre del tipo -->
                                    <div>
                                        <Label>Tipo</Label>
                                        <div class="mt-1 px-3 py-2 bg-gray-100 dark:bg-gray-800 rounded-md font-medium">
                                            {{ cuarto.nombre }}
                                        </div>
                                    </div>

                                    <!-- Número de habitaciones -->
                                    <div>
                                        <Label :for="`nro_habitaciones_${index}`">Nro. Habitaciones</Label>
                                        <Input 
                                            :id="`nro_habitaciones_${index}`" 
                                            v-model.number="cuarto.nro_habitaciones" 
                                            type="number" 
                                            min="0" 
                                            class="w-full mt-1" 
                                        />
                                        <InputError class="mt-2" :message="form.errors[`cuartos.${index}.nro_habitaciones`]" />
                                    </div>

                                    <!-- Número de personas -->
                                    <div>
                                        <Label :for="`nro_personas_${index}`">Capacidad Personas</Label>
                                        <Input 
                                            :id="`nro_personas_${index}`" 
                                            v-model.number="cuarto.nro_personas" 
                                            type="number" 
                                            min="0" 
                                            class="w-full mt-1" 
                                        />
                                        <InputError class="mt-2" :message="form.errors[`cuartos.${index}.nro_personas`]" />
                                    </div>
                                </div>

                                <!-- Botón eliminar -->
                                <Button 
                                    type="button" 
                                    variant="destructive" 
                                    size="icon"
                                    @click="eliminarTipo(index)"
                                >
                                    <Trash2 class="w-4 h-4" />
                                </Button>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end">
                            <Button type="submit" :disabled="form.processing || form.cuartos.length === 0">
                                Guardar Todos los Cambios
                            </Button>
                        </div>
                    </form>
                </div>

                <div v-else class="mt-8 text-center py-12 border-2 border-dashed rounded-lg">
                    <p class="text-gray-500">No hay tipos de cuarto configurados. Agregue uno usando el formulario de arriba.</p>
                </div>
            </div>

            <div v-else class="mt-8 text-center py-12 border-2 border-dashed rounded-lg">
                 <p class="text-gray-500">Por favor, seleccione un establecimiento o sucursal para gestionar los tipos de cuarto.</p>
            </div>
        </div>
    </AppLayout>
</template>
