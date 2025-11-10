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

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, ArcElement);

const props = defineProps<{
  nacionalidades: Array<{ id: number; gentilicio: string }>;
  establecimientos: Array<{
    id_establecimiento: number;
    razon_social: string;
    sucursales: Array<{ id_sucursal: number; nombre_sucursal: string }>;
  }>;
  estadisticas: {
    totalLlegadas: number;
    llegadasPorNacionalidad: Record<string, number>;
    totalPernoctaciones: number;
    estadiaMedia: number;
    tasaOcupacion: number;
    habitacionesOcupadas: number;
    totalHabitacionesDisponibles: number;
  } | null;
  filters: {
    fecha_inicio?: string;
    fecha_fin?: string;
    nacionalidad_id?: string;
    establecimiento_id?: string;
    sucursal_id?: string;
  };
}>();

const form = useForm({
  fecha_inicio: props.filters.fecha_inicio || new Date().toISOString().split('T')[0],
  fecha_fin: props.filters.fecha_fin || new Date().toISOString().split('T')[0],
  nacionalidad_id: props.filters.nacionalidad_id || '',
  establecimiento_id: props.filters.establecimiento_id || '',
  sucursal_id: props.filters.sucursal_id || '',
});

const sucursalesDisponibles = computed(() => {
  if (!form.establecimiento_id) return [];
  const establecimiento = props.establecimientos.find(
    (e) => e.id_establecimiento === Number(form.establecimiento_id)
  );
  return establecimiento ? establecimiento.sucursales : [];
});

watch(() => form.establecimiento_id, () => {
  form.sucursal_id = '';
});

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
      backgroundColor: ['#4A90E2', '#E0E0E0'],
      data: [0, 100],
    },
  ],
});

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
};

function updateCharts() {
  if (props.estadisticas) {
    // Update llegadas chart
    llegadasChartData.value.labels = Object.keys(props.estadisticas.llegadasPorNacionalidad);
    llegadasChartData.value.datasets[0].data = Object.values(props.estadisticas.llegadasPorNacionalidad);

    // Update ocupacion chart
    const libres = props.estadisticas.totalHabitacionesDisponibles - props.estadisticas.habitacionesOcupadas;
    ocupacionChartData.value.datasets[0].data = [props.estadisticas.habitacionesOcupadas, libres > 0 ? libres : 0];
  }
}

onMounted(() => {
  updateCharts();
});

watch(() => props.estadisticas, () => {
  updateCharts();
}, { deep: true });

function submit() {
  form.post('/estadisticas/generar', {
    preserveState: true,
    preserveScroll: true,
  });
}

function reset() {
  form.reset();
  form.clearErrors();
  // Manually navigate to clear stats from props
  form.get('/estadisticas');
}
</script>

<template>
  <AppLayout>
    <template #header>
      <Heading>Módulo de Estadísticas</Heading>
    </template>

    <div class="space-y-8">
      <Card>
        <CardHeader>
          <CardTitle>Filtros de Búsqueda</CardTitle>
        </CardHeader>
        <CardContent>
          <form @submit.prevent="submit" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Fecha Inicio -->
            <div class="space-y-2">
              <Label for="fecha_inicio">Fecha de Inicio</Label>
              <Input id="fecha_inicio" type="date" v-model="form.fecha_inicio" />
            </div>

            <!-- Fecha Fin -->
            <div class="space-y-2">
              <Label for="fecha_fin">Fecha de Fin</Label>
              <Input id="fecha_fin" type="date" v-model="form.fecha_fin" />
            </div>

            <!-- Nacionalidad -->
            <div class="space-y-2">
              <Label for="nacionalidad">Nacionalidad</Label>
              <Select v-model="form.nacionalidad_id">
                <SelectTrigger>
                  <SelectValue placeholder="Todas" />
                </SelectTrigger>
                <SelectContent>
                  <SelectGroup>
                    <SelectItem v-for="nac in nacionalidades" :key="nac.id" :value="String(nac.id)">
                      {{ nac.gentilicio }}
                    </SelectItem>
                  </SelectGroup>
                </SelectContent>
              </Select>
            </div>

            <!-- Establecimiento -->
            <div class="space-y-2">
              <Label for="establecimiento">Establecimiento</Label>
              <Select v-model="form.establecimiento_id">
                <SelectTrigger>
                  <SelectValue placeholder="Todos" />
                </SelectTrigger>
                <SelectContent>
                  <SelectGroup>
                    <SelectItem v-for="est in establecimientos" :key="est.id_establecimiento" :value="String(est.id_establecimiento)">
                      {{ est.razon_social }}
                    </SelectItem>
                  </SelectGroup>
                </SelectContent>
              </Select>
            </div>

            <!-- Sucursal -->
            <div class="space-y-2">
              <Label for="sucursal">Sucursal</Label>
              <Select v-model="form.sucursal_id" :disabled="!form.establecimiento_id">
                <SelectTrigger>
                  <SelectValue placeholder="Todas" />
                </SelectTrigger>
                <SelectContent>
                  <SelectGroup>
                    <SelectItem v-for="suc in sucursalesDisponibles" :key="suc.id_sucursal" :value="String(suc.id_sucursal)">
                      {{ suc.nombre_sucursal }}
                    </SelectItem>
                  </SelectGroup>
                </SelectContent>
              </Select>
            </div>

            <!-- Botones -->
            <div class="col-span-full flex items-center justify-end gap-4 pt-4">
              <Button type="button" variant="outline" @click="reset">Limpiar</Button>
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
      </div>
       <div v-else class="text-center py-12 text-gray-500">
        <p>Seleccione los filtros y genere una estadística para ver los resultados.</p>
      </div>
    </div>
  </AppLayout>
</template>
