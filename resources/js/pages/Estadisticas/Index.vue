<script setup lang="ts">
import { ref, watch, onMounted, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  BarElement,
  CategoryScale,
  LinearScale,
  ArcElement,
} from 'chart.js';
import { Bar, Doughnut } from 'vue-chartjs';
import AppLayout from '@/layouts/AppLayout.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { X } from 'lucide-vue-next';
import 'leaflet/dist/leaflet.css';
import L from 'leaflet';

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, ArcElement);

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

const props = defineProps<{
  nacionalidades: Array<{ id: number; gentilicio: string }>;
  departamentos: Departamento[];
  establecimientos: Establecimiento[];
  sucursales: Sucursal[];
  alcance: 'nacional' | 'departamental' | 'establecimiento';
  estadisticas: {
    totalLlegadas: number;
    llegadasPorNacionalidad: Record<string, number>;
    totalPernoctaciones: number;
    estadiaMedia: number;
    tasaOcupacion: number;
    habitacionesOcupadas: number;
    totalHabitacionesDisponibles: number;
    distribucionEdad: Record<string, number>;
    distribucionSexo: Record<string, number>;
    topProcedencias: Record<string, number>;
    puntosMapa: Array<{
      pais: string;
      lat: number;
      lng: number;
      count: number;
    }>;
  } | null;
  filters: {
    fecha_inicio?: string;
    fecha_fin?: string;
    departamento_ids?: number[];
    establecimiento_ids?: number[];
    sucursal_ids?: number[];
  };
}>();

const form = useForm({
  fecha_inicio: props.filters.fecha_inicio || new Date().toISOString().split('T')[0],
  fecha_fin: props.filters.fecha_fin || new Date().toISOString().split('T')[0],
  departamento_ids: props.filters.departamento_ids || [] as number[],
  establecimiento_ids: props.filters.establecimiento_ids || [] as number[],
  sucursal_ids: props.filters.sucursal_ids || [] as number[],
});

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
    return props.alcance === 'nacional' || props.alcance === 'departamental';
});

const canFilterByEstablishment = computed(() => {
    return props.alcance === 'nacional' || props.alcance === 'departamental' || props.alcance === 'establecimiento';
});

