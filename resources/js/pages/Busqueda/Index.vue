<script setup lang="ts">
import { ref, computed } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Search, X, Download } from 'lucide-vue-next';

interface Nacionalidad {
    id: number;
    gentilicio: string;
}

interface Resultado {
    id: number;
    nombre_completo: string;
    documento: string;
    nacionalidad: string;
    procedencia: string;
    sexo: string;
    edad: number | null;
    fecha_ingreso: string;
    fecha_salida: string;
    establecimiento: string;
    departamento_establecimiento: string;
}

interface PaginatedResults {
    data: Resultado[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: Array<{
        url: string | null;
        label: string;
        active: boolean;
    }>;
}

const props = defineProps<{
    nacionalidades: Nacionalidad[];
    resultados?: PaginatedResults;
    filtros?: {
        nacionalidad_id?: number | null;
        nombre?: string;
        documento?: string;
        rango_edad?: string;
        fecha_desde?: string;
        fecha_hasta?: string;
    };
}>();

// Estados para búsqueda de nacionalidad
const searchNacionalidad = ref('');
const showNacionalidadDropdown = ref(false);
const selectedNacionalidad = ref<Nacionalidad | null>(null);

// Inicializar nacionalidad seleccionada si viene de filtros
if (props.filtros?.nacionalidad_id) {
    selectedNacionalidad.value = props.nacionalidades.find(n => n.id === props.filtros?.nacionalidad_id) || null;
}

const form = useForm({
    nacionalidad_id: props.filtros?.nacionalidad_id?.toString() || '',
    nombre: props.filtros?.nombre || '',
    documento: props.filtros?.documento || '',
    rango_edad: props.filtros?.rango_edad || '',
    fecha_desde: props.filtros?.fecha_desde || '',
    fecha_hasta: props.filtros?.fecha_hasta || '',
});

// Computed para filtrar nacionalidades según búsqueda
const filteredNacionalidades = computed(() => {
    if (!props.nacionalidades) return [];
    const search = searchNacionalidad.value.toLowerCase();
    return props.nacionalidades.filter(n => 
        n.gentilicio.toLowerCase().includes(search)
    );
});

// Funciones para manejar nacionalidad
const addNacionalidad = (nac: Nacionalidad) => {
    selectedNacionalidad.value = nac;
    form.nacionalidad_id = nac.id.toString();
    searchNacionalidad.value = '';
    showNacionalidadDropdown.value = false;
};

const removeNacionalidad = () => {
    selectedNacionalidad.value = null;
    form.nacionalidad_id = '';
};

// Helper para setTimeout (evitar errores de TypeScript en template)
const handleBlur = () => {
    setTimeout(() => {
        showNacionalidadDropdown.value = false;
    }, 200);
};

// Validación para nombres (solo letras mayúsculas y espacios, sin números ni caracteres especiales)
const validateNombre = (event: Event) => {
    const input = event.target as HTMLInputElement;
    // Remover números y caracteres especiales, mantener solo letras y espacios
    let value = input.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/g, '');
    // Convertir a mayúsculas
    value = value.toUpperCase();
    input.value = value;
    form.nombre = value;
};

// Validación para documento (solo números y guion, sin letras ni caracteres especiales)
const validateDocumento = (event: Event) => {
    const input = event.target as HTMLInputElement;
    // Permitir solo números y guion
    const value = input.value.replace(/[^0-9-]/g, '');
    input.value = value;
    form.documento = value;
};

const buscar = () => {
    // Convertir rango de edad a edad_min y edad_max
    let edad_min = null;
    let edad_max = null;
    
    if (form.rango_edad) {
        const rangos: Record<string, { min: number; max: number | null }> = {
            '18-25': { min: 18, max: 25 },
            '26-35': { min: 26, max: 35 },
            '36-45': { min: 36, max: 45 },
            '46-60': { min: 46, max: 60 },
            '60+': { min: 60, max: null },
        };
        
        const rango = rangos[form.rango_edad];
        if (rango) {
            edad_min = rango.min;
            edad_max = rango.max;
        }
    }

    const data = {
        nacionalidad_id: form.nacionalidad_id ? parseInt(form.nacionalidad_id) : null,
        nombre: form.nombre,
        documento: form.documento,
        edad_min,
        edad_max,
        rango_edad: form.rango_edad,
        fecha_desde: form.fecha_desde,
        fecha_hasta: form.fecha_hasta,
    };

    router.get('/busqueda/search', data, {
        preserveState: true,
        preserveScroll: true,
    });
};

const limpiarFiltros = () => {
    form.reset();
    selectedNacionalidad.value = null;
    searchNacionalidad.value = '';
    router.get('/busqueda');
};

