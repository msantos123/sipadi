<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { Head } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Bar, Doughnut, Line } from 'vue-chartjs';
import { Chart as ChartJS, CategoryScale, LinearScale, BarElement, PointElement, LineElement, Title, Tooltip, Legend, ArcElement } from 'chart.js';
import { ref, onMounted } from 'vue';
import 'leaflet/dist/leaflet.css';
import L from 'leaflet';

ChartJS.register(CategoryScale, LinearScale, BarElement, PointElement, LineElement, Title, Tooltip, Legend, ArcElement);

const breadcrumbs = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

const props = defineProps<{
  estadisticas: {
    distribucionEdad: Record<string, number>;
    distribucionSexo: Record<string, number>;
    topProcedencias: Record<string, number>;
    puntosMapa: Array<{
      pais: string;
      lat: number;
      lng: number;
      count: number;
    }>;
    llegadasPorMes: Record<string, number>;
    topEstablecimientos: Record<string, number>;
    totalPersonas: number;
    totalEstancias: number;
  };
}>();

// Chart Data
const edadChartData = ref({
  labels: ['18-25', '26-35', '36-45', '46-60', '60+'],
  datasets: [{ label: 'Personas', data: [0, 0, 0, 0, 0], backgroundColor: '#10B981' }],
});

const sexoChartData = ref({
  labels: ['Masculino', 'Femenino', 'Otro'],
  datasets: [{ label: 'Distribución', data: [0, 0, 0], backgroundColor: ['#3B82F6', '#EC4899', '#8B5CF6'] }],
});

const procedenciaChartData = ref({
  labels: [] as string[],
  datasets: [{ label: 'Llegadas', data: [] as number[], backgroundColor: '#F59E0B' }],
});

const llegadasMesChartData = ref({
  labels: [] as string[],
  datasets: [{ label: 'Llegadas', data: [] as number[], borderColor: '#3B82F6', backgroundColor: 'rgba(59, 130, 246, 0.1)', tension: 0.4 }],
});

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
};

// Mapa
const mapContainer = ref<HTMLElement | null>(null);
let map: L.Map | null = null;

function initMap() {
  if (!mapContainer.value || !props.estadisticas?.puntosMapa) return;

  if (map) {
    map.remove();
  }

  map = L.map(mapContainer.value).setView([20, 0], 2);

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors',
    maxZoom: 18,
  }).addTo(map);

  props.estadisticas.puntosMapa.forEach((punto) => {
    const marker = L.circleMarker([punto.lat, punto.lng], {
      radius: Math.min(8 + punto.count * 2, 20),
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

function updateCharts() {
  if (props.estadisticas) {
    edadChartData.value = {
      labels: ['18-25', '26-35', '36-45', '46-60', '60+'],
      datasets: [{ label: 'Personas', data: Object.values(props.estadisticas.distribucionEdad), backgroundColor: '#10B981' }],
    };

    sexoChartData.value = {
      labels: ['Masculino', 'Femenino', 'Otro'],
      datasets: [{ label: 'Distribución', data: Object.values(props.estadisticas.distribucionSexo), backgroundColor: ['#3B82F6', '#EC4899', '#8B5CF6'] }],
    };

    procedenciaChartData.value = {
      labels: Object.keys(props.estadisticas.topProcedencias),
      datasets: [{ label: 'Llegadas', data: Object.values(props.estadisticas.topProcedencias), backgroundColor: '#F59E0B' }],
    };

    llegadasMesChartData.value = {
      labels: Object.keys(props.estadisticas.llegadasPorMes),
      datasets: [{ label: 'Llegadas', data: Object.values(props.estadisticas.llegadasPorMes), borderColor: '#3B82F6', backgroundColor: 'rgba(59, 130, 246, 0.1)', tension: 0.4 }],
    };
  }
}

onMounted(() => {
  updateCharts();
  if (props.estadisticas && props.estadisticas.puntosMapa) {
    initMap();
  }
});
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Tarjetas de Resumen -->
            <div class="grid auto-rows-min gap-4 md:grid-cols-3">
                <Card>
                    <CardHeader>
                        <CardTitle class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Personas Registradas</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-3xl font-bold">{{ estadisticas.totalPersonas.toLocaleString() }}</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <CardTitle class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Estancias</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-3xl font-bold">{{ estadisticas.totalEstancias.toLocaleString() }}</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <CardTitle class="text-sm font-medium text-gray-600 dark:text-gray-400">Países de Procedencia</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-3xl font-bold">{{ Object.keys(estadisticas.topProcedencias).length }}</p>
                    </CardContent>
                </Card>
            </div>

            <!-- Gráfico de Llegadas por Mes (Ancho Completo) -->
            <Card>
                <CardHeader>
                    <CardTitle>Llegadas por Mes (Últimos 12 Meses)</CardTitle>
                </CardHeader>
                <CardContent class="h-80">
                    <Line :data="llegadasMesChartData" :options="chartOptions" />
                </CardContent>
            </Card>

            <!-- Gráficos Principales -->
            <div class="grid gap-4 md:grid-cols-3">
                <Card>
                    <CardHeader><CardTitle>Distribución por Edad</CardTitle></CardHeader>
                    <CardContent class="h-80">
                        <Bar :data="edadChartData" :options="chartOptions" />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader><CardTitle>Distribución por Sexo</CardTitle></CardHeader>
                    <CardContent class="h-80">
                        <Doughnut :data="sexoChartData" :options="chartOptions" />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader><CardTitle>Top 20 Procedencias</CardTitle></CardHeader>
                    <CardContent class="h-80">
                        <Bar :data="procedenciaChartData" :options="chartOptions" />
                    </CardContent>
                </Card>
            </div>

            <!-- Mapa Mundial (Ancho Completo) -->
            <Card>
                <CardHeader><CardTitle>Mapa Mundial de Procedencias</CardTitle></CardHeader>
                <CardContent>
                    <div ref="mapContainer" class="h-96 w-full rounded-lg"></div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
