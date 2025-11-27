<script setup lang="ts">
import { ref, computed, PropType } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Heading from '@/components/Heading.vue';
import AppContent from '@/components/AppContent.vue';
import axios from 'axios';
import { X } from 'lucide-vue-next';

// --- Interfaces de TypeScript para el tipado ---
interface Departamento {
    id: number;
    nombre: string;
}

interface Establecimiento {
    id_establecimiento: number;
    razon_social: string;
}

interface Sucursal {
    id_sucursal: number;
    id_casa_matriz: number;
    nombre_sucursal: string;
}

interface EstanciaReporteItem {
    id: number;
    nro_cuarto: string;
    fecha_hora_ingreso: string;
    fecha_hora_salida_efectiva?: string;
    persona?: {
        nombres: string;
        apellido_paterno: string;
        nro_documento: string;
        ocupacion: string;
        nacionalidad?: { pais: string };
        departamento?: { nombre: string };
    };
    lote?: {
        establecimiento?: { razon_social: string };
        sucursal?: { nombre_sucursal: string };
        departamento?: { nombre: string };
    };
    tipo_cuarto?: {
        nombre: string;
    };
}

const props = defineProps({
    departamentos: Array as PropType<Departamento[]>,
    establecimientos: Array as PropType<Establecimiento[]>,
    sucursales: Array as PropType<Sucursal[]>,
    alcance: String as PropType<'nacional' | 'departamental' | 'establecimiento'>,
});

const form = useForm({
    fecha_inicio: '',
    fecha_fin: '',
    departamento_ids: [] as number[],
    establecimiento_ids: [] as number[],
    sucursal_ids: [] as number[],
});

const reporteData = ref<EstanciaReporteItem[]>([]);
const loading = ref(false);

// Estados de búsqueda
const searchDepartamento = ref('');
const searchEstablecimiento = ref('');
const searchSucursal = ref('');
const showDepartamentoDropdown = ref(false);
const showEstablecimientoDropdown = ref(false);
const showSucursalDropdown = ref(false);

// Computed para filtrar opciones según búsqueda
const filteredDepartamentos = computed(() => {
    if (!props.departamentos) return [];
    const search = searchDepartamento.value.toLowerCase();
    return props.departamentos.filter(d => 
        d.nombre.toLowerCase().includes(search) && 
        !form.departamento_ids.includes(d.id)
    );
});

const filteredEstablecimientos = computed(() => {
    if (!props.establecimientos) return [];
    const search = searchEstablecimiento.value.toLowerCase();
    return props.establecimientos.filter(e => 
        e.razon_social.toLowerCase().includes(search) && 
        !form.establecimiento_ids.includes(e.id_establecimiento)
    );
});

const filteredSucursales = computed(() => {
    if (!props.sucursales) return [];
    const search = searchSucursal.value.toLowerCase();
    
    // Si hay establecimientos seleccionados, filtrar por ellos
    let sucursales = props.sucursales;
    if (form.establecimiento_ids.length > 0) {
        sucursales = sucursales.filter(s => 
            form.establecimiento_ids.includes(s.id_casa_matriz)
        );
    }
    
    return sucursales.filter(s => 
        s.nombre_sucursal.toLowerCase().includes(search) && 
        !form.sucursal_ids.includes(s.id_sucursal)
    );
});

// Computed para controlar visibilidad de filtros según alcance
const canFilterByDepartment = computed(() => {
    // Solo nacional y departamental pueden filtrar por departamento
    return props.alcance === 'nacional' || props.alcance === 'departamental';
});

const canFilterByEstablishment = computed(() => {
    // Nacional, departamental y prestador pueden filtrar por establecimiento
    return props.alcance === 'nacional' || props.alcance === 'departamental' || props.alcance === 'establecimiento';
});

const canFilterBySucursal = computed(() => {
    // Nacional, departamental y prestador pueden filtrar por sucursal
    return props.alcance === 'nacional' || props.alcance === 'departamental' || props.alcance === 'establecimiento';
});

// Computed para obtener nombres de los tags seleccionados
const selectedDepartamentos = computed(() => {
    if (!props.departamentos) return [];
    return props.departamentos.filter(d => form.departamento_ids.includes(d.id));
});

const selectedEstablecimientos = computed(() => {
    if (!props.establecimientos) return [];
    return props.establecimientos.filter(e => form.establecimiento_ids.includes(e.id_establecimiento));
});

const selectedSucursales = computed(() => {
    if (!props.sucursales) return [];
    return props.sucursales.filter(s => form.sucursal_ids.includes(s.id_sucursal));
});