const goToPage = (url: string | null) => {
    if (!url) return;
    router.get(url, {}, {
        preserveState: true,
        preserveScroll: true,
    });
};

const downloadExcel = () => {
    // Convertir rango de edad a edad_min y edad_max
    let edad_min = null;
    let edad_max = null;
    
    if (form.rango_edad) {
        const rangos: Record<string, { min: number; max: number | null }> = {
            '18-25': { min: 18, max: 25 },
            '26-35': { min: 26, max: 35 },
            '36-45': { min: 36, max: 45 },
            '46-60': { min: 46, max: 60 },
            '60+': { min: 60, max: null },
        };
        
        const rango = rangos[form.rango_edad];
        if (rango) {
            edad_min = rango.min;
            edad_max = rango.max;
        }
    }

    const params = new URLSearchParams();
    
    if (form.nacionalidad_id) params.append('nacionalidad_id', form.nacionalidad_id);
    if (form.nombre) params.append('nombre', form.nombre);
    if (form.documento) params.append('documento', form.documento);
    if (edad_min !== null) params.append('edad_min', edad_min.toString());
    if (edad_max !== null) params.append('edad_max', edad_max.toString());
    if (form.rango_edad) params.append('rango_edad', form.rango_edad);
    if (form.fecha_desde) params.append('fecha_desde', form.fecha_desde);
    if (form.fecha_hasta) params.append('fecha_hasta', form.fecha_hasta);
    
    window.location.href = `/busqueda/export-excel?${params.toString()}`;
};
</script>