const canFilterBySucursal = computed(() => {
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

const llegadasChartData = ref({
  labels: [] as string[],
  datasets: [
    {
      label: 'Llegadas',
      backgroundColor: '#4A90E2',
      data: [] as number[],
    },
  ],
});

const ocupacionChartData = ref({
  labels: ['Ocupadas', 'Disponibles'],
  datasets: [
    {
      label: 'Habitaciones',
      data: [0, 0],
      backgroundColor: ['#3B82F6', '#E5E7EB'],
    },
  ],
});

const edadChartData = ref({
  labels: ['18-25', '26-35', '36-45', '46-60', '60+'],
  datasets: [
    {
      label: 'Personas',
      data: [0, 0, 0, 0, 0],
      backgroundColor: '#10B981',
    },
  ],
});

const sexoChartData = ref({
  labels: ['Masculino', 'Femenino', 'Otro'],
  datasets: [
    {
      label: 'Distribución',
      data: [0, 0, 0],
      backgroundColor: ['#3B82F6', '#EC4899', '#8B5CF6'],
    },
  ],
});

const procedenciaChartData = ref({
  labels: [] as string[],
  datasets: [
    {
      label: 'Llegadas',
      data: [] as number[],
      backgroundColor: '#F59E0B',
    },
  ],
});

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
};

function updateCharts() {
  if (props.estadisticas) {
    // Update llegadas chart - crear nuevo objeto
    llegadasChartData.value = {
      labels: Object.keys(props.estadisticas.llegadasPorNacionalidad),
      datasets: [
        {
          label: 'Llegadas',
          data: Object.values(props.estadisticas.llegadasPorNacionalidad),
          backgroundColor: '#4A90E2',
        },
      ],
    };

    // Update ocupacion chart - crear nuevo objeto
    const libres = props.estadisticas.totalHabitacionesDisponibles - props.estadisticas.habitacionesOcupadas;
    ocupacionChartData.value = {
      labels: ['Ocupadas', 'Disponibles'],
      datasets: [
        {
          label: 'Habitaciones',
          data: [props.estadisticas.habitacionesOcupadas, libres > 0 ? libres : 0],
          backgroundColor: ['#3B82F6', '#E5E7EB'],
        },
      ],
    };

    // Update edad chart - crear nuevo objeto
    if (props.estadisticas.distribucionEdad) {
      edadChartData.value = {
        labels: ['18-25', '26-35', '36-45', '46-60', '60+'],
        datasets: [
          {
            label: 'Personas',
            data: Object.values(props.estadisticas.distribucionEdad),
            backgroundColor: '#10B981',
          },
        ],
      };
    }

    // Update sexo chart - crear nuevo objeto
    if (props.estadisticas.distribucionSexo) {
      sexoChartData.value = {
        labels: ['Masculino', 'Femenino', 'Otro'],
        datasets: [
          {
            label: 'Distribución',
            data: Object.values(props.estadisticas.distribucionSexo),
            backgroundColor: ['#3B82F6', '#EC4899', '#8B5CF6'],
          },
        ],
      };
    }

    // Update procedencia chart - crear nuevo objeto
    if (props.estadisticas.topProcedencias) {
      procedenciaChartData.value = {
        labels: Object.keys(props.estadisticas.topProcedencias),
        datasets: [
          {
            label: 'Llegadas',
            data: Object.values(props.estadisticas.topProcedencias),
            backgroundColor: '#F59E0B',
          },
        ],
      };
    }
  }
}

onMounted(() => {
  updateCharts();
  if (props.estadisticas && props.estadisticas.puntosMapa) {
    initMap();
  }
});

watch(() => props.estadisticas, () => {
  updateCharts();
  if (props.estadisticas && props.estadisticas.puntosMapa) {
    initMap();
  }
}, { deep: true });

// Mapa mundial
const mapContainer = ref<HTMLElement | null>(null);
let map: L.Map | null = null;

function initMap() {
  if (!mapContainer.value || !props.estadisticas?.puntosMapa) return;

  // Limpiar mapa existente
  if (map) {
    map.remove();
  }

  // Crear mapa centrado en el mundo
  map = L.map(mapContainer.value).setView([20, 0], 2);

  // Agregar capa de tiles (OpenStreetMap)
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors',
    maxZoom: 18,
  }).addTo(map);

  // Agregar marcadores para cada país
  props.estadisticas.puntosMapa.forEach((punto) => {
    const marker = L.circleMarker([punto.lat, punto.lng], {
      radius: Math.min(8 + punto.count * 2, 20), // Tamaño según cantidad
      fillColor: '#3B82F6',
      color: '#1E40AF',
      weight: 2,
      opacity: 1,
      fillOpacity: 0.7,
    }).addTo(map!);

    marker.bindPopup(`
      <div class="text-center">
        <strong>${punto.pais}</strong><br>
        ${punto.count} llegada${punto.count > 1 ? 's' : ''}
      </div>
    `);
  });
}

function submit() {
  form.post('/estadisticas/generar', {
    preserveScroll: true,
    onSuccess: () => {
      // Forzar actualización de gráficos después de recibir nuevos datos
      updateCharts();
      if (props.estadisticas && props.estadisticas.puntosMapa) {
        initMap();
      }
    },
  });
}

function limpiarFiltros() {
  form.reset();
  form.fecha_inicio = '';
  form.fecha_fin = '';
  form.departamento_ids = [];
  form.establecimiento_ids = [];
  form.sucursal_ids = [];
  
  // Recargar la página sin filtros
  window.location.href = '/estadisticas';
}
</script>