// Funciones para agregar/remover tags
const addDepartamento = (id: number) => {
    if (!form.departamento_ids.includes(id)) {
        form.departamento_ids.push(id);
    }
    searchDepartamento.value = '';
    // No cerrar el dropdown para permitir selecciones múltiples
};

const removeDepartamento = (id: number) => {
    form.departamento_ids = form.departamento_ids.filter(i => i !== id);
};

const addEstablecimiento = (id: number) => {
    if (!form.establecimiento_ids.includes(id)) {
        form.establecimiento_ids.push(id);
    }
    searchEstablecimiento.value = '';
    // No cerrar el dropdown para permitir selecciones múltiples
};

const removeEstablecimiento = (id: number) => {
    form.establecimiento_ids = form.establecimiento_ids.filter(i => i !== id);
    // Remover sucursales de este establecimiento
    if (props.sucursales) {
        const sucursalesToRemove = props.sucursales
            .filter(s => s.id_casa_matriz === id)
            .map(s => s.id_sucursal);
        form.sucursal_ids = form.sucursal_ids.filter(i => !sucursalesToRemove.includes(i));
    }
};

const addSucursal = (id: number) => {
    if (!form.sucursal_ids.includes(id)) {
        form.sucursal_ids.push(id);
    }
    searchSucursal.value = '';
    // No cerrar el dropdown para permitir selecciones múltiples
};

const removeSucursal = (id: number) => {
    form.sucursal_ids = form.sucursal_ids.filter(i => i !== id);
};

const generarReporte = async () => {
    loading.value = true;
    try {
        // Preparar datos solo con los campos que tienen valores
        const payload: any = {};
        
        if (form.fecha_inicio) payload.fecha_inicio = form.fecha_inicio;
        if (form.fecha_fin) payload.fecha_fin = form.fecha_fin;
        if (form.departamento_ids.length > 0) payload.departamento_ids = form.departamento_ids;
        if (form.establecimiento_ids.length > 0) payload.establecimiento_ids = form.establecimiento_ids;
        if (form.sucursal_ids.length > 0) payload.sucursal_ids = form.sucursal_ids;
        
        console.log('Enviando payload:', payload);
        
        const response = await axios.post('/reporte/generar', payload);
        console.log('Respuesta recibida:', response.data);
        reporteData.value = response.data;
    } catch (error) {
        console.error('Error al generar el reporte:', error);
        alert('Hubo un error al generar el reporte.');
    } finally {
        loading.value = false;
    }
};

const generarExcel = () => {
    const params = new URLSearchParams();
    
    if (form.fecha_inicio) params.append('fecha_inicio', form.fecha_inicio);
    if (form.fecha_fin) params.append('fecha_fin', form.fecha_fin);
    
    form.departamento_ids.forEach(id => params.append('departamento_ids[]', id.toString()));
    form.establecimiento_ids.forEach(id => params.append('establecimiento_ids[]', id.toString()));
    form.sucursal_ids.forEach(id => params.append('sucursal_ids[]', id.toString()));
    
    const url = `/reporte/generar-excel?${params.toString()}`;
    window.open(url, '_blank');
};

const limpiarFiltros = () => {
    form.reset();
    reporteData.value = [];
    searchDepartamento.value = '';
    searchEstablecimiento.value = '';
    searchSucursal.value = '';
};
</script>