<template>
    <AppLayout>
        <template #header>
            <Heading title="Búsqueda Avanzada de Estancias" />
        </template>

        <div class="space-y-6">
            <!-- Formulario de búsqueda -->
            <Card>
                <CardHeader>
                    <CardTitle>Filtros de Búsqueda</CardTitle>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="buscar" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <!-- Nacionalidad con búsqueda -->
                        <div class="space-y-2 relative">
                            <Label>Nacionalidad</Label>
                            <input 
                                type="text" 
                                v-model="searchNacionalidad"
                                @focus="showNacionalidadDropdown = true"
                                @blur="handleBlur"
                                placeholder="Buscar nacionalidad..."
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                            <!-- Dropdown de resultados -->
                            <div v-if="showNacionalidadDropdown && filteredNacionalidades.length > 0" class="absolute z-10 w-full mt-1 bg-background border border-input rounded-md shadow-lg max-h-60 overflow-y-auto">
                                <button
                                    v-for="nac in filteredNacionalidades"
                                    :key="nac.id"
                                    @mousedown.prevent="addNacionalidad(nac)"
                                    type="button"
                                    class="w-full px-4 py-2 text-left text-sm hover:bg-accent hover:text-accent-foreground"
                                >
                                    {{ nac.gentilicio }}
                                </button>
                            </div>
                            <!-- Tag seleccionado -->
                            <div v-if="selectedNacionalidad" class="flex items-center gap-2 mt-2">
                                <span class="inline-flex items-center px-3 py-1 text-sm font-medium text-primary bg-primary/10 rounded-full">
                                    {{ selectedNacionalidad.gentilicio }}
                                    <button
                                        type="button"
                                        @click="removeNacionalidad"
                                        class="ml-2 text-primary hover:text-primary/80"
                                    >
                                        <X :size="14" />
                                    </button>
                                </span>
                            </div>
                        </div>

                        <!-- Nombres y Apellidos con validación -->
                        <div class="space-y-2">
                            <Label for="nombre">Nombres y Apellidos</Label>
                            <Input
                                id="nombre"
                                v-model="form.nombre"
                                @input="validateNombre"
                                type="text"
                                placeholder="Buscar por nombre..."
                            />
                        </div>

                        <!-- CI/Pasaporte con validación -->
                        <div class="space-y-2">
                            <Label for="documento">Carnet de Identidad/Pasaporte</Label>
                            <Input
                                id="documento"
                                v-model="form.documento"
                                @input="validateDocumento"
                                type="text"
                                placeholder="Número de documento..."
                            />
                        </div>

                        <!-- Rango de Edad -->
                        <div class="space-y-2">
                            <Label for="rango_edad">Rango de Edad</Label>
                            <select
                                id="rango_edad"
                                v-model="form.rango_edad"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <option value="">Todos</option>
                                <option value="18-25">18-25 años</option>
                                <option value="26-35">26-35 años</option>
                                <option value="36-45">36-45 años</option>
                                <option value="46-60">46-60 años</option>
                                <option value="60+">60+ años</option>
                            </select>
                        </div>

                        <!-- Fecha Desde -->
                        <div class="space-y-2">
                            <Label for="fecha_desde">Fecha Desde</Label>
                            <Input
                                id="fecha_desde"
                                v-model="form.fecha_desde"
                                type="date"
                            />
                        </div>

                        <!-- Fecha Hasta -->
                        <div class="space-y-2">
                            <Label for="fecha_hasta">Fecha Hasta</Label>
                            <Input
                                id="fecha_hasta"
                                v-model="form.fecha_hasta"
                                type="date"
                            />
                        </div>

                        <!-- Botones -->
                        <div class="col-span-full flex items-center justify-end gap-4 pt-4">
                            <Button 
                                type="button" 
                                variant="outline" 
                                @click="limpiarFiltros"
                                :disabled="form.processing"
                            >
                                Limpiar
                            </Button>
                            <Button 
                                type="submit" 
                                :disabled="form.processing"
                            >
                                <Search class="mr-2 h-4 w-4" />
                                Buscar
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>

            <!-- Resultados -->
            <Card v-if="resultados">
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <CardTitle>
                            Resultados de Búsqueda
                            <span class="text-sm font-normal text-muted-foreground ml-2">
                                ({{ resultados.total }} registro{{ resultados.total !== 1 ? 's' : '' }} encontrado{{ resultados.total !== 1 ? 's' : '' }})
                            </span>
                        </CardTitle>
                        <Button 
                            v-if="resultados.data.length > 0"
                            @click="downloadExcel"
                            variant="outline"
                            size="sm"
                        >
                            <Download class="mr-2 h-4 w-4" />
                            Descargar Excel
                        </Button>
                    </div>
                </CardHeader>
                <CardContent>
                    <div v-if="resultados.data.length > 0">
                        <!-- Tabla de resultados -->
                        <div class="rounded-md border overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Nombre y Apellido</TableHead>
                                        <TableHead>Documento</TableHead>
                                        <TableHead>Nacionalidad</TableHead>
                                        <TableHead>Procedencia</TableHead>
                                        <TableHead>Sexo</TableHead>
                                        <TableHead>Edad</TableHead>
                                        <TableHead>Fecha Ingreso</TableHead>
                                        <TableHead>Fecha Salida</TableHead>
                                        <TableHead>Establecimiento</TableHead>
                                        <TableHead>Departamento</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="resultado in resultados.data" :key="resultado.id">
                                        <TableCell class="font-medium">{{ resultado.nombre_completo }}</TableCell>
                                        <TableCell>{{ resultado.documento }}</TableCell>
                                        <TableCell>{{ resultado.nacionalidad }}</TableCell>
                                        <TableCell>{{ resultado.procedencia }}</TableCell>
                                        <TableCell>{{ resultado.sexo }}</TableCell>
                                        <TableCell>{{ resultado.edad ?? 'N/A' }}</TableCell>
                                        <TableCell>{{ resultado.fecha_ingreso }}</TableCell>
                                        <TableCell>{{ resultado.fecha_salida }}</TableCell>
                                        <TableCell>{{ resultado.establecimiento }}</TableCell>
                                        <TableCell>{{ resultado.departamento_establecimiento }}</TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>

                        <!-- Paginación -->
                        <div v-if="resultados.last_page > 1" class="flex items-center justify-between mt-6">
                            <div class="text-sm text-muted-foreground">
                                Mostrando {{ ((resultados.current_page - 1) * resultados.per_page) + 1 }} 
                                a {{ Math.min(resultados.current_page * resultados.per_page, resultados.total) }} 
                                de {{ resultados.total }} resultados
                            </div>
                            <div class="flex gap-2">
                                <Button
                                    v-for="link in resultados.links"
                                    :key="link.label"
                                    :variant="link.active ? 'default' : 'outline'"
                                    :disabled="!link.url"
                                    @click="goToPage(link.url)"
                                    size="sm"
                                    v-html="link.label"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Sin resultados -->
                    <div v-else class="text-center py-12 text-muted-foreground">
                        <p class="text-lg">No se encontraron resultados con los filtros aplicados.</p>
                        <p class="text-sm mt-2">Intenta ajustar los criterios de búsqueda.</p>
                    </div>
                </CardContent>
            </Card>

            <!-- Mensaje inicial -->
            <Card v-else>
                <CardContent class="text-center py-12 text-muted-foreground">
                    <Search class="mx-auto h-12 w-12 mb-4 opacity-50" />
                    <p class="text-lg">Utiliza los filtros de búsqueda para encontrar estancias.</p>
                    <p class="text-sm mt-2">Puedes combinar múltiples criterios para refinar tu búsqueda.</p>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
