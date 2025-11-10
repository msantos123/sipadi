<script setup lang="ts">
import { ref, computed, PropType } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Heading from '@/components/Heading.vue';
import AppContent from '@/components/AppContent.vue';
import axios from 'axios';

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
    };
    tipo_cuarto?: {
        nombre: string;
    };
}

const props = defineProps({
    departamentos: Array as PropType<Departamento[]>,
    establecimientos: Array as PropType<Establecimiento[]>,
    sucursales: Array as PropType<Sucursal[]>,
});

const form = useForm({
    fecha_inicio: '',
    fecha_fin: '',
    departamento_id: '',
    establecimiento_id: '',
    sucursal_id: '',
});

const reporteData = ref<EstanciaReporteItem[]>([]);
const loading = ref(false);

const filteredSucursales = computed(() => {
    if (!form.establecimiento_id || !props.sucursales) {
        return [];
    }
    return props.sucursales.filter((s: Sucursal) => s.id_casa_matriz === +form.establecimiento_id);
});

const generarReporte = async () => {
    loading.value = true;
    try {
        const response = await axios.post('/reporte/generar', form.data());
        reporteData.value = response.data;
    } catch (error) {
        console.error('Error al generar el reporte:', error);
        alert('Hubo un error al generar el reporte.');
    } finally {
        loading.value = false;
    }
};

const generarExcel = () => {
    const params = new URLSearchParams(form.data()).toString();
    const url = `/reporte/generar-excel?${params}`;
    window.open(url, '_blank');
};

const limpiarFiltros = () => {
    form.reset();
    reporteData.value = [];
};
</script>

<template>
    <AppLayout>
        <AppContent>
            <Heading>Reporte de Estancias</Heading>

            <div class="p-6 mt-4 bg-white border border-gray-200 rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700">
                <!-- Filtros -->
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3 lg:grid-cols-4">
                    <div>
                        <label for="fecha_inicio" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Fecha Inicio</label>
                        <input type="date" id="fecha_inicio" v-model="form.fecha_inicio" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white">
                    </div>
                    <div>
                        <label for="fecha_fin" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Fecha Fin</label>
                        <input type="date" id="fecha_fin" v-model="form.fecha_fin" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white">
                    </div>
                    <div>
                        <label for="departamento" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Departamento</label>
                        <select id="departamento" v-model="form.departamento_id" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white">
                            <option value="">Todos</option>
                            <option v-for="depto in departamentos" :key="depto.id" :value="depto.id">{{ depto.nombre }}</option>
                        </select>
                    </div>
                    <div>
                        <label for="establecimiento" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Establecimiento</label>
                        <select id="establecimiento" v-model="form.establecimiento_id" @change="form.sucursal_id = ''" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white">
                            <option value="">Todos</option>
                            <option v-for="est in establecimientos" :key="est.id_establecimiento" :value="est.id_establecimiento">{{ est.razon_social }}</option>
                        </select>
                    </div>
                    <div>
                        <label for="sucursal" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Sucursal</label>
                        <select id="sucursal" v-model="form.sucursal_id" :disabled="!form.establecimiento_id" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white disabled:opacity-50">
                            <option value="">Todas</option>
                            <option v-for="suc in filteredSucursales" :key="suc.id_sucursal" :value="suc.id_sucursal">{{ suc.nombre_sucursal }}</option>
                        </select>
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