<template>
    <AppLayout>
        <AppContent>
            <Heading title="Reporte de Estancias" />

            <div class="p-6 mt-4 bg-white border border-gray-200 rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700">
                <!-- Filtros -->
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <!-- Fechas -->
                    <div>
                        <label for="fecha_inicio" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Fecha Inicio</label>
                        <input type="date" id="fecha_inicio" v-model="form.fecha_inicio" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white">
                    </div>
                    <div>
                        <label for="fecha_fin" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Fecha Fin</label>
                        <input type="date" id="fecha_fin" v-model="form.fecha_fin" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white">
                    </div>

                    <!-- Búsqueda de Departamentos (solo para nacional y departamental) -->
                    <div v-if="canFilterByDepartment" class="relative">
                        <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Departamentos</label>
                        
                        <!-- Wrapper con badges y input -->
                        <div class="flex flex-wrap gap-2 p-2 bg-gray-50 border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 min-h-[42px]">
                            <!-- Tags seleccionados dentro del input -->
                            <span
                                v-for="depto in selectedDepartamentos"
                                :key="depto.id"
                                class="inline-flex items-center px-2 py-1 text-xs font-medium text-blue-800 bg-blue-100 rounded-full dark:bg-blue-900 dark:text-blue-300"
                            >
                                {{ depto.nombre }}
                                <button
                                    @click="removeDepartamento(depto.id)"
                                    class="ml-1 text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-200"
                                >
                                    <X :size="12" />
                                </button>
                            </span>
                            
                            <!-- Input de búsqueda -->
                            <input 
                                type="text" 
                                v-model="searchDepartamento"
                                @focus="showDepartamentoDropdown = true"
                                @blur="showDepartamentoDropdown = false"
                                placeholder="Buscar departamento..."
                                class="flex-1 min-w-[150px] bg-transparent border-0 focus:ring-0 text-gray-900 text-sm dark:text-white p-0"
                            >
                        </div>
                        
                        <!-- Dropdown de resultados -->
                        <div v-if="showDepartamentoDropdown" class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg dark:bg-gray-700 dark:border-gray-600 max-h-60 overflow-y-auto">
                            <button
                                v-for="depto in filteredDepartamentos"
                                :key="depto.id"
                                @mousedown.prevent="addDepartamento(depto.id)"
                                class="w-full px-4 py-2 text-left text-sm hover:bg-gray-100 dark:hover:bg-gray-600 dark:text-white"
                            >
                                {{ depto.nombre }}
                            </button>
                            <div v-if="filteredDepartamentos.length === 0" class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">
                                No hay resultados
                            </div>
                        </div>
                    </div>

                    <!-- Búsqueda de Establecimientos (para nacional, departamental y prestador) -->
                    <div v-if="canFilterByEstablishment" class="relative">
                        <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Establecimientos</label>
                        
                        <!-- Wrapper con badges y input -->
                        <div class="flex flex-wrap gap-2 p-2 bg-gray-50 border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 min-h-[42px]">
                            <!-- Tags seleccionados dentro del input -->
                            <span
                                v-for="est in selectedEstablecimientos"
                                :key="est.id_establecimiento"
                                class="inline-flex items-center px-2 py-1 text-xs font-medium text-green-800 bg-green-100 rounded-full dark:bg-green-900 dark:text-green-300"
                            >
                                {{ est.razon_social }}
                                <button
                                    @click="removeEstablecimiento(est.id_establecimiento)"
                                    class="ml-1 text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-200"
                                >
                                    <X :size="12" />
                                </button>
                            </span>
                            
                            <!-- Input de búsqueda -->
                            <input 
                                type="text" 
                                v-model="searchEstablecimiento"
                                @focus="showEstablecimientoDropdown = true"
                                @blur="showEstablecimientoDropdown = false"
                                placeholder="Buscar establecimiento..."
                                class="flex-1 min-w-[150px] bg-transparent border-0 focus:ring-0 text-gray-900 text-sm dark:text-white p-0"
                            >
                        </div>
                        
                        <!-- Dropdown de resultados -->
                        <div v-if="showEstablecimientoDropdown" class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg dark:bg-gray-700 dark:border-gray-600 max-h-60 overflow-y-auto">
                            <button
                                v-for="est in filteredEstablecimientos"
                                :key="est.id_establecimiento"
                                @mousedown.prevent="addEstablecimiento(est.id_establecimiento)"
                                class="w-full px-4 py-2 text-left text-sm hover:bg-gray-100 dark:hover:bg-gray-600 dark:text-white"
                            >
                                {{ est.razon_social }}
                            </button>
                            <div v-if="filteredEstablecimientos.length === 0" class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">
                                No hay resultados
                            </div>
                        </div>
                    </div>

                    <!-- Búsqueda de Sucursales (para nacional, departamental y prestador) -->
                    <div v-if="canFilterBySucursal" class="relative">
                        <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Sucursales</label>
                        
                        <!-- Wrapper con badges y input -->
                        <div class="flex flex-wrap gap-2 p-2 bg-gray-50 border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 min-h-[42px]" :class="{ 'opacity-50': (alcance === 'nacional' || alcance === 'departamental') && form.establecimiento_ids.length === 0 }">
                            <!-- Tags seleccionados dentro del input -->
                            <span
                                v-for="suc in selectedSucursales"
                                :key="suc.id_sucursal"
                                class="inline-flex items-center px-2 py-1 text-xs font-medium text-purple-800 bg-purple-100 rounded-full dark:bg-purple-900 dark:text-purple-300"
                            >
                                {{ suc.nombre_sucursal }}
                                <button
                                    @click="removeSucursal(suc.id_sucursal)"
                                    class="ml-1 text-purple-600 hover:text-purple-800 dark:text-purple-400 dark:hover:text-purple-200"
                                >
                                    <X :size="12" />
                                </button>
                            </span>
                            
                            <!-- Input de búsqueda -->
                            <input 
                                type="text" 
                                v-model="searchSucursal"
                                @focus="showSucursalDropdown = true"
                                @blur="showSucursalDropdown = false"
                                placeholder="Buscar sucursal..."
                                :disabled="(alcance === 'nacional' || alcance === 'departamental') && form.establecimiento_ids.length === 0"
                                class="flex-1 min-w-[150px] bg-transparent border-0 focus:ring-0 text-gray-900 text-sm dark:text-white p-0 disabled:cursor-not-allowed"
                            >
                        </div>
                        
                        <!-- Dropdown de resultados -->
                        <div v-if="showSucursalDropdown" class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg dark:bg-gray-700 dark:border-gray-600 max-h-60 overflow-y-auto">
                            <button
                                v-for="suc in filteredSucursales"
                                :key="suc.id_sucursal"
                                @mousedown.prevent="addSucursal(suc.id_sucursal)"
                                class="w-full px-4 py-2 text-left text-sm hover:bg-gray-100 dark:hover:bg-gray-600 dark:text-white"
                            >
                                {{ suc.nombre_sucursal }}
                            </button>
                            <div v-if="filteredSucursales.length === 0" class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">
                                No hay resultados
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Botones -->
                <div class="flex items-center justify-end mt-4 space-x-3">
                    <button @click="limpiarFiltros" type="button" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:bg-gray-800 dark:text-white dark:border-gray-600 dark:hover:bg-gray-700 dark:hover:border-gray-600">
                        Limpiar
                    </button>
                    <button @click="generarReporte" :disabled="loading" type="button" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-300 disabled:opacity-50">
                        {{ loading ? 'Generando...' : 'Generar Reporte' }}
                    </button>
                    <button @click="generarExcel" type="button" class="px-4 py-2 text-sm font-medium text-white bg-green-600 border border-transparent rounded-lg hover:bg-green-700 focus:outline-none focus:ring-4 focus:ring-green-300">
                        Generar Excel
                    </button>
                </div>
            </div>

            <!-- Resultados -->
            <div class="mt-6 overflow-x-auto">
                <div class="p-4 mt-4 bg-white border border-gray-200 rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700" v-if="reporteData.length > 0">
                    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                            <tr>
                                <th scope="col" class="px-6 py-3">Huésped</th>
                                <th scope="col" class="px-6 py-3">Documento</th>
                                <th scope="col" class="px-6 py-3">Origen</th>
                                <th scope="col" class="px-6 py-3">Establecimiento/Sucursal</th>
                                <th scope="col" class="px-6 py-3">Departamento Est./Suc.</th>
                                <th scope="col" class="px-6 py-3">Cuarto</th>
                                <th scope="col" class="px-6 py-3">Ingreso/Salida</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in reporteData" :key="item.id" class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                <td class="px-6 py-4">
                                    {{ item.persona?.nombres }} {{ item.persona?.apellido_paterno }}
                                    <div class="text-xs text-gray-500">{{ item.persona?.ocupacion }}</div>
                                </td>
                                <td class="px-6 py-4">{{ item.persona?.nro_documento }}</td>
                                <td class="px-6 py-4">
                                    {{ item.persona?.nacionalidad?.pais }}
                                    <div class="text-xs text-gray-500">{{ item.persona?.departamento?.nombre }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    {{ item.lote?.establecimiento?.razon_social }}
                                    <div class="text-xs text-green-500">{{ item.lote?.sucursal?.nombre_sucursal }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    {{ item.lote?.departamento?.nombre }}
                                </td>
                                <td class="px-6 py-4">
                                    #{{ item.nro_cuarto }}
                                    <div class="text-xs text-gray-500">{{ item.tipo_cuarto?.nombre }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    {{ new Date(item.fecha_hora_ingreso).toLocaleString() }}
                                    <div class="text-xs text-red-500" v-if="item.fecha_hora_salida_efectiva">{{ new Date(item.fecha_hora_salida_efectiva).toLocaleString() }}</div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                 <div v-else class="p-6 mt-4 text-center text-gray-500 bg-white border border-gray-200 rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700">
                    <p>No hay datos para mostrar. Por favor, ajuste los filtros y genere un nuevo reporte.</p>
                </div>
            </div>
        </AppContent>
    </AppLayout>
</template>