<template>
  <AppLayout>
    <template #header>
      <Heading title="Módulo de Estadísticas" />
    </template>

    <div class="space-y-8">
      <Card>
        <CardHeader>
          <CardTitle>Filtros de Búsqueda</CardTitle>
        </CardHeader>
        <CardContent>
          <form @submit.prevent="submit" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Fecha Inicio -->
            <div>
              <label for="fecha_inicio" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Fecha Inicio</label>
              <input type="date" id="fecha_inicio" v-model="form.fecha_inicio" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white">
            </div>
            
            <!-- Fecha Fin -->
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

            <!-- Botones -->
            <div class="col-span-full flex items-center justify-end gap-4 pt-4">
              <Button type="button" variant="outline" @click="limpiarFiltros">
                Limpiar
              </Button>
              <Button type="submit" :disabled="form.processing">Generar Estadística</Button>
            </div>
          </form>
        </CardContent>
      </Card>

      <div v-if="estadisticas" class="space-y-8">
        <!-- Summary Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
          <Card>
            <CardHeader><CardTitle>Total Llegadas</CardTitle></CardHeader>
            <CardContent><p class="text-3xl font-bold">{{ estadisticas.totalLlegadas }}</p></CardContent>
          </Card>
          <Card>
            <CardHeader><CardTitle>Total Pernoctaciones</CardTitle></CardHeader>
            <CardContent><p class="text-3xl font-bold">{{ estadisticas.totalPernoctaciones }}</p></CardContent>
          </Card>
          <Card>
            <CardHeader><CardTitle>Estadía Media</CardTitle></CardHeader>
            <CardContent><p class="text-3xl font-bold">{{ estadisticas.estadiaMedia }} días</p></CardContent>
          </Card>
          <Card>
            <CardHeader><CardTitle>Tasa de Ocupación</CardTitle></CardHeader>
            <CardContent><p class="text-3xl font-bold">{{ estadisticas.tasaOcupacion }}%</p></CardContent>
          </Card>
        </div>

        <!-- Charts -->
        <div class="grid grid-cols-1 lg:grid-cols-5 gap-8">
          <Card class="lg:col-span-3">
            <CardHeader><CardTitle>Llegadas por Nacionalidad</CardTitle></CardHeader>
            <CardContent class="h-96">
              <Bar :data="llegadasChartData" :options="chartOptions" />
            </CardContent>
          </Card>
          <Card class="lg:col-span-2">
            <CardHeader><CardTitle>Ocupación de Habitaciones</CardTitle></CardHeader>
            <CardContent class="h-96">
              <Doughnut :data="ocupacionChartData" :options="chartOptions" />
            </CardContent>
          </Card>
        </div>

        <!-- Nuevos Gráficos -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mt-8">
          <!-- Distribución por Edad -->
          <Card>
            <CardHeader><CardTitle>Distribución por Edad</CardTitle></CardHeader>
            <CardContent class="h-80">
              <Bar :data="edadChartData" :options="chartOptions" />
            </CardContent>
          </Card>

          <!-- Distribución por Sexo -->
          <Card>
            <CardHeader><CardTitle>Distribución por Sexo</CardTitle></CardHeader>
            <CardContent class="h-80">
              <Doughnut :data="sexoChartData" :options="chartOptions" />
            </CardContent>
          </Card>

          <!-- Top 6 Procedencias -->
          <Card>
            <CardHeader><CardTitle>Top 6 Procedencias</CardTitle></CardHeader>
            <CardContent class="h-80">
              <Bar :data="procedenciaChartData" :options="chartOptions" />
            </CardContent>
          </Card>
        </div>

        <!-- Mapa Mundial -->
        <Card class="mt-8">
          <CardHeader><CardTitle>Mapa Mundial de Procedencias</CardTitle></CardHeader>
          <CardContent>
            <div ref="mapContainer" class="h-96 w-full rounded-lg"></div>
          </CardContent>
        </Card>
      </div>
       <div v-else class="text-center py-12 text-gray-500">
        <p>Seleccione los filtros y genere una estadística para ver los resultados.</p>
      </div>
    </div>
  </AppLayout>
</template>
